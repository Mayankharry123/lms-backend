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
        return $this->model->paginateRecords($criteria, $perPage);
    }

    /**
     * Find a finance record by ID
     */
    public function find(int $id): ?FinanceRecord
    {
        return $this->model->findRecordById($id);
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
    public function updateCostSheet(int $id, string $path, ?int $assignBy): ?FinanceRecord
    {
        return $this->model->replaceCostSheet($id, $path, $assignBy);
    }
}
