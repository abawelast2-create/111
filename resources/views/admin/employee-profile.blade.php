@extends('layouts.admin')

@php
    $pageTitle = 'بروفايل الموظف';
    $activePage = 'employees';
@endphp

@section('content')
<div class="card" style="margin-bottom:16px">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
        <div>
            <div class="card-title">{{ $employee->name }}</div>
            <div style="color:var(--text2);font-size:.9rem">{{ $employee->job_title }} {{ $employee->branch ? ' - ' . $employee->branch->name : '' }}</div>
        </div>
        <a href="{{ route('admin.employees.index') }}" class="btn btn-secondary">عودة</a>
    </div>

    <div style="display:grid;grid-template-columns:180px 1fr;gap:16px;align-items:start">
        <div>
            <div style="width:160px;height:160px;border-radius:12px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;overflow:hidden;border:1px solid #e2e8f0">
                @if($employee->profile_photo)
                    <img src="{{ url('/api/serve-file?f=' . urlencode($employee->profile_photo)) }}" alt="صورة الموظف" style="width:100%;height:100%;object-fit:cover">
                @else
                    <span style="color:#64748b">بدون صورة</span>
                @endif
            </div>
            <form id="photoForm" style="margin-top:10px;display:flex;gap:8px;flex-direction:column">
                @csrf
                <input type="hidden" name="action" value="photo">
                <input type="hidden" name="employee_id" value="{{ $employee->id }}">
                <input type="file" name="file" accept=".jpg,.jpeg,.png,.webp" required class="form-control">
                <button type="submit" class="btn btn-primary">رفع صورة</button>
                <button type="button" class="btn btn-danger" onclick="deletePhoto()">حذف الصورة</button>
            </form>
        </div>

        <div>
            <div class="card" style="margin:0">
                <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
                    <div class="card-title">مجموعات الوثائق</div>
                    <button class="btn btn-primary btn-sm" onclick="addGroup()">إضافة مجموعة</button>
                </div>
                <div id="groupsWrap" style="display:grid;gap:10px">
                    @forelse($employee->documentGroups as $group)
                        <div class="group-card" data-group-id="{{ $group->id }}" style="border:1px solid #e2e8f0;border-radius:10px;padding:12px">
                            <div style="display:grid;grid-template-columns:1fr 150px auto;gap:8px;align-items:end">
                                <div class="form-group" style="margin:0">
                                    <label class="form-label">اسم المجموعة</label>
                                    <input type="text" class="form-control group-name" value="{{ $group->group_name }}">
                                </div>
                                <div class="form-group" style="margin:0">
                                    <label class="form-label">تاريخ الانتهاء</label>
                                    <input type="date" class="form-control group-expiry" value="{{ $group->expiry_date?->format('Y-m-d') }}">
                                </div>
                                <div style="display:flex;gap:6px">
                                    <button class="btn btn-secondary btn-sm" onclick="saveGroup({{ $group->id }}, this)">حفظ</button>
                                    <button class="btn btn-danger btn-sm" onclick="deleteGroup({{ $group->id }})">حذف</button>
                                </div>
                            </div>

                            <div style="margin-top:10px;display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap">
                                <span class="badge {{ $group->days_left < 0 ? 'badge-red' : ($group->days_left <= 30 ? 'badge-orange' : 'badge-green') }}">
                                    {{ $group->days_left < 0 ? 'منتهية منذ ' . abs($group->days_left) . ' يوم' : 'متبقي ' . $group->days_left . ' يوم' }}
                                </span>
                                <form class="uploadDocForm" onsubmit="uploadDocument(event, {{ $group->id }})" style="display:flex;gap:8px;align-items:center">
                                    <input type="file" name="file" accept=".jpg,.jpeg,.png,.webp,.pdf" required class="form-control" style="max-width:280px">
                                    <button type="submit" class="btn btn-primary btn-sm">رفع وثيقة</button>
                                </form>
                            </div>

                            <div class="files-list" style="margin-top:10px;display:grid;gap:8px">
                                @foreach($group->files as $file)
                                    <div style="display:flex;justify-content:space-between;align-items:center;border:1px solid #f1f5f9;border-radius:8px;padding:8px">
                                        <div>
                                            <div style="font-weight:600">{{ $file->original_name }}</div>
                                            <div style="font-size:.8rem;color:#64748b">{{ strtoupper($file->file_type) }} - {{ number_format($file->file_size / 1024, 1) }} KB</div>
                                        </div>
                                        <div style="display:flex;gap:6px">
                                            <a class="btn btn-secondary btn-sm" target="_blank" href="{{ url('/api/serve-file?f=' . urlencode($file->file_path)) }}">عرض</a>
                                            <button class="btn btn-danger btn-sm" onclick="deleteFile({{ $file->id }})">حذف</button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <div style="color:#64748b">لا توجد مجموعات وثائق بعد.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const API_BASE = '{{ url('/api') }}';
const EMPLOYEE_ID = {{ $employee->id }};

async function postForm(url, data, isFormData = false) {
    const options = {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: isFormData ? data : new URLSearchParams(data)
    };
    if (!isFormData) {
        options.headers['Content-Type'] = 'application/x-www-form-urlencoded;charset=UTF-8';
    }

    const response = await fetch(url, options);
    return await response.json();
}

async function addGroup() {
    const result = await postForm(`${API_BASE}/profile-action`, {
        action: 'add_group',
        employee_id: EMPLOYEE_ID,
    });

    if (!result.success) {
        alert(result.message || 'فشل إضافة المجموعة');
        return;
    }

    location.reload();
}

async function saveGroup(groupId, btn) {
    const card = btn.closest('.group-card');
    const groupName = card.querySelector('.group-name').value;
    const expiryDate = card.querySelector('.group-expiry').value;

    const result = await postForm(`${API_BASE}/profile-action`, {
        action: 'save_group',
        employee_id: EMPLOYEE_ID,
        group_id: groupId,
        group_name: groupName,
        expiry_date: expiryDate,
    });

    if (!result.success) {
        alert(result.message || 'فشل الحفظ');
        return;
    }

    location.reload();
}

async function deleteGroup(groupId) {
    if (!confirm('حذف المجموعة وكل ملفاتها؟')) return;

    const result = await postForm(`${API_BASE}/profile-action`, {
        action: 'delete_group',
        employee_id: EMPLOYEE_ID,
        group_id: groupId,
    });

    if (!result.success) {
        alert(result.message || 'فشل الحذف');
        return;
    }

    location.reload();
}

async function deleteFile(fileId) {
    if (!confirm('حذف هذا الملف؟')) return;

    const result = await postForm(`${API_BASE}/profile-action`, {
        action: 'delete_file',
        employee_id: EMPLOYEE_ID,
        file_id: fileId,
    });

    if (!result.success) {
        alert(result.message || 'فشل حذف الملف');
        return;
    }

    location.reload();
}

async function deletePhoto() {
    if (!confirm('حذف صورة البروفايل؟')) return;

    const result = await postForm(`${API_BASE}/profile-action`, {
        action: 'delete_photo',
        employee_id: EMPLOYEE_ID,
    });

    if (!result.success) {
        alert(result.message || 'فشل حذف الصورة');
        return;
    }

    location.reload();
}

async function uploadDocument(event, groupId) {
    event.preventDefault();

    const form = event.target;
    const fileInput = form.querySelector('input[type="file"]');
    if (!fileInput.files.length) return;

    const data = new FormData();
    data.append('action', 'document');
    data.append('employee_id', EMPLOYEE_ID);
    data.append('group_id', groupId);
    data.append('file', fileInput.files[0]);

    const result = await postForm(`${API_BASE}/upload-profile`, data, true);
    if (!result.success) {
        alert(result.message || 'فشل رفع الوثيقة');
        return;
    }

    location.reload();
}

document.getElementById('photoForm').addEventListener('submit', async (event) => {
    event.preventDefault();

    const formData = new FormData(event.target);
    const result = await postForm(`${API_BASE}/upload-profile`, formData, true);
    if (!result.success) {
        alert(result.message || 'فشل رفع الصورة');
        return;
    }

    location.reload();
});
</script>
@endpush
