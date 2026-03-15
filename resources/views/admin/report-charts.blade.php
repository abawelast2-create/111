@extends('layouts.admin')

@push('styles')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
@endpush

@section('content')

<div class="card" style="margin-bottom:16px">
    <form method="GET" action="{{ route('admin.report-charts') }}" class="filter-bar" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap">
        <div class="form-group" style="margin:0">
            <label class="form-label">من تاريخ</label>
            <input type="date" name="from" value="{{ request('from', now()->subDays(30)->format('Y-m-d')) }}" class="form-control">
        </div>
        <div class="form-group" style="margin:0">
            <label class="form-label">إلى تاريخ</label>
            <input type="date" name="to" value="{{ request('to', now()->format('Y-m-d')) }}" class="form-control">
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
        <button type="submit" class="btn btn-primary btn-sm"><x-icon name="filter" :size="16"/> عرض</button>
    </form>
</div>

<div class="dashboard-grid">
    <!-- Daily attendance chart -->
    <div class="card">
        <div class="card-header">
            <span class="card-title"><span class="card-title-bar"></span> الحضور اليومي</span>
        </div>
        <canvas id="dailyChart" height="200"></canvas>
    </div>

    <!-- Type distribution -->
    <div class="card">
        <div class="card-header">
            <span class="card-title"><span class="card-title-bar"></span> توزيع أنواع التسجيل</span>
        </div>
        <canvas id="typeChart" height="200"></canvas>
    </div>

    <!-- Branch comparison -->
    <div class="card">
        <div class="card-header">
            <span class="card-title"><span class="card-title-bar"></span> مقارنة الفروع</span>
        </div>
        <canvas id="branchChart" height="200"></canvas>
    </div>

    <!-- Late distribution -->
    <div class="card">
        <div class="card-header">
            <span class="card-title"><span class="card-title-bar"></span> توزيع التأخير</span>
        </div>
        <canvas id="lateChart" height="200"></canvas>
    </div>
</div>

@endsection

@push('scripts')
<script>
const chartData = @json($chartData);
const rtlFont = { family: 'Tajawal', size: 12 };

// Daily Chart
new Chart(document.getElementById('dailyChart'), {
    type: 'bar',
    data: {
        labels: chartData.daily.labels,
        datasets: [
            { label: 'دخول', data: chartData.daily.checkin, backgroundColor: '#059669' },
            { label: 'انصراف', data: chartData.daily.checkout, backgroundColor: '#DC2626' }
        ]
    },
    options: { responsive: true, plugins: { legend: { labels: { font: rtlFont } } }, scales: { x: { ticks: { font: rtlFont } }, y: { beginAtZero: true } } }
});

// Type Chart
new Chart(document.getElementById('typeChart'), {
    type: 'doughnut',
    data: {
        labels: ['دخول', 'انصراف', 'بداية إضافي', 'نهاية إضافي'],
        datasets: [{ data: chartData.types, backgroundColor: ['#059669','#DC2626','#7C3AED','#2563EB'] }]
    },
    options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { font: rtlFont } } } }
});

// Branch Chart
new Chart(document.getElementById('branchChart'), {
    type: 'bar',
    data: {
        labels: chartData.branches.labels,
        datasets: [{ label: 'عدد التسجيلات', data: chartData.branches.data, backgroundColor: '#F97316' }]
    },
    options: { responsive: true, indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true } } }
});

// Late Chart
new Chart(document.getElementById('lateChart'), {
    type: 'line',
    data: {
        labels: chartData.late.labels,
        datasets: [{ label: 'متأخرين', data: chartData.late.data, borderColor: '#D97706', backgroundColor: 'rgba(217,119,6,.1)', fill: true, tension: .4 }]
    },
    options: { responsive: true, plugins: { legend: { labels: { font: rtlFont } } }, scales: { y: { beginAtZero: true } } }
});
</script>
@endpush
