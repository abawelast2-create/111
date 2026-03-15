<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeHistory extends Model
{
    protected $table = 'employee_history';

    protected $fillable = [
        'employee_id', 'event_type', 'description',
        'old_value', 'new_value', 'changed_by', 'effective_date',
    ];

    protected $casts = [
        'effective_date' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function changedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'changed_by');
    }

    public static function record(int $employeeId, string $type, ?string $description = null, ?string $oldValue = null, ?string $newValue = null, ?string $effectiveDate = null): self
    {
        return static::create([
            'employee_id'    => $employeeId,
            'event_type'     => $type,
            'description'    => $description,
            'old_value'      => $oldValue,
            'new_value'      => $newValue,
            'changed_by'     => session('admin_id'),
            'effective_date' => $effectiveDate ?? today()->toDateString(),
        ]);
    }
}
