<?php

namespace App\Models;

use App\Support\UserAccessScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;

class Brief extends Model
{
    use HasFactory, SoftDeletes;

    protected const DEFAULT_RELATIONSHIPS = [
        'contactPerson.organisation',
        'brand',
        'agency',
        'assignedUser',
        'createdByUser',
        'briefStatus',
        'costSheetStatus',
        'priority',
    ];

    // Campaign Mode Constants
    const MODE_PROGRAMMATIC = 'programmatic';
    const MODE_NON_PROGRAMMATIC = 'non_programmatic';

    // Campaign Type Constants (Media Types)
    const TYPE_PROGRAMMATIC_DOOH = 'dooh';
    const TYPE_PROGRAMMATIC_CTV = 'ctv';
    const TYPE_NON_PROGRAMMATIC_DOOH = 'dooh';
    const TYPE_NON_PROGRAMMATIC_OOH = 'ooh';

    // Fields to track for history
    const TRACKED_FIELDS = ['assign_user_id', 'brief_status_id', 'submission_date', 'comment'];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'briefs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'product_name',
        'contact_person_id',
        'brand_id',
        'agency_id',
        'mode_of_campaign',
        'media_type',
        'budget',
        'assign_user_id',
        'created_by',
        'brief_status_id',
        'cost_sheet_status_id',
        'priority_id',
        'comment',
        'attachment',
        'submission_date',
        'status',
        'campaign_start_date',
        'campaign_end_date',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'submission_date' => 'datetime',
        'campaign_start_date' => 'date',
        'campaign_end_date' => 'date',
        'budget' => 'decimal:2',
        'attachment' => 'string',
        'campaign_duration' => 'integer',
    ];

    /**
     * The attributes that should be appended to JSON.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'latest_planner_id',
    ];

    /**
     * The "booted" method of the model.
     * Register model event listeners.
     */
    protected static function booted()
    {
        static::creating(function ($brief) {
            $brief->calculateCampaignDuration();

            if (empty($brief->cost_sheet_status_id)) {
                $pendingId = CostSheetStatus::query()->where('slug', 'pending')->value('id');

                if ($pendingId) {
                    $brief->cost_sheet_status_id = $pendingId;
                }
            }
        });

        static::created(function ($brief) {
            if ($brief->assign_user_id !== null || filled($brief->comment)) {
                $brief->createAssignHistory([
                    'assign_user_id' => ['new' => $brief->assign_user_id],
                ]);
            }
        });

        static::updating(function ($brief) {
            $brief->calculateCampaignDuration();
        });

        static::updated(function ($brief) {
            $brief->saveHistoryIfFieldsChanged();
        });
    }

    /**
     * Get brief count statistics for a priority.
     *
     * @param int $priorityId
     * @param array $filters
     * @return array
     */
    public static function getBriefCountStatsForPriority(int $priorityId, array $filters): array
    {
        $totalBriefQuery = self::accessibleToUser()
            ->whereNull('deleted_at')
            ->whereRaw('briefs.status != 15');
        \App\Support\DashboardFilters::applyBriefDashboardFilters($totalBriefQuery, $filters, 'briefs');

        $priorityBriefQuery = self::accessibleToUser()
            ->whereNull('deleted_at')
            ->whereRaw('briefs.status != 15')
            ->where('priority_id', $priorityId);
        \App\Support\DashboardFilters::applyBriefDashboardFilters($priorityBriefQuery, $filters, 'briefs');

        return [
            'total_briefs' => $totalBriefQuery->count(),
            'priority_brief_count' => $priorityBriefQuery->count(),
        ];
    }

    public function getAllBriefs(int $perPage = 15, ?string $searchTerm = null): LengthAwarePaginator
    {
        $query = $this->newQuery()->with(self::DEFAULT_RELATIONSHIPS)->accessibleToUser(Auth::user());
        $this->applyOrganisationValidation($query, Auth::user());
        if ($searchTerm !== null && $searchTerm !== '') {
            $this->applySearchFilter($query, $searchTerm);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage)->appends(request()->query());
    }

    public function getBriefById(int $id): ?self
    {
        return $this->newQuery()->with(self::DEFAULT_RELATIONSHIPS)->find($id);
    }

    public function getBriefsByBrand(int $brandId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->getAccessibleBriefQuery()
            ->where('brand_id', $brandId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->appends(request()->query());
    }

    public function getBriefsByAgency(int $agencyId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->getAccessibleBriefQuery()
            ->where('agency_id', $agencyId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->appends(request()->query());
    }

    public function getBriefsByAssignedUser(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->getAccessibleBriefQuery()
            ->where('assign_user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->appends(request()->query());
    }

    public function getBriefsByStatus(int $statusId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->getAccessibleBriefQuery()
            ->where('brief_status_id', $statusId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->appends(request()->query());
    }

    public function getBriefsByPriority(int $priorityId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->getAccessibleBriefQuery()
            ->where('priority_id', $priorityId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->appends(request()->query());
    }

    public function getBriefWithRelations(int $id): ?self
    {
        return $this->getBriefById($id);
    }

    public function searchBriefs(array $criteria, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->getAccessibleBriefQuery();
        foreach ($criteria as $field => $value) {
            if ($value !== null && $value !== '') {
                $query->where($field, $value);
            }
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage)->appends(request()->query());
    }

    public function filterBriefs(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->getAccessibleBriefQuery();

        foreach (['brand_id', 'agency_id', 'assign_user_id', 'brief_status_id', 'priority_id', 'status'] as $field) {
            if (isset($filters[$field]) && !empty($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }

        if (isset($filters['search']) && !empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('product_name', 'LIKE', "%{$search}%");
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage)->appends(request()->query());
    }

    public function getBriefLogs(int $perPage = 10, ?string $searchTerm = null): LengthAwarePaginator
    {
        $query = $this->getAccessibleBriefQuery();
        if ($searchTerm !== null && $searchTerm !== '') {
            $this->applySearchFilter($query, $searchTerm);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage)->appends(request()->query());
    }

    private function getAccessibleBriefQuery(): Builder
    {
        $query = $this->newQuery()->with(self::DEFAULT_RELATIONSHIPS)->accessibleToUser(Auth::user());
        $this->applyOrganisationValidation($query, Auth::user());

        return $query;
    }

    private function applySearchFilter(Builder $query, string $searchTerm): void
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

    protected function applyOrganisationValidation(Builder $query, $user): void
    {
        if (!$user) {
            return;
        }

        $userOrgIds = \App\Support\UserAccessScope::getAccessibleOrganisationIds($user);
        if (empty($userOrgIds)) {
            $query->whereRaw('0 = 1');
            return;
        }

        $orgUserIds = \App\Support\DashboardFilters::getOrganisationUserIds($userOrgIds);
        if (empty($orgUserIds)) {
            $query->whereRaw('0 = 1');
            return;
        }

        $query->where(function ($q) use ($orgUserIds) {
            $q->whereIn('assign_user_id', $orgUserIds)
                ->orWhereIn('created_by', $orgUserIds);
        });

        $ancestorIds = \App\Support\UserAccessScope::getAncestorIds($user);
        if (!empty($ancestorIds)) {
            $query->where(function ($q) use ($ancestorIds, $user) {
                $descendantIds = \App\Support\UserAccessScope::getStrictDescendantIds($user);
                $q->whereNotIn('created_by', $ancestorIds)
                    ->orWhereIn('assign_user_id', $descendantIds);
            });
        }
    }

    public function getLatestTwoBriefs(array $filters = []): Collection
    {
        $query = $this->getAccessibleBriefQuery()
            ->whereNull('briefs.deleted_at')
            ->whereRaw('briefs.status != 15');
        \App\Support\DashboardFilters::applyBriefDashboardFilters($query, $filters, 'briefs');

        return $query->orderBy('briefs.created_at', 'desc')->limit(2)->get();
    }

    public function getLatestFiveBriefs(array $filters = []): Collection
    {
        $query = $this->newQuery()->with(self::DEFAULT_RELATIONSHIPS)
            ->accessibleToUser(Auth::user())
            ->whereNull('briefs.deleted_at')
            ->whereRaw('briefs.status != 15');

        $this->applyLatestBriefOrganisationFilter($query, $filters);
        \App\Support\DashboardFilters::applyDateFilter($query, $filters, 'briefs.created_at');
        \App\Support\DashboardFilters::applyLeadPriorityFilter($query, $filters, 'briefs');

        return $query->orderBy('briefs.created_at', 'desc')->limit(5)->get();
    }

    public function getRecentBriefs(int $limit = 5, array $filters = []): Collection
    {
        $query = $this->getAccessibleBriefQuery()
            ->whereNull('briefs.deleted_at')
            ->whereRaw('briefs.status != 15');
        \App\Support\DashboardFilters::applyBriefDashboardFilters($query, $filters, 'briefs');

        return $query->orderBy('briefs.created_at', 'desc')->limit($limit)->get();
    }

    public function getPlannerDashboardCardData(array $filters = []): array
    {
        $baseQuery = $this->newQuery()->accessibleToUser(Auth::user())
            ->whereNull('briefs.deleted_at')
            ->whereRaw('briefs.status != 15');
        $this->applyOrganisationValidation($baseQuery, Auth::user());
        \App\Support\DashboardFilters::applyBriefDashboardFilters($baseQuery, $filters, 'briefs');

        $activeBriefs = (clone $baseQuery)->whereDate('submission_date', '>=', now())->count();
        $closedBriefs = (clone $baseQuery)->whereHas('briefStatus', function ($query) {
            $query->where('slug', 'closed');
        })->count();
        $overdueTime = (clone $baseQuery)->where('submission_date', '<', now())->count();
        $averagePlanningTime = (clone $baseQuery)
            ->selectRaw('AVG(DATEDIFF(submission_date, created_at)) as avg_days')
            ->value('avg_days');

        $briefIds = (clone $baseQuery)->pluck('briefs.id')->all();
        $plannerSummary = Planner::getSummaryForBriefIds($briefIds);

        return [
            'active_briefs' => $activeBriefs,
            'closed_briefs' => $closedBriefs,
            'overdue_briefs' => $overdueTime ?? 0,
            'assigned_plans' => $plannerSummary['assigned_plans'],
            'average_planning_time_days' => $averagePlanningTime ? round($averagePlanningTime, 2) : 0,
            'average_assignment_days' => $plannerSummary['average_assignment_days']
                ? round($plannerSummary['average_assignment_days'], 1)
                : 0,
        ];
    }

    public function getBusinessForecast(array $filters = []): array
    {
        $query = $this->newQuery()->accessibleToUser(Auth::user())
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

    private function applyLatestBriefOrganisationFilter(Builder $query, array $filters): void
    {
        $organisationIds = array_values(array_filter(array_map('intval', $filters['organisation_ids'] ?? [])));
        if ($organisationIds === []) {
            $query->whereRaw('0 = 1');
            return;
        }

        $userIds = \App\Support\DashboardFilters::getOrganisationUserIds($organisationIds);
        $query->where(function ($builder) use ($organisationIds, $userIds) {
            $builder->whereHas('contactPerson', function ($contactQuery) use ($organisationIds) {
                $contactQuery->whereIn('leads.organisation_id', $organisationIds);
            });

            if ($userIds !== []) {
                $builder->orWhereIn('briefs.created_by', $userIds)
                    ->orWhereIn('briefs.assign_user_id', $userIds);
            }
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
     * Scope to filter briefs accessible to the given user.
     * Super Admin sees all. Others see only briefs where they are creator or assigned user.
     *
     * @param Builder $query
     * @param mixed $user
     * @return Builder
     */
    public function scopeAccessibleToUser(Builder $query, $user = null): Builder
    {
        $user = $user ?? auth()->user();

        // If no user is authenticated, return empty query
        if (!$user) {
            return $query->whereRaw('0 = 1');
        }

        // Super Admin with organisation assignment may view all records (dashboard uses org filters).
        if (UserAccessScope::hasGlobalRecordAccess($user)) {
            return $query;
        }

        UserAccessScope::applyVisibleUserFilter(
            $query,
            $user,
            ['created_by', 'assign_user_id'],
            $this->getTable()
        );

        return $query;
    }

    // ===================================================================
    // RELATIONSHIPS
    // ===================================================================

    /**
     * Get the brief assign histories for this brief.
     */
    public function assignHistories()
    {
        return $this->hasMany(BriefAssignHistory::class, 'brief_id');
    }

    /**
     * Get the contact person (lead) associated with this brief.
     */
    public function contactPerson()
    {
        return $this->belongsTo(Lead::class, 'contact_person_id');
    }

    /**
     * Get the brand associated with this brief.
     */
    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    /**
     * Get the agency associated with this brief.
     */
    public function agency()
    {
        return $this->belongsTo(Agency::class, 'agency_id');
    }

    /**
     * Get the user assigned to this brief.
     */
    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assign_user_id');
    }

    /**
     * Get the user who created this brief.
     */
    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the brief status associated with this brief.
     */
    public function briefStatus()
    {
        return $this->belongsTo(BriefStatus::class, 'brief_status_id');
    }

    /**
     * Cost sheet status for this brief. Pending until a plan cost sheet is saved.
     */
    public function costSheetStatus()
    {
        return $this->belongsTo(CostSheetStatus::class, 'cost_sheet_status_id');
    }

    /**
     * Mark this brief's cost status as submitted after a plan cost sheet is saved.
     */
    public function markCostSheetSubmitted(int $briefId): void
    {
        $submittedId = CostSheetStatus::query()->where('slug', 'submitted')->value('id');

        if (!$submittedId) {
            return;
        }

        $this->newQuery()->where('id', $briefId)->update([
            'cost_sheet_status_id' => $submittedId,
        ]);
    }

    /**
     * Mark this brief's cost status as pending when its cost sheet is removed.
     */
    public function markCostSheetPending(int $briefId): void
    {
        $pendingId = CostSheetStatus::query()->where('slug', 'pending')->value('id');

        if (!$pendingId) {
            return;
        }

        $this->newQuery()->where('id', $briefId)->update([
            'cost_sheet_status_id' => $pendingId,
        ]);
    }

    /**
     * Set a brief's cost sheet status when the status exists.
     */
    public function assignCostSheetStatus(int $briefId, int $statusId): bool
    {
        $statusExists = CostSheetStatus::query()
            ->where('id', $statusId)
            ->where('status', '1')
            ->exists();

        if (!$statusExists) {
            return false;
        }

        return (bool) $this->newQuery()->where('id', $briefId)->update([
            'cost_sheet_status_id' => $statusId,
        ]);
    }

    /**
     * Get the priority associated with this brief.
     */
    public function priority()
    {
        return $this->belongsTo(Priority::class, 'priority_id');
    }

    /**
     * Get all notifications for this brief.
     */
    public function notifications(): MorphMany
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }

    /**
     * Get the latest planner id from planner history.
     *
     * @return int|null
     */
    public function getLatestPlannerIdAttribute(): ?int
    {
        return PlannerHistory::where('brief_id', $this->id)
            ->latest()
            ->value('planner_id');
    }

    // ===================================================================
    // HISTORY TRACKING METHODS
    // ===================================================================

    /**
     * Calculate campaign duration in days based on start and end dates.
     * Auto-calculates and sets the campaign_duration attribute.
     */
    private function calculateCampaignDuration(): void
    {
        if (!$this->campaign_start_date || !$this->campaign_end_date) {
            $this->campaign_duration = 0;
            return;
        }

        $startDate = Carbon::parse($this->campaign_start_date);
        $endDate = Carbon::parse($this->campaign_end_date);
        
        // Check for invalid date range (start after end)
        if ($startDate->greaterThan($endDate)) {
            $this->campaign_duration = 0;
            return;
        }
        
        // Calculate difference in days (inclusive of both start and end dates)
        $this->campaign_duration = $endDate->diffInDays($startDate) + 1;
    }

    /**
     * Check if any tracked fields have changed and save history if needed.
     */
    private function saveHistoryIfFieldsChanged(): void
    {
        $changeData = $this->getTrackedFieldChanges();
        
        if (empty($changeData)) {
            return;
        }

        try {
            $this->createAssignHistory($changeData);
        } catch (\Exception $e) {
            Log::error('Failed to create BriefAssignHistory', [
                'brief_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get the changes for tracked fields.
     */
    private function getTrackedFieldChanges(): array
    {
        $changeData = [];

        foreach (self::TRACKED_FIELDS as $field) {
            if ($this->isDirty($field)) {
                $changeData[$field] = [
                    'old' => $this->getOriginal($field),
                    'new' => $this->getAttribute($field),
                ];
            }
        }

        return $changeData;
    }

    /**
     * Create a brief assign history record.
     */
    private function createAssignHistory(array $changeData): void
    {
        BriefAssignHistory::create([
            'uuid' => Str::uuid(),
            'brief_id' => $this->id,
            'assign_by_id' => $this->getCurrentUserId(),
            'assign_to_id' => $changeData['assign_user_id']['new'] ?? $this->assign_user_id ?? $this->getCurrentUserId(),
            'brief_status_id' => $changeData['brief_status_id']['new'] ?? $this->brief_status_id,
            'brief_status_time' => now(),
            'submission_date' => $changeData['submission_date']['new'] ?? $this->submission_date,
            'comment' => $this->comment,
            'status' => '2',
        ]);
    }

    /**
     * Get the current authenticated user ID.
     */
    private function getCurrentUserId(): ?int
    {
        return Auth::id() ?? $this->created_by;
    }
    // ===================================================================
    // STATIC HELPER METHODS
    // ===================================================================

    /**
     * Get valid campaign types for a given campaign mode.
     *
     * @param string $mode
     * @return array
     */
    public static function getCampaignTypesByMode(string $mode): array
    {
        return match ($mode) {
            self::MODE_PROGRAMMATIC => [self::TYPE_PROGRAMMATIC_DOOH, self::TYPE_PROGRAMMATIC_CTV],
            self::MODE_NON_PROGRAMMATIC => [self::TYPE_NON_PROGRAMMATIC_DOOH, self::TYPE_NON_PROGRAMMATIC_OOH],
            default => [],
        };
    }

    /**
     * Get all available campaign modes.
     *
     * @return array
     */
    public static function getCampaignModes(): array
    {
        return [
            self::MODE_PROGRAMMATIC,
            self::MODE_NON_PROGRAMMATIC,
        ];
    }

    /**
     * Get campaign mode display labels.
     *
     * @return array
     */
    public static function getCampaignModeLabels(): array
    {
        return [
            self::MODE_PROGRAMMATIC => 'Programmatic',
            self::MODE_NON_PROGRAMMATIC => 'Non-Programmatic',
        ];
    }

    /**
     * Get campaign type display labels.
     *
     * @return array
     */
    public static function getCampaignTypeLabels(): array
    {
        return [
            self::TYPE_PROGRAMMATIC_DOOH => 'DOOH',
            self::TYPE_PROGRAMMATIC_CTV => 'CTV',
            self::TYPE_NON_PROGRAMMATIC_DOOH => 'DOOH',
            self::TYPE_NON_PROGRAMMATIC_OOH => 'OOH',
        ];
    }
}
