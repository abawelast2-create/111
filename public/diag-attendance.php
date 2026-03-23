<?php

declare(strict_types=1);

header('Content-Type: text/plain; charset=UTF-8');

function print_line(string $label, string $value): void
{
    echo $label . ': ' . $value . PHP_EOL;
}

function print_exception(Throwable $e): void
{
    echo 'ERROR: ' . $e->getMessage() . PHP_EOL;
    echo 'FILE: ' . $e->getFile() . ':' . $e->getLine() . PHP_EOL;
    echo 'TRACE:' . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
}

echo 'Attendance Diagnostic' . PHP_EOL;
echo str_repeat('=', 80) . PHP_EOL;
print_line('UTC', gmdate('Y-m-d H:i:s'));
print_line('PHP', PHP_VERSION);
print_line('SCRIPT', __FILE__);
echo str_repeat('-', 80) . PHP_EOL;

try {
    require __DIR__ . '/../vendor/autoload.php';
    $app = require __DIR__ . '/../bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    print_line('BOOT', 'OK');
} catch (Throwable $e) {
    print_line('BOOT', 'FAIL');
    print_exception($e);
    exit(1);
}

echo str_repeat('-', 80) . PHP_EOL;

try {
    $db = config('database.connections.mysql', []);
    print_line('DB_HOST', (string) ($db['host'] ?? ''));
    print_line('DB_PORT', (string) ($db['port'] ?? ''));
    print_line('DB_DATABASE', (string) ($db['database'] ?? ''));
    print_line('DB_USERNAME', (string) ($db['username'] ?? ''));

    Illuminate\Support\Facades\DB::connection()->getPdo();
    print_line('DB_CONNECT', 'OK');
} catch (Throwable $e) {
    print_line('DB_CONNECT', 'FAIL');
    print_exception($e);
    exit(1);
}

echo str_repeat('-', 80) . PHP_EOL;

try {
    $total = App\Models\Attendance::count();
    print_line('ATTENDANCE_COUNT', (string) $total);

    $last = App\Models\Attendance::with('employee.branch')->latest('id')->first();
    if ($last) {
        print_line('LAST_ATTENDANCE_ID', (string) $last->id);
        print_line('LAST_EMPLOYEE', (string) ($last->employee?->name ?? 'NULL_RELATION'));
        print_line('LAST_BRANCH', (string) ($last->employee?->branch?->name ?? 'NULL_RELATION'));
        print_line('LAST_TYPE', (string) $last->type);
        print_line('LAST_DATE', (string) $last->attendance_date);
    } else {
        print_line('LAST_ATTENDANCE', 'NONE');
    }
} catch (Throwable $e) {
    print_line('ATTENDANCE_QUERY', 'FAIL');
    print_exception($e);
    exit(1);
}

echo str_repeat('-', 80) . PHP_EOL;

try {
    $controller = app(App\Http\Controllers\Admin\AttendanceController::class);
    $request = Illuminate\Http\Request::create('/admin/attendance', 'GET', [
        'from' => date('Y-m-01'),
        'to' => date('Y-m-d'),
    ]);

    $response = $controller->index($request);
    print_line('CONTROLLER_INDEX', 'OK');
    print_line('RESPONSE_CLASS', get_class($response));
} catch (Throwable $e) {
    print_line('CONTROLLER_INDEX', 'FAIL');
    print_exception($e);
    exit(1);
}

echo str_repeat('-', 80) . PHP_EOL;
print_line('STATUS', 'ALL_CHECKS_PASSED');
