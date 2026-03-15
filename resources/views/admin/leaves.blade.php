@extends('layouts.admin')

@section('content')

<!-- Stats -->
<div class="stats-grid" style="margin-bottom:18px">
    <div class="stat-card">
        <div class="stat-icon-wrap yellow"><x-icon name="clock" :size="22"/></div>
        <div><div class="stat-value">{{ $pendingCount }}</div><div class="stat-label">قيد الانتظار</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon-wrap green"><x-icon name="checkin" :size="22"/></div>
        <div><div class="stat-value">{{ $approvedCount }}</div><div class="stat-label">مقبولة</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon-wrap red"><x-icon name="checkout" :size="22"/></div>
        <div><div class="stat-value">{{ $rejectedCount }}</div><div class="stat-label">مرفوضة</div></div>
    </div>
</div>

<!-- Filter -->
<div class="card" style="margin-bottom:16px">
    <form method="GET" action="{{ route('admin.leaves.index') }}" class="filter-bar" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap">
        <div class="form-group" style="margin:0">
            <label class="form-label">الحالة</label>
            <select name="status" class="form-control">
                <option value="">الكل</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>قيد الانتظار</option>
                <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>مقبولة</option>
                <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>مرفوضة</option>
            </select>
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
        <button type="submit" class="btn btn-primary btn-sm"><x-icon name="filter" :size="16"/> بحث</button>
    </form>
</div>

<!-- Table -->
<div class="card">
    <div class="card-header">
        <span class="card-title"><span class="card-title-bar"></span> الإجازات</span>
    </div>
    <div style="overflow-x:auto">
    <table>
        <thead>
            <tr><th>الموظف</th><th>الفرع</th><th>النوع</th><th>من</th><th>إلى</th><th>الحالة</th><th>إجراءات</th></tr>
        </thead>
        <tbody>
        @forelse($leaves as $leave)
            <tr>
                <td><strong>{{ $leave->employee->name ?? '-' }}</strong></td>
                <td style="font-size:.78rem">{{ $leave->employee->branch->name ?? '-' }}</td>
                <td>
                    @php
                    $typeMap = ['annual'=>'سنوية','sick'=>'مرضية','unpaid'=>'بدون راتب','other'=>'أخرى'];
                    @endphp
                    {{ $typeMap[$leave->type] ?? $leave->type }}
                </td>
                <td>{{ $leave->start_date }}</td>
                <td>{{ $leave->end_date }}</td>
                <td>
                    @php
                    $statusMap = ['pending'=>['قيد الانتظار','badge-yellow'],'approved'=>['مقبولة','badge-green'],'rejected'=>['مرفوضة','badge-red']];
                    $s = $statusMap[$leave->status] ?? ['غير معروف','badge-gray'];
                    @endphp
                    <span class="badge {{ $s[1] }}">{{ $s[0] }}</span>
                </td>
                <td>
                    @if($leave->status === 'pending')
                    <div style="display:flex;gap:4px">
                        <form method="POST" action="{{ route('admin.leaves.approve', $leave) }}" style="display:inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-green">قبول</button>
                        </form>
                        <form method="POST" action="{{ route('admin.leaves.reject', $leave) }}" style="display:inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-danger">رفض</button>
                        </form>
                    </div>
                    @else
                        -
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="7" style="text-align:center;color:var(--text3);padding:24px">لا توجد إجازات</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>

    @if($leaves->hasPages())
    <div style="margin-top:16px;display:flex;justify-content:center">
        {{ $leaves->appends(request()->query())->links('pagination::simple-default') }}
    </div>
    @endif
</div>

@endsection
