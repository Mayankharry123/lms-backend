<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use App\Support\PlannerMetrics;
use App\Support\UserAccessScope;
use App\Traits\HandlesFileUploads;

class Planner extends BaseModel
{
    use HasFactory, SoftDeletes, HandlesFileUploads;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'planners';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'brief_id',
        'created_by',
        'planner_status_id',
        'status',
        'submitted_plan',
        'backup_plan',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'submitted_plan' => 'array', // Cast JSON to array
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relationship: A planner belongs to a brief.
     */
    public function brief()
    {
        return $this->belongsTo(Brief::class, 'brief_id');
    }

    /**
     * Relationship: A planner is created by a user.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relationship: A planner has a planner status.
     */
    public function plannerStatus()
    {
        return $this->belongsTo(PlannerStatus::class, 'planner_status_id');
    }

    /**
     * Scope: Get only active planners.
     */
    public function scopeActive($query)
    {
        return $query->where('status', '1');
    }

    /**
     * Scope: Get only deactivated planners.
     */
    public function scopeDeactivated($query)
    {
        return $query->where('status', '2');
    }

    /**
     * Scope: Get planners by brief ID.
     */
    public function scopeByBrief($query, $briefId)
    {
        return $query->where('brief_id', $briefId);
    }

    /**
     * Find a planner by ID with its status loaded.
     */
    public function findByIdWithStatus(int $id): ?self
    {
        return $this->newQuery()->with('plannerStatus')->find($id);
    }

    /**
     * Scope: Get planners created by a specific user.
     */
    public function scopeCreatedBy($query, $userId)
    {
        return $query->where('created_by', $userId);
    }

    /**
     * Scope: Filter planners accessible to the given user.
     */
    public function scopeAccessibleToUser($query, $user = null)
    {
        $user = $user ?? auth()->user();

        // If no user is authenticated, return empty query
        if (!$user) {
            return $query->whereRaw('0 = 1');
        }

        // Super Admin with organisation assignment may view all records (dashboard uses org filters).
        if (\App\Support\UserAccessScope::hasGlobalRecordAccess($user)) {
            return $query;
        }

        \App\Support\UserAccessScope::applyVisibleUserFilter(
            $query,
            $user,
            ['created_by'],
            $this->getTable()
        );

        return $query;
    }

    /**
     * Check if the planner has submitted plans.
     */
    public function hasSubmittedPlans(): bool
    {
        return !empty($this->submitted_plan) && is_array($this->submitted_plan) && count($this->submitted_plan) > 0;
    }

    /**
     * Check if the planner has a backup plan.
     */
    public function hasBackupPlan(): bool
    {
        return !empty($this->backup_plan);
    }

    /**
     * True when the current planner status is Plan Approved.
     */
    public function isPlanApproved(): bool
    {
        $status = $this->relationLoaded('plannerStatus')
            ? $this->plannerStatus
            : $this->plannerStatus()->first();

        if (!$status) {
            return false;
        }

        return strcasecmp((string) $status->slug, 'plan-approved') === 0
            || strcasecmp(trim((string) $status->name), 'Plan Approved') === 0;
    }

    /**
     * Get the count of submitted plan files.
     */
    public function getSubmittedPlanCount(): int
    {
        if ($this->hasSubmittedPlans()) {
            return count($this->submitted_plan);
        }
        return 0;
    }

    /** @param array<int, int> $briefIds
     * @return array{assigned_plans: int, average_assignment_days: float}
     */
    public static function getSummaryForBriefIds(array $briefIds): array
    {
        if ($briefIds === []) {
            return ['assigned_plans' => 0, 'average_assignment_days' => 0.0];
        }

        $plannerQuery = self::query()
            ->whereNull('planners.deleted_at')
            ->whereIn('planners.brief_id', $briefIds);

        $submittedQuery = PlannerMetrics::applySubmittedPlansScope(clone $plannerQuery);
        $averageAssignmentDays = $submittedQuery
            ->selectRaw(
                'AVG(' . PlannerMetrics::assignmentToSubmissionDaysSql() . ') as avg_days'
            )
            ->value('avg_days');

        return [
            'assigned_plans' => self::query()
                ->whereNull('deleted_at')
                ->whereIn('brief_id', $briefIds)
                ->count(),
            'average_assignment_days' => $averageAssignmentDays ? (float) $averageAssignmentDays : 0.0,
        ];
    }

    /**
     * Add a submitted plan file.
     */
    public function addSubmittedPlan($filePath): void
    {
        if (!is_array($this->submitted_plan)) {
            $this->submitted_plan = [];
        }

        if (count($this->submitted_plan) < 2) {
            $this->submitted_plan[] = $filePath;
            $this->save();
        }
    }

    /**
     * Remove a submitted plan file by index.
     */
    public function removeSubmittedPlan($index): void
    {
        if (is_array($this->submitted_plan) && isset($this->submitted_plan[$index])) {
            unset($this->submitted_plan[$index]);
            $this->submitted_plan = array_values($this->submitted_plan); // Re-index array
            $this->save();
        }
    }
    public function buildSubmittedPlansQuery(array $filters = [], $user = null): Builder
    {
        $user = $user ?? auth()->user();

        $query = $this->newQuery()
            ->with([
                'brief.contactPerson.organisation',
                'brief.contactPerson.department',
                'brief.assignedUser.departments',
                'creator.departments',
                'creator.organisations',
                'creator.organisation',
                'plannerStatus',
            ])
            ->whereNull('planners.deleted_at')
            ->where('planners.status', '!=', '15')
            ->whereNotNull('planners.submitted_plan')
            ->whereRaw('JSON_LENGTH(planners.submitted_plan) > 0');

        // User hierarchy and visible record scoping
        if ($user) {
            $query->accessibleToUser($user);
        } else {
            return $query->whereRaw('0 = 1');
        }

        // Apply organisation scoping and filters
        $accessibleOrgIds = $user ? UserAccessScope::getAccessibleOrganisationIds($user) : [];
        $isSuperAdmin = UserAccessScope::isSuperAdmin($user);

        $filterOrgIds = [];
        if (!empty($filters['organisation_ids'])) {
            $filterOrgIds = is_array($filters['organisation_ids'])
                ? $filters['organisation_ids']
                : explode(',', (string) $filters['organisation_ids']);
        } elseif (!empty($filters['organisation_id'])) {
            $filterOrgIds = [$filters['organisation_id']];
        }
        $filterOrgIds = array_values(array_filter(array_map('intval', $filterOrgIds)));

        $effectiveOrgIds = [];
        if (!empty($accessibleOrgIds)) {
            if (!empty($filterOrgIds)) {
                $effectiveOrgIds = array_values(array_intersect($filterOrgIds, $accessibleOrgIds));
                if (empty($effectiveOrgIds)) {
                    $query->whereRaw('0 = 1');
                }
            } else {
                $effectiveOrgIds = $accessibleOrgIds;
            }
        } elseif (!empty($filterOrgIds)) {
            $effectiveOrgIds = $filterOrgIds;
        }

        if (!empty($effectiveOrgIds)) {
            $query->where(function (Builder $orgQ) use ($effectiveOrgIds) {
                $orgQ->whereHas('brief.contactPerson', function ($q) use ($effectiveOrgIds) {
                    $q->whereIn('organisation_id', $effectiveOrgIds);
                })->orWhereHas('creator', function ($q) use ($effectiveOrgIds) {
                    $q->where(function ($sub) use ($effectiveOrgIds) {
                        $sub->whereIn('organisation_id', $effectiveOrgIds)
                            ->orWhereHas('organisations', function ($orgSub) use ($effectiveOrgIds) {
                                $orgSub->whereIn('organisations.id', $effectiveOrgIds);
                            });
                    });
                })->orWhereHas('brief.assignedUser', function ($q) use ($effectiveOrgIds) {
                    $q->where(function ($sub) use ($effectiveOrgIds) {
                        $sub->whereIn('organisation_id', $effectiveOrgIds)
                            ->orWhereHas('organisations', function ($orgSub) use ($effectiveOrgIds) {
                                $orgSub->whereIn('organisations.id', $effectiveOrgIds);
                            });
                    });
                });
            });
        }

        // Exclude ancestor planners for non-superadmin
        if (!$isSuperAdmin) {
            $ancestorIds = UserAccessScope::getAncestorIds($user);
            if (!empty($ancestorIds)) {
                $query->whereNotIn('planners.created_by', $ancestorIds);
            }
        }

        // Apply department filters
        $filterDeptIds = [];
        if (!empty($filters['department_ids'])) {
            $filterDeptIds = is_array($filters['department_ids'])
                ? $filters['department_ids']
                : explode(',', (string) $filters['department_ids']);
        } elseif (!empty($filters['department_id'])) {
            $filterDeptIds = [$filters['department_id']];
        }
        $filterDeptIds = array_values(array_filter(array_map('intval', $filterDeptIds)));

        if (!empty($filterDeptIds)) {
            $query->where(function (Builder $deptQ) use ($filterDeptIds) {
                $deptQ->whereHas('brief.contactPerson', function ($q) use ($filterDeptIds) {
                    $q->whereIn('department_id', $filterDeptIds);
                })->orWhereHas('creator.departments', function ($q) use ($filterDeptIds) {
                    $q->whereIn('departments.id', $filterDeptIds);
                })->orWhereHas('brief.assignedUser.departments', function ($q) use ($filterDeptIds) {
                    $q->whereIn('departments.id', $filterDeptIds);
                });
            });
        }

        // Additional filters
        if (!empty($filters['brief_id'])) {
            $query->where('planners.brief_id', (int) $filters['brief_id']);
        }

        if (!empty($filters['created_by'])) {
            $query->where('planners.created_by', (int) $filters['created_by']);
        }

        if (!empty($filters['planner_status_id'])) {
            $query->where('planners.planner_status_id', (int) $filters['planner_status_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('planners.status', (string) $filters['status']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('planners.created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('planners.created_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->whereHas('brief', function ($bQ) use ($search) {
                    $bQ->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('product_name', 'LIKE', "%{$search}%");
                })->orWhereHas('creator', function ($uQ) use ($search) {
                    $uQ->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('email', 'LIKE', "%{$search}%");
                });
            });
        }

        return $query->latest('planners.updated_at');
    }
    public function fetchSubmittedPlans(int $perPage = 5, array $filters = [], $user = null): LengthAwarePaginator
    {
        return $this->buildSubmittedPlansQuery($filters, $user)->paginate($perPage);
    }

    
    public function fetchLatestSubmittedPlans(int $limit = 5, array $filters = [], $user = null): Collection
    {
        return $this->buildSubmittedPlansQuery($filters, $user)->limit($limit)->get();
    }
}
