<?php

namespace App\Models;

use App\Support\DashboardFilters;
use App\Support\UserAccessScope;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class FinanceRecord extends BaseModel
{
    public ?string $history_comment = null;

    protected $table = 'finance_records';

    protected $fillable = [
        'uuid',
        'brief_id',
        'planner_id',
        'finance_status_id',
        'cost_sheet',
        'assign_by',
        'assign_to',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function brief()
    {
        return $this->belongsTo(Brief::class, 'brief_id');
    }

    public function planner()
    {
        return $this->belongsTo(Planner::class, 'planner_id');
    }

    public function financeStatus()
    {
        return $this->belongsTo(FinanceStatus::class, 'finance_status_id');
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assign_by');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assign_to');
    }

    /**
     * Latest purchase order raised from this cost sheet.
     */
    public function latestPurchaseOrder()
    {
        return $this->hasOne(PurchaseOrder::class, 'finance_record_id')->latestOfMany();
    }

    public function histories()
    {
        return $this->hasMany(FinanceRecordHistory::class, 'finance_record_id')->orderBy('created_at', 'desc');
    }

    public function scopeActive($query)
    {
        return $query->where('status', '1');
    }

    /**
     * Relations returned with a cost sheet upload and standard finance list.
     *
     * @var array<int, string>
     */
    public const RESPONSE_RELATIONS = [
        'brief:id,name,product_name,campaign_start_date,campaign_end_date,created_by,assign_user_id,contact_person_id',
        'brief.createdByUser:id,name',
        'brief.assignedUser:id,name',
        'brief.contactPerson:id,name,organisation_id,department_id',
        'brief.contactPerson.organisation:id,name',
        'brief.contactPerson.department:id,name',
        'planner:id,created_by,backup_plan',
        'planner.creator:id,name',
        'financeStatus:id,name',
        'assignedBy:id,name',
        'assignedTo:id,name,organisation_id',
        'assignedTo.departments:id,name',
        'assignedTo.organisation:id,name',
    ];

    /**
     * Relations returned by the cost sheet list and detail APIs.
     *
     * @var array<int, string>
     */
    public const COST_SHEET_RELATIONS = [
        'brief:id,name,product_name,cost_sheet_status_id,created_by,assign_user_id,contact_person_id',
        'brief.costSheetStatus:id,name,slug',
        'brief.createdByUser:id,name',
        'brief.assignedUser:id,name',
        'brief.contactPerson:id,name,organisation_id,department_id',
        'brief.contactPerson.organisation:id,name',
        'brief.contactPerson.department:id,name',
        'planner:id,created_by,backup_plan',
        'planner.creator:id,name',
        'financeStatus:id,name',
        'assignedBy:id,name',
        'assignedTo:id,name,organisation_id',
        'assignedTo.departments:id,name',
        'assignedTo.organisation:id,name',
        'latestPurchaseOrder',
    ];

    public function scopeAccessibleToUser(Builder $query, $user = null): Builder
    {
        $user = $user ?? auth()->user();

        if (!$user) {
            return $query->whereRaw('0 = 1');
        }

        if (UserAccessScope::hasGlobalRecordAccess($user)) {
            return $query;
        }

        $table = $this->getTable();

        UserAccessScope::applyVisibleUserFilter(
            $query,
            $user,
            ['assign_to', 'assign_by'],
            $table
        );

        $ancestorIds = UserAccessScope::getAncestorIds($user);
        if (!empty($ancestorIds)) {
            $descendantIds = UserAccessScope::getStrictDescendantIds($user);
            $query->where(function ($q) use ($ancestorIds, $descendantIds, $table) {
                $q->where(function ($sub) use ($ancestorIds, $table) {
                    $sub->whereNotIn("{$table}.assign_by", $ancestorIds)
                        ->orWhereNull("{$table}.assign_by");
                })->orWhereIn("{$table}.assign_to", $descendantIds);
            });
        }

        return $query;
    }

    public function applyOrganisationValidation(Builder $query, $user, array $filters = []): void
    {
        if (!$user) {
            $query->whereRaw('0 = 1');
            return;
        }

        $accessibleOrgIds = UserAccessScope::getAccessibleOrganisationIds($user);
        $isSuperAdmin = UserAccessScope::isSuperAdmin($user);

        if (empty($accessibleOrgIds) && !$isSuperAdmin) {
            $query->whereRaw('0 = 1');
            return;
        }

        $requestedOrgIds = [];
        if (!empty($filters['organisation_ids'])) {
            $requestedOrgIds = is_array($filters['organisation_ids'])
                ? $filters['organisation_ids']
                : explode(',', (string) $filters['organisation_ids']);
        } elseif (!empty($filters['organisation_id'])) {
            $requestedOrgIds = [$filters['organisation_id']];
        }
        $requestedOrgIds = array_values(array_filter(array_map('intval', $requestedOrgIds)));

        $effectiveOrgIds = [];
        if (!empty($accessibleOrgIds)) {
            if (!empty($requestedOrgIds)) {
                $effectiveOrgIds = array_values(array_intersect($requestedOrgIds, $accessibleOrgIds));
                if (empty($effectiveOrgIds)) {
                    $query->whereRaw('0 = 1');
                    return;
                }
            } else {
                $effectiveOrgIds = $accessibleOrgIds;
            }
        } elseif (!empty($requestedOrgIds)) {
            $effectiveOrgIds = $requestedOrgIds;
        }

        if (!empty($effectiveOrgIds)) {
            $orgUserIds = DashboardFilters::getOrganisationUserIds($effectiveOrgIds);
            $table = $this->getTable();

            $query->where(function (Builder $orgQ) use ($effectiveOrgIds, $orgUserIds, $table) {
                $orgQ->whereHas('brief.contactPerson', function ($q) use ($effectiveOrgIds) {
                    $q->whereIn('organisation_id', $effectiveOrgIds);
                });

                if (!empty($orgUserIds)) {
                    $orgQ->orWhereIn("{$table}.assign_to", $orgUserIds)
                        ->orWhereIn("{$table}.assign_by", $orgUserIds)
                        ->orWhereHas('planner', function ($pQ) use ($orgUserIds) {
                            $pQ->whereIn('created_by', $orgUserIds);
                        })
                        ->orWhereHas('brief', function ($bQ) use ($orgUserIds) {
                            $bQ->whereIn('assign_user_id', $orgUserIds)
                                ->orWhereIn('created_by', $orgUserIds);
                        });
                }
            });
        }
    }

    public function applyDepartmentFilter(Builder $query, array $filters = []): void
    {
        $deptIds = [];
        if (!empty($filters['department_ids'])) {
            $deptIds = is_array($filters['department_ids'])
                ? $filters['department_ids']
                : explode(',', (string) $filters['department_ids']);
        } elseif (!empty($filters['department_id'])) {
            $deptIds = [$filters['department_id']];
        }
        $deptIds = array_values(array_filter(array_map('intval', $deptIds)));

        if (empty($deptIds)) {
            return;
        }

        $query->where(function (Builder $deptQ) use ($deptIds) {
            $deptQ->whereHas('assignedTo.departments', function ($d) use ($deptIds) {
                $d->whereIn('departments.id', $deptIds);
            })->orWhereHas('assignedBy.departments', function ($d) use ($deptIds) {
                $d->whereIn('departments.id', $deptIds);
            })->orWhereHas('brief.contactPerson', function ($cp) use ($deptIds) {
                $cp->whereIn('department_id', $deptIds);
            })->orWhereHas('planner.creator.departments', function ($d) use ($deptIds) {
                $d->whereIn('departments.id', $deptIds);
            })->orWhereHas('brief.assignedUser.departments', function ($d) use ($deptIds) {
                $d->whereIn('departments.id', $deptIds);
            });
        });
    }

    public function buildBaseFinanceQuery(array $criteria = [], array $relations = [], $user = null): Builder
    {
        $user = $user ?? auth()->user();
        $table = $this->getTable();

        $query = $this->newQuery()
            ->with($relations)
            ->whereNull("{$table}.deleted_at");

        if (isset($criteria['status']) && $criteria['status'] !== '') {
            $query->where("{$table}.status", $criteria['status']);
        } else {
            $query->where("{$table}.status", '!=', '15');
        }

        if ($user) {
            $this->scopeAccessibleToUser($query, $user);
            $this->applyOrganisationValidation($query, $user, $criteria);
        } else {
            return $query->whereRaw('0 = 1');
        }

        $this->applyDepartmentFilter($query, $criteria);

        if (!empty($criteria['brief_id'])) {
            $query->where("{$table}.brief_id", $criteria['brief_id']);
        }

        if (!empty($criteria['planner_id'])) {
            $query->where("{$table}.planner_id", $criteria['planner_id']);
        }

        if (!empty($criteria['finance_status_id'])) {
            $query->where("{$table}.finance_status_id", $criteria['finance_status_id']);
        }

        if (!empty($criteria['assign_by'])) {
            $query->where("{$table}.assign_by", $criteria['assign_by']);
        }

        if (!empty($criteria['assign_to'])) {
            $query->where("{$table}.assign_to", $criteria['assign_to']);
        }

        if (!empty($criteria['date_from'])) {
            $query->whereDate("{$table}.created_at", '>=', $criteria['date_from']);
        }

        if (!empty($criteria['date_to'])) {
            $query->whereDate("{$table}.created_at", '<=', $criteria['date_to']);
        }

        if (!empty($criteria['search'])) {
            $search = trim((string) $criteria['search']);
            $query->where(function ($q) use ($search) {
                $q->whereHas('brief', function ($bQ) use ($search) {
                    $bQ->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('product_name', 'LIKE', "%{$search}%");
                })->orWhereHas('assignedTo', function ($uQ) use ($search) {
                    $uQ->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('email', 'LIKE', "%{$search}%");
                })->orWhereHas('assignedBy', function ($uQ) use ($search) {
                    $uQ->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('email', 'LIKE', "%{$search}%");
                });
            });
        }

        return $query;
    }

    public function paginateRecords(array $criteria = [], int $perPage = 15, $user = null): LengthAwarePaginator
    {
        return $this->buildBaseFinanceQuery($criteria, self::RESPONSE_RELATIONS, $user)
            ->orderByDesc($this->getTable() . '.id')
            ->paginate($perPage);
    }

    public function findRecordById(int $id, $user = null): ?self
    {
        $query = $this->newQuery()->with(self::RESPONSE_RELATIONS)->whereNull($this->getTable() . '.deleted_at');

        if ($user !== false) {
            $user = $user ?? auth()->user();
            if ($user) {
                $this->scopeAccessibleToUser($query, $user);
                $this->applyOrganisationValidation($query, $user);
            } else {
                return null;
            }
        }

        return $query->find($id);
    }

    public function findActiveByBriefAndPlanner(int $briefId, int $plannerId): ?self
    {
        return $this->newQuery()
            ->where('brief_id', $briefId)
            ->where('planner_id', $plannerId)
            ->active()
            ->latest('id')
            ->first();
    }

    public function storeRecord(array $data): self
    {
        return $this->create($data)->load(self::RESPONSE_RELATIONS);
    }

    public function replaceCostSheet(int $id, string $path, ?int $assignBy, ?int $assignTo = null, ?string $comment = null): ?self
    {
        $financeRecord = $this->newQuery()->whereNull('deleted_at')->find($id);

        if (!$financeRecord) {
            return null;
        }

        if ($comment !== null) {
            $financeRecord->history_comment = $comment;
        }

        $updates = [
            'cost_sheet' => $path,
            'assign_by' => $assignBy,
        ];

        if ($assignTo) {
            $updates['assign_to'] = $assignTo;
        }

        $financeRecord->update($updates);

        return $financeRecord->refresh()->load(self::RESPONSE_RELATIONS);
    }

    public function paginateCostSheets(array $criteria = [], int $perPage = 15, $user = null): LengthAwarePaginator
    {
        return $this->buildBaseFinanceQuery($criteria, self::COST_SHEET_RELATIONS, $user)
            ->orderByDesc($this->getTable() . '.id')
            ->paginate($perPage);
    }

    public function findCostSheetById(int $id, $user = null): ?self
    {
        $query = $this->newQuery()->with(self::COST_SHEET_RELATIONS)->whereNull($this->getTable() . '.deleted_at');

        if ($user !== false) {
            $user = $user ?? auth()->user();
            if ($user) {
                $this->scopeAccessibleToUser($query, $user);
                $this->applyOrganisationValidation($query, $user);
            } else {
                return null;
            }
        }

        return $query->find($id);
    }

    public function updateCostSheetRecord(int $id, array $data, ?string $comment = null): ?self
    {
        $financeRecord = $this->newQuery()->whereNull('deleted_at')->find($id);

        if (!$financeRecord) {
            return null;
        }

        if ($comment !== null) {
            $financeRecord->history_comment = $comment;
        }

        $financeRecord->update($data);

        return $financeRecord->refresh()->load(self::COST_SHEET_RELATIONS);
    }

    public function softDeleteRecord(int $id): bool
    {
        $financeRecord = $this->newQuery()->whereNull('deleted_at')->find($id);

        if (!$financeRecord) {
            return false;
        }

        $financeRecord->status = '15';
        $financeRecord->save();

        return (bool) $financeRecord->delete();
    }

    public function hasOtherActiveCostSheet(int $briefId, int $exceptId): bool
    {
        return $this->newQuery()
            ->where('brief_id', $briefId)
            ->where('id', '!=', $exceptId)
            ->where('status', '1')
            ->whereNotNull('cost_sheet')
            ->where('cost_sheet', '!=', '')
            ->exists();
    }
}
