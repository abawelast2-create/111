<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmpDocumentGroup extends Model
{
    protected $table = 'emp_document_groups';

    protected $fillable = [
        'employee_id', 'group_name', 'expiry_date', 'sort_order',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'sort_order'  => 'integer',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(EmpDocumentFile::class, 'group_id')->orderBy('sort_order')->orderBy('id');
    }

    public function getDaysLeftAttribute(): int
    {
        $today = today();
        if (!$this->expiry_date) {
            return 0;
        }

        return (int) $today->diffInDays($this->expiry_date, false);
    }
}
