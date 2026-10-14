<?php

/**
 * OperationStatus Repository Interface
 * -----------------------------------------
 * Defines the contract for OperationStatus repository operations, including CRUD and pagination methods.
 *
 * @package App\Contracts\Repositories
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-03
 */

namespace App\Contracts\Repositories;

use App\Models\OperationStatus;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface OperationStatusRepositoryInterface
{
    /**
     * Get all operation statuses with pagination
     *
     * @param int $perPage
     * @return LengthAwarePaginator<OperationStatus>
     */
    public function all(int $perPage = 15): LengthAwarePaginator;

    /**
     * Find an operation status by ID
     */
    public function find(int $id): ?OperationStatus;

    /**
     * Find an operation status by UUID
     */
    public function findByUuid(string $uuid): ?OperationStatus;

    /**
     * Find an operation status by name
     */
    public function findByName(string $name): ?OperationStatus;

    /**
     * Find an operation status by slug
     */
    public function findBySlug(string $slug): ?OperationStatus;

    /**
     * Create a new operation status
     */
    public function create(array $data): OperationStatus;

    /**
     * Update an operation status by ID
     */
    public function update(int $id, array $data): bool;

    /**
     * Soft delete an operation status by ID
     */
    public function delete(int $id): bool;

    /**
     * Search operation statuses by criteria with pagination
     */
    public function search(array $criteria, int $perPage = 15): LengthAwarePaginator;
}
