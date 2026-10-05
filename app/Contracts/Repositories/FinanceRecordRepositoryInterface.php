<?php

/**
 * FinanceRecord Repository Interface
 * -----------------------------------------
 * Defines the contract for finance record lookups and cost sheet storage.
 *
 * @package App\Contracts\Repositories
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-04
 */

namespace App\Contracts\Repositories;

use App\Models\FinanceRecord;
use App\Models\Planner;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface FinanceRecordRepositoryInterface
{
    /**
     * Get finance records with pagination
     */
    public function paginate(array $criteria = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Find a finance record by ID
     */
    public function find(int $id): ?FinanceRecord;

    /**
     * Find a planner by ID with its status
     */
    public function findPlannerById(int $plannerId): ?Planner;

    /**
     * Find the active finance record for a brief and planner
     */
    public function findActiveByBriefAndPlanner(int $briefId, int $plannerId): ?FinanceRecord;

    /**
     * Create a finance record
     */
    public function create(array $data): FinanceRecord;

    /**
     * Replace the cost sheet path on a finance record
     */
    public function updateCostSheet(int $id, string $path, ?int $assignBy): ?FinanceRecord;

    /**
     * Paginate cost sheets for the list API.
     */
    public function paginateCostSheets(array $criteria = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Find one cost sheet for the detail API.
     */
    public function findCostSheet(int $id): ?FinanceRecord;

    /**
     * Update editable cost sheet fields.
     */
    public function updateRecord(int $id, array $data): ?FinanceRecord;

    /**
     * Soft delete a cost sheet.
     */
    public function deleteRecord(int $id): bool;

    /**
     * Whether another active cost sheet remains for this brief.
     */
    public function hasOtherActiveCostSheet(int $briefId, int $exceptId): bool;
}
