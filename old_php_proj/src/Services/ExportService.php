<?php
// =============================================================
// src/Services/ExportService.php - خدمة تصدير التقارير (v4.0)
// =============================================================

namespace App\Services;

use App\Models\Attendance;

class ExportService
{
    private Attendance $attendance;

    public function __construct()
    {
        $this->attendance = new Attendance();
    }

    /**
     * تصدير CSV
     */
    public function exportCsv(array $filters, string $filename = 'attendance_report.csv'): void
    {
        $result = $this->attendance->getFilteredReport($filters, 1, 100000);
        $records = $result['data'];

        $safeName = preg_replace('/[^a-zA-Z0-9_\-.]/', '_', $filename);
        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"{$safeName}\"");

        // BOM for Excel
        echo "\xEF\xBB\xBF";

        $output = fopen('php://output', 'w');

        // Header row
        fputcsv($output, ['الموظف', 'المسمى', 'الفرع', 'النوع', 'التاريخ', 'الوقت', 'التأخير (دقائق)', 'خط العرض', 'خط الطول']);

        $typeLabels = [
            'in' => 'حضور',
            'out' => 'انصراف',
            'overtime-start' => 'بداية إضافي',
            'overtime-end' => 'نهاية إضافي',
        ];

        foreach ($records as $r) {
            fputcsv($output, [
                $r['employee_name'],
                $r['job_title'],
                $r['branch_name'] ?? '-',
                $typeLabels[$r['type']] ?? $r['type'],
                date('Y-m-d', strtotime($r['timestamp'])),
                date('H:i:s', strtotime($r['timestamp'])),
                $r['late_minutes'] ?? 0,
                $r['latitude'],
                $r['longitude'],
            ]);
        }

        fclose($output);
        exit;
    }

    /**
     * تصدير JSON
     */
    public function exportJson(array $filters, string $filename = 'attendance_report.json'): void
    {
        $result = $this->attendance->getFilteredReport($filters, 1, 100000);
        $safeName = preg_replace('/[^a-zA-Z0-9_\-.]/', '_', $filename);

        header('Content-Type: application/json; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"{$safeName}\"");

        echo json_encode([
            'generated_at' => date('Y-m-d H:i:s'),
            'total_records' => $result['total'],
            'data' => $result['data'],
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * تصدير Excel (XLSX) - بدون مكتبات خارجية
     * يُنشئ ملف XML Spreadsheet 2003 متوافق مع Excel
     */
    public function exportExcel(array $filters, string $filename = 'attendance_report.xlsx'): void
    {
        $result = $this->attendance->getFilteredReport($filters, 1, 100000);
        $records = $result['data'];

        $typeLabels = [
            'in' => 'حضور',
            'out' => 'انصراف',
            'overtime-start' => 'بداية إضافي',
            'overtime-end' => 'نهاية إضافي',
        ];

        $safeName = preg_replace('/[^a-zA-Z0-9_\-.]/', '_', str_replace('.xlsx', '.xls', $filename));
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"{$safeName}\"");

        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<?mso-application progid="Excel.Sheet"?>' . "\n";
        echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
                xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">' . "\n";
        echo '<Styles>
            <Style ss:ID="header">
                <Font ss:Bold="1" ss:Size="11" ss:Color="#FFFFFF"/>
                <Interior ss:Color="#2563EB" ss:Pattern="Solid"/>
                <Alignment ss:Horizontal="Center"/>
            </Style>
            <Style ss:ID="late">
                <Font ss:Color="#DC2626" ss:Bold="1"/>
            </Style>
            <Style ss:ID="default">
                <Alignment ss:Horizontal="Right"/>
            </Style>
        </Styles>' . "\n";
        echo '<Worksheet ss:Name="تقرير الحضور">' . "\n";
        echo '<Table>' . "\n";

        // Column widths
        $widths = [120, 100, 100, 80, 90, 70, 80, 90, 90];
        foreach ($widths as $w) {
            echo "<Column ss:Width=\"{$w}\"/>\n";
        }

        // Header
        $headers = ['الموظف', 'المسمى', 'الفرع', 'النوع', 'التاريخ', 'الوقت', 'التأخير', 'خط العرض', 'خط الطول'];
        echo '<Row>' . "\n";
        foreach ($headers as $h) {
            echo '<Cell ss:StyleID="header"><Data ss:Type="String">' . htmlspecialchars($h) . '</Data></Cell>' . "\n";
        }
        echo '</Row>' . "\n";

        // Data rows
        foreach ($records as $r) {
            $late = (int)($r['late_minutes'] ?? 0);
            $style = $late > 0 ? ' ss:StyleID="late"' : ' ss:StyleID="default"';
            echo '<Row>' . "\n";
            echo '<Cell ss:StyleID="default"><Data ss:Type="String">' . htmlspecialchars($r['employee_name']) . '</Data></Cell>' . "\n";
            echo '<Cell ss:StyleID="default"><Data ss:Type="String">' . htmlspecialchars($r['job_title'] ?? '') . '</Data></Cell>' . "\n";
            echo '<Cell ss:StyleID="default"><Data ss:Type="String">' . htmlspecialchars($r['branch_name'] ?? '-') . '</Data></Cell>' . "\n";
            echo '<Cell ss:StyleID="default"><Data ss:Type="String">' . ($typeLabels[$r['type']] ?? $r['type']) . '</Data></Cell>' . "\n";
            echo '<Cell ss:StyleID="default"><Data ss:Type="String">' . date('Y-m-d', strtotime($r['timestamp'])) . '</Data></Cell>' . "\n";
            echo '<Cell ss:StyleID="default"><Data ss:Type="String">' . date('H:i:s', strtotime($r['timestamp'])) . '</Data></Cell>' . "\n";
            echo "<Cell{$style}><Data ss:Type=\"Number\">{$late}</Data></Cell>\n";
            echo '<Cell ss:StyleID="default"><Data ss:Type="Number">' . ($r['latitude'] ?? 0) . '</Data></Cell>' . "\n";
            echo '<Cell ss:StyleID="default"><Data ss:Type="Number">' . ($r['longitude'] ?? 0) . '</Data></Cell>' . "\n";
            echo '</Row>' . "\n";
        }

        echo '</Table></Worksheet></Workbook>';
        exit;
    }

    /**
     * تصدير تقرير مطبوع (HTML) - يُفتح في نافذة جديدة للطباعة كـ PDF
     */
    public function exportPrintable(array $data, string $title = 'تقرير الحضور', string $dateRange = ''): string
    {
        $html = '<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="UTF-8">';
        $html .= '<title>' . htmlspecialchars($title) . '</title>';
        $html .= '<style>
            * { margin:0; padding:0; box-sizing:border-box; }
            body { font-family:Tajawal,Arial,sans-serif; font-size:12px; color:#1E293B; padding:20px; }
            .report-header { text-align:center; margin-bottom:20px; padding:16px; background:linear-gradient(135deg,#1E293B,#334155); color:#fff; border-radius:8px; }
            .report-header h1 { font-size:18px; margin-bottom:4px; }
            .report-header .date { font-size:12px; opacity:.8; }
            table { width:100%; border-collapse:collapse; margin-top:12px; }
            th { background:#F1F5F9; color:#475569; font-weight:600; font-size:11px; padding:8px 10px; text-align:right; border:1px solid #E2E8F0; }
            td { padding:6px 10px; border:1px solid #E2E8F0; font-size:11px; }
            tr:nth-child(even) { background:#F8FAFC; }
            .late { color:#DC2626; font-weight:600; }
            .footer { margin-top:30px; display:flex; justify-content:space-between; font-size:10px; color:#94A3B8; }
            @media print {
                body { padding:0; }
                .no-print { display:none !important; }
                .report-header { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
            }
        </style></head><body>';
        $html .= '<div class="no-print" style="text-align:center;margin-bottom:16px">';
        $html .= '<button onclick="window.print()" style="padding:8px 24px;background:#2563EB;color:#fff;border:none;border-radius:6px;font-size:14px;cursor:pointer;font-family:Tajawal">🖨️ طباعة / حفظ كـ PDF</button>';
        $html .= '</div>';
        $html .= '<div class="report-header"><h1>' . htmlspecialchars($title) . '</h1>';
        if ($dateRange) {
            $html .= '<div class="date">' . htmlspecialchars($dateRange) . '</div>';
        }
        $html .= '<div class="date">تم الإنشاء: ' . date('Y-m-d H:i') . '</div></div>';

        return $html;
    }

    /**
     * Generate attendance printable report
     */
    public function exportAttendancePrintable(array $filters, string $title = 'تقرير الحضور'): void
    {
        $result = $this->attendance->getFilteredReport($filters, 1, 100000);
        $records = $result['data'];

        $dateRange = '';
        if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
            $dateRange = $filters['date_from'] . ' إلى ' . $filters['date_to'];
        }

        $typeLabels = [
            'in' => 'حضور',
            'out' => 'انصراف',
            'overtime-start' => 'بداية إضافي',
            'overtime-end' => 'نهاية إضافي',
        ];

        echo $this->exportPrintable($records, $title, $dateRange);

        echo '<table><thead><tr>';
        echo '<th>#</th><th>الموظف</th><th>الفرع</th><th>النوع</th><th>التاريخ</th><th>الوقت</th><th>التأخير</th>';
        echo '</tr></thead><tbody>';

        foreach ($records as $i => $r) {
            $late = (int)($r['late_minutes'] ?? 0);
            $lateClass = $late > 0 ? ' class="late"' : '';
            echo '<tr>';
            echo '<td>' . ($i + 1) . '</td>';
            echo '<td>' . htmlspecialchars($r['employee_name']) . '</td>';
            echo '<td>' . htmlspecialchars($r['branch_name'] ?? '-') . '</td>';
            echo '<td>' . ($typeLabels[$r['type']] ?? $r['type']) . '</td>';
            echo '<td>' . date('Y-m-d', strtotime($r['timestamp'])) . '</td>';
            echo '<td>' . date('H:i', strtotime($r['timestamp'])) . '</td>';
            echo "<td{$lateClass}>" . ($late > 0 ? $late . ' دقيقة' : '-') . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
        echo '<div class="footer"><span>عدد السجلات: ' . count($records) . '</span><span>' . date('Y-m-d H:i') . '</span></div>';
        echo '</body></html>';
        exit;
    }
}
