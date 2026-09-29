<?php

namespace App\Repositories;

use App\Contracts\Repositories\BriefRepositoryInterface;
use App\Models\Brief;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class BriefRepository implements BriefRepositoryInterface
{
    /**
     * Default relationships to eager load.
     *
     * @var array<string>
     */
    protected const DEFAULT_RELATIONSHIPS = [
        'contactPerson.organisation',
        'brand',
        'agency',
        'assignedUser',
        'createdByUser',
        'briefStatus',
        'priority',
    ];

    /**
     * @var Brief
     */
    protected Brief $model;

    /**
     * Create a new BriefRepository instance.
     *
     * @param Brief $brief
     */
    public function __construct(Brief $brief)
    {
        $this->model = $brief;
    }

    // ============================================================================
    // READ OPERATIONS
    // ============================================================================

    /**
     * Fetch paginated list of briefs with relationships.
     * Filtered by user access: Only creator and assigned user can see.
     *
     * @param int $perPage The number of items per page.
     * @param string|null $searchTerm Optional search term to filter briefs.
     * @return LengthAwarePaginator
     */
    public function getAllBriefs(int $perPage = 15, ?string $searchTerm = null): LengthAwarePaginator
    {
        return $this->model->getAllBriefs($perPage, $searchTerm);
    }

    /**
     * Fetch a single brief by its primary ID.
     *
     * @param int $id The brief ID.
     * @return Brief|null
     */
    public function getBriefById(int $id): ?Brief
    {
        return $this->model->getBriefById($id);
    }

    /**
     * Fetch briefs by brand ID.
     *
     * @param int $brandId The brand ID.
     * @param int $perPage The number of items per page.
     * @return LengthAwarePaginator
     */
    public function getBriefsByBrand(int $brandId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->getBriefsByBrand($brandId, $perPage);
    }

    /**
     * Fetch briefs by agency ID.
     *
     * @param int $agencyId The agency ID.
     * @param int $perPage The number of items per page.
     * @return LengthAwarePaginator
     */
    public function getBriefsByAgency(int $agencyId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->getBriefsByAgency($agencyId, $perPage);
    }

    /**
     * Fetch briefs by assigned user ID.
     *
     * @param int $userId The user ID.
     * @param int $perPage The number of items per page.
     * @return LengthAwarePaginator
     */
    public function getBriefsByAssignedUser(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->getBriefsByAssignedUser($userId, $perPage);
    }

    /**
     * Fetch briefs by status ID.
     *
     * @param int $statusId The status ID.
     * @param int $perPage The number of items per page.
     * @return LengthAwarePaginator
     */
    public function getBriefsByStatus(int $statusId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->getBriefsByStatus($statusId, $perPage);
    }

    /**
     * Fetch briefs by priority ID.
     *
     * @param int $priorityId The priority ID.
     * @param int $perPage The number of items per page.
     * @return LengthAwarePaginator
     */
    public function getBriefsByPriority(int $priorityId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->getBriefsByPriority($priorityId, $perPage);
    }

    /**
     * Get brief with all relationships loaded.
     *
     * @param int $id The brief ID.
     * @return Brief|null
     */
    public function getBriefWithRelations(int $id): ?Brief
    {
        return $this->model->getBriefWithRelations($id);
    }

    /**
     * Get the latest two briefs.
     *
     * @return Collection
     */
    public function getLatestTwoBriefs(array $filters = [])
    {
        return $this->model->getLatestTwoBriefs($filters);
    }

    /**
     * Get the latest five briefs.
     *
     * @return Collection
     */
    public function getLatestFiveBriefs(array $filters = [])
    {
        return $this->model->getLatestFiveBriefs($filters);
    }

    /**
     * Search briefs by multiple criteria.
     *
     * @param array $criteria The search criteria.
     * @param int $perPage The number of items per page.
     * @return LengthAwarePaginator
     */
    public function searchBriefs(array $criteria, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->searchBriefs($criteria, $perPage);
    }

    /**
     * Get briefs with pagination and filters.
     *
     * @param array $filters The filter criteria.
     * @param int $perPage The number of items per page.
     * @return LengthAwarePaginator
     */
    public function filterBriefs(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->filterBriefs($filters, $perPage);
    }

    // ============================================================================
    // WRITE OPERATIONS
    // ============================================================================

    /**
     * Create a new brief.
     *
     * @param array $data The brief data.
     * @return Brief
     */
    public function createBrief(array $data): Brief
    {
        try {
            $brief = $this->model->create($data);
            Log::info('Brief created successfully', ['brief_id' => $brief->id]);
            return $brief;
        } catch (\Exception $e) {
            Log::error('Error creating brief', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Update an existing brief.
     *
     * @param int $id The brief ID.
     * @param array $data The brief data to update.
     * @return Brief|null
     */
    public function updateBrief(int $id, array $data): ?Brief
    {
        try {
            $brief = $this->model->find($id);
            if (!$brief) {
                return null;
            }

            $brief->update($data);
            Log::info('Brief updated successfully', ['brief_id' => $id]);
            return $brief->fresh(self::DEFAULT_RELATIONSHIPS);
        } catch (\Exception $e) {
            Log::error('Error updating brief', ['brief_id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Delete a brief by ID.
     *
     * @param int $id The brief ID.
     * @return bool
     */
    public function deleteBrief(int $id): bool
    {
        try {
            $brief = $this->model->find($id);
            if (!$brief) {
                return false;
            }

            $brief->delete();
            Log::info('Brief deleted successfully', ['brief_id' => $id]);
            return true;
        } catch (\Exception $e) {
            Log::error('Error deleting brief', ['brief_id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Get recent briefs with all related information.
     *
     * @param int $limit
     * @return Collection
     */
    public function getRecentBriefs(int $limit = 5, array $filters = [])
    {
        return $this->model->getRecentBriefs($limit, $filters);
    }

    public function getPlannerDashboardCardData(array $filters = []): array
    {
        return $this->model->getPlannerDashboardCardData($filters);
    }

    /**
     * Get brief logs with pagination.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getBriefLogs(int $perPage = 10, ?string $searchTerm = null): LengthAwarePaginator
    {
        return $this->model->getBriefLogs($perPage, $searchTerm);
    }

    /**
     * Get business forecast data including total budget and business weightage.
     * Business weightage = (sum of brief_status percentages / total brief count) * 100
     *
     * @return array
     */
    public function getBusinessForecast(array $filters = []): array
    {
        return $this->model->getBusinessForecast($filters);
    }
    /**
     * Get brief count statistics for a given priority.
     *
     * @param int $priorityId
     * @param array $filters
     * @return array
     */
    public function getBriefCountStatsForPriority(int $priorityId, array $filters): array
    {
        return $this->model->getBriefCountStatsForPriority($priorityId, $filters);
    }
}