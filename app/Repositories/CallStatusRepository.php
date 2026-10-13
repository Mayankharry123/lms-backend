<?php

/**
 * CallStatusRepository
 * 
 * @author Achal Sharma
 * @package App\Repositories
 * @version 1.0.1
 */

namespace App\Repositories;

use App\Contracts\Repositories\CallStatusRepositoryInterface;
use App\Models\CallStatus;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CallStatusRepository implements CallStatusRepositoryInterface
{
    /**
     * @var CallStatus
     */
    protected CallStatus $model;

    /**
     * Create a new CallStatusRepository instance.
     *
     * @param CallStatus $callStatus
     */
    public function __construct(CallStatus $callStatus)
    {
        $this->model = $callStatus;
    }

    // ============================================================================
    // READ OPERATIONS
    // ============================================================================

    /**
     * Fetch paginated list of call statuses with optional search.
     *
     * @param int $perPage
     * @param string|null $searchTerm
     * @return LengthAwarePaginator
     */
    public function getAllCallStatuses(int $perPage = 10, ?string $searchTerm = null): LengthAwarePaginator
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
     * Fetch a single call status by its primary ID.
     *
     * @param int $id
     * @return CallStatus|null
     */
    public function getCallStatusById(int $id): ?CallStatus
    {
        return $this->model->find($id);
    }

    /**
     * Fetch a single call status by its unique slug.
     *
     * @param string $slug
     * @return CallStatus|null
     */
    public function getCallStatusBySlug(string $slug): ?CallStatus
    {
        return $this->model->where('slug', $slug)->first();
    }

    /**
     * Fetch a single call status by its UUID.
     *
     * @param string $uuid
     * @return CallStatus|null
     */
    public function getCallStatusByUuid(string $uuid): ?CallStatus
    {
        return $this->model->where('uuid', $uuid)->first();
    }

    /**
     * Get all active call statuses.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getActiveCallStatuses(int $perPage = 10): LengthAwarePaginator
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
     * Create a new call status record.
     *
     * @param array<string, mixed> $data
     * @return CallStatus
     */
    public function createCallStatus(array $data): CallStatus
    {
        return $this->model->create($data);
    }

    /**
     * Update an existing call status by ID.
     *
     * @param int $id
     * @param array<string, mixed> $data
     * @return CallStatus
     */
    public function updateCallStatus(int $id, array $data): CallStatus
    {
        $callStatus = $this->model->findOrFail($id);
        $callStatus->update($data);
        return $callStatus;
    }

    /**
     * Soft delete a call status by ID.
     *
     * @param int $id
     * @return bool
     */
    public function deleteCallStatus(int $id): bool
    {
        $callStatus = $this->model->findOrFail($id);
        return $callStatus->delete();
    }
}
