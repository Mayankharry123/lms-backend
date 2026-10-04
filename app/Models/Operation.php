<?php

/**
 * Operation Model
 * -----------------------------------------
 * Represents the operations table, which links a brief and planner
 * to an operation status and the users who assigned and received it.
 *
 * @package App\Models
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-04
 */

namespace App\Models;

class Operation extends BaseModel
{
    protected $table = 'operations';

    protected $fillable = [
        'uuid',
        'brief_id',
        'planner_id',
        'operation_status_id',
        'assign_by',
        'assign_to',
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

    public function brief()
    {
        return $this->belongsTo(Brief::class, 'brief_id');
    }

    public function planner()
    {
        return $this->belongsTo(Planner::class, 'planner_id');
    }

    public function operationStatus()
    {
        return $this->belongsTo(OperationStatus::class, 'operation_status_id');
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assign_by');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assign_to');
    }

    public function scopeActive($query)
    {
        return $query->where('status', '1');
    }
}
