<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل دخول المدير - {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/images/loogo.png') }}">
    <link rel="stylesheet" href="{{ asset('assets/fonts/tajawal.css') }}">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family:'Tajawal',sans-serif;
            background:#F8FAFC;
            min-height:100vh; display:flex; align-items:center; justify-content:center; padding:20px;
            position:relative; overflow:hidden;
        }
        body::before {
            content:''; position:fixed; inset:0;
            background:
                radial-gradient(ellipse 700px 500px at 20% 10%, rgba(249,115,22,.08) 0%, transparent 60%),
                radial-gradient(ellipse 500px 400px at 80% 90%, rgba(234,88,12,.06) 0%, transparent 60%);
            pointer-events:none;
        }
        .login-wrap {
            display:flex; width:100%; max-width:880px;
            border-radius:20px; overflow:hidden;
            box-shadow:0 20px 50px rgba(0,0,0,.08), 0 4px 16px rgba(0,0,0,.04);
            position:relative; z-index:1; border:1px solid #E2E8F0;
        }
        .login-banner {
            flex:1; display:none;
            background:linear-gradient(155deg,#F97316 0%,#EA580C 50%,#C2410C 100%);
            padding:48px 36px; align-items:center; justify-content:center;
            flex-direction:column; text-align:center; position:relative; overflow:hidden;
        }
        .login-banner::before {
            content:''; position:absolute; inset:0;
            background:url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }
        .banner-title { color:#fff; font-size:1.5rem; font-weight:800; line-height:1.4; margin-bottom:12px; }
        .banner-sub   { color:rgba(255,255,255,.8); font-size:.88rem; line-height:1.7; }
        .banner-dots  { display:flex; gap:8px; justify-content:center; margin-top:28px; }
        .banner-dots span { width:8px; height:8px; border-radius:50%; background:rgba(255,255,255,.3); }
        .banner-dots span:first-child { background:rgba(255,255,255,.8); width:24px; border-radius:4px; }
        @media (min-width:700px) { .login-banner { display:flex; } }

        .login-card {
            background:#fff; padding:48px 40px;
            width:100%; max-width:420px; min-width:300px;
        }
        .brand { text-align:center; margin-bottom:36px; }
        .brand-logo-wrap {
            width:72px; height:72px; margin:0 auto 16px;
            display:flex; align-items:center; justify-content:center;
        }
        .brand-name { color:#1E293B; font-size:1.2rem; font-weight:800; }
        .brand-sub  { color:#94A3B8; font-size:.82rem; margin-top:4px; }

        .form-group { margin-bottom:20px; }
        label { display:block; color:#475569; font-size:.84rem; font-weight:600; margin-bottom:7px; }
        .input-wrap { position:relative; }
        .input-icon {
            position:absolute; right:14px; top:50%; transform:translateY(-50%);
            color:#CBD5E1; pointer-events:none; display:flex;
        }
        input[type=text],input[type=password] {
            width:100%; padding:11px 44px 11px 14px;
            background:#F8FAFC; border:1.5px solid #E2E8F0;
            border-radius:10px; color:#1E293B; font-size:.92rem;
            font-family:inherit; direction:ltr; text-align:right; outline:none;
            transition:border-color .2s, box-shadow .2s;
        }
        input:focus {
            border-color:#F97316; background:#fff;
            box-shadow:0 0 0 3px rgba(249,115,22,.1);
        }
        input::placeholder { color:#94A3B8; }

        .btn-login {
            width:100%; padding:13px;
            background:linear-gradient(135deg,#F97316,#EA580C);
            border:none; border-radius:12px;
            color:#fff; font-size:1rem; font-weight:700;
            font-family:inherit; cursor:pointer;
            transition:all .2s; margin-top:8px;
            box-shadow:0 4px 14px rgba(249,115,22,.3);
            display:flex; align-items:center; justify-content:center; gap:8px;
        }
        .btn-login:hover { transform:translateY(-1px); box-shadow:0 6px 20px rgba(249,115,22,.4); }
        .btn-login:active { transform:translateY(0); }

        .alert-error {
            background:#FEE2E2; color:#DC2626;
            border:1px solid #FECACA; border-radius:10px;
            padding:12px 16px; font-size:.875rem; margin-bottom:20px;
            display:flex; gap:8px; align-items:center;
        }
        .alert-locked {
            background:#FEF3C7; color:#D97706;
            border:1px solid #FDE68A; border-radius:10px;
            padding:12px 16px; font-size:.875rem; margin-bottom:20px;
            display:flex; gap:8px; align-items:center;
        }
        .footer-note {
            text-align:center; color:#CBD5E1; font-size:.74rem; margin-top:28px;
            display:flex; align-items:center; justify-content:center; gap:6px;
        }
    </style>
</head>
<body>
<div class="login-wrap">
    <!-- بانر -->
    <div class="login-banner">
        <img src="{{ asset('assets/images/loogo.png') }}" alt="Logo" style="width:80px;height:80px;border-radius:16px;margin-bottom:24px;opacity:.95">
        <div class="banner-title">نظام الحضور<br>والانصراف الذكي</div>
        <div class="banner-sub">تتبع وإدارة حضور موظفيك<br>بكل سهولة ودقة عالية</div>
        <div class="banner-dots"><span></span><span></span><span></span></div>
    </div>

    <!-- نموذج الدخول -->
    <div class="login-card">
        <div class="brand">
            <div class="brand-logo-wrap">
                <img src="{{ asset('assets/images/loogo.png') }}" alt="Logo" style="width:64px;height:64px;border-radius:14px">
            </div>
            <div class="brand-name">{{ config('app.name') }}</div>
            <div class="brand-sub">لوحة تحكم المدير</div>
        </div>

        @if($errors->any())
        <div class="{{ ($isLocked ?? false) ? 'alert-locked' : 'alert-error' }}">
            <x-icon name="{{ ($isLocked ?? false) ? 'lock' : 'absent' }}" :size="18"/>
            {{ $errors->first() }}
        </div>
        @endif

        <form method="POST" action="{{ route('admin.login.submit') }}" {!! ($isLocked ?? false) ? 'style="opacity:.5;pointer-events:none"' : '' !!}>
            @csrf
            <div class="form-group">
                <label for="username">اسم المستخدم</label>
                <div class="input-wrap">
                    <span class="input-icon"><x-icon name="user" :size="18"/></span>
                    <input type="text" id="username" name="username"
                           value="{{ old('username') }}"
                           placeholder="admin" required autofocus>
                </div>
            </div>
            <div class="form-group">
                <label for="password">كلمة المرور</label>
                <div class="input-wrap">
                    <span class="input-icon"><x-icon name="key" :size="18"/></span>
                    <input type="password" id="password" name="password" placeholder="••••••••" required>
                </div>
            </div>
            <div class="form-group" style="margin-bottom:12px">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:500;color:#64748B">
                    <input type="checkbox" name="remember_me" value="1" style="width:16px;height:16px;accent-color:#F97316;cursor:pointer">
                    تذكرني (30 يوم)
                </label>
            </div>
            <button type="submit" class="btn-login">
                <x-icon name="lock" :size="18"/>
                تسجيل الدخول
            </button>
        </form>

        <div class="footer-note">
            <x-icon name="lock" :size="12"/>
            <span>اتصال آمن ومشفر</span>
        </div>
    </div>
</div>
</body>
</html>
