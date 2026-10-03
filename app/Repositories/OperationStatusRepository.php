<?php

/**
 * OperationStatus Repository
 * -----------------------------------------
 * Implements the OperationStatus repository interface, providing data access for operation statuses.
 *
 * @package App\Repositories
 * @author Achal Sharma
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
        return $this->model->latest()->paginate($perPage);
    }

    public function find(int $id): ?OperationStatus
    {
        return $this->model->find($id);
    }

    public function findByUuid(string $uuid): ?OperationStatus
    {
        return $this->model->where('uuid', $uuid)->first();
    }

    public function findByName(string $name): ?OperationStatus
    {
        return $this->model->where('name', $name)->first();
    }

    public function findBySlug(string $slug): ?OperationStatus
    {
        return $this->model->where('slug', $slug)->first();
    }

    public function create(array $data): OperationStatus
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): bool
    {
        $operationStatus = $this->model->find($id);

        if (!$operationStatus) {
            return false;
        }

        return (bool) $operationStatus->update($data);
    }

    public function delete(int $id): bool
    {
        $operationStatus = $this->model->find($id);

        if (!$operationStatus) {
            return false;
        }

        $operationStatus->status = '15';
        $operationStatus->save();

        return (bool) $operationStatus->delete();
    }

    public function search(array $criteria, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        if (!empty($criteria['q'])) {
            $q = $criteria['q'];
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('slug', 'like', "%{$q}%");
            });
        }

        if (!empty($criteria['name'])) {
            $query->where('name', 'like', "%{$criteria['name']}%");
        }

        if (!empty($criteria['slug'])) {
            $query->where('slug', $criteria['slug']);
        }

        if (isset($criteria['status'])) {
            $query->where('status', $criteria['status']);
        }

        return $query->orderBy('id', 'asc')->paginate($perPage);
    }
}
