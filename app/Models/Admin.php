<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Admin extends Authenticatable
{
    use HasApiTokens, HasFactory;

    protected $fillable = [
        'username', 'password_hash', 'full_name', 'last_login',
        'two_factor_enabled', 'two_factor_secret', 'two_factor_recovery_codes', 'email',
    ];

    protected $hidden = ['password_hash', 'two_factor_secret', 'two_factor_recovery_codes'];

    protected $casts = [
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
}
