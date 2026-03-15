@extends('layouts.admin')

@section('content')

<!-- Stats -->
<div class="stats-grid" style="margin-bottom:18px">
    <div class="stat-card">
        <div class="stat-icon-wrap red"><x-icon name="lock" :size="22"/></div>
        <div><div class="stat-value">{{ $totalCases }}</div><div class="stat-label">إجمالي الحالات</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon-wrap yellow"><x-icon name="lock" :size="22"/></div>
        <div><div class="stat-value">{{ $highSeverity }}</div><div class="stat-label">خطورة عالية</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon-wrap orange"><x-icon name="lock" :size="22"/></div>
        <div><div class="stat-value">{{ $mediumSeverity }}</div><div class="stat-label">خطورة متوسطة</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon-wrap blue"><x-icon name="lock" :size="22"/></div>
        <div><div class="stat-value">{{ $lowSeverity }}</div><div class="stat-label">خطورة منخفضة</div></div>
    </div>
</div>

<!-- Table -->
<div class="card">
    <div class="card-header">
        <span class="card-title"><span class="card-title-bar"></span> حالات التلاعب</span>
    </div>
    <div style="overflow-x:auto">
    <table>
        <thead>
            <tr><th>الموظف</th><th>الفرع</th><th>نوع التلاعب</th><th>الخطورة</th><th>التفاصيل</th><th>التاريخ</th></tr>
        </thead>
        <tbody>
        @forelse($cases as $case)
            <tr>
                <td><strong>{{ $case->employee->name ?? '-' }}</strong></td>
                <td style="font-size:.78rem">{{ $case->employee->branch->name ?? '-' }}</td>
                <td>{{ $case->case_type }}</td>
                <td>
                    @php
                    $sevMap = ['high'=>['عالية','badge-red'],'medium'=>['متوسطة','badge-yellow'],'low'=>['منخفضة','badge-blue']];
                    $sv = $sevMap[$case->severity] ?? ['غير محدد','badge-gray'];
                    @endphp
                    <span class="badge {{ $sv[1] }}">{{ $sv[0] }}</span>
                </td>
                <td style="font-size:.78rem;max-width:200px;word-break:break-word">
                    @php
                    $details = json_decode($case->details_json, true);
                    @endphp
                    {{ $details['message'] ?? ($details['reason'] ?? json_encode($details)) }}
                </td>
                <td style="font-size:.78rem;color:var(--text3)">{{ $case->created_at->format('Y-m-d h:i A') }}</td>
            </tr>
        @empty
            <tr><td colspan="6" style="text-align:center;color:var(--text3);padding:24px">لا توجد حالات تلاعب</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>

    @if($cases->hasPages())
    <div style="margin-top:16px;display:flex;justify-content:center">
        {{ $cases->appends(request()->query())->links('pagination::simple-default') }}
    </div>
    @endif
</div>

@endsection
