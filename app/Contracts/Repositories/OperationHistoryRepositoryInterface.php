<?php

/**
 * Operation History Repository Interface
 * -----------------------------------------
 * Defines the contract for querying and storing operation histories.
 *
 * @package App\Contracts\Repositories
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-06
 */

namespace App\Contracts\Repositories;

use App\Models\OperationHistory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface OperationHistoryRepositoryInterface
{
    /**
     * List operation histories by criteria with pagination.
     *
     * @param array $criteria
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function paginate(array $criteria = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Get histories for a specific operation.
     *
     * @param int $operationId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getByOperationId(int $operationId, int $perPage = 15): LengthAwarePaginator;

    /**
     * Find an operation history by ID.
     */
    public function find(int $id): ?OperationHistory;

    /**
     * Create an operation history record.
     */
    public function create(array $data): OperationHistory;
}
