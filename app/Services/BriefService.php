<?php

namespace App\Services;

use App\Contracts\Repositories\BriefRepositoryInterface;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Models\Brief;
use App\Models\Lead;
use App\Support\UserAccessScope;
use DomainException;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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
     * @var UserRepositoryInterface
     */
    protected UserRepositoryInterface $userRepository;

    /**
     * Create a new BriefService instance.
     *
     * @param BriefRepositoryInterface $briefRepository
     * @param UserRepositoryInterface $userRepository
     */
    public function __construct(
        BriefRepositoryInterface $briefRepository,
        UserRepositoryInterface $userRepository
    ) {
        $this->briefRepository = $briefRepository;
        $this->userRepository = $userRepository;
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
    /**
     * Added logic to resolve the top-level planner-admin user within the
     * organisation hierarchy for brief assignment.
     * Only users in the given organisation are considered.
     */
    public function resolveTopPlannerAdminUserId(?int $organisationId = null): ?int
    {
        if (!$organisationId) {
            return null;
        }

        $plannerAdmins = $this->userRepository->findActivePlannerAdminsByOrganisation($organisationId);

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
    /**
     * Updated brief creation to automatically assign the brief to the
     * resolved top-level planner-admin when no assignee is provided.
     */
    public function createBrief(array $data): Brief
    {
        try {
            $data = $this->applyPlannerAdminAssignment($data);

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
            $existingBrief = null;

            if ($this->assignUserIdIsEmpty($data)) {
                $existingBrief = $this->briefRepository->getBriefById($id);
                $data = $this->applyPlannerAdminAssignment($data, $existingBrief);
            }

            // Check if assign_user_id is being updated
            $fireAssignmentEvent = false;
            if (array_key_exists('assign_user_id', $data) && !empty($data['assign_user_id'])) {
                $existingBrief = $existingBrief ?? $this->briefRepository->getBriefById($id);
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
     * Set assign_user_id to the organisation planner-admin when it is empty.
     * A provided assignee is left unchanged. If no planner-admin exists, the
     * assignee stays empty and the brief is still saved.
     *
     * @param array $data
     * @param Brief|null $existingBrief
     * @return array
     */
    private function applyPlannerAdminAssignment(array $data, ?Brief $existingBrief = null): array
    {
        $assigneeProvided = array_key_exists('assign_user_id', $data) && !empty($data['assign_user_id']);

        if ($assigneeProvided) {
            return $data;
        }

        // On update, a missing assign_user_id keeps the assignee already stored.
        if ($existingBrief !== null && !array_key_exists('assign_user_id', $data)) {
            return $data;
        }

        $organisationId = $this->resolveBriefOrganisationId($data, $existingBrief);
        $plannerAdminId = $this->resolveTopPlannerAdminUserId($organisationId);

        if ($plannerAdminId) {
            $data['assign_user_id'] = $plannerAdminId;
            return $data;
        }

        Log::info('No planner-admin found for brief organisation; assign_user_id left empty', [
            'organisation_id' => $organisationId,
            'brief_id' => $existingBrief?->id,
        ]);

        return $data;
    }

    /**
     * True when the payload explicitly has an empty assign_user_id.
     * A missing key on update means the current assignee should stay.
     *
     * @param array $data
     * @return bool
     */
    private function assignUserIdIsEmpty(array $data): bool
    {
        return array_key_exists('assign_user_id', $data) && empty($data['assign_user_id']);
    }

    /**
     * Organisation linked to a saved brief through its contact person.
     *
     * @param int $briefId
     * @return int|null
     * @throws ModelNotFoundException
     * @throws DomainException
     */
    public function getOrganisationIdForBrief(int $briefId): ?int
    {
        try {
            $brief = $this->briefRepository->getBriefById($briefId);

            if (!$brief || (string) $brief->status === '15') {
                throw (new ModelNotFoundException())->setModel(Brief::class, [$briefId]);
            }

            return $this->resolveBriefOrganisationId([], $brief);
        } catch (ModelNotFoundException $e) {
            throw $e;
        } catch (QueryException $e) {
            Log::error('Database error resolving brief organisation', ['brief_id' => $briefId, 'exception' => $e]);
            throw new DomainException('Database error while fetching brief.');
        } catch (Exception $e) {
            Log::error('Unexpected error resolving brief organisation', ['brief_id' => $briefId, 'exception' => $e]);
            throw new DomainException('Unexpected error while fetching brief.');
        }
    }

    /**
     * Organisation linked to the brief through its contact person (lead).
     *
     * @param array $data
     * @param Brief|null $existingBrief
     * @return int|null
     */
    private function resolveBriefOrganisationId(array $data, ?Brief $existingBrief = null): ?int
    {
        $contactPersonId = $data['contact_person_id'] ?? $existingBrief?->contact_person_id;

        if (empty($contactPersonId)) {
            return null;
        }

        if ($existingBrief && (int) $existingBrief->contact_person_id === (int) $contactPersonId) {
            $existingBrief->loadMissing('contactPerson');
            $organisationId = $existingBrief->contactPerson?->organisation_id;

            return $organisationId ? (int) $organisationId : null;
        }

        $lead = Lead::query()->find($contactPersonId);

        return $lead && $lead->organisation_id ? (int) $lead->organisation_id : null;
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
