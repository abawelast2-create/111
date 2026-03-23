<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

echo '=== TIMEZONE ===' . PHP_EOL;
$tz = db()->query('SELECT NOW() as n, CURDATE() as d, @@session.time_zone as tz')->fetch();
echo 'DB_NOW=' . $tz['n'] . ' CURDATE=' . $tz['d'] . ' TZ=' . $tz['tz'] . PHP_EOL;
echo 'PHP=' . date('Y-m-d H:i:s') . PHP_EOL;

echo PHP_EOL . '=== TABLES ===' . PHP_EOL;
$tables = db()->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
echo implode(', ', $tables) . PHP_EOL;

echo PHP_EOL . '=== EMPLOYEES ===' . PHP_EOL;
$c = db()->query('SELECT COUNT(*) as t, SUM(is_active) as a FROM employees')->fetch();
echo 'Total=' . $c['t'] . ' Active=' . $c['a'] . PHP_EOL;

echo PHP_EOL . '=== BRANCHES ===' . PHP_EOL;
$b = db()->query('SELECT id, name, is_active FROM branches ORDER BY id')->fetchAll();
foreach ($b as $r) echo $r['id'] . '. ' . $r['name'] . ' (active=' . $r['is_active'] . ')' . PHP_EOL;

echo PHP_EOL . '=== ATTENDANCE ===' . PHP_EOL;
$att = db()->query('SELECT COUNT(*) FROM attendances WHERE attendance_date=CURDATE()')->fetchColumn();
echo 'Today=' . $att . PHP_EOL;
$last = db()->query('SELECT timestamp FROM attendances ORDER BY id DESC LIMIT 1')->fetchColumn();
echo 'Last=' . $last . PHP_EOL;
$total = db()->query('SELECT COUNT(*) FROM attendances')->fetchColumn();
echo 'All=' . $total . PHP_EOL;

echo PHP_EOL . '=== SETTINGS ===' . PHP_EOL;
$s = db()->query('SELECT setting_key, setting_value FROM settings')->fetchAll();
foreach ($s as $r) echo $r['setting_key'] . '=' . $r['setting_value'] . PHP_EOL;

echo PHP_EOL . '=== CRON ===' . PHP_EOL;
echo 'auto-checkout: ' . (file_exists(__DIR__ . '/cron/auto-checkout.php') ? 'EXISTS' : 'MISSING') . PHP_EOL;
echo 'rate_limiter: ' . (file_exists(__DIR__ . '/includes/rate_limiter.php') ? 'EXISTS' : 'MISSING') . PHP_EOL;

echo PHP_EOL . '=== FILES COUNT ===' . PHP_EOL;
$phpFiles = glob(__DIR__ . '/{,*/,*/*/}*.php', GLOB_BRACE);
echo 'PHP files: ' . count($phpFiles) . PHP_EOL;
