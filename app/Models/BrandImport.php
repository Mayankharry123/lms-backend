<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BrandImport extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    protected $table = 'brand_imports';

    protected $fillable = [
        'original_filename',
        'stored_filename',
        'stored_path',
        'total_records',
        'processed_records',
        'created_records',
        'updated_records',
        'failed_records',
        'current_chunk',
        'error_message',
        'failed_details',
        'status',
        'created_by',
    ];

    protected $casts = [
        'total_records' => 'integer',
        'processed_records' => 'integer',
        'created_records' => 'integer',
        'updated_records' => 'integer',
        'failed_records' => 'integer',
        'current_chunk' => 'integer',
        'failed_details' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
