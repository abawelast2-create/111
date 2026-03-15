@extends('layouts.admin')

@section('content')

<!-- Filter -->
<div class="card" style="margin-bottom:20px">
    <form method="GET" action="{{ route('admin.analytics.index') }}" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">
        <div class="form-group" style="margin:0">
            <label class="form-label">من تاريخ</label>
            <input type="date" name="from" class="form-control" value="{{ request('from', now()->startOfMonth()->format('Y-m-d')) }}">
        </div>
        <div class="form-group" style="margin:0">
            <label class="form-label">إلى تاريخ</label>
            <input type="date" name="to" class="form-control" value="{{ request('to', now()->format('Y-m-d')) }}">
        </div>
        <div class="form-group" style="margin:0">
            <label class="form-label">الفرع</label>
            <select name="branch_id" class="form-control">
                <option value="">الكل</option>
                @foreach($branches as $branch)
                    <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-sm">عرض</button>
    </form>
</div>

<!-- Stats -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon-wrap blue"><x-icon name="employees" :size="26"/></div>
        <div>
            <div class="stat-value" id="statTotalEmployees">-</div>
            <div class="stat-label">إجمالي الموظفين</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon-wrap green"><x-icon name="checkin" :size="26"/></div>
        <div>
            <div class="stat-value" id="statAttendanceRate">-</div>
            <div class="stat-label">{{ __('messages.attendance_rate') }}</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon-wrap red"><x-icon name="attendance" :size="26"/></div>
        <div>
            <div class="stat-value" id="statAvgLate">-</div>
            <div class="stat-label">{{ __('messages.avg_late') }}</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon-wrap purple"><x-icon name="clock" :size="26"/></div>
        <div>
            <div class="stat-value" id="statAvgHours">-</div>
            <div class="stat-label">متوسط ساعات العمل</div>
        </div>
    </div>
</div>

<!-- Charts Grid -->
<div class="dashboard-grid" style="margin-top:20px">
    <!-- Daily Trend Chart -->
    <div class="card">
        <div class="card-header">
            <span class="card-title"><span class="card-title-bar"></span> {{ __('messages.daily_trend') }}</span>
        </div>
        <div style="padding:16px">
            <canvas id="trendChart" height="300"></canvas>
        </div>
    </div>

    <!-- Heatmap -->
    <div class="card">
        <div class="card-header">
            <span class="card-title"><span class="card-title-bar"></span> {{ __('messages.heatmap') }}</span>
        </div>
        <div style="padding:16px;overflow-x:auto">
            <table id="heatmapTable" style="width:100%;text-align:center;font-size:.8rem">
                <thead>
                    <tr>
                        <th>اليوم</th>
                        <th>07</th><th>08</th><th>09</th><th>10</th><th>11</th><th>12</th>
                        <th>13</th><th>14</th><th>15</th><th>16</th><th>17</th><th>18</th>
                    </tr>
                </thead>
                <tbody id="heatmapBody"></tbody>
            </table>
        </div>
    </div>
</div>

<div class="dashboard-grid" style="margin-top:20px">
    <!-- Branch Stats -->
    <div class="card">
        <div class="card-header">
            <span class="card-title"><span class="card-title-bar"></span> {{ __('messages.branch_stats') }}</span>
        </div>
        <div style="overflow-x:auto">
            <table>
                <thead><tr><th>الفرع</th><th>الموظفين</th><th>نسبة الحضور</th><th>متوسط التأخر</th></tr></thead>
                <tbody id="branchStatsBody"></tbody>
            </table>
        </div>
    </div>

    <!-- Top Late Employees -->
    <div class="card">
        <div class="card-header">
            <span class="card-title"><span class="card-title-bar"></span> {{ __('messages.top_late') }}</span>
        </div>
        <div style="overflow-x:auto">
            <table>
                <thead><tr><th>الموظف</th><th>الفرع</th><th>مرات التأخر</th><th>متوسط التأخر</th></tr></thead>
                <tbody id="topLateBody"></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Financial Report -->
<div class="card" style="margin-top:20px">
    <div class="card-header">
        <span class="card-title"><span class="card-title-bar"></span> {{ __('messages.financial_report') }}</span>
    </div>
    <div class="stats-grid" style="padding:16px">
        <div class="stat-card">
            <div>
                <div class="stat-value" id="finOvertimeHours">-</div>
                <div class="stat-label">ساعات إضافية</div>
            </div>
        </div>
        <div class="stat-card">
            <div>
                <div class="stat-value" id="finOvertimeCost">-</div>
                <div class="stat-label">{{ __('messages.overtime_cost') }}</div>
            </div>
        </div>
        <div class="stat-card">
            <div>
                <div class="stat-value" id="finLateMinutes">-</div>
                <div class="stat-label">دقائق تأخر</div>
            </div>
        </div>
        <div class="stat-card">
            <div>
                <div class="stat-value" id="finDeductions">-</div>
                <div class="stat-label">{{ __('messages.deductions') }}</div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const params = new URLSearchParams(window.location.search);
    const url = '{{ route("admin.analytics.data") }}?' + params.toString();

    fetch(url, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content } })
    .then(r => r.json())
    .then(data => {
        // Overview
        const ov = data.overview;
        document.getElementById('statTotalEmployees').textContent = ov.total_employees;
        document.getElementById('statAttendanceRate').textContent = ov.attendance_rate + '%';
        document.getElementById('statAvgLate').textContent = ov.avg_late_minutes + ' د';
        document.getElementById('statAvgHours').textContent = ov.avg_work_hours + ' س';

        // Trend Chart
        const ctx = document.getElementById('trendChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.trends.map(t => t.date),
                datasets: [
                    { label: 'حضور', data: data.trends.map(t => t.attendance), borderColor: '#22c55e', tension: 0.3, fill: false },
                    { label: 'تأخر', data: data.trends.map(t => t.late), borderColor: '#f97316', tension: 0.3, fill: false },
                    { label: 'غياب', data: data.trends.map(t => t.absent), borderColor: '#ef4444', tension: 0.3, fill: false }
                ]
            },
            options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
        });

        // Heatmap
        const days = ['الأحد','الاثنين','الثلاثاء','الأربعاء','الخميس','الجمعة','السبت'];
        const hours = [7,8,9,10,11,12,13,14,15,16,17,18];
        let heatHtml = '';
        for (let d = 0; d < 7; d++) {
            heatHtml += '<tr><td><strong>' + days[d] + '</strong></td>';
            for (const h of hours) {
                const val = (data.heatmap.find(x => x.day == d && x.hour == h) || {}).count || 0;
                const max = Math.max(...data.heatmap.map(x => x.count), 1);
                const opacity = Math.max(val / max, 0.05);
                heatHtml += '<td style="background:rgba(249,115,22,' + opacity + ');padding:8px;border-radius:4px">' + val + '</td>';
            }
            heatHtml += '</tr>';
        }
        document.getElementById('heatmapBody').innerHTML = heatHtml;

        // Branch Stats
        let bHtml = '';
        data.branch_stats.forEach(b => {
            bHtml += '<tr><td><strong>' + b.name + '</strong></td><td>' + b.total + '</td><td>' + b.rate + '%</td><td>' + b.avg_late + ' د</td></tr>';
        });
        document.getElementById('branchStatsBody').innerHTML = bHtml || '<tr><td colspan="4" style="text-align:center;color:var(--text3)">لا توجد بيانات</td></tr>';

        // Top Late
        let lHtml = '';
        data.top_late.forEach(e => {
            lHtml += '<tr><td><strong>' + e.name + '</strong></td><td>' + (e.branch || '-') + '</td><td>' + e.late_count + '</td><td>' + e.avg_minutes + ' د</td></tr>';
        });
        document.getElementById('topLateBody').innerHTML = lHtml || '<tr><td colspan="4" style="text-align:center;color:var(--text3)">لا توجد بيانات</td></tr>';

        // Financial
        const fin = data.financial;
        document.getElementById('finOvertimeHours').textContent = fin.total_overtime_hours + ' س';
        document.getElementById('finOvertimeCost').textContent = fin.overtime_cost + ' ر.س';
        document.getElementById('finLateMinutes').textContent = fin.total_late_minutes + ' د';
        document.getElementById('finDeductions').textContent = fin.late_deductions + ' ر.س';
    })
    .catch(err => console.error('Analytics error:', err));
});
</script>
@endpush
