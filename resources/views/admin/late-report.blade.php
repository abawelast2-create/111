@extends('layouts.admin')

@section('content')

<div class="card" style="margin-bottom:16px">
    <form method="GET" action="{{ route('admin.late-report') }}" class="filter-bar" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap">
        <div class="form-group" style="margin:0">
            <label class="form-label">من تاريخ</label>
            <input type="date" name="from" value="{{ request('from', $from) }}" class="form-control">
        </div>
        <div class="form-group" style="margin:0">
            <label class="form-label">إلى تاريخ</label>
            <input type="date" name="to" value="{{ request('to', $to) }}" class="form-control">
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
        <a href="{{ route('admin.late-report') }}?from={{ request('from', $from) }}&to={{ request('to', $to) }}&branch_id={{ request('branch_id') }}&export=csv" class="btn btn-green btn-sm">
            <x-icon name="export" :size="16"/> تصدير
        </a>
    </form>
</div>

<!-- Employee Summary -->
<div class="card">
    <div class="card-header">
        <span class="card-title"><span class="card-title-bar"></span> ملخص التأخير حسب الموظف</span>
    </div>
    <div style="overflow-x:auto">
    <table>
        <thead>
            <tr><th>الموظف</th><th>الفرع</th><th>عدد مرات التأخير</th><th>إجمالي الدقائق</th><th>التقييم</th></tr>
        </thead>
        <tbody>
        @forelse($summary as $item)
            <tr>
                <td><strong>{{ $item->name }}</strong></td>
                <td style="font-size:.78rem">{{ $item->branch_name ?? '-' }}</td>
                <td>{{ $item->late_count }}</td>
                <td>{{ $item->total_late_minutes }} دقيقة</td>
                <td>
                    @if($item->late_count <= 2)
                        <span class="badge badge-green">ممتاز</span>
                    @elseif($item->late_count <= 5)
                        <span class="badge badge-yellow">مقبول</span>
                    @else
                        <span class="badge badge-red">سيء</span>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="5" style="text-align:center;color:var(--green);padding:24px">لا يوجد تأخير في هذه الفترة</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
</div>

<!-- Daily Details -->
<div class="card">
    <div class="card-header">
        <span class="card-title"><span class="card-title-bar"></span> تفاصيل التأخير اليومي</span>
    </div>
    <div style="overflow-x:auto">
    <table>
        <thead>
            <tr><th>التاريخ</th><th>الموظف</th><th>الفرع</th><th>وقت الدخول</th><th>دقائق التأخير</th></tr>
        </thead>
        <tbody>
        @forelse($details as $d)
            <tr>
                <td>{{ $d->attendance_date }}</td>
                <td><strong>{{ $d->employee->name ?? '-' }}</strong></td>
                <td style="font-size:.78rem">{{ $d->employee->branch->name ?? '-' }}</td>
                <td style="font-size:.8rem">{{ \Carbon\Carbon::parse($d->timestamp)->format('h:i A') }}</td>
                <td><span class="badge badge-yellow">{{ $d->late_minutes }} د</span></td>
            </tr>
        @empty
            <tr><td colspan="5" style="text-align:center;color:var(--text3);padding:24px">لا توجد سجلات</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>

    @if($details->hasPages())
    <div style="margin-top:16px;display:flex;justify-content:center">
        {{ $details->appends(request()->query())->links('pagination::simple-default') }}
    </div>
    @endif
</div>

@endsection
