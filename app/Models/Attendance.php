<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $fillable = [
        'employee_id', 'type', 'timestamp', 'attendance_date',
        'late_minutes', 'latitude', 'longitude', 'location_accuracy',
        'mock_location_detected', 'validation_score', 'wifi_networks', 'ip_location_match',
        'ip_address', 'user_agent', 'notes',
    ];

    protected $casts = [
        'timestamp'               => 'datetime',
        'attendance_date'         => 'date',
        'late_minutes'            => 'integer',
        'latitude'                => 'decimal:8',
        'longitude'               => 'decimal:8',
        'location_accuracy'       => 'decimal:2',
        'validation_score'        => 'decimal:2',
        'mock_location_detected'  => 'boolean',
        'ip_location_match'       => 'boolean',
        'wifi_networks'           => 'array',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public static function hasRecentRecord(int $employeeId, string $type, int $minutes = 5): bool
    {
        return static::where('employee_id', $employeeId)
            ->where('type', $type)
            ->where('timestamp', '>=', now()->subMinutes($minutes))
            ->exists();
    }

    public static function getTodayStats(): array
    {
        $today = now()->toDateString();
        return [
            'checked_in'      => static::where('attendance_date', $today)->where('type', 'in')->distinct('employee_id')->count('employee_id'),
            'checked_out'     => static::where('attendance_date', $today)->where('type', 'out')->distinct('employee_id')->count('employee_id'),
            'total_employees' => Employee::where('is_active', true)->whereNull('deleted_at')->count(),
        ];
    }

    public function scopeForDate($query, string $date)
    {
        return $query->where('attendance_date', $date);
    }
}
