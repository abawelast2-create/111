@extends('layouts.admin')

@section('content')

<div class="card">
    <div class="card-header">
        <span class="card-title"><span class="card-title-bar"></span> {{ __('messages.two_factor') }}</span>
    </div>

    <div style="padding:24px;max-width:500px">
        @if($admin->two_factor_enabled)
            <!-- 2FA Enabled -->
            <div style="text-align:center;padding:20px;background:var(--bg2);border-radius:12px;margin-bottom:20px">
                <div style="font-size:3rem;margin-bottom:8px">🔒</div>
                <h3 style="color:var(--success);margin:0 0 8px">{{ __('messages.2fa_enabled') }}</h3>
                <p style="color:var(--text3);font-size:.85rem;margin:0">حسابك محمي بالمصادقة الثنائية</p>
            </div>

            <form method="POST" action="{{ route('admin.2fa.disable') }}" onsubmit="return confirm('هل أنت متأكد من تعطيل المصادقة الثنائية؟')">
                @csrf
                <div class="form-group">
                    <label class="form-label">{{ __('messages.enter_code') }} للتعطيل</label>
                    <input type="text" name="code" class="form-control" inputmode="numeric" maxlength="6" required autocomplete="off" dir="ltr" style="text-align:center;letter-spacing:8px;font-size:1.5rem">
                </div>
                <button type="submit" class="btn" style="width:100%;background:var(--danger)">{{ __('messages.disable_2fa') }}</button>
            </form>
        @else
            <!-- 2FA Disabled -->
            <div style="text-align:center;padding:20px;background:var(--bg2);border-radius:12px;margin-bottom:20px">
                <div style="font-size:3rem;margin-bottom:8px">🔓</div>
                <h3 style="color:var(--text3);margin:0 0 8px">{{ __('messages.2fa_disabled') }}</h3>
                <p style="color:var(--text3);font-size:.85rem;margin:0">فعّل المصادقة الثنائية لحماية إضافية لحسابك</p>
            </div>

            <form method="POST" action="{{ route('admin.2fa.enable') }}">
                @csrf
                <button type="submit" class="btn" style="width:100%">{{ __('messages.enable_2fa') }}</button>
            </form>
        @endif
    </div>
</div>

@endsection
