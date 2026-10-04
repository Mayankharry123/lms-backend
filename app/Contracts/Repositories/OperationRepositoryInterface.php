<?php

/**
 * Operation Repository Interface
 * -----------------------------------------
 * Defines the contract for listing operations with filters and pagination.
 *
 * @package App\Contracts\Repositories
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-04
 */

namespace App\Contracts\Repositories;

use App\Models\Operation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface OperationRepositoryInterface
{
    /**
     * List operations by criteria with pagination.
     *
     * @param array $criteria
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function paginate(array $criteria = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Find an operation by ID with the list relations.
     */
    public function find(int $id): ?Operation;

    /**
     * Whether a non-deleted operation already exists for the planner.
     */
    public function existsForPlanner(int $plannerId): bool;

    /**
     * Create an operation.
     */
    public function create(array $data): Operation;

    /**
     * Update the operation status for an operation.
     */
    public function updateStatus(int $id, int $operationStatusId): ?Operation;

    /**
     * Soft delete an operation and mark its status as 15.
     */
    public function delete(int $id): bool;
}
