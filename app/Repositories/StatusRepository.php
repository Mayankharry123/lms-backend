<?php

/**
 * StatusRepository
 * 
 * @author Achal Sharma
 * @package App\Repositories
 * @version 1.0.1
 */

namespace App\Repositories;

use App\Contracts\Repositories\StatusRepositoryInterface;
use App\Models\Status;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class StatusRepository implements StatusRepositoryInterface
{
    /**
     * @var Status
     */
    protected Status $model;

    /**
     * Create a new StatusRepository instance.
     *
     * @param Status $status
     */
    public function __construct(Status $status)
    {
        $this->model = $status;
    }

    // ============================================================================
    // READ OPERATIONS
    // ============================================================================

    /**
     * Fetch paginated list of statuses with optional search.
     *
     * @param int $perPage
     * @param string|null $searchTerm
     * @return LengthAwarePaginator
     */
    public function getAllStatuses(int $perPage = 10, ?string $searchTerm = null): LengthAwarePaginator
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
     * Fetch a single status by its primary ID.
     *
     * @param int $id
     * @return Status|null
     */
    public function getStatusById(int $id): ?Status
    {
        return $this->model->find($id);
    }

    /**
     * Fetch a single status by its unique slug.
     *
     * @param string $slug
     * @return Status|null
     */
    public function getStatusBySlug(string $slug): ?Status
    {
        return $this->model->where('slug', $slug)->first();
    }

    /**
     * Fetch a single status by its UUID.
     *
     * @param string $uuid
     * @return Status|null
     */
    public function getStatusByUuid(string $uuid): ?Status
    {
        return $this->model->where('uuid', $uuid)->first();
    }

    /**
     * Get all active statuses.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getActiveStatuses(int $perPage = 10): LengthAwarePaginator
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
     * Create a new status record.
     *
     * @param array<string, mixed> $data
     * @return Status
     */
    public function createStatus(array $data): Status
    {
        return $this->model->create($data);
    }

    /**
     * Update an existing status by ID.
     *
     * @param int $id
     * @param array<string, mixed> $data
     * @return Status
     */
    public function updateStatus(int $id, array $data): Status
    {
        $status = $this->model->findOrFail($id);
        $status->update($data);
        return $status;
    }

    /**
     * Soft delete a status by ID.
     *
     * @param int $id
     * @return bool
     */
    public function deleteStatus(int $id): bool
    {
        $status = $this->model->findOrFail($id);
        return $status->delete();
    }
}
