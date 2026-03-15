@extends('layouts.admin')

@section('content')

<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:20px" class="top-actions">
    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
        <input type="text" id="searchInput" class="form-control" placeholder="بحث بالاسم..." style="max-width:200px" onkeyup="filterTable()">
        <select id="branchFilter" class="form-control" style="max-width:180px" onchange="filterTable()">
            <option value="">كل الفروع</option>
            @foreach($branches as $branch)
                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
            @endforeach
        </select>
    </div>
    <button class="btn btn-primary" onclick="document.getElementById('addModal').classList.add('show')">
        <x-icon name="add" :size="18"/> إضافة موظف
    </button>
</div>

<div class="card">
    <div style="overflow-x:auto">
    <table class="emp-table">
        <thead>
            <tr>
                <th>#</th>
                <th>الاسم</th>
                <th>الوظيفة</th>
                <th>الهاتف</th>
                <th>الحالة</th>
                <th>الفرع</th>
                <th>الرابط</th>
                <th>إجراءات</th>
            </tr>
        </thead>
        <tbody id="employeesBody">
        @php $prevBranch = null; @endphp
        @foreach($employees as $emp)
            @if($emp->branch_id !== $prevBranch)
                @php $prevBranch = $emp->branch_id; @endphp
                <tr class="branch-separator" data-branch="{{ $emp->branch_id }}">
                    <td colspan="8" style="background:var(--primary-l);color:var(--primary-d);font-weight:700;font-size:.82rem">
                        <x-icon name="branch" :size="14"/> {{ $emp->branch->name ?? 'بدون فرع' }}
                    </td>
                </tr>
            @endif
            <tr data-branch="{{ $emp->branch_id }}" data-name="{{ $emp->name }}">
                <td>{{ $emp->id }}</td>
                <td><strong>{{ $emp->name }}</strong></td>
                <td>{{ $emp->job_title }}</td>
                <td style="direction:ltr">{{ $emp->phone ?? '-' }}</td>
                <td><span class="badge {{ $emp->is_active ? 'badge-green' : 'badge-red' }}">{{ $emp->is_active ? 'نشط' : 'معطل' }}</span></td>
                <td>{{ $emp->branch->name ?? '-' }}</td>
                <td>
                    @if($emp->unique_token)
                    <button class="btn btn-sm btn-secondary" onclick="copyLink('{{ url('/employee?token=' . $emp->unique_token) }}')" title="نسخ الرابط">
                        <x-icon name="copy" :size="14"/>
                    </button>
                    @endif
                </td>
                <td>
                    <div class="actions-wrap" style="display:flex;gap:4px;flex-wrap:wrap">
                        <button class="btn btn-sm btn-secondary" onclick="editEmployee({{ json_encode($emp) }})">
                            <x-icon name="edit" :size="14"/>
                        </button>
                        @if($emp->phone)
                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $emp->phone) }}" target="_blank" class="btn btn-sm btn-wa" title="واتساب">
                            <x-icon name="whatsapp" :size="14"/>
                        </a>
                        @endif
                        <form method="POST" action="{{ route('admin.employees.destroy', $emp) }}" style="display:inline" onsubmit="return confirm('حذف هذا الموظف؟')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger"><x-icon name="delete" :size="14"/></button>
                        </form>
                    </div>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    </div>
</div>

<!-- Add Modal -->
<div class="modal-overlay" id="addModal">
    <div class="modal">
        <div class="modal-title"><x-icon name="add" :size="20"/> إضافة موظف جديد</div>
        <form method="POST" action="{{ route('admin.employees.store') }}">
            @csrf
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label">الاسم الكامل</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">الوظيفة</label>
                    <input type="text" name="job_title" class="form-control" required>
                </div>
            </div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label">رقم الهاتف</label>
                    <input type="text" name="phone" class="form-control" dir="ltr">
                </div>
                <div class="form-group">
                    <label class="form-label">الفرع</label>
                    <select name="branch_id" class="form-control" required>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">وضع ربط الجهاز</label>
                <select name="device_bind_mode" class="form-control">
                    <option value="0">حر (0)</option>
                    <option value="1">صارم (1)</option>
                    <option value="2">مراقبة (2)</option>
                </select>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="this.closest('.modal-overlay').classList.remove('show')">إلغاء</button>
                <button type="submit" class="btn btn-primary">إضافة</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal-overlay" id="editModal">
    <div class="modal">
        <div class="modal-title"><x-icon name="edit" :size="20"/> تعديل الموظف</div>
        <form method="POST" id="editForm">
            @csrf @method('PUT')
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label">الاسم الكامل</label>
                    <input type="text" name="name" id="editName" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">الوظيفة</label>
                    <input type="text" name="job_title" id="editJob" class="form-control" required>
                </div>
            </div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label">رقم الهاتف</label>
                    <input type="text" name="phone" id="editPhone" class="form-control" dir="ltr">
                </div>
                <div class="form-group">
                    <label class="form-label">الفرع</label>
                    <select name="branch_id" id="editBranch" class="form-control" required>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label">الحالة</label>
                    <select name="is_active" id="editActive" class="form-control">
                        <option value="1">نشط</option>
                        <option value="0">معطل</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">وضع ربط الجهاز</label>
                    <select name="device_bind_mode" id="editBindMode" class="form-control">
                        <option value="0">حر (0)</option>
                        <option value="1">صارم (1)</option>
                        <option value="2">مراقبة (2)</option>
                    </select>
                </div>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="this.closest('.modal-overlay').classList.remove('show')">إلغاء</button>
                <button type="submit" class="btn btn-primary">حفظ التعديلات</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
function filterTable() {
    const search = document.getElementById('searchInput').value.toLowerCase();
    const branch = document.getElementById('branchFilter').value;
    document.querySelectorAll('#employeesBody tr').forEach(tr => {
        if (tr.classList.contains('branch-separator')) {
            tr.style.display = (!branch || tr.dataset.branch === branch) ? '' : 'none';
            return;
        }
        const matchName = !search || (tr.dataset.name || '').toLowerCase().includes(search);
        const matchBranch = !branch || tr.dataset.branch === branch;
        tr.style.display = (matchName && matchBranch) ? '' : 'none';
    });
}

function editEmployee(emp) {
    document.getElementById('editForm').action = '/admin/employees/' + emp.id;
    document.getElementById('editName').value = emp.name;
    document.getElementById('editJob').value = emp.job_title || '';
    document.getElementById('editPhone').value = emp.phone || '';
    document.getElementById('editBranch').value = emp.branch_id;
    document.getElementById('editActive').value = emp.is_active ? '1' : '0';
    document.getElementById('editBindMode').value = emp.device_bind_mode || '0';
    document.getElementById('editModal').classList.add('show');
}

function copyLink(url) {
    navigator.clipboard.writeText(url).then(() => {
        if (typeof Toast !== 'undefined') Toast.success('تم نسخ الرابط');
    });
}

// Close modals on overlay click
document.querySelectorAll('.modal-overlay').forEach(ov => {
    ov.addEventListener('click', e => { if (e.target === ov) ov.classList.remove('show'); });
});
</script>
@endpush
