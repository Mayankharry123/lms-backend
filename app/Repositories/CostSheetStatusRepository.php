<?php

/**
 * CostSheetStatus Repository
 * -----------------------------------------
 * Implements the CostSheetStatus repository interface, providing data access for cost sheet statuses.
 *
 * @package App\Repositories
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-05
 */

namespace App\Repositories;

use App\Contracts\Repositories\CostSheetStatusRepositoryInterface;
use App\Models\CostSheetStatus;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CostSheetStatusRepository implements CostSheetStatusRepositoryInterface
{
    protected CostSheetStatus $model;

    /**
     * Inject the cost sheet status model used for queries.
     */
    public function __construct(CostSheetStatus $model)
    {
        $this->model = $model;
    }

    /**
     * Get all cost sheet statuses with pagination.
     */
    public function all(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->paginateAll($perPage);
    }

    /**
     * Find a cost sheet status by ID.
     */
    public function find(int $id): ?CostSheetStatus
    {
        return $this->model->findById($id);
    }

    /**
     * Find a cost sheet status by UUID.
     */
    public function findByUuid(string $uuid): ?CostSheetStatus
    {
        return $this->model->findByUuid($uuid);
    }

    /**
     * Find a cost sheet status by name.
     */
    public function findByName(string $name): ?CostSheetStatus
    {
        return $this->model->findByName($name);
    }

    /**
     * Find a cost sheet status by slug.
     */
    public function findBySlug(string $slug): ?CostSheetStatus
    {
        return $this->model->findBySlug($slug);
    }

    /**
     * Find the first active cost sheet status.
     */
    public function findFirstActive(): ?CostSheetStatus
    {
        return $this->model->findFirstActive();
    }

    /**
     * Create a new cost sheet status.
     */
    public function create(array $data): CostSheetStatus
    {
        return $this->model->storeRecord($data);
    }

    /**
     * Update a cost sheet status by ID.
     */
    public function update(int $id, array $data): bool
    {
        return $this->model->updateById($id, $data);
    }

    /**
     * Soft delete a cost sheet status by ID and set status to 15.
     */
    public function delete(int $id): bool
    {
        return $this->model->softDeleteById($id);
    }

    /**
     * Search cost sheet statuses by criteria with pagination.
     */
    public function search(array $criteria, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->searchRecords($criteria, $perPage);
    }
}
