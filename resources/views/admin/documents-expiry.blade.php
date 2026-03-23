@extends('layouts.admin')

@php
    $pageTitle = 'متابعة انتهاء الوثائق';
    $activePage = 'documents-expiry';
@endphp

@section('content')
<div class="card" style="margin-bottom:16px">
    <form method="GET" class="form-grid-2" style="align-items:end;gap:10px">
        <div class="form-group" style="margin:0">
            <label class="form-label">الفرع</label>
            <select name="branch_id" class="form-control">
                <option value="">كل الفروع</option>
                @foreach($branches as $branch)
                    <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group" style="margin:0">
            <label class="form-label">الحالة</label>
            <select name="status" class="form-control">
                <option value="all" {{ $status === 'all' ? 'selected' : '' }}>الكل</option>
                <option value="expired" {{ $status === 'expired' ? 'selected' : '' }}>منتهية</option>
                <option value="soon" {{ $status === 'soon' ? 'selected' : '' }}>قريبة الانتهاء (30 يوم)</option>
            </select>
        </div>
        <div>
            <button class="btn btn-primary" type="submit">تطبيق</button>
        </div>
    </form>
</div>

<div class="card">
    <div style="overflow-x:auto">
        <table class="emp-table">
            <thead>
                <tr>
                    <th>الموظف</th>
                    <th>الفرع</th>
                    <th>المجموعة</th>
                    <th>تاريخ الانتهاء</th>
                    <th>المتبقي</th>
                    <th>الملفات</th>
                    <th>إجراء</th>
                </tr>
            </thead>
            <tbody>
                @forelse($groups as $item)
                    <tr>
                        <td>{{ $item['employee']->name ?? '-' }}</td>
                        <td>{{ $item['employee']->branch->name ?? '-' }}</td>
                        <td>{{ $item['group_name'] }}</td>
                        <td>{{ $item['expiry_date']?->format('Y-m-d') }}</td>
                        <td>
                            <span class="badge {{ $item['days_left'] < 0 ? 'badge-red' : ($item['days_left'] <= 30 ? 'badge-orange' : 'badge-green') }}">
                                {{ $item['days_left'] < 0 ? 'منتهية' : $item['days_left'] . ' يوم' }}
                            </span>
                        </td>
                        <td>{{ $item['file_count'] }}</td>
                        <td>
                            <a class="btn btn-secondary btn-sm" href="{{ route('admin.employees.profile', $item['employee_id']) }}">عرض الملف</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align:center;color:#64748b">لا توجد نتائج</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top:12px">
        {{ $groups->withQueryString()->links() }}
    </div>
</div>
@endsection
