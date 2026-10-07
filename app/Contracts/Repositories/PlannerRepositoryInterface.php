<?php

namespace App\Contracts\Repositories;

use App\Models\Planner;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PlannerRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Fetch paginated list of planners with relationships.
     *
     * @param int $perPage The number of items per page.
     * @param array $filters Optional filters to apply.
     * @return LengthAwarePaginator
     */
    public function getAllPlanners(int $perPage = 10, array $filters = []): LengthAwarePaginator;

    /**
     * Get planner by ID with relationships.
     *
     * @param int $id
     * @return Planner|null
     */
    public function getPlannerById(int $id): ?Planner;

    /**
     * Get planners by brief ID.
     *
     * @param int $briefId
     * @param int $perPage
     * @param string|null $status
     * @return LengthAwarePaginator
     */
    public function getPlannersByBriefId(int $briefId, int $perPage = 10, ?string $status = null): LengthAwarePaginator;

    /**
     * Get planners by user (creator).
     *
     * @param int $userId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPlannersByCreator(int $userId, int $perPage = 10): LengthAwarePaginator;

    /**
     * Create a new planner.
     *
     * @param array $data
     * @return Planner
     */
    public function createPlanner(array $data): Planner;

    /**
     * Update planner.
     *
     * @param int $id
     * @param array $data
     * @return Planner|null
     */
    public function updatePlanner(int $id, array $data): ?Planner;

    /**
     * Delete planner.
     *
     * @param int $id
     * @return bool
     */
    public function deletePlanner(int $id): bool;

    /**
     * Get active planners.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getActivePlanners(int $perPage = 10): LengthAwarePaginator;

    /**
     * Count planners by brief ID.
     *
     * @param int $briefId
     * @return int
     */
    public function countByBriefId(int $briefId): int;

    /**
     * Check if planner exists by UUID.
     *
     * @param string $uuid
     * @return bool
     */
    public function existsByUuid(string $uuid): bool;

    /**
     * Get planner by UUID.
     *
     * @param string $uuid
     * @return Planner|null
     */
    public function getPlannerByUuid(string $uuid): ?Planner;

    /**
     * Get approved backup plan for a brief.
     *
     * @param int $briefId
     * @param mixed $user
     * @return Planner|null
     */
    public function getApprovedBackupPlanByBriefId(int $briefId, $user = null): ?Planner;
}
