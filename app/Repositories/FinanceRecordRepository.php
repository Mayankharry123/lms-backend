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
use App\Models\Brief;
use App\Models\FinanceRecord;
use App\Models\Planner;

class FinanceRecordRepository implements FinanceRecordRepositoryInterface
{
    protected FinanceRecord $model;
    protected Brief $brief;
    protected Planner $planner;

    /**
     * Inject the models that own the finance record queries.
     */
    public function __construct(FinanceRecord $model, Brief $brief, Planner $planner)
    {
        $this->model = $model;
        $this->brief = $brief;
        $this->planner = $planner;
    }

    /**
     * Check whether a brief exists
     */
    public function briefExists(int $briefId): bool
    {
        return $this->brief->existsById($briefId);
    }

    /**
     * Find the latest approved planner for a brief
     */
    public function findApprovedPlannerByBriefId(int $briefId): ?Planner
    {
        return $this->planner->findLatestApprovedByBriefId($briefId);
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
