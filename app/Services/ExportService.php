<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportService
{
    private static function csvSafe(mixed $value): string
    {
        $text = (string) ($value ?? '');
        if ($text !== '' && in_array($text[0], ['=', '+', '-', '@', '|', '%'], true)) {
            return "'" . $text;
        }

        return $text;
    }

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
                    self::csvSafe($record->employee->name ?? '-'),
                    self::csvSafe($typeNames[$record->type] ?? $record->type),
                    self::csvSafe($record->attendance_date?->format('Y-m-d')),
                    self::csvSafe($record->timestamp?->format('H:i:s')),
                    self::csvSafe((string) $record->late_minutes),
                    self::csvSafe($record->employee->branch->name ?? '-'),
                ]);
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=utf-8',
        ]);
    }

    public static function exportExcel(Collection $records, string $filename = 'export.xls'): StreamedResponse
    {
        return response()->streamDownload(function () use ($records) {
            echo "\xEF\xBB\xBF";
            echo "<table border=\"1\"><thead><tr>";
            echo "<th>الموظف</th><th>النوع</th><th>التاريخ</th><th>الوقت</th><th>التأخير (دقائق)</th><th>الفرع</th>";
            echo "</tr></thead><tbody>";

            $typeNames = [
                'in' => 'حضور', 'out' => 'انصراف',
                'overtime-start' => 'بدء إضافي', 'overtime-end' => 'نهاية إضافي',
            ];

            foreach ($records as $record) {
                echo '<tr>';
                echo '<td>' . e($record->employee->name ?? '-') . '</td>';
                echo '<td>' . e($typeNames[$record->type] ?? $record->type) . '</td>';
                echo '<td>' . e($record->attendance_date?->format('Y-m-d')) . '</td>';
                echo '<td>' . e($record->timestamp?->format('H:i:s')) . '</td>';
                echo '<td>' . e((string) $record->late_minutes) . '</td>';
                echo '<td>' . e($record->employee->branch->name ?? '-') . '</td>';
                echo '</tr>';
            }

            echo '</tbody></table>';
        }, $filename, [
            'Content-Type' => 'application/vnd.ms-excel; charset=utf-8',
        ]);
    }

    public static function exportPrint(Collection $records)
    {
        return response()->view('admin.exports.attendance-print', [
            'records' => $records,
            'printedAt' => now(),
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
