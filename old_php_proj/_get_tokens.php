<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
$s = db()->query('SELECT unique_token, name FROM employees WHERE is_active=1 AND deleted_at IS NULL LIMIT 3');
while ($r = $s->fetch()) {
  echo $r['name'] . ' => https://mycorner.site/sys_1/employee/attendance.php?token=' . $r['unique_token'] . "\n";
}
