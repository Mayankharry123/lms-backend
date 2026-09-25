<?php

namespace App\Repositories;

use App\Contracts\Repositories\BriefRepositoryInterface;
use App\Models\Brief;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

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
        $query = $this->model
            ->with(self::DEFAULT_RELATIONSHIPS)
            ->accessibleToUser(Auth::user());

        $this->applyOrganisationValidation($query, Auth::user());

        // Apply search filter if search term is provided
        if ($searchTerm !== null && $searchTerm !== '') {
            $this->applySearchFilter($query, $searchTerm);
        }

        return $query
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->appends(request()->query());
    }

    /**
     * Fetch a single brief by its primary ID.
     *
     * @param int $id The brief ID.
     * @return Brief|null
     */
    public function getBriefById(int $id): ?Brief
    {
        return $this->model
            ->with(self::DEFAULT_RELATIONSHIPS)
            ->find($id);
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
        $query = $this->model
            ->with(self::DEFAULT_RELATIONSHIPS)
            ->accessibleToUser(Auth::user())
            ->where('brand_id', $brandId);

        $this->applyOrganisationValidation($query, Auth::user());

        return $query
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->appends(request()->query());
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
        $query = $this->model
            ->with(self::DEFAULT_RELATIONSHIPS)
            ->accessibleToUser(Auth::user())
            ->where('agency_id', $agencyId);

        $this->applyOrganisationValidation($query, Auth::user());

        return $query
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->appends(request()->query());
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
        $query = $this->model
            ->with(self::DEFAULT_RELATIONSHIPS)
            ->accessibleToUser(Auth::user())
            ->where('assign_user_id', $userId);

        $this->applyOrganisationValidation($query, Auth::user());

        return $query
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->appends(request()->query());
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
        $query = $this->model
            ->with(self::DEFAULT_RELATIONSHIPS)
            ->accessibleToUser(Auth::user())
            ->where('brief_status_id', $statusId);

        $this->applyOrganisationValidation($query, Auth::user());

        return $query
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->appends(request()->query());
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
        $query = $this->model
            ->with(self::DEFAULT_RELATIONSHIPS)
            ->accessibleToUser(Auth::user())
            ->where('priority_id', $priorityId);

        $this->applyOrganisationValidation($query, Auth::user());

        return $query
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->appends(request()->query());
    }

    /**
     * Get brief with all relationships loaded.
     *
     * @param int $id The brief ID.
     * @return Brief|null
     */
    public function getBriefWithRelations(int $id): ?Brief
    {
        return $this->model
            ->with(self::DEFAULT_RELATIONSHIPS)
            ->find($id);
    }

    /**
     * Get the latest two briefs.
     *
     * @return Collection
     */
    public function getLatestTwoBriefs(array $filters = [])
    {
        $query = $this->model
            ->with(self::DEFAULT_RELATIONSHIPS)
            ->accessibleToUser(Auth::user())
            ->whereNull('briefs.deleted_at')
            ->whereRaw('briefs.status != 15');

        $this->applyOrganisationValidation($query, Auth::user());
        \App\Support\DashboardFilters::applyBriefDashboardFilters($query, $filters, 'briefs');

        return $query
            ->orderBy('briefs.created_at', 'desc')
            ->limit(2)
            ->get();
    }

    /**
     * Get the latest five briefs.
     *
     * @return Collection
     */
    public function getLatestFiveBriefs(array $filters = [])
    {
        $query = $this->model
            ->with(self::DEFAULT_RELATIONSHIPS)
            ->accessibleToUser(Auth::user())
            ->whereNull('briefs.deleted_at')
            ->whereRaw('briefs.status != 15');

        $this->applyLatestBriefOrganisationFilter($query, $filters);
        \App\Support\DashboardFilters::applyDateFilter($query, $filters, 'briefs.created_at');
        \App\Support\DashboardFilters::applyLeadPriorityFilter($query, $filters, 'briefs');

        return $query
            ->orderBy('briefs.created_at', 'desc')
            ->limit(5)
            ->get();
    }

    /**
     * A latest brief matches a selected organisation when its contact person
     * is in that organisation, or when its creator or assignee belongs to it.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array<string, mixed> $filters
     */
    protected function applyLatestBriefOrganisationFilter($query, array $filters): void
    {
        $organisationIds = array_values(array_filter(array_map(
            'intval',
            $filters['organisation_ids'] ?? []
        )));

        if ($organisationIds === []) {
            $query->whereRaw('0 = 1');

            return;
        }

        $userIds = \App\Support\DashboardFilters::getOrganisationUserIds($organisationIds);

        $query->where(function ($builder) use ($organisationIds, $userIds) {
            $builder->whereHas('contactPerson', function ($contactQuery) use ($organisationIds) {
                $contactQuery->whereIn('leads.organisation_id', $organisationIds);
            });

            if ($userIds === []) {
                return;
            }

            $builder->orWhereIn('briefs.created_by', $userIds)
                ->orWhereIn('briefs.assign_user_id', $userIds);
        });

        $user = Auth::user();
        if (!$user) {
            return;
        }

        $ancestorIds = \App\Support\UserAccessScope::getAncestorIds($user);
        if ($ancestorIds === []) {
            return;
        }

        $descendantIds = \App\Support\UserAccessScope::getStrictDescendantIds($user);
        $query->where(function ($builder) use ($ancestorIds, $descendantIds) {
            $builder->whereNotIn('briefs.created_by', $ancestorIds)
                ->orWhereIn('briefs.assign_user_id', $descendantIds);
        });
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
        $query = $this->model->with(self::DEFAULT_RELATIONSHIPS)->accessibleToUser(Auth::user());

        $this->applyOrganisationValidation($query, Auth::user());

        foreach ($criteria as $field => $value) {
            if ($value !== null && $value !== '') {
                $query->where($field, $value);
            }
        }

        return $query
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->appends(request()->query());
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
        $query = $this->model->with(self::DEFAULT_RELATIONSHIPS)->accessibleToUser(Auth::user());

        $this->applyOrganisationValidation($query, Auth::user());

        // Apply filters
        if (isset($filters['brand_id']) && !empty($filters['brand_id'])) {
            $query->where('brand_id', $filters['brand_id']);
        }

        if (isset($filters['agency_id']) && !empty($filters['agency_id'])) {
            $query->where('agency_id', $filters['agency_id']);
        }

        if (isset($filters['assign_user_id']) && !empty($filters['assign_user_id'])) {
            $query->where('assign_user_id', $filters['assign_user_id']);
        }

        if (isset($filters['brief_status_id']) && !empty($filters['brief_status_id'])) {
            $query->where('brief_status_id', $filters['brief_status_id']);
        }

        if (isset($filters['priority_id']) && !empty($filters['priority_id'])) {
            $query->where('priority_id', $filters['priority_id']);
        }

        if (isset($filters['status']) && !empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['search']) && !empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'LIKE', "%{$filters['search']}%")
                  ->orWhere('product_name', 'LIKE', "%{$filters['search']}%");
            });
        }

        return $query
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->appends(request()->query());
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
        $query = $this->model
            ->with(self::DEFAULT_RELATIONSHIPS)
            ->accessibleToUser(Auth::user())
            ->whereNull('briefs.deleted_at')
            ->whereRaw('briefs.status != 15');

        $this->applyOrganisationValidation($query, Auth::user());
        \App\Support\DashboardFilters::applyBriefDashboardFilters($query, $filters, 'briefs');

        return $query
            ->orderBy('briefs.created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getPlannerDashboardCardData(array $filters = []): array
    {
        $baseQuery = $this->model
            ->accessibleToUser(Auth::user())
            ->whereNull('briefs.deleted_at')
            ->whereRaw('briefs.status != 15');

        $this->applyOrganisationValidation($baseQuery, Auth::user());
        \App\Support\DashboardFilters::applyBriefDashboardFilters($baseQuery, $filters, 'briefs');

        $activeBriefs = (clone $baseQuery)
            ->whereDate('submission_date', '>=', now())
            ->count();

        $closedBriefs = (clone $baseQuery)
            ->whereHas('briefStatus', function ($query) {
                $query->where('slug', 'closed');
            })
            ->count();

        $overdueTime = (clone $baseQuery)
            ->where('submission_date', '<', now())
            ->count();

        $averagePlanningTime = (clone $baseQuery)
            ->selectRaw('AVG(DATEDIFF(submission_date, created_at)) as avg_days')
            ->value('avg_days');

        $briefIds = (clone $baseQuery)->pluck('briefs.id');
        $assignedPlans = $briefIds->isEmpty()
            ? 0
            : \App\Models\Planner::query()
                ->whereNull('deleted_at')
                ->whereIn('brief_id', $briefIds)
                ->count();

        $avgAssignmentDays = 0;
        if (!$briefIds->isEmpty()) {
            $plannerQuery = \App\Models\Planner::query()
                ->whereNull('planners.deleted_at')
                ->whereIn('planners.brief_id', $briefIds);

            $submittedQuery = \App\Support\PlannerMetrics::applySubmittedPlansScope(clone $plannerQuery);
            $avgAssignmentDays = $submittedQuery
                ->selectRaw(
                    'AVG(' . \App\Support\PlannerMetrics::assignmentToSubmissionDaysSql() . ') as avg_days'
                )
                ->value('avg_days');
        }

        return [
            'active_briefs' => $activeBriefs,
            'closed_briefs' => $closedBriefs,
            'overdue_briefs' => $overdueTime ?? 0,
            'assigned_plans' => $assignedPlans,
            'average_planning_time_days' => $averagePlanningTime ? round($averagePlanningTime, 2) : 0,
            'average_assignment_days' => $avgAssignmentDays ? round((float) $avgAssignmentDays, 1) : 0,
        ];
    }

    /**
     * Get brief logs with pagination.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getBriefLogs(int $perPage = 10, ?string $searchTerm = null): LengthAwarePaginator
    {
        $query = $this->model
            ->with(self::DEFAULT_RELATIONSHIPS)
            ->accessibleToUser(Auth::user());

        $this->applyOrganisationValidation($query, Auth::user());

        // Apply search filter if search term is provided
        if ($searchTerm !== null && $searchTerm !== '') {
            $this->applySearchFilter($query, $searchTerm);
        }

        return $query
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->appends(request()->query());
    }

    /**
     * Apply search filter to a query builder instance.
     * Filters by name, product_name, and related relationships (brand, contactPerson, priority, assignedUser).
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $searchTerm
     */
    private function applySearchFilter($query, string $searchTerm): void
    {
        $query->where(function ($q) use ($searchTerm) {
            $q->where('name', 'LIKE', "%{$searchTerm}%")
              ->orWhere('product_name', 'LIKE', "%{$searchTerm}%")
              ->orWhereHas('brand', function ($brandQuery) use ($searchTerm) {
                  $brandQuery->where('name', 'LIKE', "%{$searchTerm}%");
              })
              ->orWhereHas('contactPerson', function ($contactQuery) use ($searchTerm) {
                  $contactQuery->where('name', 'LIKE', "%{$searchTerm}%");
              })
              ->orWhereHas('priority', function ($priorityQuery) use ($searchTerm) {
                  $priorityQuery->where('name', 'LIKE', "%{$searchTerm}%");
              })
              ->orWhereHas('assignedUser', function ($userQuery) use ($searchTerm) {
                  $userQuery->where('name', 'LIKE', "%{$searchTerm}%");
              });
        });
    }

    /**
     * Get business forecast data including total budget and business weightage.
     * Business weightage = (sum of brief_status percentages / total brief count) * 100
     *
     * @return array
     */
    public function getBusinessForecast(array $filters = []): array
    {
        $query = $this->model
            ->accessibleToUser(Auth::user())
            ->whereNull('briefs.deleted_at')
            ->whereRaw('briefs.status != 15');

        $this->applyOrganisationValidation($query, Auth::user());
        \App\Support\DashboardFilters::applyBriefDashboardFilters($query, $filters, 'briefs');

        $totalBudget = (clone $query)->sum('briefs.budget');
        $totalBriefCount = (clone $query)->count();

        $businessWeightage = 0;
        if ($totalBriefCount > 0) {
            $totalStatusPercentage = (clone $query)
                ->join('brief_statuses', 'briefs.brief_status_id', '=', 'brief_statuses.id')
                ->sum('brief_statuses.percentage');

            $businessWeightage = round(($totalStatusPercentage / $totalBriefCount), 2);
        }

        return [
            'total_budget' => (float) $totalBudget,
            'total_brief_count' => $totalBriefCount,
            'business_weightage' => $businessWeightage,
        ];
    }

    /**
     * Ensure briefs belong to the user's organisation.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \App\Models\User|null $user
     */
    protected function applyOrganisationValidation($query, $user): void
    {
        if (!$user) {
            return;
        }

        $userOrgIds = \App\Support\UserAccessScope::getAccessibleOrganisationIds($user);

        if (empty($userOrgIds)) {
            // If user has no organisation, they see NO briefs
            $query->whereRaw('0 = 1');
        } else {
            // If user has an organisation, they MUST only see briefs from that organisation
            $orgUserIds = \App\Support\DashboardFilters::getOrganisationUserIds($userOrgIds);

            if (empty($orgUserIds)) {
                $query->whereRaw('0 = 1');
            } else {
                $query->where(function ($q) use ($orgUserIds) {
                    $q->whereIn('assign_user_id', $orgUserIds)
                      ->orWhereIn('created_by', $orgUserIds);
                });

                // Explicitly exclude briefs created by the user's ancestors (e.g. parents)
                $ancestorIds = \App\Support\UserAccessScope::getAncestorIds($user);
                if (!empty($ancestorIds)) {
                    $query->where(function ($q) use ($ancestorIds, $user) {
                        $descendantIds = \App\Support\UserAccessScope::getStrictDescendantIds($user);
                        $q->whereNotIn('created_by', $ancestorIds)
                          ->orWhereIn('assign_user_id', $descendantIds);
                    });
                }
            }
        }
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