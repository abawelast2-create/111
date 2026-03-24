<?php

namespace App\Providers;

use App\Services\AdminPermissionService;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Blade::if('adminCan', function (string $permissionKey) {
            return AdminPermissionService::currentCan($permissionKey);
        });
    }
}
