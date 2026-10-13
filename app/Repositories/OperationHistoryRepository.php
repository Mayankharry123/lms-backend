<?php

/**
 * Operation History Repository
 * -----------------------------------------
 * Data access for operation histories. Delegates queries to OperationHistory model.
 *
 * @package App\Repositories
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-06
 */

namespace App\Repositories;

use App\Contracts\Repositories\OperationHistoryRepositoryInterface;
use App\Models\OperationHistory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class OperationHistoryRepository implements OperationHistoryRepositoryInterface
{
    /**
     * @var OperationHistory
     */
    protected OperationHistory $model;

    /**
     * OperationHistoryRepository constructor.
     *
     * @param OperationHistory $model
     */
    public function __construct(OperationHistory $model)
    {
        $this->model = $model;
    }

    /**
     * Paginate operation histories by criteria.
     *
     * @param array $criteria
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function paginate(array $criteria = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->paginateHistories($criteria, $perPage);
    }

    /**
     * Get histories by operation ID.
     *
     * @param int $operationId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getByOperationId(int $operationId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->getHistoriesByOperationId($operationId, $perPage);
    }

    /**
     * Find an operation history by ID.
     *
     * @param int $id
     * @return OperationHistory|null
     */
    public function find(int $id): ?OperationHistory
    {
        return $this->model->findHistoryById($id);
    }

    /**
     * Create a new operation history record.
     *
     * @param array $data
     * @return OperationHistory
     */
    public function create(array $data): OperationHistory
    {
        return $this->model->storeHistory($data);
    }
}
