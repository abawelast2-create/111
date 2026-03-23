<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
$s=db()->query('SELECT b.id, b.name, b.geofence_radius, b.allow_overtime, b.is_active, GROUP_CONCAT(CONCAT(bs.shift_number,":",bs.shift_start,"-",bs.shift_end) ORDER BY bs.shift_number SEPARATOR " | ") AS shifts FROM branches b LEFT JOIN branch_shifts bs ON bs.branch_id = b.id AND bs.is_active = 1 GROUP BY b.id ORDER BY b.id');
while($r=$s->fetch(PDO::FETCH_ASSOC)){echo json_encode($r,JSON_UNESCAPED_UNICODE).PHP_EOL;}