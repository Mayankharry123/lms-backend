<?php

/**
 * PriorityRepository
 * 
 * @author Achal Sharma
 * @package App\Repositories
 * @version 1.0.1
 */

namespace App\Repositories;

use App\Contracts\Repositories\PriorityRepositoryInterface;
use App\Models\Priority;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PriorityRepository implements PriorityRepositoryInterface
{
    /**
     * @var Priority
     */
    protected Priority $model;

    /**
     * Create a new PriorityRepository instance.
     *
     * @param Priority $priority
     */
    public function __construct(Priority $priority)
    {
        $this->model = $priority;
    }

    // ============================================================================
    // READ OPERATIONS
    // ============================================================================

    /**
     * Fetch paginated list of priorities with optional search.
     *
     * @param int $perPage
     * @param string|null $searchTerm
     * @return LengthAwarePaginator
     */
    public function getAllPriorities(int $perPage = 10, ?string $searchTerm = null): LengthAwarePaginator
    {
        $query = $this->model->where('status', '1');

        // Apply search filter if search term is provided
        if ($searchTerm) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('slug', 'LIKE', "%{$searchTerm}%");
            });
        }

        return $query
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->appends(request()->query());
    }

    /**
     * Fetch a single priority by its primary ID.
     *
     * @param int $id
     * @return Priority|null
     */
    public function getPriorityById(int $id): ?Priority
    {
        return $this->model->find($id);
    }

    /**
     * Fetch a single priority by its unique slug.
     *
     * @param string $slug
     * @return Priority|null
     */
    public function getPriorityBySlug(string $slug): ?Priority
    {
        return $this->model->where('slug', $slug)->first();
    }

    /**
     * Fetch a single priority by its UUID.
     *
     * @param string $uuid
     * @return Priority|null
     */
    public function getPriorityByUuid(string $uuid): ?Priority
    {
        return $this->model->where('uuid', $uuid)->first();
    }

    /**
     * Get all active priorities.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getActivePriorities(int $perPage = 10): LengthAwarePaginator
    {
        return $this->model
            ->where('status', '1')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->appends(request()->query());
    }

    // ============================================================================
    // WRITE OPERATIONS
    // ============================================================================

    /**
     * Create a new priority record.
     *
     * @param array<string, mixed> $data
     * @return Priority
     */
    public function createPriority(array $data): Priority
    {
        return $this->model->create($data);
    }

    /**
     * Update an existing priority by ID.
     *
     * @param int $id
     * @param array<string, mixed> $data
     * @return Priority
     */
    public function updatePriority(int $id, array $data): Priority
    {
        $priority = $this->model->findOrFail($id);
        $priority->update($data);
        return $priority;
    }

    /**
     * Soft delete a priority by ID.
     *
     * @param int $id
     * @return bool
     */
    public function deletePriority(int $id): bool
    {
        $priority = $this->model->findOrFail($id);
        return $priority->delete();
    }
}
