<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Shared hosting: app is at ~/domains/sarh.io/attendance
// public_html is at ~/domains/sarh.io/public_html

$appPath = __DIR__ . '/../attendance';

if (file_exists($maintenance = $appPath . '/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $appPath . '/vendor/autoload.php';

$app = require_once $appPath . '/bootstrap/app.php';

$app->bind('path.public', function () {
    return __DIR__;
});

$kernel = $app->make(Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);
