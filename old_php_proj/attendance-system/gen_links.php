<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
header('Content-Type: text/plain; charset=utf-8');

$stmt = db()->query("SELECT e.id, e.name, e.pin, e.unique_token, e.phone, b.name as branch_name 
    FROM employees e 
    LEFT JOIN branches b ON e.branch_id = b.id 
    WHERE e.is_active=1 AND e.deleted_at IS NULL 
    ORDER BY b.name, e.name");

$currentBranch = '';
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if ($row['branch_name'] !== $currentBranch) {
        $currentBranch = $row['branch_name'];
        echo "\n=== {$currentBranch} ===\n";
    }
    $link = SITE_URL . '/employee/attendance.php?token=' . $row['unique_token'];
    echo "{$row['name']} (PIN:{$row['pin']}) - {$row['phone']}\n";
    echo "  {$link}\n\n";
}
