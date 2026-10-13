<?php
/**
 * Interface BriefRepositoryInterface
 * 
 * @author Achal Sharma
 * @package App\Contracts\Repositories
 * @version 1.0.1
 */

namespace App\Contracts\Repositories;

use App\Models\Brief;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface BriefRepositoryInterface
{
    /**
     * Fetch paginated list of briefs with relationships.
     *
     * @param int $perPage The number of items per page.
     * @param string|null $searchTerm Optional search term to filter briefs.
     * @return LengthAwarePaginator
     */
    public function getAllBriefs(int $perPage = 15, ?string $searchTerm = null): LengthAwarePaginator;

    /**
     * Fetch a single brief by its primary ID.
     *
     * @param int $id The brief ID.
     * @return Brief|null
     */
    public function getBriefById(int $id): ?Brief;

    /**
     * Fetch briefs by brand ID.
     *
     * @param int $brandId The brand ID.
     * @param int $perPage The number of items per page.
     * @return LengthAwarePaginator
     */
    public function getBriefsByBrand(int $brandId, int $perPage = 15): LengthAwarePaginator;

    /**
     * Fetch briefs by agency ID.
     *
     * @param int $agencyId The agency ID.
     * @param int $perPage The number of items per page.
     * @return LengthAwarePaginator
     */
    public function getBriefsByAgency(int $agencyId, int $perPage = 15): LengthAwarePaginator;

    /**
     * Fetch briefs by assigned user ID.
     *
     * @param int $userId The user ID.
     * @param int $perPage The number of items per page.
     * @return LengthAwarePaginator
     */
    public function getBriefsByAssignedUser(int $userId, int $perPage = 15): LengthAwarePaginator;

    /**
     * Fetch briefs by status ID.
     *
     * @param int $statusId The status ID.
     * @param int $perPage The number of items per page.
     * @return LengthAwarePaginator
     */
    public function getBriefsByStatus(int $statusId, int $perPage = 15): LengthAwarePaginator;

    /**
     * Fetch briefs by priority ID.
     *
     * @param int $priorityId The priority ID.
     * @param int $perPage The number of items per page.
     * @return LengthAwarePaginator
     */
    public function getBriefsByPriority(int $priorityId, int $perPage = 15): LengthAwarePaginator;

    /**
     * Create a new brief.
     *
     * @param array $data The brief data.
     * @return Brief
     */
    public function createBrief(array $data): Brief;

    /**
     * Update an existing brief.
     *
     * @param int $id The brief ID.
     * @param array $data The brief data to update.
     * @return Brief|null
     */
    public function updateBrief(int $id, array $data): ?Brief;

    /**
     * Delete a brief by ID.
     *
     * @param int $id The brief ID.
     * @return bool
     */
    public function deleteBrief(int $id): bool;

    /**
     * Get brief with all relationships loaded.
     *
     * @param int $id The brief ID.
     * @return Brief|null
     */
    public function getBriefWithRelations(int $id): ?Brief;

    /**
     * Search briefs by multiple criteria.
     *
     * @param array $criteria The search criteria.
     * @param int $perPage The number of items per page.
     * @return LengthAwarePaginator
     */
    public function searchBriefs(array $criteria, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get briefs with pagination and filters.
     *
     * @param array $filters The filter criteria.
     * @param int $perPage The number of items per page.
     * @return LengthAwarePaginator
     */
    public function filterBriefs(array $filters, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get the latest two briefs.
     *
     * @return Collection
     */
    public function getLatestTwoBriefs(array $filters = []);

    /**
     * Get the latest two briefs.
     *
     * @return Collection
     */
    public function getLatestFiveBriefs(array $filters = []);

    /**
     * Get recent briefs with all related information.
     *
     * @param int $limit The number of briefs to retrieve.
     * @return Collection
     */
    public function getRecentBriefs(int $limit = 5, array $filters = []);


    /**
     * Get planner dashboard card data.
     *
     * @param array $filters
     * @return array
     */
    public function getPlannerDashboardCardData(array $filters = []);

    /**
     * Get brief logs with pagination.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getBriefLogs(int $perPage = 10, ?string $searchTerm = null): LengthAwarePaginator;

    /**
     * Get business forecast data including total budget and business weightage.
     *
     * @return array
     */
    public function getBusinessForecast(array $filters = []): array;

    
    /**
     * Get brief count statistics for a given priority.
     *
     * @param int $priorityId
     * @param array $filters
     * @return array
     */
    public function getBriefCountStatsForPriority(int $priorityId, array $filters): array;
}
