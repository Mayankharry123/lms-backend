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

interface FinanceRecordRepositoryInterface
{
    /**
     * Check whether a brief exists
     */
    public function briefExists(int $briefId): bool;

    /**
     * Find the latest approved planner for a brief
     */
    public function findApprovedPlannerByBriefId(int $briefId): ?Planner;

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
}
