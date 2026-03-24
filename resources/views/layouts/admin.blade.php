<!DOCTYPE html>
<html lang="ar" dir="rtl" class="{{ request()->cookie('attendance_theme') === 'dark' ? 'dark' : '' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, maximum-scale=1, user-scalable=no">
    <meta name="theme-color" content="#F97316">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <title>{{ $pageTitle ?? config('app.name') }} - لوحة التحكم</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/images/loogo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/images/loogo.png') }}">
    <link rel="stylesheet" href="{{ asset('assets/fonts/tajawal.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/admin.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/dark-mode.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>window.SITE_URL = '{{ url('/') }}';</script>
    <script src="{{ asset('assets/js/theme.js') }}"></script>
    @stack('styles')
</head>
<body>

    <!-- Sidebar Overlay (mobile) -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <div class="brand-logo">
                <div class="brand-mark"><img src="{{ asset('assets/images/loogo.png') }}" alt="Logo" style="width:42px;height:42px;border-radius:10px;object-fit:cover"></div>
                <div>
                    <div class="brand-name">{{ config('app.name') }}</div>
                    <div class="brand-sub">مرحباً، {{ session('admin_name', 'مدير') }}</div>
                </div>
            </div>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-label">القائمة الرئيسية</div>
            @adminCan('dashboard.view')
            <a href="{{ route('admin.dashboard') }}" class="nav-item {{ ($activePage ?? '') === 'dashboard' ? 'active' : '' }}">
                <span class="nav-icon"><x-icon name="dashboard" :size="18"/></span> لوحة التحكم
            </a>
            @endadminCan
            @adminCan('branches.view')
            <a href="{{ route('admin.branches.index') }}" class="nav-item {{ ($activePage ?? '') === 'branches' ? 'active' : '' }}">
                <span class="nav-icon"><x-icon name="branch" :size="18"/></span> إدارة الفروع
            </a>
            @endadminCan
            @adminCan('employees.view')
            <a href="{{ route('admin.employees.index') }}" class="nav-item {{ ($activePage ?? '') === 'employees' ? 'active' : '' }}">
                <span class="nav-icon"><x-icon name="employees" :size="18"/></span> إدارة الموظفين
            </a>
            @endadminCan
            @adminCan('employees.documents_expiry')
            <a href="{{ route('admin.documents-expiry') }}" class="nav-item {{ ($activePage ?? '') === 'documents-expiry' ? 'active' : '' }}">
                <span class="nav-icon"><x-icon name="attendance" :size="18"/></span> انتهاء الوثائق
            </a>
            @endadminCan
            @adminCan('attendance.view')
            <a href="{{ route('admin.attendance.index') }}" class="nav-item {{ ($activePage ?? '') === 'attendance' ? 'active' : '' }}">
                <span class="nav-icon"><x-icon name="attendance" :size="18"/></span> تقارير الحضور
            </a>
            @endadminCan
            @adminCan('attendance.late_report')
            <a href="{{ route('admin.late-report') }}" class="nav-item {{ ($activePage ?? '') === 'late-report' ? 'active' : '' }}">
                <span class="nav-icon"><x-icon name="attendance" :size="18"/></span> تقرير التأخير
            </a>
            @endadminCan
            @adminCan('attendance.charts')
            <a href="{{ route('admin.report-charts') }}" class="nav-item {{ ($activePage ?? '') === 'report-charts' ? 'active' : '' }}">
                <span class="nav-icon"><x-icon name="chart" :size="18"/></span> التقارير البيانية
            </a>
            @endadminCan
            @adminCan('leaves.view')
            <a href="{{ route('admin.leaves.index') }}" class="nav-item {{ ($activePage ?? '') === 'leaves' ? 'active' : '' }}">
                <span class="nav-icon"><x-icon name="absent" :size="18"/></span> إدارة الإجازات
            </a>
            @endadminCan
            @adminCan('tampering.view')
            <a href="{{ route('admin.tampering') }}" class="nav-item {{ ($activePage ?? '') === 'tampering' ? 'active' : '' }}">
                <span class="nav-icon"><x-icon name="lock" :size="18"/></span> حالات التلاعب
            </a>
            @endadminCan
            @adminCan('secret_reports.view')
            <a href="{{ route('admin.secret-reports') }}" class="nav-item {{ ($activePage ?? '') === 'secret-reports' ? 'active' : '' }}">
                <span class="nav-icon"><x-icon name="absent" :size="18"/></span> التقارير السرية
            </a>
            @endadminCan
            @adminCan('analytics.view')
            <a href="{{ route('admin.analytics.index') }}" class="nav-item {{ ($activePage ?? '') === 'analytics' ? 'active' : '' }}">
                <span class="nav-icon"><x-icon name="chart" :size="18"/></span> التحليلات المتقدمة
            </a>
            @endadminCan
            <div class="nav-label">النظام</div>
            @adminCan('notifications.view')
            <a href="{{ route('admin.notifications.index') }}" class="nav-item {{ ($activePage ?? '') === 'notifications' ? 'active' : '' }}">
                <span class="nav-icon"><x-icon name="attendance" :size="18"/></span> الإشعارات
                @php $unreadNotif = \App\Models\Notification::where('notifiable_type', \App\Models\Admin::class)->whereNull('read_at')->count(); @endphp
                @if($unreadNotif > 0)<span class="badge badge-orange" style="font-size:.65rem;padding:2px 6px;margin-right:auto">{{ $unreadNotif }}</span>@endif
            </a>
            @endadminCan
            @adminCan('backups.view')
            <a href="{{ route('admin.backups.index') }}" class="nav-item {{ ($activePage ?? '') === 'backups' ? 'active' : '' }}">
                <span class="nav-icon"><x-icon name="settings" :size="18"/></span> النسخ الاحتياطي
            </a>
            @endadminCan
            @adminCan('webhooks.view')
            <a href="{{ route('admin.webhooks.index') }}" class="nav-item {{ ($activePage ?? '') === 'webhooks' ? 'active' : '' }}">
                <span class="nav-icon"><x-icon name="lock" :size="18"/></span> Webhooks
            </a>
            @endadminCan
            @adminCan('twofactor.manage')
            <a href="{{ route('admin.2fa.index') }}" class="nav-item {{ ($activePage ?? '') === '2fa' ? 'active' : '' }}">
                <span class="nav-icon"><x-icon name="lock" :size="18"/></span> المصادقة الثنائية
            </a>
            @endadminCan
            @adminCan('settings.view')
            <a href="{{ route('admin.settings.index') }}" class="nav-item {{ ($activePage ?? '') === 'settings' ? 'active' : '' }}">
                <span class="nav-icon"><x-icon name="settings" :size="18"/></span> إعدادات النظام
            </a>
            @endadminCan
            @adminCan('permissions.manage')
            <a href="{{ route('admin.permissions.index') }}" class="nav-item {{ ($activePage ?? '') === 'permissions' ? 'active' : '' }}">
                <span class="nav-icon"><x-icon name="settings" :size="18"/></span> الصلاحيات
            </a>
            @endadminCan
        </nav>
        <div class="sidebar-footer">
            <form method="POST" action="{{ route('admin.logout') }}" style="margin:0">
                @csrf
                <button type="submit" class="logout-btn" style="width:100%;border:none;cursor:pointer;background:inherit;color:inherit;font:inherit;text-align:start;display:flex;align-items:center;padding:inherit">
                    <span class="nav-icon"><x-icon name="logout" :size="18"/></span> تسجيل الخروج
                </button>
            </form>
        </div>
    </aside>

    <div class="main-content">
        <div class="topbar">
            <div class="topbar-left">
                <button class="hamburger" onclick="toggleSidebar()" aria-label="القائمة">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/>
                    </svg>
                </button>
                <div class="topbar-page-icon">
                    @php
                    $pageIcons = ['dashboard'=>'dashboard','employees'=>'employees','attendance'=>'attendance','settings'=>'settings','branches'=>'branch'];
                    @endphp
                    <x-icon :name="$pageIcons[$activePage ?? 'dashboard'] ?? 'dashboard'" :size="20"/>
                </div>
                <h1>{{ $pageTitle ?? '' }}</h1>
                <span class="topbar-badge">لوحة التحكم</span>
            </div>
            <div class="topbar-right">
                <span class="topbar-clock" id="topbarClock"></span>
                <button class="theme-toggle" onclick="ThemeManager.toggle()" title="تبديل المظهر">
                    <span class="icon-moon">🌙</span>
                    <span class="icon-sun">☀️</span>
                </button>
                <form method="POST" action="{{ route('admin.logout') }}" style="margin:0;display:inline">
                    @csrf
                    <button type="submit" class="topbar-logout-btn" title="تسجيل الخروج" style="border:none;cursor:pointer;background:inherit;color:inherit;font:inherit">
                        <x-icon name="logout" :size="18"/>
                        <span class="topbar-logout-text">خروج</span>
                    </button>
                </form>
            </div>
        </div>

        <!-- Bottom Navigation (mobile) -->
        <nav class="bottom-nav" id="bottomNav">
            @adminCan('dashboard.view')
            <a href="{{ route('admin.dashboard') }}" class="bnav-item {{ ($activePage ?? '') === 'dashboard' ? 'active' : '' }}">
                <x-icon name="dashboard" :size="22"/>
                <span>الرئيسية</span>
            </a>
            @endadminCan
            @adminCan('branches.view')
            <a href="{{ route('admin.branches.index') }}" class="bnav-item {{ ($activePage ?? '') === 'branches' ? 'active' : '' }}">
                <x-icon name="branch" :size="22"/>
                <span>الفروع</span>
            </a>
            @endadminCan
            @adminCan('employees.view')
            <a href="{{ route('admin.employees.index') }}" class="bnav-item {{ ($activePage ?? '') === 'employees' ? 'active' : '' }}">
                <x-icon name="employees" :size="22"/>
                <span>الموظفين</span>
            </a>
            @endadminCan
            @adminCan('attendance.view')
            <a href="{{ route('admin.attendance.index') }}" class="bnav-item {{ ($activePage ?? '') === 'attendance' ? 'active' : '' }}">
                <x-icon name="attendance" :size="22"/>
                <span>الحضور</span>
            </a>
            @endadminCan
            @adminCan('settings.view')
            <a href="{{ route('admin.settings.index') }}" class="bnav-item {{ ($activePage ?? '') === 'settings' ? 'active' : '' }}">
                <x-icon name="settings" :size="22"/>
                <span>الإعدادات</span>
            </a>
            @endadminCan
        </nav>

        <!-- Dropdown Overlay (mobile bottom-sheet) -->
        <div class="dropdown-overlay" id="dropdownOverlay"></div>

        <div class="content fade-in">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-error">{{ session('error') }}</div>
            @endif

            @yield('content')
        </div>
    </div>

<script>
// Session Timeout Manager
if (typeof SessionManager !== 'undefined') {
    SessionManager.init({{ \App\Models\Setting::getValue('session_timeout', '30') }});
}

// Clock
function tick(){
    const el = document.getElementById('topbarClock');
    if(el) el.textContent = new Date().toLocaleString('ar-SA');
}
tick(); setInterval(tick, 1000);

// Sidebar Toggle
function toggleSidebar(){
    document.getElementById('sidebar')?.classList.toggle('open');
    document.getElementById('sidebarOverlay')?.classList.toggle('show');
}
document.getElementById('sidebarOverlay')?.addEventListener('click', toggleSidebar);
</script>
@stack('scripts')
</body>
</html>
