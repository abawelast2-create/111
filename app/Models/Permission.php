<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'permission_group_id',
        'permission_key',
        'name',
        'description',
        'depends_on',
        'is_system',
    ];

    protected $casts = [
        'depends_on' => 'array',
        'is_system' => 'boolean',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(PermissionGroup::class, 'permission_group_id');
    }
}
