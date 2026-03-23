<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

$s = db()->query("SELECT id, name, unique_token, device_fingerprint, device_bind_mode, is_active, deleted_at FROM employees ORDER BY id");

while ($r = $s->fetch(PDO::FETCH_ASSOC)) {
    $tk = substr($r['unique_token'], 0, 16);
    $df = $r['device_fingerprint'] ?: 'NULL';
    $bm = $r['device_bind_mode'];
    $ac = $r['is_active'];
    $del = $r['deleted_at'] ?? 'NULL';
    echo "{$r['id']} | {$r['name']} | tk={$tk}... | device={$df} | bind_mode={$bm} | active={$ac} | deleted={$del}\n";
}
