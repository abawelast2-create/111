<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportSchedule extends Model
{
    protected $fillable = [
        'name', 'report_type', 'frequency', 'send_time', 'send_day',
        'recipients', 'filters', 'is_active', 'created_by', 'last_sent_at',
    ];

    protected $casts = [
        'recipients'   => 'array',
        'filters'      => 'array',
        'is_active'    => 'boolean',
        'last_sent_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function isDueNow(): bool
    {
        $now = now();
        $currentTime = $now->format('H:i');

        if ($currentTime !== $this->send_time) {
            return false;
        }

        return match ($this->frequency) {
            'daily'   => true,
            'weekly'  => strtolower($now->englishDayOfWeek) === strtolower($this->send_day ?? 'sunday'),
            'monthly' => $now->day === (int) ($this->send_day ?? 1),
            default   => false,
        };
    }
}
