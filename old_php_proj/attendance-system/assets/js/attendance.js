// =============================================================
// assets/js/attendance.js - جافا سكريبت الحضور والانصراف
// =============================================================

let currentLat = null;
let currentLon = null;
let currentAccuracy = null;
let locationReady = false;

/**
 * تهيئة تحديد الموقع عند تحميل الصفحة
 */
function initLocation() {
    if (!navigator.geolocation) {
        setLocationStatus('error', '⚠️ المتصفح لا يدعم تحديد الموقع الجغرافي');
        return;
    }

    setLocationStatus('getting', '🔍 جاري تحديد موقعك الجغرافي...');

    navigator.geolocation.watchPosition(
        function (position) {
            currentLat      = position.coords.latitude;
            currentLon      = position.coords.longitude;
            currentAccuracy = position.coords.accuracy;
            locationReady   = true;

            const accuracyText = currentAccuracy < 50 ? 'دقة عالية' :
                                 currentAccuracy < 150 ? 'دقة متوسطة' : 'دقة منخفضة';
            setLocationStatus('ready',
                `📍 تم تحديد موقعك (${accuracyText}: ±${Math.round(currentAccuracy)} م)`
            );
            enableButtons();
        },
        function (error) {
            let msg = '';
            switch (error.code) {
                case error.PERMISSION_DENIED:
                    msg = '🚫 الرجاء السماح للمتصفح بالوصول إلى موقعك من إعدادات الهاتف';
                    break;
                case error.POSITION_UNAVAILABLE:
                    msg = '📡 تعذّر تحديد موقعك. تأكد من تفعيل GPS';
                    break;
                case error.TIMEOUT:
                    msg = '⏱️ انتهت مهلة تحديد الموقع. حاول مرة أخرى';
                    break;
                default:
                    msg = '❌ خطأ في تحديد الموقع: ' + error.message;
            }
            setLocationStatus('error', msg);
            // السماح بالضغط حتى مع عدم دقة الموقع
            enableButtons(true);
        },
        {
            enableHighAccuracy: true,
            timeout: 15000,
            maximumAge: 30000
        }
    );
}

/**
 * تفعيل الأزرار
 */
function enableButtons(withWarning = false) {
    const btnIn  = document.getElementById('btnCheckIn');
    const btnOut = document.getElementById('btnCheckOut');
    if (btnIn)  btnIn.disabled  = false;
    if (btnOut) btnOut.disabled = false;
}

/**
 * تغيير حالة شريط الموقع
 */
function setLocationStatus(state, text) {
    const el = document.getElementById('locationStatus');
    const tx = document.getElementById('locationText');
    if (!el || !tx) return;
    el.className = 'location-status ' + state;
    tx.textContent = text;
}

/**
 * إرسال طلب الحضور/الانصراف
 */
function getLocationAndSubmit(type, token) {
    if (!currentLat || !currentLon) {
        // محاولة جلب الموقع مرة أخرى قبل الإرسال
        setLocationStatus('getting', '🔍 جاري تحديد موقعك...');
        navigator.geolocation.getCurrentPosition(
            function (pos) {
                currentLat      = pos.coords.latitude;
                currentLon      = pos.coords.longitude;
                currentAccuracy = pos.coords.accuracy;
                submitAttendance(token, type, currentLat, currentLon, currentAccuracy);
            },
            function () {
                showMessage('error', '🚫 لم نتمكن من تحديد موقعك. تأكد من تفعيل GPS والسماح للمتصفح.');
            },
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
        );
        return;
    }
    submitAttendance(token, type, currentLat, currentLon, currentAccuracy);
}

/**
 * إرسال بيانات الحضور إلى API
 */
function submitAttendance(token, type, lat, lon, accuracy) {
    const spinner = document.getElementById('spinner');
    const btnIn   = document.getElementById('btnCheckIn');
    const btnOut  = document.getElementById('btnCheckOut');

    // تعطيل الأزرار وإظهار الـ spinner
    if (btnIn)  btnIn.disabled  = true;
    if (btnOut) btnOut.disabled = true;
    if (spinner) spinner.classList.add('show');
    hideMessage();

    const endpoint = type === 'in' ? '../api/check-in.php' : '../api/check-out.php';

    fetch(endpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            token:    token,
            latitude: lat,
            longitude: lon,
            accuracy: accuracy || 0
        })
    })
    .then(res => res.json())
    .then(data => {
        if (spinner) spinner.classList.remove('show');
        if (data.success) {
            showMessage('success', '✅ ' + data.message);
            // تحديث الصفحة بعد ثانيتين
            setTimeout(() => location.reload(), 2500);
        } else {
            showMessage('error', '❌ ' + data.message);
            if (btnIn)  btnIn.disabled  = false;
            if (btnOut) btnOut.disabled = false;
        }
    })
    .catch(err => {
        if (spinner) spinner.classList.remove('show');
        showMessage('error', '❌ خطأ في الاتصال بالخادم. حاول مرة أخرى.');
        if (btnIn)  btnIn.disabled  = false;
        if (btnOut) btnOut.disabled = false;
        console.error(err);
    });
}

/**
 * عرض رسالة للمستخدم
 */
function showMessage(type, text) {
    const box = document.getElementById('messageBox');
    if (!box) return;
    box.className = 'message ' + type + ' show';
    box.textContent = text;
    box.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

/**
 * إخفاء رسالة
 */
function hideMessage() {
    const box = document.getElementById('messageBox');
    if (box) box.className = 'message';
}
