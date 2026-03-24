<?php

namespace App\Services;

use App\Mail\AttendanceReportMail;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Branch;
use Illuminate\Support\Facades\Mail;

class ReportMailService
{
    public static function buildReportData(string $type, string $from, string $to, array $filters = []): array
    {
        $query = Attendance::with('employee.branch')
            ->whereBetween('attendance_date', [$from, $to]);

        if (!empty($filters['branch_id'])) {
            $query->whereHas('employee', fn ($q) => $q->where('branch_id', $filters['branch_id']));
        }
        if (!empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }

        $records = $query->latest('timestamp')->get();

        $data = [];

        // Summary stats
        $data['summary'] = [
            ['label' => 'إجمالي السجلات', 'count' => $records->count()],
            ['label' => 'حضور',          'count' => $records->where('type', 'in')->count()],
            ['label' => 'انصراف',         'count' => $records->where('type', 'out')->count()],
            ['label' => 'حالات تأخير',    'count' => $records->where('late_minutes', '>', 0)->count()],
        ];

        // Records table
        $typeNames = ['in' => 'حضور', 'out' => 'انصراف', 'overtime-start' => 'بدء إضافي', 'overtime-end' => 'نهاية إضافي'];
        $data['records'] = $records->take(100)->map(fn ($r) => [
            'employee'     => $r->employee->name ?? '-',
            'type'         => $r->type,
            'date'         => $r->attendance_date?->format('Y-m-d'),
            'time'         => $r->timestamp?->format('H:i:s'),
            'late_minutes' => $r->late_minutes,
            'branch'       => $r->employee->branch->name ?? '-',
        ])->toArray();

        // Late summary (if type includes late)
        if (in_array($type, ['daily', 'late', 'full'])) {
            $lateRecords = $records->where('type', 'in')->where('late_minutes', '>', 0);
            $data['late_summary'] = $lateRecords->groupBy('employee_id')->map(function ($group) {
                $emp = $group->first()->employee;
                $total = $group->sum('late_minutes');
                $count = $group->count();
                $avg = $count > 0 ? round($total / $count) : 0;
                return [
                    'employee'      => $emp->name ?? '-',
                    'count'         => $count,
                    'total_minutes' => $total,
                    'avg_minutes'   => $avg,
                ];
            })->sortByDesc('total_minutes')->values()->toArray();
        }

        if ($records->count() > 100) {
            $data['note'] = 'يتم عرض أول 100 سجل فقط. للاطلاع على كامل التفاصيل يرجى تحميل المرفق.';
        }

        return $data;
    }

    public static function generateCsv(array $records): ?string
    {
        if (empty($records)) {
            return null;
        }

        $path = storage_path('app/reports/report_' . now()->format('Y-m-d_His') . '.csv');
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $file = fopen($path, 'w');
        fwrite($file, "\xEF\xBB\xBF");
        fputcsv($file, ['الموظف', 'النوع', 'التاريخ', 'الوقت', 'التأخير (دقائق)', 'الفرع']);

        $typeNames = ['in' => 'حضور', 'out' => 'انصراف', 'overtime-start' => 'بدء إضافي', 'overtime-end' => 'نهاية إضافي'];
        foreach ($records as $record) {
            fputcsv($file, [
                $record['employee'],
                $typeNames[$record['type']] ?? $record['type'],
                $record['date'],
                $record['time'],
                $record['late_minutes'],
                $record['branch'],
            ]);
        }

        fclose($file);
        return $path;
    }

    public static function sendReport(string $type, array $recipients, string $from, string $to, array $filters = []): array
    {
        $reportData = self::buildReportData($type, $from, $to, $filters);

        $titles = [
            'daily' => 'التقرير اليومي',
            'late'  => 'تقرير التأخيرات',
            'full'  => 'التقرير الشامل',
        ];

        $title = $titles[$type] ?? 'تقرير الحضور';
        $period = "من {$from} إلى {$to}";

        $csvPath = self::generateCsv($reportData['records'] ?? []);

        $sent = 0;
        $failed = [];

        foreach ($recipients as $email) {
            try {
                Mail::to(trim($email))->send(new AttendanceReportMail(
                    reportTitle: $title,
                    reportType: $type,
                    reportData: $reportData,
                    period: $period,
                    csvPath: $csvPath,
                ));
                $sent++;
            } catch (\Exception $e) {
                $failed[] = ['email' => $email, 'error' => $e->getMessage()];
            }
        }

        // Cleanup CSV
        if ($csvPath && file_exists($csvPath)) {
            @unlink($csvPath);
        }

        return [
            'sent'   => $sent,
            'failed' => $failed,
            'total'  => count($recipients),
        ];
    }
}
