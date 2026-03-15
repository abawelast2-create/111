@extends('layouts.admin')

@section('content')

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px" class="top-actions">
    <span></span>
    <button class="btn btn-primary" onclick="document.getElementById('addBranchModal').classList.add('show')">
        <x-icon name="add" :size="18"/> إضافة فرع
    </button>
</div>

<div class="branch-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:18px">
@foreach($branches as $branch)
    <div class="card branch-card" style="margin-bottom:0">
        <div class="card-header" style="margin-bottom:12px;padding-bottom:12px">
            <span class="card-title">
                <span class="card-title-bar"></span>
                {{ $branch->name }}
                @if(!$branch->is_active)
                    <span class="badge badge-red">معطل</span>
                @endif
            </span>
            <div style="display:flex;gap:6px">
                <button class="btn btn-sm btn-secondary" onclick="editBranch({{ json_encode($branch) }})"><x-icon name="edit" :size="14"/></button>
                <form method="POST" action="{{ route('admin.branches.destroy', $branch) }}" onsubmit="return confirm('حذف هذا الفرع؟')" style="display:inline">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-danger"><x-icon name="delete" :size="14"/></button>
                </form>
            </div>
        </div>
        <div class="bc-info-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:8px 16px;font-size:.82rem">
            <div><span style="color:var(--text3)">الموقع:</span><br>{{ number_format($branch->latitude, 5) }}, {{ number_format($branch->longitude, 5) }}</div>
            <div><span style="color:var(--text3)">نطاق السياج:</span><br>{{ $branch->geofence_radius ?? '-' }} متر</div>
            <div><span style="color:var(--text3)">بداية الدوام:</span><br>{{ $branch->work_start_time ?? '-' }}</div>
            <div><span style="color:var(--text3)">نهاية الدوام:</span><br>{{ $branch->work_end_time ?? '-' }}</div>
            <div><span style="color:var(--text3)">نافذة الدخول:</span><br>{{ $branch->check_in_start ?? '-' }} - {{ $branch->check_in_end ?? '-' }}</div>
            <div><span style="color:var(--text3)">نافذة الانصراف:</span><br>{{ $branch->check_out_start ?? '-' }} - {{ $branch->check_out_end ?? '-' }}</div>
            @if($branch->overtime_start || $branch->overtime_end)
            <div><span style="color:var(--text3)">الإضافي:</span><br>{{ $branch->overtime_start ?? '-' }} - {{ $branch->overtime_end ?? '-' }}</div>
            @endif
        </div>
    </div>
@endforeach
</div>

<!-- Add Branch Modal -->
<div class="modal-overlay" id="addBranchModal">
    <div class="modal" style="max-width:600px">
        <div class="modal-title"><x-icon name="add" :size="20"/> إضافة فرع جديد</div>
        <form method="POST" action="{{ route('admin.branches.store') }}">
            @csrf
            <div class="form-group">
                <label class="form-label">اسم الفرع</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label">خط العرض</label>
                    <input type="number" step="any" name="latitude" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">خط الطول</label>
                    <input type="number" step="any" name="longitude" class="form-control" required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">نطاق السياج الجغرافي (متر)</label>
                <input type="number" name="geofence_radius" class="form-control" value="100">
            </div>
            <hr>
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label">بداية الدوام</label>
                    <input type="time" name="work_start_time" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">نهاية الدوام</label>
                    <input type="time" name="work_end_time" class="form-control">
                </div>
            </div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label">بداية نافذة الدخول</label>
                    <input type="time" name="check_in_start" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">نهاية نافذة الدخول</label>
                    <input type="time" name="check_in_end" class="form-control">
                </div>
            </div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label">بداية نافذة الانصراف</label>
                    <input type="time" name="check_out_start" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">نهاية نافذة الانصراف</label>
                    <input type="time" name="check_out_end" class="form-control">
                </div>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="this.closest('.modal-overlay').classList.remove('show')">إلغاء</button>
                <button type="submit" class="btn btn-primary">إضافة</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Branch Modal -->
<div class="modal-overlay" id="editBranchModal">
    <div class="modal" style="max-width:600px">
        <div class="modal-title"><x-icon name="edit" :size="20"/> تعديل الفرع</div>
        <form method="POST" id="editBranchForm">
            @csrf @method('PUT')
            <div class="form-group">
                <label class="form-label">اسم الفرع</label>
                <input type="text" name="name" id="eBranchName" class="form-control" required>
            </div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label">خط العرض</label>
                    <input type="number" step="any" name="latitude" id="eBranchLat" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">خط الطول</label>
                    <input type="number" step="any" name="longitude" id="eBranchLng" class="form-control" required>
                </div>
            </div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label">نطاق السياج (متر)</label>
                    <input type="number" name="geofence_radius" id="eBranchRadius" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">الحالة</label>
                    <select name="is_active" id="eBranchActive" class="form-control">
                        <option value="1">نشط</option>
                        <option value="0">معطل</option>
                    </select>
                </div>
            </div>
            <hr>
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label">بداية الدوام</label>
                    <input type="time" name="work_start_time" id="eBranchWorkStart" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">نهاية الدوام</label>
                    <input type="time" name="work_end_time" id="eBranchWorkEnd" class="form-control">
                </div>
            </div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label">بداية نافذة الدخول</label>
                    <input type="time" name="check_in_start" id="eBranchCiStart" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">نهاية نافذة الدخول</label>
                    <input type="time" name="check_in_end" id="eBranchCiEnd" class="form-control">
                </div>
            </div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label">بداية نافذة الانصراف</label>
                    <input type="time" name="check_out_start" id="eBranchCoStart" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">نهاية نافذة الانصراف</label>
                    <input type="time" name="check_out_end" id="eBranchCoEnd" class="form-control">
                </div>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="this.closest('.modal-overlay').classList.remove('show')">إلغاء</button>
                <button type="submit" class="btn btn-primary">حفظ</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
function editBranch(b) {
    document.getElementById('editBranchForm').action = '/admin/branches/' + b.id;
    document.getElementById('eBranchName').value = b.name;
    document.getElementById('eBranchLat').value = b.latitude;
    document.getElementById('eBranchLng').value = b.longitude;
    document.getElementById('eBranchRadius').value = b.geofence_radius || '';
    document.getElementById('eBranchActive').value = b.is_active ? '1' : '0';
    document.getElementById('eBranchWorkStart').value = b.work_start_time || '';
    document.getElementById('eBranchWorkEnd').value = b.work_end_time || '';
    document.getElementById('eBranchCiStart').value = b.check_in_start || '';
    document.getElementById('eBranchCiEnd').value = b.check_in_end || '';
    document.getElementById('eBranchCoStart').value = b.check_out_start || '';
    document.getElementById('eBranchCoEnd').value = b.check_out_end || '';
    document.getElementById('editBranchModal').classList.add('show');
}

document.querySelectorAll('.modal-overlay').forEach(ov => {
    ov.addEventListener('click', e => { if (e.target === ov) ov.classList.remove('show'); });
});
</script>
@endpush
