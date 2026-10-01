<?php

namespace App\Repositories;

use App\Contracts\Repositories\BriefAssignHistoryRepositoryInterface;
use App\Models\BriefAssignHistory;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

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
        return $this->model->getAllBriefAssignHistories($perPage, $searchTerm);
    }

    /**
     * Fetch a single brief assign history by its primary ID.
     *
     * @param int $id The brief assign history ID.
     * @return BriefAssignHistory|null
     */
    public function getBriefAssignHistoryById(int $id): ?BriefAssignHistory
    {
        return $this->model->getBriefAssignHistoryById($id);
    }

    /**
     * Fetch a single brief assign history by its UUID.
     *
     * @param string $uuid The unique brief assign history UUID.
     * @return BriefAssignHistory|null
     */
    public function getBriefAssignHistoryByUuid(string $uuid): ?BriefAssignHistory
    {
        return $this->model->getBriefAssignHistoryByUuid($uuid);
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
        return $this->model->getBriefAssignHistoriesByBriefId($briefId, $perPage, $user);
    }

    /**
     * Fetch all chat histories for a brief, newest first.
     *
     * @param int $briefId
     * @return Collection<int, BriefAssignHistory>
     */
    public function getBriefAssignHistoryChat(int $briefId): Collection
    {
        return $this->model->getBriefAssignHistoryChat($briefId);
    }

    /**
     * Store a brief activity entry and its reminder details.
     *
     * @param int $briefId
     * @param int $currentUserId
     * @param array<string, mixed> $data
     * @return BriefAssignHistory
     */
    public function createBriefActivity(int $briefId, int $currentUserId, array $data): BriefAssignHistory
    {
        return $this->model->createBriefActivity($briefId, $currentUserId, $data);
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
        return $this->model->getBriefAssignHistoriesByAssignBy($userId, $perPage);
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
        return $this->model->getBriefAssignHistoriesByAssignTo($userId, $perPage);
    }

    /**
     * Assignment rows for one brief, oldest first.
     *
     * @param int $briefId
     * @return Collection<int, BriefAssignHistory>
     */
    public function getAssignmentHistoriesForBrief(int $briefId): Collection
    {
        return $this->model->getAssignmentHistoriesForBrief($briefId);
    }

    /**
     * Assignment rows for many briefs, oldest first within each brief.
     *
     * @param array<int, int> $briefIds
     * @return Collection<int, BriefAssignHistory>
     */
    public function getAssignmentHistoriesForBriefs(array $briefIds): Collection
    {
        return $this->model->getAssignmentHistoriesForBriefs($briefIds);
    }

    /**
     * Assignment rows for every brief that was assigned to the user.
     *
     * @param int $userId
     * @return Collection<int, BriefAssignHistory>
     */
    public function getAssignmentHistoriesForUserCycles(int $userId): Collection
    {
        return $this->model->getAssignmentHistoriesForUserCycles($userId);
    }
}
