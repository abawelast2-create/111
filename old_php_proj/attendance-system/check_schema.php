<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
foreach(db()->query("SHOW COLUMNS FROM employees") as $c) {
    echo $c['Field'] . '|' . $c['Type'] . '|' . $c['Null'] . '|' . ($c['Default'] ?? 'NULL') . "\n";
}
echo "---BRANCHES---\n";
foreach(db()->query("SELECT id, name FROM branches WHERE is_active=1 ORDER BY name") as $b) {
    echo $b['id'] . '|' . $b['name'] . "\n";
}
echo "---EMPLOYEES---\n";
foreach(db()->query("SELECT e.id, e.name, e.branch_id, b.name as branch_name FROM employees e LEFT JOIN branches b ON e.branch_id=b.id WHERE e.deleted_at IS NULL ORDER BY COALESCE(b.name,'zzz'), e.name") as $e) {
    echo $e['id'] . '|' . $e['name'] . '|' . ($e['branch_id'] ?? 'NULL') . '|' . ($e['branch_name'] ?? 'بدون فرع') . "\n";
}
