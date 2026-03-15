<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    public $timestamps = false;

    protected $fillable = ['setting_key', 'setting_value', 'description'];

    protected static ?array $cache = null;

    public static function loadAll(bool $refresh = false): array
    {
        if (static::$cache === null || $refresh) {
            try {
                static::$cache = static::pluck('setting_value', 'setting_key')->toArray();
            } catch (\Exception $e) {
                static::$cache = [];
            }
        }
        return static::$cache;
    }

    public static function getValue(string $key, string $default = ''): string
    {
        $cache = static::loadAll();
        return $cache[$key] ?? $default;
    }

    public static function setValue(string $key, string $value): void
    {
        static::updateOrCreate(
            ['setting_key' => $key],
            ['setting_value' => $value]
        );
        static::loadAll(true);
    }
}
