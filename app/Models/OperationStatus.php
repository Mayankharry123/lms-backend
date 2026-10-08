<?php
/**
 * OperationStatus
 * -----------------------------------------
 * This model represents the operation_statuses table,
 * which stores the statuses of operations.
 *
 * @package App\Models
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-03
 */
namespace App\Models;

class OperationStatus extends BaseModel
{
    protected $table = 'operation_statuses';

    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
}
