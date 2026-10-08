<?php

/**
 * Operation History Repository
 * -----------------------------------------
 * Implements querying and creating operation history records.
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
    protected OperationHistory $model;

    public function __construct(OperationHistory $model)
    {
        $this->model = $model;
    }

    public function paginate(array $criteria = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->newQuery()->with($this->relations());

        if (!empty($criteria['operation_id'])) {
            $query->where('operation_id', $criteria['operation_id']);
        }

        if (!empty($criteria['brief_id'])) {
            $query->where('brief_id', $criteria['brief_id']);
        }

        if (!empty($criteria['planner_id'])) {
            $query->where('planner_id', $criteria['planner_id']);
        }

        if (!empty($criteria['operation_status_id'])) {
            $query->where('operation_status_id', $criteria['operation_status_id']);
        }

        if (!empty($criteria['assign_by'])) {
            $query->where('assign_by', $criteria['assign_by']);
        }

        if (!empty($criteria['assign_to'])) {
            $query->where('assign_to', $criteria['assign_to']);
        }

        if (isset($criteria['status']) && $criteria['status'] !== '') {
            $query->where('status', $criteria['status']);
        }

        return $query->orderBy('id', 'desc')->paginate($perPage);
    }

    public function getByOperationId(int $operationId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->with($this->relations())
            ->where('operation_id', $operationId)
            ->orderBy('id', 'desc')
            ->paginate($perPage);
    }

    public function find(int $id): ?OperationHistory
    {
        return $this->model->newQuery()
            ->with($this->relations())
            ->find($id);
    }

    public function create(array $data): OperationHistory
    {
        return $this->model->create($data);
    }

    /**
     * Eager loaded relations for history payloads.
     */
    protected function relations(): array
    {
        return [
            'brief:id,name',
            'operationStatus:id,name',
            'assignedBy:id,name',
            'assignedTo:id,name',
        ];
    }
}
