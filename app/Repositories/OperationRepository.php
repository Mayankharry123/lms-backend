<?php

/**
 * OperationRepository
 * 
 * @author Achal Sharma
 * @package App\Repositories
 * @version 1.0.1
 */

namespace App\Repositories;

use App\Contracts\Repositories\OperationRepositoryInterface;
use App\Models\Operation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class OperationRepository implements OperationRepositoryInterface
{
    /**
     * @var Operation
     */
    protected Operation $model;

    /**
     * OperationRepository constructor.
     *
     * @param Operation $model
     */
    public function __construct(Operation $model)
    {
        $this->model = $model;
    }

    /**
     * Paginate operations by criteria.
     *
     * @param array $criteria
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function paginate(array $criteria = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->paginateOperations($criteria, $perPage, auth()->user());
    }

    /**
     * Find an operation by ID.
     *
     * @param int $id
     * @return Operation|null
     */
    public function find(int $id): ?Operation
    {
        return $this->model->findOperationById($id, auth()->user());
    }

    /**
     * Check if an operation exists for the given planner ID.
     *
     * @param int $plannerId
     * @return bool
     */
    public function existsForPlanner(int $plannerId): bool
    {
        return $this->model->existsForPlanner($plannerId);
    }

    /**
     * Create a new operation record.
     *
     * @param array $data
     * @return Operation
     */
    public function create(array $data): Operation
    {
        return $this->model->storeOperation($data);
    }

    /**
     * Update operation status with optional comment.
     *
     * @param int $id
     * @param int $operationStatusId
     * @param string|null $comment
     * @return Operation|null
     */
    public function updateStatus(int $id, int $operationStatusId, ?string $comment = null): ?Operation
    {
        return $this->model->updateOperationStatus($id, $operationStatusId, $comment);
    }

    /**
     * Update assigned user for an operation.
     *
     * @param int $id
     * @param int $assignTo
     * @param int $assignBy
     * @param string|null $comment
     * @return Operation|null
     */
    public function updateAssignUser(int $id, int $assignTo, int $assignBy, ?string $comment = null): ?Operation
    {
        return $this->model->updateOperationAssignUser($id, $assignTo, $assignBy, $comment);
    }

    /**
     * Soft delete an operation by ID.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        return $this->model->softDeleteOperation($id);
    }
}
