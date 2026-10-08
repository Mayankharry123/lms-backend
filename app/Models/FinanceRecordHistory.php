<?php

/**
 * Finance Record History Model
 * -----------------------------------------
 * Tracks status transitions, assignments, cost sheet updates, and audit logs for finance records.
 *
 * @package App\Models
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-06
 */

namespace App\Models;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class FinanceRecordHistory extends BaseModel
{
    protected $table = 'finance_record_histories';

    protected $fillable = [
        'uuid',
        'finance_record_id',
        'brief_id',
        'planner_id',
        'finance_status_id',
        'cost_sheet',
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
     * Relationship: A finance record history belongs to a finance record.
     */
    public function financeRecord()
    {
        return $this->belongsTo(FinanceRecord::class, 'finance_record_id');
    }

    /**
     * Relationship: A finance record history belongs to a brief.
     */
    public function brief()
    {
        return $this->belongsTo(Brief::class, 'brief_id');
    }

    /**
     * Relationship: A finance record history belongs to a planner.
     */
    public function planner()
    {
        return $this->belongsTo(Planner::class, 'planner_id');
    }

    /**
     * Relationship: A finance record history has a finance status.
     */
    public function financeStatus()
    {
        return $this->belongsTo(FinanceStatus::class, 'finance_status_id');
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
     * Relations to load with history payloads.
     *
     * @var array<int, string>
     */
    protected const RESPONSE_RELATIONS = [
        'brief:id,name',
        'financeStatus:id,name',
        'assignedBy:id,name',
        'assignedTo:id,name',
    ];

    /**
     * Paginate finance record histories by criteria.
     */
    public function paginateHistories(array $criteria = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->newQuery()->with(self::RESPONSE_RELATIONS);

        if (!empty($criteria['finance_record_id'])) {
            $query->where('finance_record_id', $criteria['finance_record_id']);
        }

        if (!empty($criteria['brief_id'])) {
            $query->where('brief_id', $criteria['brief_id']);
        }

        if (!empty($criteria['planner_id'])) {
            $query->where('planner_id', $criteria['planner_id']);
        }

        if (!empty($criteria['finance_status_id'])) {
            $query->where('finance_status_id', $criteria['finance_status_id']);
        }

        if (!empty($criteria['assign_by'])) {
            $query->where('assign_by', $criteria['assign_by']);
        }

        if (!empty($criteria['assign_to'])) {
            $query->where('assign_to', $criteria['assign_to']);
        }

        if (isset($criteria['status']) && $criteria['status'] !== '') {
            $query->where('status', $criteria['status']);
        }

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * Get histories for a specific finance record.
     */
    public function getHistoriesByFinanceRecordId(int $financeRecordId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->newQuery()
            ->with(self::RESPONSE_RELATIONS)
            ->where('finance_record_id', $financeRecordId)
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * Find a history record by ID.
     */
    public function findHistoryById(int $id): ?self
    {
        return $this->newQuery()
            ->with(self::RESPONSE_RELATIONS)
            ->find($id);
    }

    /**
     * Create and return a new history record.
     */
    public function storeHistory(array $data): self
    {
        return $this->create($data);
    }
}
