<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/plain; charset=utf-8');

echo "Step 1: Before config\n";
try {
    require_once __DIR__ . '/../includes/config.php';
    echo "Step 2: Config loaded. SITE_URL=" . SITE_URL . "\n";
} catch (Throwable $e) {
    echo "ERROR at config: " . $e->getMessage() . "\n";
    exit;
}

try {
    require_once __DIR__ . '/../includes/functions.php';
    echo "Step 3: Functions loaded\n";
} catch (Throwable $e) {
    echo "ERROR at functions: " . $e->getMessage() . "\n";
    exit;
}

echo "Step 4: Testing db()\n";
try {
    $pdo = db();
    echo "Step 5: DB connected\n";
} catch (Throwable $e) {
    echo "ERROR at db: " . $e->getMessage() . "\n";
    exit;
}

$token = trim($_GET['token'] ?? 'NONE');
echo "Step 6: Token=$token\n";

if ($token !== 'NONE') {
    $emp = getEmployeeByToken($token);
    echo "Step 7: Employee=" . ($emp ? $emp['name'] : 'NULL') . "\n";
}

echo "\nALL OK\n";
