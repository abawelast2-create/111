@extends('layouts.admin')

@section('content')

<!-- Filters -->
<div class="card" style="margin-bottom:16px">
    <form method="GET" action="{{ route('admin.attendance.index') }}" class="filter-bar" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap">
        <div class="form-group" style="margin:0">
            <label class="form-label">من تاريخ</label>
            <input type="date" name="from" value="{{ request('from', now()->format('Y-m-d')) }}" class="form-control">
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
        <div class="form-group" style="margin:0">
            <label class="form-label">النوع</label>
            <select name="type" class="form-control">
                <option value="">الكل</option>
                <option value="in" {{ request('type') === 'in' ? 'selected' : '' }}>دخول</option>
                <option value="out" {{ request('type') === 'out' ? 'selected' : '' }}>انصراف</option>
                <option value="overtime-start" {{ request('type') === 'overtime-start' ? 'selected' : '' }}>بداية إضافي</option>
                <option value="overtime-end" {{ request('type') === 'overtime-end' ? 'selected' : '' }}>نهاية إضافي</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary btn-sm"><x-icon name="filter" :size="16"/> بحث</button>
        <a href="{{ route('admin.attendance.index') }}?from={{ request('from', now()->format('Y-m-d')) }}&to={{ request('to', now()->format('Y-m-d')) }}&branch_id={{ request('branch_id') }}&export=csv" class="btn btn-green btn-sm">
            <x-icon name="export" :size="16"/> تصدير CSV
        </a>
    </form>
</div>

<!-- Stats -->
<div class="stats-grid" style="margin-bottom:18px">
    <div class="stat-card">
        <div class="stat-icon-wrap green"><x-icon name="checkin" :size="22"/></div>
        <div><div class="stat-value">{{ $stats['in'] ?? 0 }}</div><div class="stat-label">تسجيل دخول</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon-wrap red"><x-icon name="checkout" :size="22"/></div>
        <div><div class="stat-value">{{ $stats['out'] ?? 0 }}</div><div class="stat-label">تسجيل انصراف</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon-wrap purple"><x-icon name="overtime" :size="22"/></div>
        <div><div class="stat-value">{{ $stats['overtime'] ?? 0 }}</div><div class="stat-label">دوام إضافي</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon-wrap blue"><x-icon name="clock" :size="22"/></div>
        <div><div class="stat-value">{{ $stats['late'] ?? 0 }}</div><div class="stat-label">تأخير</div></div>
    </div>
</div>

<!-- Table -->
<div class="card">
    <div class="card-header">
        <span class="card-title"><span class="card-title-bar"></span> سجل الحضور</span>
        <span class="badge badge-blue">{{ $attendances->total() }} سجل</span>
    </div>
    <div style="overflow-x:auto">
    <table class="att-table">
        <thead>
            <tr>
                <th>#</th>
                <th>الموظف</th>
                <th>الفرع</th>
                <th>النوع</th>
                <th>التاريخ</th>
                <th>الوقت</th>
                <th>الموقع</th>
                <th>الدقة</th>
                <th>التأخير</th>
            </tr>
        </thead>
        <tbody>
        @forelse($attendances as $att)
            <tr>
                <td>{{ $att->id }}</td>
                <td><strong>{{ $att->employee->name ?? '-' }}</strong></td>
                <td style="font-size:.78rem">{{ $att->employee->branch->name ?? '-' }}</td>
                <td>
                    @php
                    $typeMap = ['in'=>['دخول','badge-green'], 'out'=>['انصراف','badge-red'],
                        'overtime-start'=>['بداية إضافي','badge-purple'], 'overtime-end'=>['نهاية إضافي','badge-blue']];
                    $t = $typeMap[$att->type] ?? ['غير معروف','badge-gray'];
                    @endphp
                    <span class="badge {{ $t[1] }}">{{ $t[0] }}</span>
                </td>
                <td>{{ $att->attendance_date }}</td>
                <td style="font-size:.8rem;color:var(--text3)">{{ \Carbon\Carbon::parse($att->timestamp)->format('h:i A') }}</td>
                <td style="font-size:.72rem;color:var(--text3)">
                    @if($att->latitude && $att->longitude)
                        {{ number_format($att->latitude, 4) }}, {{ number_format($att->longitude, 4) }}
                    @else - @endif
                </td>
                <td>{{ $att->accuracy ? round($att->accuracy) . 'm' : '-' }}</td>
                <td>
                    @if($att->late_minutes > 0)
                        <span class="badge badge-yellow">{{ $att->late_minutes }} د</span>
                    @else - @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="9" style="text-align:center;color:var(--text3);padding:24px">لا توجد سجلات</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>

    <!-- Pagination -->
    @if($attendances->hasPages())
    <div style="margin-top:16px;display:flex;justify-content:center">
        {{ $attendances->appends(request()->query())->links('pagination::simple-default') }}
    </div>
    @endif
</div>

@endsection
