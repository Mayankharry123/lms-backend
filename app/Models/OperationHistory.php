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

    /**
     * Relationship: An operation history belongs to an operation.
     */
    public function operation()
    {
        return $this->belongsTo(Operation::class, 'operation_id');
    }

    /**
     * Relationship: An operation history belongs to a brief.
     */
    public function brief()
    {
        return $this->belongsTo(Brief::class, 'brief_id');
    }

    /**
     * Relationship: An operation history belongs to a planner.
     */
    public function planner()
    {
        return $this->belongsTo(Planner::class, 'planner_id');
    }

    /**
     * Relationship: An operation history has an operation status.
     */
    public function operationStatus()
    {
        return $this->belongsTo(OperationStatus::class, 'operation_status_id');
    }

    /**
     * Relationship: User who assigned or updated the record.
     */
    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assign_by');
    }

    /**
     * Relationship: User to whom the record is assigned.
     */
    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assign_to');
    }

    /**
     * Scope a query to only include active records.
     */
    public function scopeActive($query)
    {
        return $query->where('status', '1');
    }
}
