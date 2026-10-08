<?php

namespace App\Repositories;

use App\Contracts\Repositories\OperationRepositoryInterface;
use App\Models\Operation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class OperationRepository implements OperationRepositoryInterface
{
    protected Operation $model;

    public function __construct(Operation $model)
    {
        $this->model = $model;
    }

    public function paginate(array $criteria = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->paginateOperations($criteria, $perPage, auth()->user());
    }

    public function find(int $id): ?Operation
    {
        return $this->model->findOperationById($id, auth()->user());
    }

    public function existsForPlanner(int $plannerId): bool
    {
        return $this->model->existsForPlanner($plannerId);
    }

    public function create(array $data): Operation
    {
        return $this->model->storeOperation($data);
    }

    public function updateStatus(int $id, int $operationStatusId, ?string $comment = null): ?Operation
    {
        return $this->model->updateOperationStatus($id, $operationStatusId, $comment);
    }

    public function updateAssignUser(int $id, int $assignTo, int $assignBy, ?string $comment = null): ?Operation
    {
        return $this->model->updateOperationAssignUser($id, $assignTo, $assignBy, $comment);
    }

    public function delete(int $id): bool
    {
        return $this->model->softDeleteOperation($id);
    }
}
