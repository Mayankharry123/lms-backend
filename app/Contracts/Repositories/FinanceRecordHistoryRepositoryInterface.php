<?php

/**
 * Finance Record History Repository Interface
 * -----------------------------------------
 * Defines the contract for querying and storing finance record histories.
 *
 * @package App\Contracts\Repositories
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-06
 */

namespace App\Contracts\Repositories;

use App\Models\FinanceRecordHistory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface FinanceRecordHistoryRepositoryInterface
{
    /**
     * List finance record histories by criteria with pagination.
     *
     * @param array $criteria
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function paginate(array $criteria = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Get histories for a specific finance record.
     *
     * @param int $financeRecordId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getByFinanceRecordId(int $financeRecordId, int $perPage = 15): LengthAwarePaginator;

    /**
     * Find a finance record history by ID.
     */
    public function find(int $id): ?FinanceRecordHistory;

    /**
     * Create a finance record history record.
     */
    public function create(array $data): FinanceRecordHistory;
}
