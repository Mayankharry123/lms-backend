<?php

/**
 * Operation History Repository
 * -----------------------------------------
 * Data access for operation histories. Delegates queries to OperationHistory model.
 *
 * @package App\Repositories
 * @version 1.0.0
 * @since 2026-10-06
 */

namespace App\Repositories;

use App\Contracts\Repositories\OperationHistoryRepositoryInterface;
use App\Models\OperationHistory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class OperationHistoryRepository implements OperationHistoryRepositoryInterface
{
    protected OperationHistory $model;

    public function __construct(OperationHistory $model)
    {
        $this->model = $model;
    }

    public function paginate(array $criteria = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->paginateHistories($criteria, $perPage);
    }

    public function getByOperationId(int $operationId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->getHistoriesByOperationId($operationId, $perPage);
    }

    public function find(int $id): ?OperationHistory
    {
        return $this->model->findHistoryById($id);
    }

    public function create(array $data): OperationHistory
    {
        return $this->model->storeHistory($data);
    }
}
