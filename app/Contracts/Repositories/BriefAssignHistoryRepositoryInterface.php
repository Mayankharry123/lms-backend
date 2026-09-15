<?php

namespace App\Contracts\Repositories;

use App\Models\BriefAssignHistory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface BriefAssignHistoryRepositoryInterface
{
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
    public function getAllBriefAssignHistories(int $perPage = 10, ?string $searchTerm = null): LengthAwarePaginator;

    /**
     * Fetch a single brief assign history by its primary ID.
     *
     * @param int $id The brief assign history ID.
     * @return BriefAssignHistory|null
     */
    public function getBriefAssignHistoryById(int $id): ?BriefAssignHistory;

    /**
     * Fetch a single brief assign history by its UUID.
     *
     * @param string $uuid The unique brief assign history UUID.
     * @return BriefAssignHistory|null
     */
    public function getBriefAssignHistoryByUuid(string $uuid): ?BriefAssignHistory;

    /**
     * Fetch all assign histories for a specific brief.
     *
     * @param int $briefId The brief ID.
     * @param int $perPage The number of items per page.
     * @param \App\Models\User|null $user The user instance to scope visibility.
     * @return LengthAwarePaginator
     */

    /**
     * Added brief-wise assignment history retrieval with pagination
     * and authenticated-user visibility scoping.
     */
    public function getBriefAssignHistoriesByBriefId(int $briefId, int $perPage = 10, ?\App\Models\User $user = null): LengthAwarePaginator;

    /**
     * Fetch all assign histories assigned by a specific user.
     *
     * @param int $userId The user ID who assigned.
     * @param int $perPage The number of items per page.
     * @return LengthAwarePaginator
     */
    public function getBriefAssignHistoriesByAssignBy(int $userId, int $perPage = 10): LengthAwarePaginator;

    /**
     * Fetch all assign histories assigned to a specific user.
     *
     * @param int $userId The user ID assigned to.
     * @param int $perPage The number of items per page.
     * @return LengthAwarePaginator
     */
    public function getBriefAssignHistoriesByAssignTo(int $userId, int $perPage = 10): LengthAwarePaginator;
}
