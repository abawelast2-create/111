@extends('layouts.admin')

@section('content')

{{-- Stats --}}
@if($genCount > 0)
<div class="card" style="margin-bottom:16px;border-right:4px solid var(--warning)">
    <div style="padding:16px;display:flex;align-items:center;justify-content:between;gap:16px;flex-wrap:wrap">
        <div style="flex:1">
            <strong style="color:var(--warning)">⚠ بيانات مولّدة في القاعدة:</strong>
            <span style="font-size:1.2rem;font-weight:700;margin:0 8px">{{ number_format($genCount) }}</span> سجل
        </div>
        <form method="POST" action="{{ route('admin.data-generator.cleanup') }}" onsubmit="return confirm('هل تريد حذف جميع البيانات المولّدة؟ هذا لن يؤثر على البيانات الحقيقية')">
            @csrf
            <button type="submit" class="btn btn-sm" style="background:var(--danger);color:#fff">حذف جميع البيانات المولّدة</button>
        </form>
    </div>
</div>
@endif

{{-- Generator Form --}}
<div class="card" style="margin-bottom:20px">
    <div class="card-header">
        <span class="card-title"><span class="card-title-bar"></span> مولّد البيانات الاحترافي</span>
    </div>
    <form method="POST" action="{{ route('admin.data-generator.generate') }}" id="genForm" style="padding:16px">
        @csrf

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px">
            {{-- Scope --}}
            <div class="form-group">
                <label class="form-label">النطاق</label>
                <select name="scope" id="scopeSelect" class="form-control" required onchange="updateScope()">
                    <option value="all">جميع الموظفين ({{ $employees->count() }})</option>
                    <option value="branch">فرع محدد</option>
                    <option value="employee">موظف محدد</option>
                </select>
            </div>

            {{-- Branch Selector --}}
            <div class="form-group" id="branchGroup" style="display:none">
                <label class="form-label">الفرع</label>
                <select name="branch_id" id="branchSelect" class="form-control">
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }} ({{ $branch->employees_count }})</option>
                    @endforeach
                </select>
            </div>

            {{-- Employee Selector --}}
            <div class="form-group" id="employeeGroup" style="display:none">
                <label class="form-label">الموظف</label>
                <select name="employee_id" id="employeeSelect" class="form-control">
                    @foreach($employees as $emp)
                        <option value="{{ $emp->id }}">{{ $emp->name }} - {{ $emp->branch->name ?? 'بدون فرع' }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Date Range --}}
            <div class="form-group">
                <label class="form-label">من تاريخ</label>
                <input type="date" name="from" class="form-control" value="{{ today()->subMonth()->toDateString() }}" required>
            </div>
            <div class="form-group">
                <label class="form-label">إلى تاريخ</label>
                <input type="date" name="to" class="form-control" value="{{ today()->subDay()->toDateString() }}" required>
            </div>
        </div>

        {{-- Discipline Level --}}
        <div class="form-group" style="margin-top:16px">
            <label class="form-label">مستوى الانضباط: <strong id="levelValue" style="font-size:1.3rem;color:var(--primary)">5</strong>/10</label>
            <div style="display:flex;align-items:center;gap:12px;margin-top:8px">
                <span style="font-size:.8rem;color:var(--danger);white-space:nowrap">1 فوضوي</span>
                <input type="range" name="level" id="levelSlider" min="1" max="10" value="5" style="flex:1;height:8px;accent-color:var(--primary)" oninput="updateLevel(this.value)">
                <span style="font-size:.8rem;color:var(--success);white-space:nowrap">10 مثالي</span>
            </div>

            {{-- Level Description --}}
            <div id="levelDesc" style="margin-top:10px;padding:12px;border-radius:8px;background:var(--bg2);font-size:.85rem;line-height:1.6"></div>
        </div>

        {{-- Hidden scope_id --}}
        <input type="hidden" name="scope_id" id="scopeIdInput" value="">

        {{-- Preview Box --}}
        <div id="previewBox" style="margin-top:16px;padding:14px;border-radius:8px;background:var(--bg2);display:none">
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:10px;text-align:center">
                <div>
                    <div style="font-size:.8rem;color:var(--text3)">الموظفين</div>
                    <div id="prevEmployees" style="font-size:1.4rem;font-weight:700">-</div>
                </div>
                <div>
                    <div style="font-size:.8rem;color:var(--text3)">أيام العمل</div>
                    <div id="prevDays" style="font-size:1.4rem;font-weight:700">-</div>
                </div>
                <div>
                    <div style="font-size:.8rem;color:var(--text3)">السجلات المتوقعة</div>
                    <div id="prevRecords" style="font-size:1.4rem;font-weight:700;color:var(--primary)">-</div>
                </div>
                <div>
                    <div style="font-size:.8rem;color:var(--text3)">الفترة</div>
                    <div id="prevRange" style="font-size:.9rem;font-weight:600" dir="ltr">-</div>
                </div>
            </div>
        </div>

        <div style="display:flex;gap:10px;margin-top:16px;flex-wrap:wrap">
            <button type="button" class="btn btn-sm btn-secondary" onclick="loadPreview()">معاينة</button>
            <button type="submit" class="btn btn-sm" onclick="return confirm('هل أنت متأكد من توليد البيانات؟')">توليد البيانات</button>
        </div>
    </form>
</div>

{{-- Batches History --}}
@if(!empty($batches))
<div class="card">
    <div class="card-header">
        <span class="card-title"><span class="card-title-bar"></span> سجل الدفعات المولّدة</span>
    </div>
    <div style="overflow-x:auto">
        <table>
            <thead>
                <tr>
                    <th>معرف الدفعة</th>
                    <th>عدد السجلات</th>
                    <th>من</th>
                    <th>إلى</th>
                    <th>تاريخ التوليد</th>
                    <th>إجراء</th>
                </tr>
            </thead>
            <tbody>
                @foreach($batches as $batch)
                    <tr>
                        <td dir="ltr" style="font-family:monospace;font-size:.8rem">{{ $batch['batch_id'] }}</td>
                        <td><strong>{{ number_format($batch['record_count']) }}</strong></td>
                        <td>{{ $batch['from_date'] }}</td>
                        <td>{{ $batch['to_date'] }}</td>
                        <td style="font-size:.85rem">{{ \Carbon\Carbon::parse($batch['created_at'])->diffForHumans() }}</td>
                        <td>
                            <form method="POST" action="{{ route('admin.data-generator.cleanup') }}" style="margin:0" onsubmit="return confirm('حذف هذه الدفعة؟')">
                                @csrf
                                <input type="hidden" name="batch_id" value="{{ $batch['batch_id'] }}">
                                <button type="submit" class="btn btn-sm" style="background:var(--danger);color:#fff;font-size:.75rem;padding:4px 10px">حذف</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<script>
const levelDescs = {
    1:  '🔴 فوضوي تام: غياب 30-42%، تأخير 60-80%، تأخير 25-120 دقيقة، تلاعب GPS، انصراف مبكر متكرر',
    2:  '🔴 ضعيف جداً: غياب 22-32%، تأخير 48-65%، تأخير 18-90 دقيقة، بعض تلاعب GPS',
    3:  '🟠 ضعيف: غياب 16-25%، تأخير 36-52%، تأخير 12-60 دقيقة',
    4:  '🟠 أقل من المتوسط: غياب 11-19%، تأخير 26-42%، تأخير 8-45 دقيقة',
    5:  '🟡 متوسط: غياب 7-14%، تأخير 18-32%، تأخير 5-30 دقيقة، بعض الإضافي',
    6:  '🟡 فوق المتوسط: غياب 5-10%، تأخير 12-23%، تأخير 3-20 دقيقة',
    7:  '🟢 جيد: غياب 3-7%، تأخير 7-16%، تأخير 2-15 دقيقة، إضافي منتظم',
    8:  '🟢 جيد جداً: غياب 2-5%، تأخير 4-10%، تأخير 1-10 دقائق، إضافي كثير',
    9:  '🔵 ممتاز: غياب 1-3%، تأخير 1-5%، تأخير 1-5 دقائق، إضافي متكرر',
    10: '🔵 مثالي: غياب 0-1%، تأخير 0-2%، التزام تام، إضافي مرتفع',
};

function updateLevel(val) {
    document.getElementById('levelValue').textContent = val;
    document.getElementById('levelDesc').textContent = levelDescs[val] || '';

    const colors = {1:'#e74c3c',2:'#e74c3c',3:'#e67e22',4:'#e67e22',5:'#f1c40f',6:'#f1c40f',7:'#27ae60',8:'#27ae60',9:'#2980b9',10:'#2980b9'};
    document.getElementById('levelValue').style.color = colors[val] || 'var(--primary)';
}

function updateScope() {
    const scope = document.getElementById('scopeSelect').value;
    document.getElementById('branchGroup').style.display = scope === 'branch' ? 'block' : 'none';
    document.getElementById('employeeGroup').style.display = scope === 'employee' ? 'block' : 'none';
    updateScopeId();
}

function updateScopeId() {
    const scope = document.getElementById('scopeSelect').value;
    let id = '';
    if (scope === 'branch') id = document.getElementById('branchSelect').value;
    if (scope === 'employee') id = document.getElementById('employeeSelect').value;
    document.getElementById('scopeIdInput').value = id;
}

function loadPreview() {
    updateScopeId();
    const form = document.getElementById('genForm');
    const fd = new FormData(form);
    const params = new URLSearchParams({
        from: fd.get('from'),
        to: fd.get('to'),
        scope: fd.get('scope'),
        scope_id: fd.get('scope_id') || '',
    });

    fetch('{{ route("admin.data-generator.preview") }}?' + params, {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        document.getElementById('prevEmployees').textContent = data.employees;
        document.getElementById('prevDays').textContent = data.work_days;
        document.getElementById('prevRecords').textContent = data.estimated_records.toLocaleString();
        document.getElementById('prevRange').textContent = data.date_range;
        document.getElementById('previewBox').style.display = 'block';
    });
}

// Init
updateLevel(5);
document.getElementById('branchSelect')?.addEventListener('change', updateScopeId);
document.getElementById('employeeSelect')?.addEventListener('change', updateScopeId);
</script>

@endsection
