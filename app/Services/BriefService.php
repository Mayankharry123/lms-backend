<?php

namespace App\Services;

use App\Contracts\Repositories\BriefRepositoryInterface;
use App\Models\Brief;
use App\Models\User;
use App\Support\UserAccessScope;
use DomainException;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use App\Events\BriefAssignedEvent;

class BriefService
{
    /**
     * @var BriefRepositoryInterface
     */
    protected BriefRepositoryInterface $briefRepository;

    /**
     * Create a new BriefService instance.
     *
     * @param BriefRepositoryInterface $briefRepository
     */
    public function __construct(BriefRepositoryInterface $briefRepository)
    {
        $this->briefRepository = $briefRepository;
    }

    // ============================================================================
    // READ OPERATIONS
    // ============================================================================

    /**
     * Get all briefs with pagination.
     *
     * @param int $perPage
     * @param string|null $searchTerm
     * @return LengthAwarePaginator
     * @throws DomainException
     */
    public function getAllBriefs(int $perPage = 15, ?string $searchTerm = null): LengthAwarePaginator
    {
        try {
            return $this->briefRepository->getAllBriefs($perPage, $searchTerm);
        } catch (QueryException $e) {
            Log::error('Database error fetching briefs', ['exception' => $e]);
            throw new DomainException('Database error while fetching briefs.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching briefs', ['exception' => $e]);
            throw new DomainException('Unexpected error while fetching briefs.');
        }
    }

    /**
     * Get a brief by ID.
     *
     * @param int $id
     * @return Brief|null
     * @throws DomainException
     */
    public function getBrief(int $id): ?Brief
    {
        try {
            return $this->briefRepository->getBriefById($id);
        } catch (QueryException $e) {
            Log::error('Database error fetching brief', ['id' => $id, 'exception' => $e]);
            throw new DomainException('Database error while fetching brief.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching brief', ['id' => $id, 'exception' => $e]);
            throw new DomainException('Unexpected error while fetching brief.');
        }
    }

    /**
     * Get briefs by brand ID.
     *
     * @param int $brandId
     * @param int $perPage
     * @return LengthAwarePaginator
     * @throws DomainException
     */
    public function getBriefsByBrand(int $brandId, int $perPage = 15): LengthAwarePaginator
    {
        try {
            return $this->briefRepository->getBriefsByBrand($brandId, $perPage);
        } catch (QueryException $e) {
            Log::error('Database error fetching briefs by brand', ['brand_id' => $brandId, 'exception' => $e]);
            throw new DomainException('Database error while fetching briefs by brand.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching briefs by brand', ['brand_id' => $brandId, 'exception' => $e]);
            throw new DomainException('Unexpected error while fetching briefs by brand.');
        }
    }

    /**
     * Get briefs by agency ID.
     *
     * @param int $agencyId
     * @param int $perPage
     * @return LengthAwarePaginator
     * @throws DomainException
     */
    public function getBriefsByAgency(int $agencyId, int $perPage = 15): LengthAwarePaginator
    {
        try {
            return $this->briefRepository->getBriefsByAgency($agencyId, $perPage);
        } catch (QueryException $e) {
            Log::error('Database error fetching briefs by agency', ['agency_id' => $agencyId, 'exception' => $e]);
            throw new DomainException('Database error while fetching briefs by agency.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching briefs by agency', ['agency_id' => $agencyId, 'exception' => $e]);
            throw new DomainException('Unexpected error while fetching briefs by agency.');
        }
    }

    /**
     * Get briefs by assigned user ID.
     *
     * @param int $userId
     * @param int $perPage
     * @return LengthAwarePaginator
     * @throws DomainException
     */
    public function getBriefsByAssignedUser(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        try {
            return $this->briefRepository->getBriefsByAssignedUser($userId, $perPage);
        } catch (QueryException $e) {
            Log::error('Database error fetching briefs by user', ['user_id' => $userId, 'exception' => $e]);
            throw new DomainException('Database error while fetching briefs by user.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching briefs by user', ['user_id' => $userId, 'exception' => $e]);
            throw new DomainException('Unexpected error while fetching briefs by user.');
        }
    }

    /**
     * Get briefs by status ID.
     *
     * @param int $statusId
     * @param int $perPage
     * @return LengthAwarePaginator
     * @throws DomainException
     */
    public function getBriefsByStatus(int $statusId, int $perPage = 15): LengthAwarePaginator
    {
        try {
            return $this->briefRepository->getBriefsByStatus($statusId, $perPage);
        } catch (QueryException $e) {
            Log::error('Database error fetching briefs by status', ['status_id' => $statusId, 'exception' => $e]);
            throw new DomainException('Database error while fetching briefs by status.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching briefs by status', ['status_id' => $statusId, 'exception' => $e]);
            throw new DomainException('Unexpected error while fetching briefs by status.');
        }
    }

    /**
     * Get briefs by priority ID.
     *
     * @param int $priorityId
     * @param int $perPage
     * @return LengthAwarePaginator
     * @throws DomainException
     */
    public function getBriefsByPriority(int $priorityId, int $perPage = 15): LengthAwarePaginator
    {
        try {
            return $this->briefRepository->getBriefsByPriority($priorityId, $perPage);
        } catch (QueryException $e) {
            Log::error('Database error fetching briefs by priority', ['priority_id' => $priorityId, 'exception' => $e]);
            throw new DomainException('Database error while fetching briefs by priority.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching briefs by priority', ['priority_id' => $priorityId, 'exception' => $e]);
            throw new DomainException('Unexpected error while fetching briefs by priority.');
        }
    }

    /**
     * Search briefs with filters.
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     * @throws DomainException
     */
    public function getBriefsByFilters(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        try {
            return $this->briefRepository->filterBriefs($filters, $perPage);
        } catch (QueryException $e) {
            Log::error('Database error filtering briefs', ['filters' => $filters, 'exception' => $e]);
            throw new DomainException('Database error while filtering briefs.');
        } catch (Exception $e) {
            Log::error('Unexpected error filtering briefs', ['filters' => $filters, 'exception' => $e]);
            throw new DomainException('Unexpected error while filtering briefs.');
        }
    }

    /**
     * Get the latest two briefs.
     *
     * @return Collection
     * @throws DomainException
     */
    public function getLatestTwoBriefs(array $filters = [])
    {
        try {
            return $this->briefRepository->getLatestTwoBriefs($filters);
        } catch (QueryException $e) {
            Log::error('Database error fetching latest two briefs', ['exception' => $e]);
            throw new DomainException('Database error while fetching latest briefs.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching latest two briefs', ['exception' => $e]);
            throw new DomainException('Unexpected error while fetching latest briefs.');
        }
    }

    /**
     * Get the latest five briefs.
     *
     * @return Collection
     * @throws DomainException
     */
    public function getLatestFiveBriefs(array $filters = [])
    {
        try {
            return $this->briefRepository->getLatestFiveBriefs($filters);
        } catch (QueryException $e) {
            Log::error('Database error fetching latest five briefs', ['exception' => $e]);
            throw new DomainException('Database error while fetching latest briefs.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching latest five briefs', ['exception' => $e]);
            throw new DomainException('Unexpected error while fetching latest briefs.');
        }
    }

    public function getPlannerDashboardCardData(array $filters = []): array
    {
        try {
            return $this->briefRepository->getPlannerDashboardCardData($filters);
        } catch (QueryException $e) {
            Log::error('Database error fetching planner dashboard card data', ['exception' => $e]);
            throw new DomainException('Database error while fetching planner dashboard data.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching planner dashboard card data', ['exception' => $e]);
            throw new DomainException('Unexpected error while fetching planner dashboard data.');
        }
    }

    /**
     * Get brief logs with pagination.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     * @throws DomainException
     */
    public function getBriefLogs(int $perPage = 10, ?string $searchTerm = null): LengthAwarePaginator
    {
        try {
            return $this->briefRepository->getBriefLogs($perPage, $searchTerm);
        } catch (QueryException $e) {
            Log::error('Database error fetching brief logs', ['exception' => $e]);
            throw new DomainException('Database error while fetching brief logs.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching brief logs', ['exception' => $e]);
            throw new DomainException('Unexpected error while fetching brief logs.');
        }
    }

    

    /**
     * Get brief count statistics for a priority.
     *
     * @param int $priorityId
     * @param array $filters
     * @return array
     * @throws DomainException
     */
    public function getBriefCountStatsForPriority(int $priorityId, array $filters): array
    {
        try {
            return $this->briefRepository->getBriefCountStatsForPriority($priorityId, $filters);
        } catch (Exception $e) {
            Log::error('Error fetching brief count stats for priority', ['priority_id' => $priorityId, 'exception' => $e]);
            throw new DomainException('Error while fetching brief count stats for priority.');
        }
    }

    // ============================================================================
    // WRITE OPERATIONS
    // ============================================================================

    /**
     * Resolve the top-level user ID in the hierarchy with role slug 'planner-admin'.
     *
     * @param int|null $organisationId
     * @return int|null
     */
    public function resolveTopPlannerAdminUserId(?int $organisationId = null): ?int
    {
        $query = User::active()->whereHas('roles', function ($q) {
            $q->where('slug', 'planner-admin');
        });

        if ($organisationId) {
            $orgQuery = (clone $query)->where(function ($q) use ($organisationId) {
                $q->where('organisation_id', $organisationId)
                  ->orWhereHas('organisations', function ($oq) use ($organisationId) {
                      $oq->where('organisations.id', $organisationId);
                  });
            });
            $orgAdmins = $orgQuery->get();
            $plannerAdmins = $orgAdmins->isNotEmpty() ? $orgAdmins : $query->get();
        } else {
            $plannerAdmins = $query->get();
        }

        if ($plannerAdmins->isEmpty()) {
            return null;
        }

        if ($plannerAdmins->count() === 1) {
            return (int) $plannerAdmins->first()->id;
        }

        $adminIds = $plannerAdmins->pluck('id')->map(fn ($id) => (int) $id)->toArray();

        foreach ($plannerAdmins as $admin) {
            $ancestorIds = UserAccessScope::getAncestorIds($admin);
            $plannerAdminAncestors = array_intersect($ancestorIds, $adminIds);
            if (empty($plannerAdminAncestors)) {
                return (int) $admin->id;
            }
        }

        return (int) $plannerAdmins->first()->id;
    }

    /**
     * Create a new brief.
     *
     * @param array $data
     * @return Brief
     * @throws DomainException
     */
    public function createBrief(array $data): Brief
    {
        try {
            // Always ensure assign_user_id is resolved to top planner-admin in the hierarchy
            if (empty($data['assign_user_id'])) {
                $data['assign_user_id'] = $this->resolveTopPlannerAdminUserId();
            }

            $brief = $this->briefRepository->createBrief($data);

            // If assign_user_id is set, fire the assignment event to create notification
            if (!empty($data['assign_user_id'])) {
                event(new BriefAssignedEvent($brief->id, $data['assign_user_id']));
            }

            return $brief;
        } catch (QueryException $e) {
            Log::error('Database error creating brief', ['exception' => $e]);
            throw new DomainException('Database error while creating brief.');
        } catch (Exception $e) {
            Log::error('Unexpected error creating brief', ['exception' => $e]);
            throw new DomainException('Unexpected error while creating brief.');
        }
    }

    /**
     * Update an existing brief.
     *
     * @param int $id
     * @param array $data
     * @return Brief|null
     * @throws DomainException
     */
    public function updateBrief(int $id, array $data): ?Brief
    {
        try {
            // Check if assign_user_id is being updated
            $fireAssignmentEvent = false;
            if (isset($data['assign_user_id'])) {
                $existingBrief = $this->briefRepository->getBriefById($id);
                if ($existingBrief && $existingBrief->assign_user_id != $data['assign_user_id']) {
                    $fireAssignmentEvent = true;
                }
            }

            $brief = $this->briefRepository->updateBrief($id, $data);

            // Fire assignment event if assign_user_id was changed
            if ($fireAssignmentEvent && $brief && isset($data['assign_user_id'])) {
                event(new BriefAssignedEvent($brief->id, $data['assign_user_id']));
            }

            return $brief;
        } catch (QueryException $e) {
            Log::error('Database error updating brief', ['id' => $id, 'exception' => $e]);
            throw new DomainException('Database error while updating brief.');
        } catch (Exception $e) {
            Log::error('Unexpected error updating brief', ['id' => $id, 'exception' => $e]);
            throw new DomainException('Unexpected error while updating brief.');
        }
    }

    /**
     * Delete a brief.
     *
     * @param int $id
     * @return bool
     * @throws DomainException
     */
    public function deleteBrief(int $id): bool
    {
        try {
            return $this->briefRepository->deleteBrief($id);
        } catch (QueryException $e) {
            Log::error('Database error deleting brief', ['id' => $id, 'exception' => $e]);
            throw new DomainException('Database error while deleting brief.');
        } catch (Exception $e) {
            Log::error('Unexpected error deleting brief', ['id' => $id, 'exception' => $e]);
            throw new DomainException('Unexpected error while deleting brief.');
        }
    }

    /**
     * Get recent briefs with all related information.
     *
     * @param int $limit
     * @return Collection
     * @throws DomainException
     */
    public function getRecentBriefs(int $limit = 5, array $filters = []): Collection
    {
        try {
            return $this->briefRepository->getRecentBriefs($limit, $filters);
        } catch (QueryException $e) {
            Log::error('Database error fetching recent briefs', ['exception' => $e]);
            throw new DomainException('Database error while fetching recent briefs.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching recent briefs', ['exception' => $e]);
            throw new DomainException('Unexpected error while fetching recent briefs.');
        }
    }

    /**
     * Get business forecast data including total budget and business weightage.
     *
     * @return array
     * @throws DomainException
     */
    public function getBusinessForecast(array $filters = []): array
    {
        try {
            return $this->briefRepository->getBusinessForecast($filters);
        } catch (QueryException $e) {
            Log::error('Database error fetching business forecast', ['exception' => $e]);
            throw new DomainException('Database error while fetching business forecast.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching business forecast', ['exception' => $e]);
            throw new DomainException('Unexpected error while fetching business forecast.');
        }
    }
}
