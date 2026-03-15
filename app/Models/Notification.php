<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'type', 'notifiable_type', 'notifiable_id',
        'title', 'body', 'data', 'channel',
        'read_at', 'sent_at',
    ];

    protected $casts = [
        'data'    => 'array',
        'read_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function notifiable()
    {
        return $this->morphTo();
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function scopeForUser($query, string $type, int $id)
    {
        return $query->where('notifiable_type', $type)->where('notifiable_id', $id);
    }

    public function markAsRead(): void
    {
        $this->update(['read_at' => now()]);
    }
}
