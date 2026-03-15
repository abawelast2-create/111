@extends('layouts.admin')

@section('content')

<div class="card" style="max-width:500px;margin:0 auto">
    <div class="card-header">
        <span class="card-title"><span class="card-title-bar"></span> إعداد {{ __('messages.two_factor') }}</span>
    </div>

    <div style="padding:24px">
        <!-- Step 1: QR Code -->
        <div style="text-align:center;margin-bottom:24px">
            <p style="color:var(--text2);font-size:.9rem;margin-bottom:16px">{{ __('messages.scan_qr') }}</p>
            <div style="background:#fff;display:inline-block;padding:16px;border-radius:12px">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={{ urlencode($qrUri) }}" alt="QR Code" style="width:200px;height:200px">
            </div>
        </div>

        <!-- Step 2: Manual entry -->
        <div style="background:var(--bg2);border-radius:8px;padding:12px;margin-bottom:20px;text-align:center">
            <small style="color:var(--text3)">أو أدخل الرمز يدوياً</small>
            <div style="font-family:monospace;font-size:1.1rem;letter-spacing:3px;margin-top:4px;direction:ltr" id="secretKey">{{ $secret }}</div>
        </div>

        <!-- Step 3: Verify -->
        <form method="POST" action="{{ route('admin.2fa.confirm') }}">
            @csrf
            <div class="form-group">
                <label class="form-label">أدخل الرمز من التطبيق للتأكيد</label>
                <input type="text" name="code" class="form-control" inputmode="numeric" maxlength="6" required autocomplete="off" dir="ltr" style="text-align:center;letter-spacing:8px;font-size:1.5rem" autofocus>
            </div>
            @if($errors->has('code'))
                <p style="color:var(--danger);font-size:.85rem;margin-bottom:12px">{{ $errors->first('code') }}</p>
            @endif
            <button type="submit" class="btn" style="width:100%">تأكيد وتفعيل</button>
        </form>

        <!-- Recovery Codes -->
        @if(isset($recoveryCodes) && count($recoveryCodes))
            <div style="margin-top:24px;padding:16px;background:var(--bg2);border-radius:8px">
                <h4 style="font-size:.9rem;margin:0 0 12px">{{ __('messages.recovery_codes') }}</h4>
                <p style="color:var(--text3);font-size:.8rem;margin-bottom:12px">احفظ هذه الرموز في مكان آمن. يمكنك استخدام كل رمز مرة واحدة فقط.</p>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px">
                    @foreach($recoveryCodes as $code)
                        <code style="background:var(--bg);padding:6px 10px;border-radius:4px;font-size:.85rem;text-align:center;direction:ltr">{{ $code }}</code>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>

@endsection
