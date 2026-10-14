<?php

/**
 * BriefStatusRepository
 * 
 * @author Achal Sharma
 * @package App\Repositories
 * @version 1.0.1
 */

namespace App\Repositories;

use App\Contracts\Repositories\BriefStatusRepositoryInterface;
use App\Models\BriefStatus;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BriefStatusRepository implements BriefStatusRepositoryInterface
{
    /**
     * @var BriefStatus
     */
    protected BriefStatus $model;

    /**
     * Create a new BriefStatusRepository instance.
     *
     * @param BriefStatus $briefStatus
     */
    public function __construct(BriefStatus $briefStatus)
    {
        $this->model = $briefStatus;
    }

    // ============================================================================
    // READ OPERATIONS
    // ============================================================================

    /**
     * Fetch paginated list of brief statuses with optional search.
     *
     * @param int $perPage The number of items per page.
     * @param string|null $searchTerm Optional search term to filter brief statuses.
     * @return LengthAwarePaginator
     */
    public function getAllBriefStatuses(int $perPage = 10, ?string $searchTerm = null): LengthAwarePaginator
    {
        $query = $this->model->query();

        if ($searchTerm) {
            $query->where('name', 'like', "%{$searchTerm}%");
        }

        return $query->paginate($perPage);
    }

    /**
     * Fetch a single brief status by its primary ID.
     *
     * @param int $id The brief status ID.
     * @return BriefStatus|null
     */
    public function getBriefStatusById(int $id): ?BriefStatus
    {
        return $this->model->find($id);
    }

    /**
     * Fetch a single brief status by its UUID.
     *
     * @param string $uuid The unique brief status UUID.
     * @return BriefStatus|null
     */
    public function getBriefStatusByUuid(string $uuid): ?BriefStatus
    {
        return $this->model->where('uuid', $uuid)->first();
    }

    /**
     * Fetch a single brief status by its name.
     *
     * @param string $name The brief status name.
     * @return BriefStatus|null
     */
    public function getBriefStatusByName(string $name): ?BriefStatus
    {
        return $this->model->where('name', $name)->first();
    }

    /**
     * Fetch a single brief status by its slug.
     *
     * @param string $slug The unique brief status slug.
     * @return BriefStatus|null
     */
    public function getBriefStatusBySlug(string $slug): ?BriefStatus
    {
        return $this->model->where('slug', $slug)->first();
    }

    /**
     * Get all active brief statuses.
     *
     * @return LengthAwarePaginator
     */
    public function getActiveBriefStatuses(int $perPage = 10): LengthAwarePaginator
    {
        return $this->model
            ->where('status', '1')
            ->paginate($perPage);
    }

    /**
     * Get priorities filtered by brief status ID.
     *
     * @param int $briefStatusId The brief status ID.
     * @return array
     */
    public function getPrioritiesByBriefStatusId(int $briefStatusId): array
    {
        $briefStatus = $this->model->find($briefStatusId);

        if (!$briefStatus) {
            return [];
        }

        // Get the priority associated with this brief status
        if ($briefStatus->priority) {
            return [
                'id' => $briefStatus->priority->id,
                'uuid' => $briefStatus->priority->uuid,
                'name' => $briefStatus->priority->name,
                'slug' => $briefStatus->priority->slug,
                'status' => $briefStatus->priority->status,
            ];
        }

        return [];
    }

    /**
     * Get brief statuses filtered by priority ID.
     *
     * @param int $priorityId The priority ID.
     * @param int $perPage The number of items per page.
     * @return LengthAwarePaginator
     */
    public function getBriefStatusesByPriorityId(int $priorityId, int $perPage = 10): LengthAwarePaginator
    {
        return $this->model
            ->where('priority_id', $priorityId)
            ->paginate($perPage);
    }

    // ============================================================================
    // WRITE OPERATIONS
    // ============================================================================

    /**
     * Create a new brief status record.
     *
     * @param array<string, mixed> $data
     * @return BriefStatus
     */
    public function createBriefStatus(array $data): BriefStatus
    {
        return $this->model->create($data);
    }

    /**
     * Update an existing brief status by ID.
     *
     * @param int $id The brief status ID.
     * @param array<string, mixed> $data
     * @return BriefStatus
     */
    public function updateBriefStatus(int $id, array $data): BriefStatus
    {
        $briefStatus = $this->getBriefStatusById($id);
        $briefStatus->update($data);

        return $briefStatus;
    }

    /**
     * Soft delete a brief status by ID.
     *
     * @param int $id The brief status ID.
     * @return bool
     */
    public function deleteBriefStatus(int $id): bool
    {
        $briefStatus = $this->getBriefStatusById($id);

        return $briefStatus->delete();
    }
}
