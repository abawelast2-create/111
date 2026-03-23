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
    <title>تسجيل الحضور - {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/images/loogo.png') }}">
    <link rel="stylesheet" href="{{ asset('assets/fonts/tajawal.css') }}">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family:'Tajawal',sans-serif;
            background:#F8FAFC;
            min-height:100vh; display:flex; align-items:center; justify-content:center; padding:20px;
        }
        .pin-card {
            background:#fff; padding:40px 32px; border-radius:20px;
            box-shadow:0 10px 40px rgba(0,0,0,.08);
            max-width:380px; width:100%; text-align:center;
            border:1px solid #E2E8F0;
        }
        .pin-logo {
            width:64px; height:64px; margin:0 auto 20px;
            border-radius:14px; overflow:hidden;
        }
        .pin-logo img { width:100%; height:100%; object-fit:cover; }
        .pin-title { font-size:1.1rem; font-weight:800; color:#1E293B; margin-bottom:6px; }
        .pin-sub { font-size:.82rem; color:#94A3B8; margin-bottom:28px; }
        .pin-inputs {
            display:flex; gap:10px; justify-content:center; margin-bottom:24px; direction:ltr;
        }
        .pin-input {
            width:52px; height:60px; border:2px solid #E2E8F0; border-radius:12px;
            text-align:center; font-size:1.5rem; font-weight:800;
            font-family:'Tajawal',sans-serif; color:#1E293B; outline:none;
            transition:border-color .2s, box-shadow .2s;
            -moz-appearance:textfield;
        }
        .pin-input::-webkit-outer-spin-button,
        .pin-input::-webkit-inner-spin-button { -webkit-appearance:none; margin:0; }
        .pin-input:focus { border-color:#F97316; box-shadow:0 0 0 3px rgba(249,115,22,.1); }
        .btn-pin {
            width:100%; padding:14px; border:none; border-radius:12px;
            background:linear-gradient(135deg,#F97316,#EA580C);
            color:#fff; font-size:1rem; font-weight:700;
            font-family:inherit; cursor:pointer;
            box-shadow:0 4px 14px rgba(249,115,22,.3);
            transition:all .2s;
        }
        .btn-pin:hover { transform:translateY(-1px); }
        .btn-pin:active { transform:translateY(0); }
        .alert-error {
            background:#FEE2E2; color:#DC2626; padding:10px 14px;
            border-radius:10px; font-size:.85rem; margin-bottom:20px;
            border:1px solid #FECACA;
        }
    </style>
</head>
<body>
    <div class="pin-card">
        <div class="pin-logo">
            <img src="{{ asset('assets/images/loogo.png') }}" alt="Logo">
        </div>
        <div class="pin-title">تسجيل الحضور</div>
        <div class="pin-sub">أدخل رقم PIN المكون من 4 أرقام</div>

        @if($errors->any())
        <div class="alert-error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('employee.auth') }}" id="pinForm">
            @csrf
            <input type="hidden" name="pin" id="pinHidden">
            <div class="pin-inputs">
                <input type="number" class="pin-input" maxlength="1" data-idx="0" inputmode="numeric" autofocus>
                <input type="number" class="pin-input" maxlength="1" data-idx="1" inputmode="numeric">
                <input type="number" class="pin-input" maxlength="1" data-idx="2" inputmode="numeric">
                <input type="number" class="pin-input" maxlength="1" data-idx="3" inputmode="numeric">
            </div>
            <button type="submit" class="btn-pin">دخول</button>
        </form>
    </div>

    <script>
    const inputs = document.querySelectorAll('.pin-input');
    const hidden = document.getElementById('pinHidden');

    inputs.forEach((inp, i) => {
        inp.addEventListener('input', () => {
            inp.value = inp.value.slice(-1);
            if (inp.value && i < 3) inputs[i+1].focus();
            updateHidden();
        });
        inp.addEventListener('keydown', e => {
            if (e.key === 'Backspace' && !inp.value && i > 0) {
                inputs[i-1].focus();
            }
        });
        inp.addEventListener('paste', e => {
            e.preventDefault();
            const paste = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g,'').slice(0,4);
            [...paste].forEach((c, j) => { if (inputs[j]) inputs[j].value = c; });
            if (paste.length > 0) inputs[Math.min(paste.length, 3)].focus();
            updateHidden();
        });
    });

    function updateHidden() {
        hidden.value = [...inputs].map(i => i.value).join('');
    }

    document.getElementById('pinForm').addEventListener('submit', e => {
        updateHidden();
        if (hidden.value.length !== 4) { e.preventDefault(); inputs[0].focus(); }
    });

    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('{{ asset('sw.js') }}').catch(() => {});
        });
    }
    </script>
</body>
</html>
