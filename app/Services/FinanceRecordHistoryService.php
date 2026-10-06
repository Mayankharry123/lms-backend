<?php

/**
 * Finance Record History Service
 * -----------------------------------------
 * Handles business logic for querying and managing finance record histories.
 *
 * @package App\Services
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-06
 */

namespace App\Services;

use App\Contracts\Repositories\FinanceRecordHistoryRepositoryInterface;
use App\Models\FinanceRecordHistory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Throwable;

class FinanceRecordHistoryService
{
    protected FinanceRecordHistoryRepositoryInterface $repository;

    public function __construct(FinanceRecordHistoryRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get paginated finance record histories with optional criteria.
     *
     * @throws Throwable
     */
    public function list(array $criteria = [], int $perPage = 15): LengthAwarePaginator
    {
        try {
            return $this->repository->paginate($criteria, $perPage);
        } catch (Throwable $e) {
            Log::error('Error fetching finance record histories list', ['criteria' => $criteria, 'exception' => $e]);
            throw $e;
        }
    }

    /**
     * Get histories for a specific finance record.
     *
     * @throws Throwable
     */
    public function getByFinanceRecordId(int $financeRecordId, int $perPage = 15): LengthAwarePaginator
    {
        try {
            return $this->repository->getByFinanceRecordId($financeRecordId, $perPage);
        } catch (Throwable $e) {
            Log::error('Error fetching finance record histories by record ID', [
                'finance_record_id' => $financeRecordId,
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
    public function find(int $id): ?FinanceRecordHistory
    {
        try {
            return $this->repository->find($id);
        } catch (Throwable $e) {
            Log::error('Error fetching finance record history by ID', ['id' => $id, 'exception' => $e]);
            throw $e;
        }
    }

    /**
     * Create a new history record manually.
     *
     * @throws Throwable
     */
    public function create(array $data): FinanceRecordHistory
    {
        try {
            return $this->repository->create($data);
        } catch (Throwable $e) {
            Log::error('Error creating finance record history', ['data' => $data, 'exception' => $e]);
            throw $e;
        }
    }
}
