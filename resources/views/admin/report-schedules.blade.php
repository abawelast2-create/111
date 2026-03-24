@extends('layouts.admin')

@section('content')

{{-- إرسال فوري --}}
<div class="card" style="margin-bottom:20px">
    <div class="card-header">
        <span class="card-title"><span class="card-title-bar"></span> إرسال تقرير فوري</span>
    </div>
    <form method="POST" action="{{ route('admin.report-schedules.send-now') }}" style="padding:16px">
        @csrf
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px">
            <div class="form-group">
                <label class="form-label">نوع التقرير</label>
                <select name="report_type" class="form-control" required>
                    <option value="daily">التقرير اليومي</option>
                    <option value="late">تقرير التأخيرات</option>
                    <option value="full">التقرير الشامل</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">من تاريخ</label>
                <input type="date" name="from" class="form-control" value="{{ today()->subDay()->toDateString() }}" required>
            </div>
            <div class="form-group">
                <label class="form-label">إلى تاريخ</label>
                <input type="date" name="to" class="form-control" value="{{ today()->toDateString() }}" required>
            </div>
            <div class="form-group">
                <label class="form-label">الفرع (اختياري)</label>
                <select name="branch_id" class="form-control">
                    <option value="">جميع الفروع</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="form-group" style="margin-top:8px">
            <label class="form-label">المستلمون (بريد إلكتروني، يفصل بفاصلة)</label>
            <input type="text" name="recipients" class="form-control" required placeholder="example@email.com, other@email.com" dir="ltr">
        </div>
        <button type="submit" class="btn btn-sm" style="margin-top:8px">إرسال الآن</button>
    </form>
</div>

{{-- الجدولات --}}
<div class="card">
    <div class="card-header">
        <span class="card-title"><span class="card-title-bar"></span> التقارير المجدولة</span>
        <button type="button" class="btn btn-sm" onclick="document.getElementById('addScheduleModal').style.display='flex'">
            إضافة جدولة
        </button>
    </div>

    <div style="overflow-x:auto">
        <table>
            <thead>
                <tr>
                    <th>الاسم</th>
                    <th>النوع</th>
                    <th>التكرار</th>
                    <th>الوقت</th>
                    <th>المستلمون</th>
                    <th>الحالة</th>
                    <th>آخر إرسال</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($schedules as $schedule)
                    <tr>
                        <td><strong>{{ $schedule->name }}</strong></td>
                        <td>
                            @php
                                $types = ['daily' => 'يومي', 'late' => 'تأخيرات', 'full' => 'شامل'];
                            @endphp
                            <span class="badge badge-blue">{{ $types[$schedule->report_type] ?? $schedule->report_type }}</span>
                        </td>
                        <td>
                            @php
                                $freqs = ['daily' => 'يومياً', 'weekly' => 'أسبوعياً', 'monthly' => 'شهرياً'];
                            @endphp
                            {{ $freqs[$schedule->frequency] ?? $schedule->frequency }}
                            @if($schedule->send_day)
                                ({{ $schedule->send_day }})
                            @endif
                        </td>
                        <td dir="ltr" style="text-align:right">{{ $schedule->send_time }}</td>
                        <td>
                            @foreach($schedule->recipients ?? [] as $email)
                                <span class="badge badge-blue" style="font-size:.7rem;margin:1px;display:inline-block" dir="ltr">{{ $email }}</span>
                            @endforeach
                        </td>
                        <td>
                            <span class="badge {{ $schedule->is_active ? 'badge-green' : 'badge-red' }}">
                                {{ $schedule->is_active ? 'فعال' : 'معطل' }}
                            </span>
                        </td>
                        <td style="font-size:.85rem">
                            {{ $schedule->last_sent_at ? $schedule->last_sent_at->diffForHumans() : 'لم يرسل بعد' }}
                        </td>
                        <td>
                            <div style="display:flex;gap:4px;flex-wrap:wrap">
                                <form method="POST" action="{{ route('admin.report-schedules.toggle', $schedule) }}" style="margin:0">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-secondary" style="font-size:.75rem;padding:4px 8px">
                                        {{ $schedule->is_active ? 'إيقاف' : 'تفعيل' }}
                                    </button>
                                </form>
                                <button type="button" class="btn btn-sm btn-secondary" style="font-size:.75rem;padding:4px 8px"
                                    onclick="openEditModal({{ $schedule->toJson() }})">تعديل</button>
                                <form method="POST" action="{{ route('admin.report-schedules.destroy', $schedule) }}" style="margin:0" onsubmit="return confirm('هل تريد حذف هذه الجدولة؟')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm" style="background:var(--danger);color:#fff;font-size:.75rem;padding:4px 8px">حذف</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" style="text-align:center;color:var(--text3);padding:20px">لا توجد جدولات حالياً</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Add Modal --}}
<div id="addScheduleModal" style="display:none;position:fixed;inset:0;z-index:1000;background:rgba(0,0,0,.5);align-items:center;justify-content:center;padding:16px">
    <div class="card" style="max-width:500px;width:100%;max-height:90vh;overflow-y:auto">
        <div class="card-header">
            <span class="card-title">إضافة جدولة تقرير</span>
            <button type="button" onclick="document.getElementById('addScheduleModal').style.display='none'" style="background:none;border:none;font-size:1.2rem;cursor:pointer;color:var(--text2)">&times;</button>
        </div>
        <form method="POST" action="{{ route('admin.report-schedules.store') }}" style="padding:16px">
            @csrf
            <div class="form-group">
                <label class="form-label">اسم الجدولة</label>
                <input type="text" name="name" class="form-control" required placeholder="مثال: التقرير اليومي للإدارة">
            </div>
            <div class="form-group">
                <label class="form-label">نوع التقرير</label>
                <select name="report_type" class="form-control" required>
                    <option value="daily">التقرير اليومي</option>
                    <option value="late">تقرير التأخيرات</option>
                    <option value="full">التقرير الشامل</option>
                </select>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <div class="form-group">
                    <label class="form-label">التكرار</label>
                    <select name="frequency" class="form-control" required onchange="toggleDayField(this)">
                        <option value="daily">يومياً</option>
                        <option value="weekly">أسبوعياً</option>
                        <option value="monthly">شهرياً</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">وقت الإرسال</label>
                    <input type="time" name="send_time" class="form-control" value="08:00" required>
                </div>
            </div>
            <div class="form-group" id="addDayField" style="display:none">
                <label class="form-label">يوم الإرسال</label>
                <input type="text" name="send_day" class="form-control" placeholder="مثال: Sunday أو 1">
            </div>
            <div class="form-group">
                <label class="form-label">الفرع (اختياري)</label>
                <select name="branch_id" class="form-control">
                    <option value="">جميع الفروع</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">المستلمون (بريد إلكتروني، يفصل بفاصلة)</label>
                <input type="text" name="recipients" class="form-control" required placeholder="example@email.com" dir="ltr">
            </div>
            <button type="submit" class="btn btn-sm" style="margin-top:8px">حفظ الجدولة</button>
        </form>
    </div>
</div>

{{-- Edit Modal --}}
<div id="editScheduleModal" style="display:none;position:fixed;inset:0;z-index:1000;background:rgba(0,0,0,.5);align-items:center;justify-content:center;padding:16px">
    <div class="card" style="max-width:500px;width:100%;max-height:90vh;overflow-y:auto">
        <div class="card-header">
            <span class="card-title">تعديل الجدولة</span>
            <button type="button" onclick="document.getElementById('editScheduleModal').style.display='none'" style="background:none;border:none;font-size:1.2rem;cursor:pointer;color:var(--text2)">&times;</button>
        </div>
        <form id="editForm" method="POST" style="padding:16px">
            @csrf @method('PUT')
            <div class="form-group">
                <label class="form-label">اسم الجدولة</label>
                <input type="text" name="name" id="editName" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">نوع التقرير</label>
                <select name="report_type" id="editReportType" class="form-control" required>
                    <option value="daily">التقرير اليومي</option>
                    <option value="late">تقرير التأخيرات</option>
                    <option value="full">التقرير الشامل</option>
                </select>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <div class="form-group">
                    <label class="form-label">التكرار</label>
                    <select name="frequency" id="editFrequency" class="form-control" required onchange="toggleEditDayField(this)">
                        <option value="daily">يومياً</option>
                        <option value="weekly">أسبوعياً</option>
                        <option value="monthly">شهرياً</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">وقت الإرسال</label>
                    <input type="time" name="send_time" id="editSendTime" class="form-control" required>
                </div>
            </div>
            <div class="form-group" id="editDayField" style="display:none">
                <label class="form-label">يوم الإرسال</label>
                <input type="text" name="send_day" id="editSendDay" class="form-control" placeholder="مثال: Sunday أو 1">
            </div>
            <div class="form-group">
                <label class="form-label">الفرع (اختياري)</label>
                <select name="branch_id" id="editBranchId" class="form-control">
                    <option value="">جميع الفروع</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">المستلمون</label>
                <input type="text" name="recipients" id="editRecipients" class="form-control" required dir="ltr">
            </div>
            <button type="submit" class="btn btn-sm" style="margin-top:8px">تحديث</button>
        </form>
    </div>
</div>

<script>
function toggleDayField(el) {
    document.getElementById('addDayField').style.display = el.value === 'daily' ? 'none' : 'block';
}
function toggleEditDayField(el) {
    document.getElementById('editDayField').style.display = el.value === 'daily' ? 'none' : 'block';
}
function openEditModal(schedule) {
    document.getElementById('editForm').action = '/admin/report-schedules/' + schedule.id;
    document.getElementById('editName').value = schedule.name;
    document.getElementById('editReportType').value = schedule.report_type;
    document.getElementById('editFrequency').value = schedule.frequency;
    document.getElementById('editSendTime').value = schedule.send_time;
    document.getElementById('editSendDay').value = schedule.send_day || '';
    document.getElementById('editRecipients').value = (schedule.recipients || []).join(', ');
    document.getElementById('editBranchId').value = schedule.filters && schedule.filters.branch_id ? schedule.filters.branch_id : '';
    toggleEditDayField(document.getElementById('editFrequency'));
    document.getElementById('editScheduleModal').style.display = 'flex';
}
</script>

@endsection
