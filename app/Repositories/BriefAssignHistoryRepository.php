<?php

namespace App\Repositories;

use App\Contracts\Repositories\BriefAssignHistoryRepositoryInterface;
use App\Models\BriefAssignHistory;
use App\Models\User;
use App\Support\UserAccessScope;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BriefAssignHistoryRepository implements BriefAssignHistoryRepositoryInterface
{
    /**
     * @var BriefAssignHistory
     */
    protected BriefAssignHistory $model;

    /**
     * Create a new BriefAssignHistoryRepository instance.
     *
     * @param BriefAssignHistory $briefAssignHistory
     */
    public function __construct(BriefAssignHistory $briefAssignHistory)
    {
        $this->model = $briefAssignHistory;
    }

    // ============================================================================
    // READ OPERATIONS
    // ============================================================================

    /**
     * Fetch paginated list of brief assign histories with optional search.
     *
     * @param int $perPage The number of items per page.
     * @param string|null $searchTerm Optional search term to filter brief assign histories.
     * @return LengthAwarePaginator
     */
    public function getAllBriefAssignHistories(int $perPage = 10, ?string $searchTerm = null): LengthAwarePaginator
    {
        $query = $this->model->query()->with(['brief', 'assignedBy', 'assignedTo', 'briefStatus']);

        if ($searchTerm) {
            $query->where('comment', 'like', "%{$searchTerm}%")
                ->orWhereHas('brief', function ($q) use ($searchTerm) {
                    $q->where('name', 'like', "%{$searchTerm}%");
                });
        }

        return $query->paginate($perPage);
    }

    /**
     * Fetch a single brief assign history by its primary ID.
     *
     * @param int $id The brief assign history ID.
     * @return BriefAssignHistory|null
     */
    public function getBriefAssignHistoryById(int $id): ?BriefAssignHistory
    {
        return $this->model->with(['brief', 'assignedBy', 'assignedTo', 'briefStatus'])->find($id);
    }

    /**
     * Fetch a single brief assign history by its UUID.
     *
     * @param string $uuid The unique brief assign history UUID.
     * @return BriefAssignHistory|null
     */
    public function getBriefAssignHistoryByUuid(string $uuid): ?BriefAssignHistory
    {
        return $this->model->with(['brief', 'assignedBy', 'assignedTo', 'briefStatus'])->where('uuid', $uuid)->first();
    }

    /**
     * Fetch all assign histories for a specific brief.
     *
     * @param int $briefId The brief ID.
     * @param int $perPage The number of items per page.
     * @param User|null $user The authenticated user to scope visibility.
     * @return LengthAwarePaginator
     */
    /**
     * Added brief-wise assignment history retrieval with pagination.
     * Applied UserAccessScope to restrict history visibility to the
     * authenticated user's accessible hierarchy, while allowing
     * Super Admin users to view all histories.
     */
    public function getBriefAssignHistoriesByBriefId(int $briefId, int $perPage = 10, ?User $user = null): LengthAwarePaginator
    {
        $query = $this->model->where('brief_id', $briefId)
            ->with(['assignedBy', 'assignedTo', 'briefStatus']);

        $user = $user ?? auth()->user();

        if ($user && !UserAccessScope::isSuperAdmin($user)) {
            $descendantIds = UserAccessScope::getStrictDescendantIds($user);
            $query->where(function ($q) use ($descendantIds) {
                $q->whereIn('assign_to_id', $descendantIds)
                  ->orWhereIn('assign_by_id', $descendantIds);
            });
        }

        return $query->paginate($perPage);
    }

    /**
     * Fetch all assign histories assigned by a specific user.
     *
     * @param int $userId The user ID who assigned.
     * @param int $perPage The number of items per page.
     * @return LengthAwarePaginator
     */
    public function getBriefAssignHistoriesByAssignBy(int $userId, int $perPage = 10): LengthAwarePaginator
    {
        return $this->model->where('assign_by_id', $userId)
            ->with(['brief', 'assignedTo', 'briefStatus'])
            ->paginate($perPage);
    }

    /**
     * Fetch all assign histories assigned to a specific user.
     *
     * @param int $userId The user ID assigned to.
     * @param int $perPage The number of items per page.
     * @return LengthAwarePaginator
     */
    public function getBriefAssignHistoriesByAssignTo(int $userId, int $perPage = 10): LengthAwarePaginator
    {
        return $this->model->where('assign_to_id', $userId)
            ->with(['brief', 'assignedBy', 'briefStatus'])
            ->paginate($perPage);
    }
}
