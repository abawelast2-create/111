<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TamperingCase extends Model
{
    protected $fillable = [
        'employee_id', 'case_type', 'description',
        'attendance_date', 'severity', 'details_json',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'details_json'    => 'array',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
