<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use App\Support\UserAccessScope;

class Lead extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * @var array<int, string>
     */
    protected $hidden = [
        'deleted_at',
    ];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'leads';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'brand_id',
        'agency_id',
        'current_assign_user',
        'created_by',
        'priority_id',
        'call_status',
        'lead_status',
        'call_attempt',
        'name',
        'slug',
        'profile_url',
        'email',
        'lead_type_id',
        'designation_id',
        'department_id',
        'sub_source_id',
        'country_id',
        'state_id',
        'city_id',
        'zone_id',
        'statuses',
        'postal_code',
        'comment',
        'status',
        'pre_lead_id',
        'organisation_id'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
    ];

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Scope to filter leads accessible to the given user.
     * Super Admin sees all. Others see only leads where they are creator or assigned user.
     *
     * @param Builder $query
     * @param mixed $user
     * @return Builder
     */
    public function scopeNotDeleted(Builder $query): Builder
    {
        return $query->whereNull($this->getTable() . '.deleted_at');
    }

    public static function getLeadCountStatsForPriority(int $priorityId, array $filters): array
    {
        $totalLeadQuery = self::accessibleToUser()->whereNull('deleted_at');
        \App\Support\DashboardFilters::applyLeadDashboardFilters($totalLeadQuery, $filters, 'leads');

        $priorityLeadQuery = self::accessibleToUser()
            ->whereNull('deleted_at')
            ->where('priority_id', $priorityId);
        \App\Support\DashboardFilters::applyLeadDashboardFilters($priorityLeadQuery, $filters, 'leads');

        return [
            'total_leads' => $totalLeadQuery->count(),
            'priority_lead_count' => $priorityLeadQuery->count(),
        ];
    }

    protected function repositoryEagerLoadRelations(): array
    {
        $notTrashed = static fn (string $table) => static fn ($query) => $query->whereNull($table . '.deleted_at');

        return [
            'brand' => $notTrashed('brands'),
            'agency' => $notTrashed('agency'),
            'leadType' => $notTrashed('lead_types'),
            'assignedUser' => $notTrashed('users'),
            'createdByUser' => $notTrashed('users'),
            'priority' => $notTrashed('priorities'),
            'designation' => $notTrashed('designations'),
            'department' => $notTrashed('departments'),
            'subSource' => $notTrashed('lead_sub_source'),
            'country',
            'state',
            'city',
            'zone' => $notTrashed('zones'),
            'statusRelation' => $notTrashed('statuses'),
            'callStatusRelation' => $notTrashed('call_statuses'),
            'leadStatusRelation' => $notTrashed('statuses'),
            'mobileNumbers',
            'organisation',
        ];
    }

    private function getRepositoryLeadQuery(): Builder
    {
        $query = $this->newQuery()->with($this->repositoryEagerLoadRelations())
            ->notDeleted()
            ->accessibleToUser(\Illuminate\Support\Facades\Auth::user());
        $this->applyOrganisationValidation($query, \Illuminate\Support\Facades\Auth::user());

        return $query;
    }

    public function getAllLeads(int $perPage = 10, ?string $searchTerm = null): LengthAwarePaginator
    {
        $query = $this->getRepositoryLeadQuery();
        if ($searchTerm) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('email', 'LIKE', "%{$searchTerm}%")
                    ->orWhereHas('brand', function ($brandQuery) use ($searchTerm) {
                        $brandQuery->whereNull('deleted_at')->where('name', 'LIKE', "%{$searchTerm}%");
                    })
                    ->orWhereHas('agency', function ($agencyQuery) use ($searchTerm) {
                        $agencyQuery->whereNull('deleted_at')->where('name', 'LIKE', "%{$searchTerm}%");
                    })
                    ->orWhereHas('assignedUser', function ($userQuery) use ($searchTerm) {
                        $userQuery->whereNull('deleted_at')->where('name', 'LIKE', "%{$searchTerm}%");
                    })
                    ->orWhereHas('mobileNumbers', function ($mobileQuery) use ($searchTerm) {
                        $mobileQuery->where('mobile_number', 'LIKE', "%{$searchTerm}%");
                    })
                    ->orWhereHas('leadStatusRelation', function ($statusQuery) use ($searchTerm) {
                        $statusQuery->where('name', 'LIKE', "%{$searchTerm}%");
                    });
            });
        }

        return $query->orderByDesc('updated_at')->paginate($perPage)->appends(request()->query());
    }

    public function getLeadById(int $id): ?self
    {
        return $this->getRepositoryLeadQuery()->find($id);
    }

    public function getLeadsByBrandId(int $brandId, int $perPage = 10): LengthAwarePaginator
    {
        return $this->getRepositoryLeadQuery()->where('brand_id', $brandId)->where('status', '1')
            ->orderBy('created_at', 'desc')->paginate($perPage)->appends(request()->query());
    }

    public function getLeadsByAgencyId(int $agencyId, int $perPage = 10): LengthAwarePaginator
    {
        return $this->getRepositoryLeadQuery()->where('agency_id', $agencyId)->where('status', '1')
            ->orderBy('created_at', 'desc')->paginate($perPage)->appends(request()->query());
    }

    public function getLeadsByAssignedUser(int $userId, int $perPage = 10): LengthAwarePaginator
    {
        return $this->getRepositoryLeadQuery()->where('current_assign_user', $userId)->where('status', '1')
            ->orderBy('created_at', 'desc')->paginate($perPage)->appends(request()->query());
    }

    public function getLeadsByStatus(string $status, int $perPage = 10): LengthAwarePaginator
    {
        return $this->getRepositoryLeadQuery()->where('status', $status)
            ->orderBy('created_at', 'desc')->paginate($perPage)->appends(request()->query());
    }

    public function getLeadsByPriority(int $priorityId, int $perPage = 10): LengthAwarePaginator
    {
        return $this->getRepositoryLeadQuery()->where('priority_id', $priorityId)->where('status', '1')
            ->orderBy('created_at', 'desc')->paginate($perPage)->appends(request()->query());
    }

    public function getLeadList(): Collection
    {
        $query = $this->newQuery()->select('id', 'name')->notDeleted()
            ->accessibleToUser(\Illuminate\Support\Facades\Auth::user());
        $this->applyOrganisationValidation($query, \Illuminate\Support\Facades\Auth::user());

        return $query->where('status', '1')->orderBy('id', 'asc')->get();
    }

    public function getLeadsWithFilters(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        $query = $this->getRepositoryLeadQuery();
        foreach ([
            'brand_id', 'agency_id', 'current_assign_user', 'priority_id', 'created_by', 'sub_source_id',
            'call_status', 'lead_type_id', 'country_id', 'state_id', 'city_id',
        ] as $column) {
            $this->applyRepositoryIdFilter($query, $column, $filters[$column] ?? null);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        } else {
            $query->where('status', '1');
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%")
                    ->orWhere('profile_url', 'LIKE', "%{$search}%")
                    ->orWhereHas('agency', function ($agencyQuery) use ($search) {
                        $agencyQuery->whereNull('deleted_at')->where('name', 'LIKE', "%{$search}%");
                    })
                    ->orWhereHas('mobileNumbers', function ($mobileQuery) use ($search) {
                        $mobileQuery->where('mobile_number', 'LIKE', "%{$search}%");
                    });
            });
        }

        return $query->orderByDesc('updated_at')->paginate($perPage)->appends(request()->query());
    }

    private function applyRepositoryIdFilter(Builder $query, string $column, $value): void
    {
        if ($value === null || $value === '' || $value === []) {
            return;
        }
        if (is_array($value)) {
            $ids = array_values(array_filter(array_map('intval', $value)));
            if ($ids !== []) {
                $query->whereIn($column, $ids);
            }
            return;
        }

        $query->where($column, (int) $value);
    }

    private function applyOrganisationValidation(Builder $query, $user): void
    {
        if (!$user) {
            return;
        }

        $userOrgIds = UserAccessScope::getAccessibleOrganisationIds($user);
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
            $q->whereIn('current_assign_user', $orgUserIds)->orWhereIn('created_by', $orgUserIds);
        });

        $ancestorIds = UserAccessScope::getAncestorIds($user);
        if (!empty($ancestorIds)) {
            $query->where(function ($q) use ($ancestorIds, $user) {
                $descendantIds = UserAccessScope::getStrictDescendantIds($user);
                $q->whereNotIn('created_by', $ancestorIds)->orWhereIn('current_assign_user', $descendantIds);
            });
        }
    }

    public function scopeAccessibleToUser(Builder $query, $user = null): Builder
    {
        $user = $user ?? auth()->user();

        if (!$user) {
            return $query->whereRaw('0 = 1');
        }

        if (UserAccessScope::hasGlobalRecordAccess($user)) {
            return $query;
        }

        UserAccessScope::applyVisibleUserFilter(
            $query,
            $user,
            ['created_by', 'current_assign_user'],
            $this->getTable()
        );

        return $query;
    }

    /**
     * Get all parent IDs (including transitive parents) for a user.
     *
     * @param int $userId
     * @return array
     */
    private function getAllParentIds(int $userId): array
    {
        $parentIds = [];
        $visited = [];
        $queue = [$userId];

        while (!empty($queue)) {
            $currentUserId = array_shift($queue);

            if (isset($visited[$currentUserId])) {
                continue;
            }
            
            $visited[$currentUserId] = true;

            // Get direct parents of current user
            $directParents = \DB::table('user_parent')
                ->where('user_id', $currentUserId)
                ->pluck('is_parent')
                ->toArray();

            foreach ($directParents as $parentId) {
                if (!isset($visited[$parentId])) {
                    $parentIds[] = $parentId;
                    $queue[] = $parentId;
                }
            }
        }

        return $parentIds;
    }
    
    private function getDirectChildsIds(int $userId): array
    {
        return UserParent::where('is_parent', $userId)->pluck('user_id')->toArray();
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    public function agency()
    {
        return $this->belongsTo(Agency::class, 'agency_id');
    }

    public function leadType()
    {
        return $this->belongsTo(LeadType::class, 'lead_type_id');
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'current_assign_user');
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function priority()
    {
        return $this->belongsTo(Priority::class, 'priority_id');
    }

    public function designation()
    {
        return $this->belongsTo(Designation::class, 'designation_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function subSource()
    {
        return $this->belongsTo(LeadSubSource::class, 'sub_source_id');
    }

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public function state()
    {
        return $this->belongsTo(State::class, 'state_id');
    }

    public function city()
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function zone()
    {
        return $this->belongsTo(Zone::class, 'zone_id');
    }

    public function statusRelation()
    {
        return $this->belongsTo(Status::class, 'statuses');
    }

    /**
     * Get the call status associated with this lead.
     */
    public function callStatusRelation()
    {
        return $this->belongsTo(CallStatus::class, 'call_status');
    }

    /**
     * Get the lead status associated with this lead.
     */
    public function leadStatusRelation()
    {
        return $this->belongsTo(Status::class, 'lead_status');
    }

    /**
     * Get the mobile numbers associated with this lead.
     */
    public function mobileNumbers()
    {
        return $this->hasMany(LeadMobileNumber::class, 'lead_id');
    }

    /**
     * Get all notifications for this lead.
     */
    public function notifications(): MorphMany
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }

    /**
     * Get the organisation associated with this lead.
     */
    public function organisation()
    {
        return $this->belongsTo(Organisation::class, 'organisation_id');
    }

    public function getLeadHistory(int $leadId, int $perPage = 10): LengthAwarePaginator
    {
        return LeadAssignHistory::getPaginatedForLead($leadId, $perPage);
    }

    public function getPendingLeads(int $perPage = 10, array $filters = []): LengthAwarePaginator
    {
        $query = $this->newQuery()
            ->with($this->repositoryEagerLoadRelations())
            ->accessibleToUser(\Illuminate\Support\Facades\Auth::user())
            ->notDeleted()
            ->whereHas('leadStatusRelation', function ($statusQuery) {
                $statusQuery->whereNull('statuses.deleted_at')->where('statuses.slug', 'pending');
            }, '>=', 1)
            ->where('leads.status', '1');

        $this->applyOrganisationValidation($query, \Illuminate\Support\Facades\Auth::user());
        \App\Support\DashboardFilters::applyPendingLeadDashboardFilters($query, $filters, 'leads');

        return $query->orderBy('leads.created_at', 'desc')->paginate($perPage)->appends(request()->query());
    }

    public function getUserLeadPerformance(int $userId, array $filters = []): Collection
    {
        $notTrashed = static fn (string $table) => static fn ($query) => $query->whereNull($table . '.deleted_at');
        $query = $this->newQuery()->with([
            'callStatusRelation' => $notTrashed('call_statuses'),
            'leadStatusRelation' => $notTrashed('statuses'),
            'priority' => $notTrashed('priorities'),
        ])->where('current_assign_user', $userId);

        foreach (['call_status', 'lead_status', 'priority_id'] as $column) {
            $this->applyRepositoryIdFilter($query, $column, $filters[$column] ?? null);
        }

        return $query->orderBy('id', 'asc')->get();
    }

    public function getAssignHistoryByLeadId(int $leadId, int $perPage = 9): LengthAwarePaginator
    {
        return LeadAssignHistory::getCommentsForLead($leadId, $perPage);
    }

    public function getLatestTwoLeads(array $filters = []): Collection
    {
        $query = $this->getRepositoryLeadQuery();
        \App\Support\DashboardFilters::applyLeadDashboardFilters($query, $filters, 'leads');

        return $query->orderBy('leads.created_at', 'desc')->limit(2)->get();
    }

    public function getLatestTwoFollowUpLeads(array $filters = []): Collection
    {
        $query = $this->getRepositoryLeadQuery()->whereHas('callStatusRelation', function ($statusQuery) {
            $statusQuery->where('slug', 'follow-up');
        });
        \App\Support\DashboardFilters::applyLeadDashboardFilters($query, $filters, 'leads');

        return $query->orderBy('leads.created_at', 'desc')->limit(2)->get();
    }

    public function getLatestTwoMeetingScheduledLeads(array $filters = []): Collection
    {
        $query = $this->getRepositoryLeadQuery()->whereHas('callStatusRelation', function ($statusQuery) {
            $statusQuery->where('slug', 'meeting-schedule');
        });
        \App\Support\DashboardFilters::applyLeadDashboardFilters($query, $filters, 'leads');

        return $query->orderBy('leads.created_at', 'desc')->limit(2)->get();
    }

    public function getLatestTwoMeetingDoneLeads(): Collection
    {
        return $this->getRepositoryLeadQuery()->whereHas('callStatusRelation', function ($statusQuery) {
            $statusQuery->where('slug', 'meeting-done');
        })->orderBy('created_at', 'desc')->limit(2)->get();
    }
}