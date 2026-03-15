<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KnownDevice extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'fingerprint', 'employee_id', 'usage_count',
        'first_used_at', 'last_used_at',
    ];

    protected $casts = [
        'first_used_at' => 'datetime',
        'last_used_at'  => 'datetime',
        'usage_count'   => 'integer',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
