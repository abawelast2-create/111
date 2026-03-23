<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, maximum-scale=1, user-scalable=no">
    <meta name="theme-color" content="#F1F5F9">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/images/loogo.png') }}">
    <title>{{ $employee->name }} - الحضور</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/images/loogo.png') }}">
    <link rel="stylesheet" href="{{ asset('assets/fonts/tajawal.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/radar.css') }}">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>

<!-- Header -->
<div class="header">
    <div class="hl">
        <div class="emp-av">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
        </div>
        <div>
            <div class="emp-name">{{ $employee->name }}</div>
            <div class="emp-job">{{ $employee->job_title }}</div>
            @if($todayStatus)
                <span class="hdr-badge {{ $todayStatus === 'in' ? 'in' : 'out' }}">
                    {{ $todayStatus === 'in' ? 'حاضر' : 'انصرف' }}
                </span>
            @else
                <span class="hdr-badge none">لم يسجل</span>
            @endif
            @if($employee->branch)
                <div class="branch-tag">
                    <svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5a2.5 2.5 0 010-5 2.5 2.5 0 010 5z"/></svg>
                    {{ $employee->branch->name }}
                </div>
            @endif
        </div>
    </div>
    <div class="hr">
        <div class="hdr-date" id="headerDate"></div>
        <div class="hdr-time" id="headerTime"></div>
    </div>
</div>

<!-- Radar Zone -->
<div class="radar-zone">
    <div class="guidance-box" id="guidanceBox"></div>

    <!-- Radar Card -->
    <div class="radar-card">
        <div class="radar-wrap">
            <div id="radarMap"></div>
            <canvas id="radarCanvas"></canvas>
        </div>
    </div>

    <!-- Distance Info -->
    <div class="dist-row">
        <span class="dist-dot" id="distDot"></span>
        <span class="dist-lbl">المسافة</span>
        <span class="dist-val" id="distVal">--</span>
        <span class="dist-unit" id="distUnit">متر</span>
        <span class="gps-row">
            <span class="gps-dot" id="gpsDot"></span>
            <span id="gpsText">GPS</span>
        </span>
    </div>

    <!-- Info Strip -->
    <div class="info-strip" id="infoStrip"></div>

    <!-- Timer -->
    <div class="timer-row" id="timerRow" style="display:none">
        <span class="timer-lbl">مدة العمل</span>
        <span class="timer-clock" id="timerClock">00:00:00</span>
    </div>

    <!-- Bottom Panel -->
    <div class="bottom-panel">
        <div class="countdown-bar" id="countdownBar"></div>
        <div class="message" id="messageBox"></div>
        <div class="spinner" id="spinnerBox"></div>

        <div class="btn-row" id="btnRow">
            <button class="btn btn-in" id="btnIn" onclick="submitAttendance('in')">
                <span class="btn-icon">📍</span> تسجيل دخول
            </button>
            <button class="btn btn-out btn-hidden" id="btnOut" onclick="submitAttendance('out')">
                <span class="btn-icon">👋</span> تسجيل انصراف
            </button>
            <button class="btn btn-overtime btn-hidden" id="btnOvertime" onclick="submitAttendance('overtime')">
                <span class="btn-icon">⏰</span> دوام إضافي
            </button>
        </div>

        <!-- History -->
        <div class="history-wrap" id="historyWrap">
            @foreach($todayRecords as $rec)
            <div class="hist-item">
                <span class="type-{{ $rec->type }}">{{ $rec->type === 'in' ? 'دخول' : ($rec->type === 'out' ? 'انصراف' : 'إضافي') }}</span>
                <span class="hist-time">{{ \Carbon\Carbon::parse($rec->timestamp)->format('h:i') }}</span>
            </div>
            @endforeach
        </div>
    </div>
</div>

<!-- Secret Report Button -->
<button class="sr-float-btn" id="srFloatBtn" onclick="document.getElementById('srModal').classList.add('show')">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
</button>

<button class="refresh-float-btn" onclick="location.reload()">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M17.65 6.35A7.958 7.958 0 0012 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08A5.99 5.99 0 0112 18c-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z"/></svg>
</button>

<button class="switch-user-float-btn" onclick="window.location.href='{{ route('employee.index') }}'">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
</button>

<!-- Secret Report Modal -->
<div class="sr-modal-overlay" id="srModal">
    <div class="sr-modal">
        <div class="sr-modal-header">
            <span class="sr-modal-title">تقرير سري</span>
            <button class="sr-close" onclick="document.getElementById('srModal').classList.remove('show')">&times;</button>
        </div>
        <div class="sr-privacy-notice">🔒 هذا التقرير سري ولا يظهر اسمك للإدارة</div>
        <form id="secretReportForm" enctype="multipart/form-data">
            <div class="sr-field">
                <label>نوع البلاغ</label>
                <select name="type" id="srType">
                    <option value="complaint">شكوى</option>
                    <option value="suggestion">اقتراح</option>
                    <option value="harassment">تحرش</option>
                    <option value="safety">سلامة</option>
                    <option value="other">أخرى</option>
                </select>
            </div>
            <div class="sr-field">
                <label>تفاصيل البلاغ</label>
                <textarea name="report_text" id="srText" rows="4" placeholder="اكتب تفاصيل البلاغ هنا..." required></textarea>
            </div>
            <div class="sr-field">
                <label>صور (اختياري)</label>
                <input type="file" name="images[]" multiple accept="image/*" style="font-size:.82rem">
            </div>
            <button type="submit" class="sr-submit">إرسال البلاغ</button>
        </form>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
// Config from server
const CONFIG = {
    token: @json($employee->unique_token),
    employeeId: {{ $employee->id }},
    branchLat: {{ $employee->branch->latitude ?? 0 }},
    branchLng: {{ $employee->branch->longitude ?? 0 }},
    geofenceRadius: {{ $employee->branch->geofence_radius ?? 100 }},
    apiUrl: '{{ url('/api') }}',
    csrfToken: '{{ csrf_token() }}'
};

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('{{ asset('sw.js') }}').catch(() => {});
    });
}
</script>
<script src="{{ asset('assets/js/radar.js') }}"></script>
</body>
</html>
