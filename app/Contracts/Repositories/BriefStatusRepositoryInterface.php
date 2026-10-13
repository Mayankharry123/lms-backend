<?php
/**
 * Interface BriefStatusRepositoryInterface
 * 
 * @author Achal Sharma
 * @package App\Contracts\Repositories
 * @version 1.0.1
 */
namespace App\Contracts\Repositories;

use App\Models\BriefStatus;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface BriefStatusRepositoryInterface
{
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
    public function getAllBriefStatuses(int $perPage = 10, ?string $searchTerm = null): LengthAwarePaginator;

    /**
     * Fetch a single brief status by its primary ID.
     *
     * @param int $id The brief status ID.
     * @return BriefStatus|null
     */
    public function getBriefStatusById(int $id): ?BriefStatus;

    /**
     * Fetch a single brief status by its UUID.
     *
     * @param string $uuid The unique brief status UUID.
     * @return BriefStatus|null
     */
    public function getBriefStatusByUuid(string $uuid): ?BriefStatus;

    /**
     * Fetch a single brief status by its name.
     *
     * @param string $name The brief status name.
     * @return BriefStatus|null
     */
    public function getBriefStatusByName(string $name): ?BriefStatus;

    /**
     * Fetch a single brief status by its slug.
     *
     * @param string $slug The unique brief status slug.
     * @return BriefStatus|null
     */
    public function getBriefStatusBySlug(string $slug): ?BriefStatus;

    /**
     * Get all active brief statuses.
     *
     * @return LengthAwarePaginator
     */
    public function getActiveBriefStatuses(int $perPage = 10): LengthAwarePaginator;

    /**
     * Get priorities filtered by brief status ID.
     *
     * @param int $briefStatusId The brief status ID.
     * @return array
     */
    public function getPrioritiesByBriefStatusId(int $briefStatusId): array;

    /**
     * Get brief statuses filtered by priority ID.
     *
     * @param int $priorityId The priority ID.
     * @param int $perPage The number of items per page.
     * @return LengthAwarePaginator
     */
    public function getBriefStatusesByPriorityId(int $priorityId, int $perPage = 10): LengthAwarePaginator;

    // ============================================================================
    // WRITE OPERATIONS
    // ============================================================================

    /**
     * Create a new brief status record.
     *
     * @param array<string, mixed> $data
     * @return BriefStatus
     */
    public function createBriefStatus(array $data): BriefStatus;

    /**
     * Update an existing brief status by ID.
     *
     * @param int $id The brief status ID.
     * @param array<string, mixed> $data
     * @return BriefStatus
     */
    public function updateBriefStatus(int $id, array $data): BriefStatus;

    /**
     * Soft delete a brief status by ID.
     *
     * @param int $id The brief status ID.
     * @return bool
     */
    public function deleteBriefStatus(int $id): bool;
}
