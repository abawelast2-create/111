<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
$s=db()->query('SELECT id,name,work_start_time,work_end_time,check_in_start_time,check_in_end_time,check_out_start_time,check_out_end_time,checkout_show_before,allow_overtime FROM branches');
while($r=$s->fetch(PDO::FETCH_ASSOC)){echo json_encode($r,JSON_UNESCAPED_UNICODE).PHP_EOL;}