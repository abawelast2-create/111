<?php
// =============================================================
// tests/run_tests.php - تشغيل الاختبارات مباشرة بدون PHPUnit
// =============================================================
require_once __DIR__ . '/bootstrap.php';

$pass    = 0;
$fail    = 0;
$skipped = 0;
$results = [];

function test(string $name, bool $result): void {
    global $pass, $fail, $results;
    if ($result) {
        $pass++;
        $results[] = "  \u{2705} PASS: $name";
    } else {
        $fail++;
        $results[] = "  \u{274C} FAIL: $name";
    }
}

function skip(string $name, string $reason = ''): void {
    global $skipped, $results;
    $skipped++;
    $results[] = "  \u{23ED}  SKIP: $name" . ($reason ? " ($reason)" : '');
}

// ================================================================
// GeofenceService Tests  (pure math - static, no DB required)
// ================================================================
echo PHP_EOL . "=== GeofenceService ===" . PHP_EOL;

test("نفس النقطة مسافة = 0",
    App\Services\GeofenceService::calculateDistance(24.7136, 46.6753, 24.7136, 46.6753) == 0);

$d_jeddah = App\Services\GeofenceService::calculateDistance(24.7136, 46.6753, 21.4858, 39.1925);
test("الرياض-جدة ~850كم", $d_jeddah > 800000 && $d_jeddah < 900000);

$d_100m = App\Services\GeofenceService::calculateDistance(24.7136, 46.6753, 24.7145, 46.6753);
test("مسافة ~100م بين نقطتين", $d_100m > 80 && $d_100m < 120);

$d_small = App\Services\GeofenceService::calculateDistance(24.71360, 46.67530, 24.71369, 46.67530);
test("مسافة زغيرة < 20م", $d_small < 20);

test("تناظر: dist(A,B) == dist(B,A)",
    abs(App\Services\GeofenceService::calculateDistance(1, 1, 2, 2) -
        App\Services\GeofenceService::calculateDistance(2, 2, 1, 1)) < 0.001);

skip("isWithinGeofence (يحتاج قاعدة بيانات)", "DB غير متاح في بيئة CLI");

// ================================================================
// CacheService Tests
// ================================================================
echo PHP_EOL . "=== CacheService ===" . PHP_EOL;
$dir = sys_get_temp_dir() . '/cache_test_' . uniqid();
@mkdir($dir, 0755, true);
$cache = new App\Services\CacheService($dir);

$cache->set('k1', 'hello', 60);
test("Set/Get قيمة نصية",       $cache->get('k1') === 'hello');
test("Get قيمة غير موجودة → null", $cache->get('missing') === null);
test("Get مع قيمة افتراضية",    $cache->get('missing2', 'def') === 'def');

$cache->set('exp', 'v', -1);
test("قيمة منتهية الصلاحية → null", $cache->get('exp') === null);

$cache->set('rf', 'v', 60);
$cache->forget('rf');
test("Forget - حذف قيمة", $cache->get('rf') === null);

$called = 0;
$cache->remember('rk', 60, function() use (&$called) { $called++; return 'cv'; });
$cache->remember('rk', 60, function() use (&$called) { $called++; return 'cv2'; });
test("Remember - الدالة تُستدعى مرة واحدة فقط", $called === 1);

$data = ['name' => 'test', 'count' => 42];
$cache->set('arr', $data, 60);
test("حفظ مصفوفة واسترجاعها", $cache->get('arr') === $data);

$cache->set('p_a', 1, 60);
$cache->set('p_b', 2, 60);
$cache->set('other', 3, 60);
$cache->forgetByPrefix('p_');
test("Forget by prefix - يحذف المطابق ويبقي الباقي",
    $cache->get('p_a') === null && $cache->get('other') !== null);

$cache->set('fl1', 'a', 60);
$cache->set('fl2', 'b', 60);
$cache->flush();
test("Flush - يمسح كل القيم",
    $cache->get('fl1') === null && $cache->get('fl2') === null);

// ================================================================
// CsrfProtection Tests
// ================================================================
echo PHP_EOL . "=== CsrfProtection ===" . PHP_EOL;
if (session_status() === PHP_SESSION_NONE) @session_start();
unset($_SESSION['csrf_token']); // حالة نظيفة

$t = App\Middleware\CsrfProtection::generateToken();
test("generateToken() يُرجع قيمة غير فارغة",  !empty($t));
test("Token طوله 64 حرف (hex)",                strlen($t) === 64);
test("Token محفوظ في SESSION['csrf_token']",   isset($_SESSION['csrf_token']) && $_SESSION['csrf_token'] === $t);
test("verifyToken() يُقبل التوكن الصحيح",      App\Middleware\CsrfProtection::verifyToken($t));

// بعد استخدام التوكن يُحذف (one-time use)
test("التوكن لا يُقبل مرة ثانية (one-time use)", !App\Middleware\CsrfProtection::verifyToken($t));
test("Token غير صالح فاشل",   !App\Middleware\CsrfProtection::verifyToken('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'));
test("Token فارغ فاشل",       !App\Middleware\CsrfProtection::verifyToken(''));

$field = App\Middleware\CsrfProtection::field();
test("field() يولّد hidden input بـ name=csrf_token",
    str_contains($field, '<input type="hidden"') && str_contains($field, 'name="csrf_token"'));

// إعادة تعيين جلسة جديدة لـ metaTag
unset($_SESSION['csrf_token']);
$meta = App\Middleware\CsrfProtection::metaTag();
test("metaTag() يولّد meta tag بـ name=csrf-token",
    str_contains($meta, '<meta name="csrf-token"'));

// ================================================================
// RateLimiter Tests (Token Bucket)
// ================================================================
echo PHP_EOL . "=== RateLimiter ===" . PHP_EOL;
$rdir = sys_get_temp_dir() . '/rate_test_' . uniqid();
@mkdir($rdir, 0755, true);
$rl = new App\Middleware\RateLimiter($rdir);

// checkByIP(endpoint, max, seconds) → ['allowed'=>bool, ...]
$_SERVER['REMOTE_ADDR'] = '10.0.0.1';
test("أول طلب مسموح دائماً", $rl->checkByIP('test', 5, 60)['allowed'] === true);

// استنزاف الـ bucket لـ 10.0.0.2
$_SERVER['REMOTE_ADDR'] = '10.0.0.2';
for ($i = 0; $i < 5; $i++) $rl->checkByIP('test', 5, 60);
test("محجوب بعد تجاوز الحد", $rl->checkByIP('test', 5, 60)['allowed'] === false);

// IP مختلف لا يتأثر
$_SERVER['REMOTE_ADDR'] = '10.0.0.3';
test("IP مختلف مستقل (لم يُستنزف)", $rl->checkByIP('test', 5, 60)['allowed'] === true);

// checkByToken(token, endpoint, max, seconds)
test("checkByToken يسمح بالطلب الأول",
    $rl->checkByToken('my-secret-token', 'api/check-in', 10, 60)['allowed'] === true);

// ================================================================
// Pure Logic Tests (لا يحتاج قاعدة بيانات)
// ================================================================
echo PHP_EOL . "=== Pure Logic ===" . PHP_EOL;

// نسخة مطابقة لدالة sanitize في includes/functions.php
function _sanitize(string $value): string {
    return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
}

test("sanitize() يزيل وسوم HTML",         _sanitize('<b>bold</b>') === 'bold');
test("sanitize() يُبقي النص العربي",      _sanitize('مرحبا') === 'مرحبا');
test("sanitize() يُحوَّل & إلى &amp;",   _sanitize('a & b') === 'a &amp; b');
test("sanitize() يُحوّل quotes",          _sanitize("it's") === "it&#039;s");
test("sanitize() يزيل script عبر strip_tags", !str_contains(_sanitize('<script>x</script>'), '<script>'));

// Haversine مطابق لـ calculateDistance() في includes/functions.php
function _haversine(float $lat1, float $lon1, float $lat2, float $lon2): float {
    $R = 6371000;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
    return 2 * $R * asin(sqrt($a));
}

test("Haversine: نفس النقطة = 0", _haversine(24.7136, 46.6753, 24.7136, 46.6753) == 0);
test("Haversine: الرياض-جدة > 800كم", _haversine(24.7136, 46.6753, 21.4858, 39.1925) > 800000);

// ================================================================
// Summary
// ================================================================
echo PHP_EOL . "================================================" . PHP_EOL;
echo "  نتائج الاختبارات" . PHP_EOL;
echo "================================================" . PHP_EOL;
foreach ($results as $r) echo $r . PHP_EOL;
echo "------------------------------------------------" . PHP_EOL;
echo "  PASS: $pass | FAIL: $fail | SKIP: $skipped | TOTAL: " . ($pass + $fail + $skipped) . PHP_EOL;
echo "================================================" . PHP_EOL;
exit($fail > 0 ? 1 : 0);
