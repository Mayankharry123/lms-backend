<?php

/**
 * CostSheetStatus Repository Interface
 * -----------------------------------------
 * Defines the contract for CostSheetStatus repository operations, including CRUD and pagination methods.
 *
 * @package App\Contracts\Repositories
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-05
 */

namespace App\Contracts\Repositories;

use App\Models\CostSheetStatus;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CostSheetStatusRepositoryInterface
{
    /**
     * Get all cost sheet statuses with pagination.
     *
     * @param int $perPage
     * @return LengthAwarePaginator<CostSheetStatus>
     */
    public function all(int $perPage = 15): LengthAwarePaginator;

    /**
     * Find a cost sheet status by ID.
     */
    public function find(int $id): ?CostSheetStatus;

    /**
     * Find a cost sheet status by UUID.
     */
    public function findByUuid(string $uuid): ?CostSheetStatus;

    /**
     * Find a cost sheet status by name.
     */
    public function findByName(string $name): ?CostSheetStatus;

    /**
     * Find a cost sheet status by slug.
     */
    public function findBySlug(string $slug): ?CostSheetStatus;

    /**
     * Find the first active cost sheet status.
     */
    public function findFirstActive(): ?CostSheetStatus;

    /**
     * Create a new cost sheet status.
     */
    public function create(array $data): CostSheetStatus;

    /**
     * Update a cost sheet status by ID.
     */
    public function update(int $id, array $data): bool;

    /**
     * Soft delete a cost sheet status by ID.
     */
    public function delete(int $id): bool;

    /**
     * Search cost sheet statuses by criteria with pagination.
     */
    public function search(array $criteria, int $perPage = 15): LengthAwarePaginator;
}
