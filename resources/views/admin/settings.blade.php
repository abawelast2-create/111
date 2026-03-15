@extends('layouts.admin')

@section('content')

<div class="card">
    <!-- Tabs -->
    <div class="settings-tabs" style="display:flex;gap:8px;margin-bottom:24px;flex-wrap:wrap">
        <button class="tab-btn btn btn-sm active" onclick="showTab('geo')" id="tab-geo">السياج الجغرافي</button>
        <button class="tab-btn btn btn-sm btn-secondary" onclick="showTab('time')" id="tab-time">أوقات العمل</button>
        <button class="tab-btn btn btn-sm btn-secondary" onclick="showTab('overtime')" id="tab-overtime">الدوام الإضافي</button>
        <button class="tab-btn btn btn-sm btn-secondary" onclick="showTab('general')" id="tab-general">عام</button>
        <button class="tab-btn btn btn-sm btn-secondary" onclick="showTab('password')" id="tab-password">كلمة المرور</button>
    </div>

    <form method="POST" action="{{ route('admin.settings.update') }}" id="settingsForm">
        @csrf

        <!-- Geo Tab -->
        <div class="tab-content" id="panel-geo">
            <h3 style="font-size:.95rem;margin-bottom:16px"><x-icon name="branch" :size="18"/> إعدادات السياج الجغرافي</h3>
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label">خط العرض الافتراضي</label>
                    <input type="number" step="any" name="DEFAULT_LATITUDE" class="form-control" value="{{ $settings['DEFAULT_LATITUDE'] ?? '' }}">
                </div>
                <div class="form-group">
                    <label class="form-label">خط الطول الافتراضي</label>
                    <input type="number" step="any" name="DEFAULT_LONGITUDE" class="form-control" value="{{ $settings['DEFAULT_LONGITUDE'] ?? '' }}">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">نطاق السياج الجغرافي الافتراضي (متر)</label>
                <input type="number" name="GEOFENCE_RADIUS" class="form-control" value="{{ $settings['GEOFENCE_RADIUS'] ?? '100' }}">
            </div>
        </div>

        <!-- Time Tab -->
        <div class="tab-content" id="panel-time" style="display:none">
            <h3 style="font-size:.95rem;margin-bottom:16px"><x-icon name="clock" :size="18"/> أوقات العمل الافتراضية</h3>
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label">بداية الدوام</label>
                    <input type="time" name="WORK_START" class="form-control" value="{{ $settings['WORK_START'] ?? '08:00' }}">
                </div>
                <div class="form-group">
                    <label class="form-label">نهاية الدوام</label>
                    <input type="time" name="WORK_END" class="form-control" value="{{ $settings['WORK_END'] ?? '16:00' }}">
                </div>
            </div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label">بداية نافذة الدخول</label>
                    <input type="time" name="CHECK_IN_START" class="form-control" value="{{ $settings['CHECK_IN_START'] ?? '07:00' }}">
                </div>
                <div class="form-group">
                    <label class="form-label">نهاية نافذة الدخول</label>
                    <input type="time" name="CHECK_IN_END" class="form-control" value="{{ $settings['CHECK_IN_END'] ?? '10:00' }}">
                </div>
            </div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label">بداية نافذة الانصراف</label>
                    <input type="time" name="CHECK_OUT_START" class="form-control" value="{{ $settings['CHECK_OUT_START'] ?? '15:00' }}">
                </div>
                <div class="form-group">
                    <label class="form-label">نهاية نافذة الانصراف</label>
                    <input type="time" name="CHECK_OUT_END" class="form-control" value="{{ $settings['CHECK_OUT_END'] ?? '18:00' }}">
                </div>
            </div>
        </div>

        <!-- Overtime Tab -->
        <div class="tab-content" id="panel-overtime" style="display:none">
            <h3 style="font-size:.95rem;margin-bottom:16px"><x-icon name="overtime" :size="18"/> إعدادات الدوام الإضافي</h3>
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label">بداية الإضافي</label>
                    <input type="time" name="overtime_start" class="form-control" value="{{ $settings['overtime_start'] ?? '' }}">
                </div>
                <div class="form-group">
                    <label class="form-label">نهاية الإضافي</label>
                    <input type="time" name="overtime_end" class="form-control" value="{{ $settings['overtime_end'] ?? '' }}">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">الحد الأدنى لمدة الإضافي (دقائق)</label>
                <input type="number" name="overtime_min_duration" class="form-control" value="{{ $settings['overtime_min_duration'] ?? '15' }}">
            </div>
        </div>

        <!-- General Tab -->
        <div class="tab-content" id="panel-general" style="display:none">
            <h3 style="font-size:.95rem;margin-bottom:16px"><x-icon name="settings" :size="18"/> إعدادات عامة</h3>
            <div class="form-group">
                <label class="form-label">اسم الموقع</label>
                <input type="text" name="SITE_NAME" class="form-control" value="{{ $settings['SITE_NAME'] ?? config('app.name') }}">
            </div>
            <div class="form-group">
                <label class="form-label">مهلة الجلسة (دقائق)</label>
                <input type="number" name="session_timeout" class="form-control" value="{{ $settings['session_timeout'] ?? '30' }}">
            </div>
            <div class="form-group">
                <label class="form-label">الحد الأدنى بين تسجيلين (دقائق)</label>
                <input type="number" name="DUPLICATE_THRESHOLD" class="form-control" value="{{ $settings['DUPLICATE_THRESHOLD'] ?? '3' }}">
            </div>
        </div>

        <div id="panel-password-form" style="display:none"></div>
        <button type="submit" class="btn btn-primary" id="saveBtn" style="margin-top:20px">
            <x-icon name="settings" :size="16"/> حفظ الإعدادات
        </button>
    </form>

    <!-- Password Tab (separate form) -->
    <div class="tab-content" id="panel-password" style="display:none;margin-top:20px">
        <h3 style="font-size:.95rem;margin-bottom:16px"><x-icon name="key" :size="18"/> تغيير كلمة المرور</h3>
        <form method="POST" action="{{ route('admin.settings.change-password') }}">
            @csrf
            <div class="form-group">
                <label class="form-label">كلمة المرور الحالية</label>
                <input type="password" name="current_password" class="form-control" required>
            </div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label">كلمة المرور الجديدة</label>
                    <input type="password" name="new_password" class="form-control" required minlength="6">
                </div>
                <div class="form-group">
                    <label class="form-label">تأكيد كلمة المرور</label>
                    <input type="password" name="new_password_confirmation" class="form-control" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary"><x-icon name="key" :size="16"/> تغيير كلمة المرور</button>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
function showTab(tab) {
    document.querySelectorAll('.tab-content').forEach(el => el.style.display = 'none');
    document.querySelectorAll('.tab-btn').forEach(el => { el.classList.remove('active','btn-primary'); el.classList.add('btn-secondary'); });
    document.getElementById('panel-' + tab).style.display = 'block';
    const btn = document.getElementById('tab-' + tab);
    btn.classList.add('active','btn-primary');
    btn.classList.remove('btn-secondary');
    document.getElementById('saveBtn').style.display = tab === 'password' ? 'none' : '';
}
</script>
@endpush
