<?php

/**
 * OperationStatus Repository
 * -----------------------------------------
 * Implements the OperationStatus repository interface, delegating data access to OperationStatus model.
 *
 * @package App\Repositories
 * @version 1.0.0
 * @since 2026-10-03
 */

namespace App\Repositories;

use App\Contracts\Repositories\OperationStatusRepositoryInterface;
use App\Models\OperationStatus;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class OperationStatusRepository implements OperationStatusRepositoryInterface
{
    protected OperationStatus $model;

    public function __construct(OperationStatus $model)
    {
        $this->model = $model;
    }

    public function all(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->paginateAll($perPage);
    }

    public function find(int $id): ?OperationStatus
    {
        return $this->model->findById($id);
    }

    public function findByUuid(string $uuid): ?OperationStatus
    {
        return $this->model->findByUuid($uuid);
    }

    public function findByName(string $name): ?OperationStatus
    {
        return $this->model->findByName($name);
    }

    public function findBySlug(string $slug): ?OperationStatus
    {
        return $this->model->findBySlug($slug);
    }

    public function create(array $data): OperationStatus
    {
        return $this->model->storeRecord($data);
    }

    public function update(int $id, array $data): bool
    {
        return $this->model->updateById($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->model->softDeleteById($id);
    }

    public function search(array $criteria, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->searchRecords($criteria, $perPage);
    }
}
