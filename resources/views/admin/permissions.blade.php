@extends('layouts.admin')

@section('content')
@php($currentAdmin = \App\Services\AdminPermissionService::currentAdmin())
<div class="card" style="margin-bottom:16px">
    <h3 style="margin:0 0 10px 0">إدارة الصلاحيات بالمجموعات</h3>
    <p style="margin:0;color:var(--text-muted,#6b7280)">
        يمكنك منح أي مستخدم إداري مجموعة أو عدة مجموعات صلاحيات بغض النظر عن المسمى الوظيفي.
        الاعتماديات يتم التحقق منها تلقائيًا لمنع صلاحيات غير منطقية.
    </p>
</div>

@foreach($admins as $admin)
<div class="card" style="margin-bottom:14px">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:12px">
        <div>
            <strong>{{ $admin->full_name }}</strong>
            <div style="font-size:.85rem;color:var(--text-muted,#6b7280)">
                {{ $admin->username }} | ID: {{ $admin->id }}
                @if($admin->is_super_admin)
                    <span class="badge badge-orange" style="margin-right:6px">صلاحيات مطلقة</span>
                @endif
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.permissions.assign', $admin) }}">
        @csrf
        <input type="hidden" name="is_super_admin" value="0">

        <div style="margin-bottom:10px">
            <label style="display:inline-flex;align-items:center;gap:8px;cursor:pointer">
                <input type="checkbox" name="is_super_admin" value="1" {{ $admin->is_super_admin ? 'checked' : '' }} {{ (!$currentAdmin || !$currentAdmin->is_super_admin) ? 'disabled' : '' }}>
                <span>صلاحيات مطلقة (Super Admin)</span>
            </label>
            @if(!$currentAdmin || !$currentAdmin->is_super_admin)
                <div style="font-size:.8rem;color:#b45309;margin-top:6px">تعديل حالة Super Admin يتطلب حساب مدير نظام مطلق.</div>
            @endif
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:12px">
            @foreach($groups as $group)
                @php
                    $checked = $admin->permissionGroups->contains('id', $group->id);
                @endphp
                <div style="border:1px solid var(--border,#e5e7eb);border-radius:10px;padding:12px">
                    <label style="display:flex;align-items:flex-start;gap:8px;cursor:pointer">
                        <input type="checkbox" name="permission_group_ids[]" value="{{ $group->id }}" {{ $checked ? 'checked' : '' }}>
                        <span>
                            <strong>{{ $group->name }}</strong>
                            <span style="display:block;font-size:.83rem;color:var(--text-muted,#6b7280)">{{ $group->description }}</span>
                        </span>
                    </label>

                    <div style="margin-top:8px;font-size:.8rem;color:var(--text-muted,#6b7280)">
                        @foreach($group->permissions as $permission)
                            <div style="margin-bottom:4px">
                                • {{ $permission->name }}
                                @if(!empty($permission->depends_on))
                                    <span style="color:#b45309">(يعتمد على: {{ implode(', ', $permission->depends_on) }})</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        <div style="margin-top:12px;display:flex;justify-content:flex-end">
            <button type="submit" class="btn btn-primary">حفظ صلاحيات {{ $admin->username }}</button>
        </div>
    </form>
</div>
@endforeach
@endsection
