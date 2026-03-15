<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    public $timestamps = false;
    protected $table = 'audit_log';

    protected $fillable = [
        'admin_id', 'action', 'details', 'target_id', 'ip_address', 'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public static function record(string $action, string $details = '', ?int $targetId = null): void
    {
        try {
            static::create([
                'admin_id'   => session('admin_id'),
                'action'     => $action,
                'details'    => $details,
                'target_id'  => $targetId,
                'ip_address' => request()->ip(),
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            // silently fail
        }
    }
}
