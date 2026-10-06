<?php

/**
 * Operation History Service
 * -----------------------------------------
 * Handles business logic for querying and managing operation histories.
 *
 * @package App\Services
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-06
 */

namespace App\Services;

use App\Contracts\Repositories\OperationHistoryRepositoryInterface;
use App\Models\OperationHistory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Throwable;

class OperationHistoryService
{
    protected OperationHistoryRepositoryInterface $repository;

    public function __construct(OperationHistoryRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get paginated operation histories with optional criteria.
     *
     * @throws Throwable
     */
    public function list(array $criteria = [], int $perPage = 15): LengthAwarePaginator
    {
        try {
            return $this->repository->paginate($criteria, $perPage);
        } catch (Throwable $e) {
            Log::error('Error fetching operation histories list', ['criteria' => $criteria, 'exception' => $e]);
            throw $e;
        }
    }

    /**
     * Get histories for a specific operation.
     *
     * @throws Throwable
     */
    public function getByOperationId(int $operationId, int $perPage = 15): LengthAwarePaginator
    {
        try {
            return $this->repository->getByOperationId($operationId, $perPage);
        } catch (Throwable $e) {
            Log::error('Error fetching operation histories by operation ID', [
                'operation_id' => $operationId,
                'exception' => $e,
            ]);
            throw $e;
        }
    }

    /**
     * Find a single history record.
     *
     * @throws Throwable
     */
    public function find(int $id): ?OperationHistory
    {
        try {
            return $this->repository->find($id);
        } catch (Throwable $e) {
            Log::error('Error fetching operation history by ID', ['id' => $id, 'exception' => $e]);
            throw $e;
        }
    }

    /**
     * Create a new history record manually.
     *
     * @throws Throwable
     */
    public function create(array $data): OperationHistory
    {
        try {
            return $this->repository->create($data);
        } catch (Throwable $e) {
            Log::error('Error creating operation history', ['data' => $data, 'exception' => $e]);
            throw $e;
        }
    }
}
