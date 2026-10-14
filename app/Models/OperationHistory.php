<?php

/**
 * Operation History Model
 * -----------------------------------------
 * Tracks status transitions, assignments, and audit logs for operations.
 *
 * @package App\Models
 * @version 1.0.0
 * @since 2026-10-06
 */

namespace App\Models;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

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
     * Relations to load with history payloads.
     *
     * @var array<int, string>
     */
    protected const RESPONSE_RELATIONS = [
        'brief:id,name',
        'operationStatus:id,name',
        'assignedBy:id,name',
        'assignedTo:id,name',
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

    /**
     * Paginate operation histories by criteria.
     *
     * @param array<string, mixed> $criteria
     */
    public function paginateHistories(array $criteria = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->newQuery()->with(self::RESPONSE_RELATIONS);

        if (!empty($criteria['operation_id'])) {
            $query->where('operation_id', (int) $criteria['operation_id']);
        }

        if (!empty($criteria['brief_id'])) {
            $query->where('brief_id', (int) $criteria['brief_id']);
        }

        if (!empty($criteria['planner_id'])) {
            $query->where('planner_id', (int) $criteria['planner_id']);
        }

        if (!empty($criteria['operation_status_id'])) {
            $query->where('operation_status_id', (int) $criteria['operation_status_id']);
        }

        if (!empty($criteria['assign_by'])) {
            $query->where('assign_by', (int) $criteria['assign_by']);
        }

        if (!empty($criteria['assign_to'])) {
            $query->where('assign_to', (int) $criteria['assign_to']);
        }

        if (isset($criteria['status']) && $criteria['status'] !== '') {
            $query->where('status', (string) $criteria['status']);
        }

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * Fetch histories for one operation.
     */
    public function getHistoriesByOperationId(int $operationId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->newQuery()
            ->with(self::RESPONSE_RELATIONS)
            ->where('operation_id', $operationId)
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * Find one history row by primary key.
     */
    public function findHistoryById(int $id): ?self
    {
        return $this->newQuery()
            ->with(self::RESPONSE_RELATIONS)
            ->find($id);
    }

    /**
     * Create and store an operation history record.
     *
     * @param array<string, mixed> $data
     */
    public function storeHistory(array $data): self
    {
        return $this->create($data);
    }
}
