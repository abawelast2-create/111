<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecretReport extends Model
{
    protected $fillable = [
        'employee_id', 'report_text', 'report_type',
        'image_paths', 'has_image', 'image_path',
        'has_voice', 'voice_path', 'voice_effect',
        'status', 'admin_notes', 'reviewed_at', 'reviewed_by',
    ];

    protected $casts = [
        'image_paths' => 'array',
        'has_image'   => 'boolean',
        'has_voice'   => 'boolean',
        'reviewed_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
