<?php
// =============================================================
// employee/index.php — بوابة تسجيل الحضور الموحدة (PIN)
// =============================================================

require_once __DIR__ . '/../includes/config.php';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=no,viewport-fit=cover">
  <title><?= SITE_NAME ?></title>
  <link rel="icon" type="image/png" href="<?= SITE_URL ?>/assets/images/loogo.png">
  <link rel="apple-touch-icon" href="<?= SITE_URL ?>/assets/images/loogo.png">
  <link rel="manifest" href="<?= SITE_URL ?>/manifest.json">
  <meta name="theme-color" content="#F97316">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
  <style>
    *{margin:0;padding:0;box-sizing:border-box}
    html,body{height:100%;font-family:'Tajawal',sans-serif;background:#F1F5F9;color:#1E293B}
    .gateway{display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:100vh;min-height:100dvh;padding:20px}
    .gateway-card{background:#fff;border-radius:20px;padding:36px 28px;width:100%;max-width:380px;box-shadow:0 8px 32px rgba(0,0,0,.08);text-align:center}
    .gateway-logo{width:72px;height:72px;border-radius:18px;margin:0 auto 16px;overflow:hidden;background:#FFF7ED;display:flex;align-items:center;justify-content:center}
    .gateway-logo img{width:56px;height:56px;object-fit:contain}
    .gateway-title{font-size:1.3rem;font-weight:800;color:#1E293B;margin-bottom:4px}
    .gateway-sub{font-size:.85rem;color:#64748B;margin-bottom:28px}
    .pin-inputs{display:flex;gap:12px;justify-content:center;direction:ltr;margin-bottom:24px}
    .pin-inputs input{width:56px;height:64px;border:2px solid #E2E8F0;border-radius:14px;text-align:center;font-size:1.8rem;font-weight:800;font-family:'Tajawal',sans-serif;color:#1E293B;background:#F8FAFC;outline:none;transition:all .2s;-webkit-appearance:none;-moz-appearance:textfield}
    .pin-inputs input::-webkit-outer-spin-button,.pin-inputs input::-webkit-inner-spin-button{-webkit-appearance:none;margin:0}
    .pin-inputs input:focus{border-color:#F97316;background:#FFF;box-shadow:0 0 0 3px rgba(249,115,22,.15)}
    .pin-inputs input.error{border-color:#EF4444;background:#FEF2F2;animation:shake .4s}
    .pin-inputs input.success{border-color:#22C55E;background:#F0FDF4}
    @keyframes shake{0%,100%{transform:translateX(0)}20%,60%{transform:translateX(-6px)}40%,80%{transform:translateX(6px)}}
    .pin-btn{width:100%;padding:14px;border:none;border-radius:12px;background:linear-gradient(135deg,#F97316,#EA580C);color:#fff;font-size:1.05rem;font-weight:700;font-family:'Tajawal',sans-serif;cursor:pointer;transition:all .2s;display:flex;align-items:center;justify-content:center;gap:8px}
    .pin-btn:hover{transform:translateY(-1px);box-shadow:0 4px 16px rgba(249,115,22,.3)}
    .pin-btn:active{transform:translateY(0)}
    .pin-btn:disabled{opacity:.6;cursor:not-allowed;transform:none}
    .pin-error{color:#EF4444;font-size:.85rem;font-weight:600;margin-bottom:16px;min-height:24px;transition:all .2s}
    .pin-loading{display:none;align-items:center;justify-content:center;gap:8px;color:#64748B;font-size:.9rem;margin-bottom:16px}
    .pin-loading.show{display:flex}
    .pin-spinner{width:20px;height:20px;border:2.5px solid #E2E8F0;border-top-color:#F97316;border-radius:50%;animation:spin .6s linear infinite}
    @keyframes spin{to{transform:rotate(360deg)}}
    .auto-login{display:none;flex-direction:column;align-items:center;gap:12px;padding:20px 0}
    .auto-login.show{display:flex}
    .auto-login-name{font-size:1.1rem;font-weight:700;color:#1E293B}
    .auto-login-msg{font-size:.85rem;color:#64748B}
    .auto-login-spinner{width:32px;height:32px;border:3px solid #E2E8F0;border-top-color:#F97316;border-radius:50%;animation:spin .6s linear infinite}
    .switch-user{margin-top:16px;background:none;border:none;color:#64748B;font-size:.8rem;font-family:'Tajawal',sans-serif;cursor:pointer;text-decoration:underline}
    .switch-user:hover{color:#F97316}
    .gateway-footer{margin-top:20px;font-size:.75rem;color:#94A3B8}
  </style>
</head>
<body>
  <div class="gateway">
    <div class="gateway-card">
      <div class="gateway-logo">
        <img src="<?= SITE_URL ?>/assets/images/loogo.png" alt="Logo">
      </div>
      <div class="gateway-title"><?= SITE_NAME ?></div>
      <div class="gateway-sub" id="gateSub">أدخل الرقم السري لتسجيل الحضور</div>

      <!-- Auto-login section (shown when PIN saved) -->
      <div class="auto-login" id="autoLogin">
        <div class="auto-login-spinner"></div>
        <div class="auto-login-name" id="autoName"></div>
        <div class="auto-login-msg">جاري تسجيل الدخول...</div>
        <button class="switch-user" id="btnSwitch" onclick="switchUser()">تسجيل دخول بحساب آخر</button>
      </div>

      <!-- PIN entry form -->
      <div id="pinForm">
        <div class="pin-inputs" id="pinInputs">
          <input type="number" inputmode="numeric" pattern="[0-9]*" maxlength="1" autocomplete="off" autofocus>
          <input type="number" inputmode="numeric" pattern="[0-9]*" maxlength="1" autocomplete="off">
          <input type="number" inputmode="numeric" pattern="[0-9]*" maxlength="1" autocomplete="off">
          <input type="number" inputmode="numeric" pattern="[0-9]*" maxlength="1" autocomplete="off">
        </div>
        <div class="pin-error" id="pinError"></div>
        <div class="pin-loading" id="pinLoading">
          <div class="pin-spinner"></div>
          <span>جاري التحقق...</span>
        </div>
        <button class="pin-btn" id="pinBtn" onclick="submitPin()" disabled>
          <span>تسجيل الدخول</span>
          <span>←</span>
        </button>
      </div>

      <div class="gateway-footer"><?= date('Y') ?> &copy; <?= SITE_NAME ?></div>
    </div>
  </div>

  <script>
  (function(){
    'use strict';

    const STORAGE_KEY = 'att_pin_auth';
    const API_PIN_URL = '<?= SITE_URL ?>/api/auth-pin.php';
    const API_DEV_URL = '<?= SITE_URL ?>/api/auth-device.php';
    const ATT_URL = '<?= SITE_URL ?>/employee/attendance.php';

    const inputs = document.querySelectorAll('#pinInputs input');
    const pinBtn = document.getElementById('pinBtn');
    const pinError = document.getElementById('pinError');
    const pinLoading = document.getElementById('pinLoading');
    const pinForm = document.getElementById('pinForm');
    const autoLogin = document.getElementById('autoLogin');
    const autoName = document.getElementById('autoName');
    const gateSub = document.getElementById('gateSub');

    var deviceFP = null; // will be populated async

    // ── Fingerprint (same algorithm as radar.js) ──
    async function getFingerprint() {
      var parts = [
        navigator.userAgent,
        screen.width + 'x' + screen.height + 'x' + screen.colorDepth,
        Intl.DateTimeFormat().resolvedOptions().timeZone,
        navigator.language,
        navigator.platform || ''
      ];
      try {
        var c = document.createElement('canvas');
        var x = c.getContext('2d');
        x.textBaseline = 'top'; x.font = '14px Tajawal,Arial';
        x.fillStyle = '#F97316'; x.fillRect(0, 0, 100, 20);
        x.fillStyle = '#1E293B'; x.fillText('attendance\u0645\u0648\u0638\u0641', 2, 2);
        parts.push(c.toDataURL().slice(-50));
      } catch(e){}
      var raw = parts.join('|');
      var buf = await crypto.subtle.digest('SHA-256', new TextEncoder().encode(raw));
      return Array.from(new Uint8Array(buf)).map(function(b){return b.toString(16).padStart(2,'0')}).join('');
    }

    // ── Main init: try device auth first, then localStorage, then show PIN ──
    async function init() {
      try {
        deviceFP = await getFingerprint();
      } catch(e) {
        deviceFP = null;
      }

      // Step 1: Try device fingerprint auth (device is bound)
      if (deviceFP) {
        try {
          var res = await fetch(API_DEV_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ fingerprint: deviceFP })
          });
          var data = await res.json();
          if (data.success && data.bound) {
            // Device is bound — auto redirect, no PIN needed
            showAutoLoginDevice(data.employee_name);
            // Save to localStorage as well
            saveAuth({
              pin: '',
              token: data.token,
              name: data.employee_name,
              pin_changed_at: data.pin_changed_at,
              device_bound: true
            });
            window.location.href = ATT_URL + '?token=' + encodeURIComponent(data.token);
            return;
          }
        } catch(e) {
          // Network error — continue to other methods
        }
      }

      // Step 2: Check localStorage for saved PIN
      var saved = getSavedAuth();
      if (saved && saved.token) {
        if (saved.device_bound) {
          // Was device-bound but auth-device failed (maybe network issue)
          // Try direct redirect with saved token
          showAutoLoginDevice(saved.name);
          window.location.href = ATT_URL + '?token=' + encodeURIComponent(saved.token);
          return;
        }
        if (saved.pin) {
          showAutoLogin(saved);
          return;
        }
      }

      // Step 3: Show PIN form
      // (already visible by default)
    }
    init();

    // ── PIN Input Logic ──
    inputs.forEach(function(inp, i) {
      inp.addEventListener('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '').slice(0, 1);
        if (this.value && i < inputs.length - 1) {
          inputs[i + 1].focus();
        }
        clearError();
        updateBtn();
      });

      inp.addEventListener('keydown', function(e) {
        if (e.key === 'Backspace' && !this.value && i > 0) {
          inputs[i - 1].focus();
          inputs[i - 1].value = '';
          updateBtn();
        }
        if (e.key === 'Enter') {
          e.preventDefault();
          if (getPin().length === 4) submitPin();
        }
      });

      inp.addEventListener('paste', function(e) {
        e.preventDefault();
        var pasted = (e.clipboardData || window.clipboardData).getData('text').replace(/[^0-9]/g, '').slice(0, 4);
        for (var j = 0; j < pasted.length && j < inputs.length; j++) {
          inputs[j].value = pasted[j];
        }
        if (pasted.length > 0) inputs[Math.min(pasted.length, inputs.length) - 1].focus();
        updateBtn();
      });

      inp.addEventListener('focus', function() { this.select(); });
    });

    function getPin() {
      var pin = '';
      inputs.forEach(function(inp) { pin += inp.value; });
      return pin;
    }

    function updateBtn() {
      pinBtn.disabled = getPin().length !== 4;
    }

    function clearError() {
      pinError.textContent = '';
      inputs.forEach(function(inp) { inp.classList.remove('error'); });
    }

    function showError(msg) {
      pinError.textContent = msg;
      inputs.forEach(function(inp) { inp.classList.add('error'); });
    }

    function showSuccess() {
      inputs.forEach(function(inp) { inp.classList.add('success'); });
    }

    // ── Submit PIN (sends fingerprint too) ──
    window.submitPin = function() {
      var pin = getPin();
      if (pin.length !== 4) return;

      clearError();
      pinBtn.disabled = true;
      pinLoading.classList.add('show');

      var payload = { pin: pin };
      if (deviceFP) payload.fingerprint = deviceFP;

      fetch(API_PIN_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      })
      .then(function(r) { return r.json(); })
      .then(function(data) {
        pinLoading.classList.remove('show');
        if (data.success) {
          showSuccess();

          var redirected = data.redirected || false;
          var msg = data.message || '';

          // Save to localStorage
          saveAuth({
            pin: redirected ? '' : pin,
            token: data.token,
            name: data.employee_name,
            pin_changed_at: data.pin_changed_at,
            device_bound: redirected
          });

          // If redirected to different employee, show note briefly
          if (redirected && msg) {
            pinError.style.color = '#F97316';
            pinError.textContent = msg;
          }

          setTimeout(function() {
            window.location.href = ATT_URL + '?token=' + encodeURIComponent(data.token);
          }, redirected ? 1200 : 400);
        } else {
          showError(data.message || 'رقم PIN غير صحيح');
          pinBtn.disabled = false;
          inputs[0].focus();
          inputs[0].select();
        }
      })
      .catch(function() {
        pinLoading.classList.remove('show');
        showError('خطأ في الاتصال، حاول مرة أخرى');
        pinBtn.disabled = false;
      });
    };

    // ── Auto Login (device bound — no PIN needed) ──
    function showAutoLoginDevice(name) {
      pinForm.style.display = 'none';
      gateSub.style.display = 'none';
      autoLogin.classList.add('show');
      autoName.textContent = 'مرحباً، ' + name;
      document.getElementById('btnSwitch').style.display = 'none';
    }

    // ── Auto Login (PIN saved in localStorage) ──
    function showAutoLogin(saved) {
      pinForm.style.display = 'none';
      gateSub.style.display = 'none';
      autoLogin.classList.add('show');
      autoName.textContent = 'مرحباً، ' + saved.name;

      // Verify PIN is still valid
      var payload = { pin: saved.pin };
      if (deviceFP) payload.fingerprint = deviceFP;

      fetch(API_PIN_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      })
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (data.success) {
          // Check if device was bound to another employee
          if (data.redirected) {
            saveAuth({
              pin: '',
              token: data.token,
              name: data.employee_name,
              pin_changed_at: data.pin_changed_at,
              device_bound: true
            });
            autoName.textContent = 'مرحباً، ' + data.employee_name;
            window.location.href = ATT_URL + '?token=' + encodeURIComponent(data.token);
            return;
          }

          // Check if PIN was changed by admin
          if (data.pin_changed_at !== saved.pin_changed_at) {
            clearSavedAuth();
            showPinForm('تم تغيير الرقم السري، أدخل الرقم الجديد');
            return;
          }
          // Update token in case it was regenerated
          saveAuth({
            pin: saved.pin,
            token: data.token,
            name: data.employee_name,
            pin_changed_at: data.pin_changed_at
          });
          window.location.href = ATT_URL + '?token=' + encodeURIComponent(data.token);
        } else {
          clearSavedAuth();
          showPinForm('الرقم السري لم يعد صالحاً، أدخل الرقم الجديد');
        }
      })
      .catch(function() {
        // Network error — try with saved token directly
        window.location.href = ATT_URL + '?token=' + encodeURIComponent(saved.token);
      });
    }

    function showPinForm(msg) {
      autoLogin.classList.remove('show');
      pinForm.style.display = '';
      gateSub.style.display = '';
      if (msg) {
        gateSub.textContent = msg;
        gateSub.style.color = '#F97316';
      }
      inputs[0].focus();
    }

    // ── Switch User ──
    window.switchUser = function() {
      clearSavedAuth();
      showPinForm('أدخل الرقم السري لتسجيل الحضور');
      gateSub.style.color = '';
    };

    // ── localStorage Helpers ──
    function getSavedAuth() {
      try {
        var s = localStorage.getItem(STORAGE_KEY);
        return s ? JSON.parse(s) : null;
      } catch(e) { return null; }
    }

    function saveAuth(data) {
      try { localStorage.setItem(STORAGE_KEY, JSON.stringify(data)); } catch(e) {}
    }

    function clearSavedAuth() {
      try { localStorage.removeItem(STORAGE_KEY); } catch(e) {}
    }
  })();
  </script>
</body>
</html>
