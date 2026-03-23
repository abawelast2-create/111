<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPreference extends Model
{
    public $timestamps = false;

    protected $table = 'user_preferences';

    protected $fillable = [
        'admin_id', 'pref_key', 'pref_value',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    public static function setValue(int $adminId, string $key, ?string $value): void
    {
        static::updateOrCreate(
            ['admin_id' => $adminId, 'pref_key' => $key],
            ['pref_value' => $value]
        );
    }

    public static function getValue(int $adminId, string $key, ?string $default = null): ?string
    {
        return static::where('admin_id', $adminId)->where('pref_key', $key)->value('pref_value') ?? $default;
    }
}
