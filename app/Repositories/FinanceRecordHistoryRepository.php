<?php

/**
 * Finance Record History Repository
 * -----------------------------------------
 * Data access for finance record histories. Delegates queries to FinanceRecordHistory model.
 *
 * @package App\Repositories
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-06
 */

namespace App\Repositories;

use App\Contracts\Repositories\FinanceRecordHistoryRepositoryInterface;
use App\Models\FinanceRecordHistory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class FinanceRecordHistoryRepository implements FinanceRecordHistoryRepositoryInterface
{
    protected FinanceRecordHistory $model;

    public function __construct(FinanceRecordHistory $model)
    {
        $this->model = $model;
    }

    /**
     * List finance record histories by criteria with pagination.
     */
    public function paginate(array $criteria = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->paginateHistories($criteria, $perPage);
    }

    /**
     * Get histories for a specific finance record.
     */
    public function getByFinanceRecordId(int $financeRecordId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->getHistoriesByFinanceRecordId($financeRecordId, $perPage);
    }

    /**
     * Find a finance record history by ID.
     */
    public function find(int $id): ?FinanceRecordHistory
    {
        return $this->model->findHistoryById($id);
    }

    /**
     * Create a finance record history record.
     */
    public function create(array $data): FinanceRecordHistory
    {
        return $this->model->storeHistory($data);
    }
}
