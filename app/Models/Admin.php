<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Services\AdminPermissionService;

class Admin extends Authenticatable
{
    use HasApiTokens, HasFactory;

    protected $fillable = [
        'username', 'password_hash', 'full_name', 'last_login',
        'is_super_admin',
        'two_factor_enabled', 'two_factor_secret', 'two_factor_recovery_codes', 'email',
    ];

    protected $hidden = ['password_hash', 'two_factor_secret', 'two_factor_recovery_codes'];

    protected $casts = [
        'is_super_admin'      => 'boolean',
        'last_login'         => 'datetime',
        'two_factor_enabled' => 'boolean',
    ];

    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    public function preferences(): HasMany
    {
        return $this->hasMany(UserPreference::class, 'admin_id');
    }

    public function permissionGroups(): BelongsToMany
    {
        return $this->belongsToMany(PermissionGroup::class, 'admin_permission_group')
            ->withTimestamps();
    }

    public function canAccess(string $permissionKey): bool
    {
        return AdminPermissionService::adminCan($this, $permissionKey);
    }
}
