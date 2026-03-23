<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/plain; charset=utf-8');

echo "=== Debug Employee Link ===\n\n";
echo "GET token: " . ($_GET['token'] ?? 'MISSING') . "\n";
echo "Token length: " . strlen($_GET['token'] ?? '') . "\n\n";

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

echo "SITE_URL: " . SITE_URL . "\n";
echo "DB_HOST: " . DB_HOST . "\n";
echo "DB_NAME: " . DB_NAME . "\n\n";

$token = trim($_GET['token'] ?? '');
if (empty($token)) {
    echo "ERROR: No token provided\n";
    echo "Try: ?token=YOUR_TOKEN\n";
    exit;
}

echo "Looking up token: $token\n";

// Direct query without deleted_at filter
$stmt = db()->prepare("SELECT id, name, is_active, deleted_at, unique_token FROM employees WHERE unique_token = ? LIMIT 1");
$stmt->execute([$token]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row) {
    echo "FOUND: ID={$row['id']}, Name={$row['name']}, Active={$row['is_active']}, Deleted=" . ($row['deleted_at'] ?? 'NULL') . "\n";
} else {
    echo "NOT FOUND in database!\n";
    // Check total employees
    $count = db()->query("SELECT COUNT(*) FROM employees")->fetchColumn();
    echo "Total employees in DB: $count\n";
}

echo "\n=== getEmployeeByToken result ===\n";
$emp = getEmployeeByToken($token);
echo $emp ? "FOUND: {$emp['name']}" : "NULL (not found or filtered)";
echo "\n";
