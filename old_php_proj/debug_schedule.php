<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

echo "=== BRANCHES ===\n";
$s = db()->query("DESCRIBE branches");
while($r = $s->fetch()) {
    echo $r['Field'] . " | " . $r['Type'] . "\n";
}

echo "\n=== ALL BRANCHES ===\n";
$s = db()->query("SELECT * FROM branches");
while($r = $s->fetch(PDO::FETCH_ASSOC)) {
    echo json_encode($r, JSON_UNESCAPED_UNICODE) . "\n";
}

echo "\n=== SCHEDULE SETTINGS ===\n";
$s = db()->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE '%check%' OR setting_key LIKE '%shift%' OR setting_key LIKE '%checkout%' OR setting_key LIKE '%overtime%' OR setting_key LIKE '%late%' ORDER BY setting_key");
while($r = $s->fetch()) {
    echo $r['setting_key'] . " = " . $r['setting_value'] . "\n";
}
