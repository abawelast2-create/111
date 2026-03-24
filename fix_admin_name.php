<?php
require "/var/www/attendance/vendor/autoload.php";
$app = require "/var/www/attendance/bootstrap/app.php";
$app->make("Illuminate\Contracts\Console\Kernel")->bootstrap();

// Use UNHEX to avoid encoding issues over SSH
DB::statement("UPDATE admins SET full_name = CONVERT(UNHEX('D985D8AFD98AD8B1D98020D8A7D984D986D8B8D8A7D985') USING utf8mb4) WHERE id = 1");

$admin = DB::table("admins")->where("id", 1)->first();
$hex = bin2hex($admin->full_name);
echo "hex: " . $hex . "\n";
echo "is_super: " . $admin->is_super_admin . "\n";
echo "OK\n";
