<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportService
{
    public static function exportCsv(Collection $records, string $filename = 'export.csv'): StreamedResponse
    {
        return response()->streamDownload(function () use ($records) {
            $output = fopen('php://output', 'w');
            // UTF-8 BOM for Excel
            fwrite($output, "\xEF\xBB\xBF");

            fputcsv($output, ['الموظف', 'النوع', 'التاريخ', 'الوقت', 'التأخير (دقائق)', 'الفرع']);

            $typeNames = [
                'in' => 'حضور', 'out' => 'انصراف',
                'overtime-start' => 'بدء إضافي', 'overtime-end' => 'نهاية إضافي',
            ];

            foreach ($records as $record) {
                fputcsv($output, [
                    $record->employee->name ?? '-',
                    $typeNames[$record->type] ?? $record->type,
                    $record->attendance_date?->format('Y-m-d'),
                    $record->timestamp?->format('H:i:s'),
                    $record->late_minutes,
                    $record->employee->branch->name ?? '-',
                ]);
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=utf-8',
        ]);
    }

    public static function exportJson(Collection $records): array
    {
        return [
            'meta' => [
                'total'       => $records->count(),
                'exported_at' => now()->toDateTimeString(),
            ],
            'data' => $records->map(fn ($r) => [
                'employee'     => $r->employee->name ?? '-',
                'type'         => $r->type,
                'date'         => $r->attendance_date?->format('Y-m-d'),
                'time'         => $r->timestamp?->format('H:i:s'),
                'late_minutes' => $r->late_minutes,
                'branch'       => $r->employee->branch->name ?? '-',
                'latitude'     => $r->latitude,
                'longitude'    => $r->longitude,
            ]),
        ];
    }
}
