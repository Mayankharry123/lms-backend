<?php

/**
 * OperationStatus Repository
 * -----------------------------------------
 * Implements the OperationStatus repository interface, delegating data access to OperationStatus model.
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
    /**
     * @var OperationStatus
     */
    protected OperationStatus $model;

    /**
     * OperationStatusRepository constructor.
     *
     * @param OperationStatus $model
     */
    public function __construct(OperationStatus $model)
    {
        $this->model = $model;
    }

    /**
     * Get all operation statuses with pagination.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function all(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->paginateAll($perPage);
    }

    /**
     * Find an operation status by ID.
     *
     * @param int $id
     * @return OperationStatus|null
     */
    public function find(int $id): ?OperationStatus
    {
        return $this->model->findById($id);
    }

    /**
     * Find an operation status by UUID.
     *
     * @param string $uuid
     * @return OperationStatus|null
     */
    public function findByUuid(string $uuid): ?OperationStatus
    {
        return $this->model->findByUuid($uuid);
    }

    /**
     * Find an operation status by name.
     *
     * @param string $name
     * @return OperationStatus|null
     */
    public function findByName(string $name): ?OperationStatus
    {
        return $this->model->findByName($name);
    }

    /**
     * Find an operation status by slug.
     *
     * @param string $slug
     * @return OperationStatus|null
     */
    public function findBySlug(string $slug): ?OperationStatus
    {
        return $this->model->findBySlug($slug);
    }

    /**
     * Create a new operation status.
     *
     * @param array $data
     * @return OperationStatus
     */
    public function create(array $data): OperationStatus
    {
        return $this->model->storeRecord($data);
    }

    /**
     * Update an operation status by ID.
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        return $this->model->updateById($id, $data);
    }

    /**
     * Soft delete an operation status by ID.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        return $this->model->softDeleteById($id);
    }

    /**
     * Search operation statuses by criteria with pagination.
     *
     * @param array $criteria
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function search(array $criteria, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->searchRecords($criteria, $perPage);
    }
}
