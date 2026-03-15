<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginAttempt extends Model
{
    public $timestamps = false;

    protected $fillable = ['ip_address', 'username', 'attempted_at'];

    protected $casts = [
        'attempted_at' => 'datetime',
    ];

    public static function isLocked(string $ip, int $maxAttempts = 5, int $lockoutMinutes = 10): bool
    {
        $count = static::where('ip_address', $ip)
            ->where('attempted_at', '>=', now()->subMinutes($lockoutMinutes))
            ->count();
        return $count >= $maxAttempts;
    }

    public static function record(string $ip, ?string $username = null): void
    {
        static::create([
            'ip_address'   => $ip,
            'username'     => $username,
            'attempted_at' => now(),
        ]);
    }

    public static function clearOld(int $olderThanMinutes = 60): void
    {
        static::where('attempted_at', '<', now()->subMinutes($olderThanMinutes))->delete();
    }
}
