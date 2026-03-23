#!/bin/bash
BASE='https://mycorner.site/sys_1'
TOKEN='f9bc041b922ad15c8bbb150d4969f157a5d7a86f76f391fa5563766085be050e'

echo '=== EMPLOYEE PAGE ==='
curl -s -o /dev/null -w 'HTTP:%{http_code} Size:%{size_download}' "$BASE/employee/attendance.php?token=$TOKEN"
echo ''
echo '=== INVALID TOKEN ==='
curl -s -o /dev/null -w 'HTTP:%{http_code} Size:%{size_download}' "$BASE/employee/attendance.php?token=INVALID"
echo ''
echo '=== CHECK-IN API ==='
curl -s -o /dev/null -w 'HTTP:%{http_code}' -X POST -H 'Content-Type: application/json' -d '{"token":"test","latitude":24.5,"longitude":46.6,"accuracy":10}' "$BASE/api/check-in.php"
echo ''
echo '=== CHECK-OUT API ==='
curl -s -o /dev/null -w 'HTTP:%{http_code}' -X POST -H 'Content-Type: application/json' -d '{"token":"test","latitude":24.5,"longitude":46.6,"accuracy":10}' "$BASE/api/check-out.php"
echo ''
echo '=== VERIFY-DEVICE API ==='
curl -s -o /dev/null -w 'HTTP:%{http_code}' -X POST -H 'Content-Type: application/json' -d '{"token":"test","fingerprint":"fp"}' "$BASE/api/verify-device.php"
echo ''
echo '=== OT API ==='
curl -s -o /dev/null -w 'HTTP:%{http_code}' -X POST -H 'Content-Type: application/json' -d '{"token":"test","latitude":24.5,"longitude":46.6,"accuracy":10}' "$BASE/api/ot.php"
echo ''
echo '=== ADMIN LOGIN ==='
curl -s -o /dev/null -w 'HTTP:%{http_code} Size:%{size_download}' "$BASE/admin/login.php"
echo ''
echo '=== ADMIN DASHBOARD (expect 302 redirect) ==='
curl -s -o /dev/null -w 'HTTP:%{http_code}' "$BASE/admin/dashboard.php"
echo ''
echo '=== ADMIN ATTENDANCE (expect 302) ==='
curl -s -o /dev/null -w 'HTTP:%{http_code}' "$BASE/admin/attendance.php"
echo ''
echo '=== ADMIN EMPLOYEES (expect 302) ==='
curl -s -o /dev/null -w 'HTTP:%{http_code}' "$BASE/admin/employees.php"
echo ''
echo '=== ADMIN SETTINGS (expect 302) ==='
curl -s -o /dev/null -w 'HTTP:%{http_code}' "$BASE/admin/settings.php"
echo ''
echo '=== REPORT DAILY (expect 302) ==='
curl -s -o /dev/null -w 'HTTP:%{http_code}' "$BASE/admin/report-daily.php"
echo ''
echo '=== CSS ==='
curl -s -o /dev/null -w 'HTTP:%{http_code} Size:%{size_download}' "$BASE/assets/css/radar.css"
echo ''
echo '=== JS ==='
curl -s -o /dev/null -w 'HTTP:%{http_code} Size:%{size_download}' "$BASE/assets/js/radar.js"
echo ''
echo '=== STYLE CSS ==='
curl -s -o /dev/null -w 'HTTP:%{http_code} Size:%{size_download}' "$BASE/assets/css/style.css"
echo ''
echo '=== MANIFEST ==='
curl -s -o /dev/null -w 'HTTP:%{http_code} Size:%{size_download}' "$BASE/manifest.json"
echo ''
echo '=== CRON SYNTAX ==='
php -l /home/u307296675/domains/mycorner.site/public_html/sys_1/cron/auto-checkout.php 2>&1
echo '=== DONE ==='
