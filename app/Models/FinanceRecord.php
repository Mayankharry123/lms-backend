<?php

/**
 * FinanceRecord Model
 * -----------------------------------------
 * Represents the finance_records table, which links a brief and planner
 * to a finance status and the users who assigned and received it.
 *
 * @package App\Models
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-04
 */

namespace App\Models;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class FinanceRecord extends BaseModel
{
    protected $table = 'finance_records';

    protected $fillable = [
        'uuid',
        'brief_id',
        'planner_id',
        'finance_status_id',
        'cost_sheet',
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

    public function financeStatus()
    {
        return $this->belongsTo(FinanceStatus::class, 'finance_status_id');
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assign_by');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assign_to');
    }

    /**
     * Latest purchase order raised from this cost sheet.
     */
    public function latestPurchaseOrder()
    {
        return $this->hasOne(PurchaseOrder::class, 'finance_record_id')->latestOfMany();
    }

    public function scopeActive($query)
    {
        return $query->where('status', '1');
    }

    /**
     * Relations returned with a cost sheet upload.
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
     * Relations returned by the cost sheet list and detail APIs.
     *
     * @var array<int, string>
     */
    protected const COST_SHEET_RELATIONS = [
        'brief:id,name,cost_sheet_status_id',
        'brief.costSheetStatus:id,name,slug',
        'planner:id,created_by',
        'planner.creator:id,name',
        'financeStatus:id,name',
        'assignedBy:id,name',
        'assignedTo:id,name',
        'latestPurchaseOrder',
    ];

    /**
     * Get finance records with pagination.
     */
    public function paginateRecords(array $criteria = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->newQuery()->with(self::RESPONSE_RELATIONS);

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
     * Find a finance record by ID.
     */
    public function findRecordById(int $id): ?self
    {
        return $this->newQuery()->with(self::RESPONSE_RELATIONS)->find($id);
    }

    /**
     * Find the active finance record for a brief and planner.
     */
    public function findActiveByBriefAndPlanner(int $briefId, int $plannerId): ?self
    {
        return $this->newQuery()
            ->where('brief_id', $briefId)
            ->where('planner_id', $plannerId)
            ->active()
            ->latest('id')
            ->first();
    }

    /**
     * Create a finance record and load the response relations.
     */
    public function storeRecord(array $data): self
    {
        return $this->create($data)->load(self::RESPONSE_RELATIONS);
    }

    /**
     * Replace the cost sheet path on a finance record.
     */
    public function replaceCostSheet(int $id, string $path, ?int $assignBy): ?self
    {
        $financeRecord = $this->newQuery()->find($id);

        if (!$financeRecord) {
            return null;
        }

        $financeRecord->update([
            'cost_sheet' => $path,
            'assign_by' => $assignBy,
        ]);

        return $financeRecord->refresh()->load(self::RESPONSE_RELATIONS);
    }

    /**
     * Paginate cost sheets with the list relations.
     */
    public function paginateCostSheets(array $criteria = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->newQuery()->with(self::COST_SHEET_RELATIONS);

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

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * Find one cost sheet with the list relations.
     */
    public function findCostSheetById(int $id): ?self
    {
        return $this->newQuery()->with(self::COST_SHEET_RELATIONS)->find($id);
    }

    /**
     * Update the editable cost sheet fields.
     */
    public function updateCostSheetRecord(int $id, array $data): ?self
    {
        $financeRecord = $this->newQuery()->find($id);

        if (!$financeRecord) {
            return null;
        }

        $financeRecord->update($data);

        return $financeRecord->refresh()->load(self::COST_SHEET_RELATIONS);
    }

    /**
     * Soft delete a cost sheet and mark it deleted.
     */
    public function softDeleteRecord(int $id): bool
    {
        $financeRecord = $this->newQuery()->find($id);

        if (!$financeRecord) {
            return false;
        }

        $financeRecord->status = '15';
        $financeRecord->save();

        return (bool) $financeRecord->delete();
    }

    /**
     * Whether another active cost sheet remains for this brief.
     */
    public function hasOtherActiveCostSheet(int $briefId, int $exceptId): bool
    {
        return $this->newQuery()
            ->where('brief_id', $briefId)
            ->where('id', '!=', $exceptId)
            ->where('status', '1')
            ->whereNotNull('cost_sheet')
            ->where('cost_sheet', '!=', '')
            ->exists();
    }
}
