<?php
// =============================================================
// m.php - Mobile Gateway with QUIC/HTTP3 Detection & Fallback
// =============================================================

// Force HTTP/1.1 by unsetting alt-svc at earliest stage
if (!headers_sent()) {
    header('Alt-Svc: clear', true);
    header_remove('Alt-Svc');
    
    // Force connection downgrade
    header('X-Protocol-Downgrade: http/1.1');
    header('Connection: keep-alive');
}

// Detect if user came from QUIC error and needs help
$showInstructions = isset($_GET['quic_error']);

// Forward to actual attendance page
require_once __DIR__ . '/includes/config.php';
$token = $_GET['token'] ?? '';
$targetUrl = SITE_URL . '/employee/attendance.php?token=' . urlencode($token);

// If browser supports HTTP/1.1 fallback, redirect
if (!$showInstructions && !empty($token)) {
    // Add cache buster to force new connection
    $targetUrl .= '&_h1=' . time();
    header('Location: ' . $targetUrl, true, 307); // 307 = Temporary Redirect preserves method
    exit;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>تحميل النظام...</title>
<style>
body{font-family:Tahoma,Arial,sans-serif;background:#f5f5f5;padding:20px;text-align:center}
.card{background:white;border-radius:8px;padding:30px;margin:20px auto;max-width:400px;box-shadow:0 2px 8px rgba(0,0,0,0.1)}
.spinner{border:4px solid #f3f3f3;border-top:4px solid #F97316;border-radius:50%;width:40px;height:40px;animation:spin 1s linear infinite;margin:20px auto}
@keyframes spin{to{transform:rotate(360deg)}}
.btn{background:#F97316;color:white;border:none;padding:12px 24px;border-radius:6px;font-size:16px;cursor:pointer;margin:10px}
.btn:active{background:#EA580C}
h2{color:#333;margin-bottom:10px}
p{color:#666;line-height:1.6}
</style>
</head>
<body>
<div class="card">
<?php if ($showInstructions): ?>
    <h2>⚠️ مشكلة في الاتصال</h2>
    <p>يبدو أن متصفحك يواجه مشكلة في بروتوكول الاتصال (QUIC).</p>
    <button class="btn" onclick="location.href=location.href.replace('quic_error=1','')">إعادة المحاولة</button>
    <br>
    <a href="help-quic.html" style="color:#666;text-decoration:underline;font-size:14px;margin-top:15px;display:inline-block">عرض التعليمات الكاملة</a>
<?php else: ?>
    <div class="spinner"></div>
    <h2>جاري التحميل...</h2>
    <p>يتم تحويلك إلى النظام بأمان</p>
<?php endif; ?>
</div>

<script>
// Auto-detect QUIC errors and show instructions
window.addEventListener('error', function(e) {
    if (e.message && /ERR_QUIC|ERR_CONNECTION|ERR_NETWORK/i.test(e.message)) {
        location.href = location.href + (location.href.includes('?') ? '&' : '?') + 'quic_error=1';
    }
});

// Timeout fallback
setTimeout(function() {
    if (!document.hidden) {
        location.href = '<?= $targetUrl ?>';
    }
}, 3000);
</script>
</body>
</html>
