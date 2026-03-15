<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LocationLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'employee_id', 'latitude', 'longitude', 'accuracy',
        'speed', 'ip_address', 'is_suspicious', 'suspicion_reason', 'recorded_at',
    ];

    protected $casts = [
        'latitude'      => 'decimal:8',
        'longitude'     => 'decimal:8',
        'accuracy'      => 'decimal:2',
        'speed'         => 'decimal:2',
        'is_suspicious' => 'boolean',
        'recorded_at'   => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
