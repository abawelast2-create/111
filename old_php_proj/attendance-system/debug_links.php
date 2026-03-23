<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
header('Content-Type: text/plain; charset=utf-8');

$stmt = db()->query("SELECT id, name, unique_token FROM employees WHERE is_active=1 AND deleted_at IS NULL LIMIT 5");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $link = SITE_URL . '/employee/attendance.php?token=' . $row['unique_token'];
    echo $row['id'] . ' | ' . $row['name'] . "\n";
    echo '  Link: ' . $link . "\n\n";
}

echo "SITE_URL = " . SITE_URL . "\n";
echo "Token length = " . strlen($row['unique_token'] ?? '') . "\n";
