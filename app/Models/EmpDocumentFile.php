<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmpDocumentFile extends Model
{
    protected $table = 'emp_document_files';

    protected $fillable = [
        'group_id', 'file_path', 'file_type', 'original_name', 'file_size', 'sort_order',
    ];

    protected $casts = [
        'file_size'  => 'integer',
        'sort_order' => 'integer',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(EmpDocumentGroup::class, 'group_id');
    }
}
