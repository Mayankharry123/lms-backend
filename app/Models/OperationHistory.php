<?php

/**
 * Operation History Model
 * -----------------------------------------
 * Tracks status transitions, assignments, and audit logs for operations.
 *
 * @package App\Models
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-06
 */

namespace App\Models;

class OperationHistory extends BaseModel
{
    protected $table = 'operation_histories';

    protected $fillable = [
        'uuid',
        'operation_id',
        'brief_id',
        'planner_id',
        'operation_status_id',
        'assign_by',
        'assign_to',
        'comment',
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

    public function operation()
    {
        return $this->belongsTo(Operation::class, 'operation_id');
    }

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
