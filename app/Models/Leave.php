<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Leave extends Model
{
    protected $fillable = [
        'employee_id', 'leave_type', 'start_date', 'end_date',
        'reason', 'status', 'approved_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function approvedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'approved_by');
    }

    public static function hasOverlapping(int $employeeId, string $startDate, string $endDate, ?int $excludeId = null): bool
    {
        $query = static::where('employee_id', $employeeId)
            ->where('status', '!=', 'rejected')
            ->where('start_date', '<=', $endDate)
            ->where('end_date', '>=', $startDate);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    public static function isOnLeaveToday(int $employeeId): bool
    {
        $today = now()->toDateString();
        return static::where('employee_id', $employeeId)
            ->where('status', 'approved')
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->exists();
    }
}
