<?php

/**
 * PlannerStatusRepository
 * 
 * @author Achal Sharma
 * @package App\Repositories
 * @version 1.0.1
 */

namespace App\Repositories;

use App\Contracts\Repositories\PlannerStatusRepositoryInterface;
use App\Models\PlannerStatus;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PlannerStatusRepository implements PlannerStatusRepositoryInterface
{
    /**
     * @var PlannerStatus
     */
    protected PlannerStatus $model;

    /**
     * PlannerStatusRepository constructor.
     *
     * @param PlannerStatus $model
     */
    public function __construct(PlannerStatus $model)
    {
        $this->model = $model;
    }

    /**
     * Get all planner statuses with pagination.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function all(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->latest()->paginate($perPage);
    }

    /**
     * Find a planner status by ID.
     *
     * @param int $id
     * @return PlannerStatus|null
     */
    public function find(int $id): ?PlannerStatus
    {
        return $this->model->find($id);
    }

    /**
     * Find a planner status by UUID.
     *
     * @param string $uuid
     * @return PlannerStatus|null
     */
    public function findByUuid(string $uuid): ?PlannerStatus
    {
        return $this->model->where('uuid', $uuid)->first();
    }

    /**
     * Find a planner status by name.
     *
     * @param string $name
     * @return PlannerStatus|null
     */
    public function findByName(string $name): ?PlannerStatus
    {
        return $this->model->where('name', $name)->first();
    }

    /**
     * Find a planner status by slug.
     *
     * @param string $slug
     * @return PlannerStatus|null
     */
    public function findBySlug(string $slug): ?PlannerStatus
    {
        return $this->model->where('slug', $slug)->first();
    }

    /**
     * Create a new planner status.
     *
     * @param array $data
     * @return PlannerStatus
     */
    public function create(array $data): PlannerStatus
    {
        return $this->model->create($data);
    }

    /**
     * Update a planner status by ID.
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $plannerStatus = $this->model->find($id);

        if (!$plannerStatus) {
            return false;
        }

        return (bool) $plannerStatus->update($data);
    }

    /**
     * Delete a planner status by ID.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $plannerStatus = $this->model->find($id);

        if (!$plannerStatus) {
            return false;
        }

        return (bool) $plannerStatus->delete();
    }

    /**
     * Search planner statuses by criteria with pagination.
     *
     * @param array $criteria
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function search(array $criteria, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        // Global q parameter for quick search
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

        if (isset($criteria['status'])) {
            $query->where('status', $criteria['status']);
        }

        // Order results ascending by id by default
        return $query->orderBy('id', 'asc')->paginate($perPage);
    }
}
