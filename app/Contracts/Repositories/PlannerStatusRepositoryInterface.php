<?php
/**
 * Interface PlannerStatusRepositoryInterface
 * 
 * @author Achal Sharma
 * @package App\Contracts\Repositories
 * @version 1.0.1
 */
namespace App\Contracts\Repositories;

use App\Models\PlannerStatus;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PlannerStatusRepositoryInterface
{
    /**
     * Get all planner statuses with pagination
     *
     * @param int $perPage
     * @return LengthAwarePaginator<PlannerStatus>
     */
    public function all(int $perPage = 15): LengthAwarePaginator;

    /**
     * Find a planner status by ID
     */
    public function find(int $id): ?PlannerStatus;

    /**
     * Find a planner status by UUID
     */
    public function findByUuid(string $uuid): ?PlannerStatus;

    /**
     * Find a planner status by name
     */
    public function findByName(string $name): ?PlannerStatus;

    /**
     * Find a planner status by slug
     */
    public function findBySlug(string $slug): ?PlannerStatus;

    /**
     * Create a new planner status
     */
    public function create(array $data): PlannerStatus;

    /**
     * Update a planner status by ID
     */
    public function update(int $id, array $data): bool;

    /**
     * Delete a planner status by ID
     */
    public function delete(int $id): bool;

    /**
     * Search planner statuses by criteria with pagination
     */
    public function search(array $criteria, int $perPage = 15): LengthAwarePaginator;
}
