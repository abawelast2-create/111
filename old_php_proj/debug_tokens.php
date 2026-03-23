<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

// Test verify-device with a real token internally
$token = 'f9bc041b922ad15c8bbb150d4969f157a5d7a86f76f391fa5563766085be050e';
$stmt = db()->prepare("SELECT id, device_fingerprint, is_active, deleted_at FROM employees WHERE unique_token = ? AND is_active = 1");
$stmt->execute([$token]);
$emp = $stmt->fetch();

echo "Direct DB query for token:\n";
echo $emp ? "  FOUND: id={$emp['id']}, fp=" . ($emp['device_fingerprint'] ?? 'NULL') . ", active={$emp['is_active']}\n" : "  NOT FOUND\n";

echo "\ngetEmployeeByToken():\n";
require_once __DIR__ . '/includes/functions.php';
$emp2 = getEmployeeByToken($token);
echo $emp2 ? "  FOUND: {$emp2['name']}\n" : "  NOT FOUND\n";

echo "\n--- DB TIMEZONE ---\n";
$tz = db()->query("SELECT NOW() as n, CURDATE() as d, @@session.time_zone as tz")->fetch();
echo "NOW()={$tz['n']} CURDATE()={$tz['d']} TZ={$tz['tz']}\n";
echo "PHP date()=" . date('Y-m-d H:i:s') . "\n";

// Test with internal HTTP call
echo "\n--- Internal verify-device test ---\n";
$url = 'https://mycorner.site/sys_1/api/verify-device.php';
$postData = json_encode(['token' => $token, 'fingerprint' => 'testfp_from_debug']);
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "HTTP $httpCode: $result\n";
