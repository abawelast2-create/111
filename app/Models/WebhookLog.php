<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'webhook_id', 'event', 'payload', 'response_code',
        'response_body', 'success', 'duration_ms', 'created_at',
    ];

    protected $casts = [
        'payload'    => 'array',
        'success'    => 'boolean',
        'created_at' => 'datetime',
    ];

    public function webhook(): BelongsTo
    {
        return $this->belongsTo(Webhook::class);
    }
}
