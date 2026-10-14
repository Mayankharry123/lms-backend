<?php

/**
 * FinanceRecord Repository
 * -----------------------------------------
 * Data access for finance records and the approved planner required before a cost sheet upload.
 *
 * @package App\Repositories
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-04
 */

namespace App\Repositories;

use App\Contracts\Repositories\FinanceRecordRepositoryInterface;
use App\Models\FinanceRecord;
use App\Models\Planner;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class FinanceRecordRepository implements FinanceRecordRepositoryInterface
{
    protected FinanceRecord $model;
    protected Planner $planner;

    /**
     * Inject the models that own the finance record queries.
     */
    public function __construct(FinanceRecord $model, Planner $planner)
    {
        $this->model = $model;
        $this->planner = $planner;
    }

    /**
     * Get finance records with pagination
     */
    public function paginate(array $criteria = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->paginateRecords($criteria, $perPage, auth()->user());
    }

    /**
     * Find a finance record by ID
     */
    public function find(int $id): ?FinanceRecord
    {
        return $this->model->findRecordById($id, auth()->user());
    }

    /**
     * Find a planner by ID with its status
     */
    public function findPlannerById(int $plannerId): ?Planner
    {
        return $this->planner->findByIdWithStatus($plannerId);
    }

    /**
     * Find the active finance record for a brief and planner
     */
    public function findActiveByBriefAndPlanner(int $briefId, int $plannerId): ?FinanceRecord
    {
        return $this->model->findActiveByBriefAndPlanner($briefId, $plannerId);
    }

    /**
     * Create a finance record
     */
    public function create(array $data): FinanceRecord
    {
        return $this->model->storeRecord($data);
    }

    /**
     * Replace the cost sheet path on a finance record
     */
    public function updateCostSheet(int $id, string $path, ?int $assignBy, ?int $assignTo = null, ?string $comment = null): ?FinanceRecord
    {
        return $this->model->replaceCostSheet($id, $path, $assignBy, $assignTo, $comment);
    }

    /**
     * Paginate cost sheets for the list API.
     */
    public function paginateCostSheets(array $criteria = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->paginateCostSheets($criteria, $perPage, auth()->user());
    }

    /**
     * Find one cost sheet for the detail API.
     */
    public function findCostSheet(int $id): ?FinanceRecord
    {
        return $this->model->findCostSheetById($id, auth()->user());
    }

    /**
     * Update editable cost sheet fields.
     */
    public function updateRecord(int $id, array $data, ?string $comment = null): ?FinanceRecord
    {
        return $this->model->updateCostSheetRecord($id, $data, $comment);
    }

    /**
     * Soft delete a cost sheet.
     */
    public function deleteRecord(int $id): bool
    {
        return $this->model->softDeleteRecord($id);
    }

    /**
     * Whether another active cost sheet remains for this brief.
     */
    public function hasOtherActiveCostSheet(int $briefId, int $exceptId): bool
    {
        return $this->model->hasOtherActiveCostSheet($briefId, $exceptId);
    }
}
