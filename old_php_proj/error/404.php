<?php
http_response_code(404);
require_once __DIR__ . '/../includes/config.php';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - الصفحة غير موجودة</title>
    <style>
        body { font-family:'Segoe UI',sans-serif; background:#0F172A; color:#E2E8F0;
               display:flex; align-items:center; justify-content:center; min-height:100vh; text-align:center; }
        .box { max-width:400px; padding:40px; }
        .code { font-size:6rem; font-weight:bold; color:#D4A841; line-height:1; }
        h1 { font-size:1.4rem; margin:12px 0; }
        p { color:#64748B; margin-bottom:24px; }
        a { display:inline-block; padding:10px 24px; background:#D4A841; color:#0F172A;
            border-radius:8px; text-decoration:none; font-weight:bold; }
    </style>
</head>
<body>
<div class="box">
    <div class="code">404</div>
    <h1>الصفحة غير موجودة</h1>
    <p>الصفحة التي تبحث عنها غير موجودة أو تم نقلها.</p>
    <a href="<?= SITE_URL ?>/admin/dashboard.php">العودة للرئيسية</a>
</div>
</body></html>
