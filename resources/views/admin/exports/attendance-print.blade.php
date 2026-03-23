<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>طباعة تقرير الحضور</title>
    <style>
        body { font-family: Tahoma, Arial, sans-serif; margin: 20px; color: #1e293b; }
        h1 { margin: 0 0 8px; font-size: 20px; }
        .meta { margin-bottom: 16px; color: #475569; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th, td { border: 1px solid #cbd5e1; padding: 6px 8px; text-align: right; }
        th { background: #f1f5f9; }
        @media print {
            @page { size: A4 portrait; margin: 10mm; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom:10px">
        <button onclick="window.print()">طباعة</button>
    </div>

    <h1>تقرير الحضور والانصراف</h1>
    <div class="meta">وقت الطباعة: {{ $printedAt->format('Y-m-d H:i:s') }}</div>

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
            @forelse($records as $record)
                <tr>
                    <td>{{ $record->employee->name ?? '-' }}</td>
                    <td>{{ $record->type }}</td>
                    <td>{{ $record->attendance_date?->format('Y-m-d') }}</td>
                    <td>{{ $record->timestamp?->format('H:i:s') }}</td>
                    <td>{{ $record->late_minutes }}</td>
                    <td>{{ $record->employee->branch->name ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align:center">لا توجد بيانات</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
