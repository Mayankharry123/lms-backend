<?php

/**
 * FinanceStatus Repository
 * -----------------------------------------
 * Implements the FinanceStatus repository interface, providing data access for finance statuses.
 *
 * @package App\Repositories
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-04
 */

namespace App\Repositories;

use App\Contracts\Repositories\FinanceStatusRepositoryInterface;
use App\Models\FinanceStatus;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class FinanceStatusRepository implements FinanceStatusRepositoryInterface
{
    protected FinanceStatus $model;

    /**
     * Inject the finance status model used for queries.
     */
    public function __construct(FinanceStatus $model)
    {
        $this->model = $model;
    }

    /**
     * Get all finance statuses with pagination
     */
    public function all(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->paginateAll($perPage);
    }

    /**
     * Find a finance status by ID
     */
    public function find(int $id): ?FinanceStatus
    {
        return $this->model->findById($id);
    }

    /**
     * Find a finance status by UUID
     */
    public function findByUuid(string $uuid): ?FinanceStatus
    {
        return $this->model->findByUuid($uuid);
    }

    /**
     * Find a finance status by name
     */
    public function findByName(string $name): ?FinanceStatus
    {
        return $this->model->findByName($name);
    }

    /**
     * Find a finance status by slug
     */
    public function findBySlug(string $slug): ?FinanceStatus
    {
        return $this->model->findBySlug($slug);
    }

    /**
     * Find the first active finance status
     */
    public function findFirstActive(): ?FinanceStatus
    {
        return $this->model->findFirstActive();
    }

    /**
     * Create a new finance status
     */
    public function create(array $data): FinanceStatus
    {
        return $this->model->storeRecord($data);
    }

    /**
     * Update a finance status by ID
     */
    public function update(int $id, array $data): bool
    {
        return $this->model->updateById($id, $data);
    }

    /**
     * Soft delete a finance status by ID and set status to 15
     */
    public function delete(int $id): bool
    {
        return $this->model->softDeleteById($id);
    }

    /**
     * Search finance statuses by criteria with pagination
     */
    public function search(array $criteria, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->searchRecords($criteria, $perPage);
    }
}
