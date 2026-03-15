<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('messages.two_factor') }} - {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/images/loogo.png') }}">
    <link rel="stylesheet" href="{{ asset('assets/fonts/tajawal.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/admin.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/dark-mode.css') }}">
    <script src="{{ asset('assets/js/theme.js') }}"></script>
</head>
<body style="display:flex;align-items:center;justify-content:center;min-height:100vh;background:var(--bg)">

<div class="card" style="max-width:400px;width:100%;margin:16px">
    <div style="text-align:center;padding:32px 24px 16px">
        <div style="font-size:3rem;margin-bottom:12px">🔐</div>
        <h2 style="margin:0 0 8px;font-size:1.1rem">{{ __('messages.two_factor') }}</h2>
        <p style="color:var(--text3);font-size:.85rem;margin:0">أدخل رمز التحقق من تطبيق المصادقة</p>
    </div>

    <form method="POST" action="{{ route('admin.2fa.verify.submit') }}" style="padding:0 24px 24px">
        @csrf
        <div class="form-group">
            <input type="text" name="code" class="form-control" inputmode="numeric" maxlength="6" required autocomplete="off"
                   dir="ltr" style="text-align:center;letter-spacing:10px;font-size:1.8rem;padding:16px" autofocus
                   placeholder="000000">
        </div>

        @if($errors->has('code'))
            <p style="color:var(--danger);font-size:.85rem;text-align:center;margin-bottom:12px">{{ $errors->first('code') }}</p>
        @endif

        <button type="submit" class="btn" style="width:100%;padding:14px">{{ __('messages.verify') }}</button>

        <div style="text-align:center;margin-top:16px">
            <a href="{{ route('admin.login') }}" style="color:var(--text3);font-size:.85rem;text-decoration:none">إلغاء تسجيل الدخول</a>
        </div>
    </form>
</div>

</body>
</html>
