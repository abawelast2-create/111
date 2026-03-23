<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '/tmp/emp_debug.log');

$_SERVER['HTTP_HOST'] = 'mycorner.site';
$_SERVER['HTTPS'] = 'on';
$_SERVER['SCRIPT_NAME'] = '/attendance-system/employee/attendance.php';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['token'] = '07dd1b9ed625ee20de3ee38f345d8efb75c3d5b6677882b4f57c98a3a568f7f4';

ob_start();
try {
    require __DIR__ . '/employee/attendance.php';
    $out = ob_get_clean();
    echo "OUTPUT_SIZE=" . strlen($out) . "\n";
    echo "FIRST_100=" . substr($out, 0, 100) . "\n";
} catch (Throwable $e) {
    ob_end_clean();
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "FILE: " . $e->getFile() . "\n";
    echo "LINE: " . $e->getLine() . "\n";
    echo "TRACE: " . $e->getTraceAsString() . "\n";
}
