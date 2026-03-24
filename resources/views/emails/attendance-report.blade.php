<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Arial, sans-serif; background: #f4f6f9; margin: 0; padding: 0; direction: rtl; }
        .container { max-width: 640px; margin: 20px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
        .header { background: linear-gradient(135deg, #1a73e8, #0d47a1); color: #fff; padding: 24px 32px; text-align: center; }
        .header h1 { margin: 0; font-size: 1.3rem; font-weight: 600; }
        .header p { margin: 6px 0 0; font-size: .85rem; opacity: .85; }
        .body { padding: 24px 32px; color: #333; line-height: 1.7; }
        .summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 12px; margin: 16px 0; }
        .summary-card { background: #f8f9fa; border-radius: 8px; padding: 16px; text-align: center; border: 1px solid #e9ecef; }
        .summary-card .num { font-size: 1.5rem; font-weight: 700; color: #1a73e8; display: block; }
        .summary-card .lbl { font-size: .78rem; color: #666; margin-top: 4px; display: block; }
        table { width: 100%; border-collapse: collapse; margin: 16px 0; font-size: .85rem; }
        th { background: #f1f3f5; padding: 10px 12px; text-align: right; font-weight: 600; color: #444; border-bottom: 2px solid #dee2e6; }
        td { padding: 8px 12px; border-bottom: 1px solid #eee; color: #555; }
        tr:hover td { background: #f8f9fa; }
        .footer { background: #f8f9fa; padding: 16px 32px; text-align: center; font-size: .75rem; color: #999; border-top: 1px solid #eee; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: .75rem; font-weight: 600; }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-danger { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>{{ $reportTitle }}</h1>
        <p>{{ $period }}</p>
    </div>

    <div class="body">
        @if(!empty($reportData['summary']))
        <div class="summary-grid">
            @foreach($reportData['summary'] as $key => $value)
            <div class="summary-card">
                <span class="num">{{ $value['count'] ?? $value }}</span>
                <span class="lbl">{{ $value['label'] ?? $key }}</span>
            </div>
            @endforeach
        </div>
        @endif

        @if(!empty($reportData['records']))
        <table>
            <thead>
                <tr>
                    <th>الموظف</th>
                    <th>النوع</th>
                    <th>التاريخ</th>
                    <th>الوقت</th>
                    <th>التأخير</th>
                    <th>الفرع</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reportData['records'] as $record)
                <tr>
                    <td>{{ $record['employee'] ?? '-' }}</td>
                    <td>
                        @php
                            $types = ['in' => 'حضور', 'out' => 'انصراف', 'overtime-start' => 'بدء إضافي', 'overtime-end' => 'نهاية إضافي'];
                        @endphp
                        {{ $types[$record['type']] ?? $record['type'] }}
                    </td>
                    <td>{{ $record['date'] ?? '-' }}</td>
                    <td>{{ $record['time'] ?? '-' }}</td>
                    <td>
                        @if(($record['late_minutes'] ?? 0) > 0)
                            <span class="badge badge-warning">{{ $record['late_minutes'] }} د</span>
                        @else
                            <span class="badge badge-success">0</span>
                        @endif
                    </td>
                    <td>{{ $record['branch'] ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        @if(!empty($reportData['late_summary']))
        <h3 style="margin-top:24px;font-size:.95rem">ملخص التأخيرات</h3>
        <table>
            <thead>
                <tr>
                    <th>الموظف</th>
                    <th>مرات التأخير</th>
                    <th>إجمالي الدقائق</th>
                    <th>المتوسط</th>
                    <th>التقييم</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reportData['late_summary'] as $item)
                <tr>
                    <td>{{ $item['employee'] }}</td>
                    <td>{{ $item['count'] }}</td>
                    <td>{{ $item['total_minutes'] }}</td>
                    <td>{{ $item['avg_minutes'] }} د</td>
                    <td>
                        @if($item['avg_minutes'] <= 5)
                            <span class="badge badge-success">ممتاز</span>
                        @elseif($item['avg_minutes'] <= 15)
                            <span class="badge badge-warning">مقبول</span>
                        @else
                            <span class="badge badge-danger">ضعيف</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        @if(!empty($reportData['note']))
        <p style="margin-top:16px;padding:12px;background:#e8f4fd;border-radius:6px;font-size:.85rem;color:#0d47a1">
            {{ $reportData['note'] }}
        </p>
        @endif
    </div>

    <div class="footer">
        <p>تم إرسال هذا التقرير تلقائياً من نظام الحضور والانصراف</p>
        <p>{{ config('app.name') }} &copy; {{ date('Y') }}</p>
    </div>
</div>
</body>
</html>
