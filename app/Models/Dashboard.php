<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use App\Models\User;
use App\Models\Organisation;
use App\Models\Lead;
use App\Models\Brief;
use App\Models\MissCampaign;
use App\Models\Operation;
use App\Models\FinanceRecord;
use App\Models\PurchaseOrder;
use Illuminate\Database\Eloquent\Builder;
use App\Support\DashboardFilters;
use App\Support\UserAccessScope;

class Dashboard extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<string>|bool
     */
    protected $guarded = ['id'];

    public function fetchTotalUserCount(array $filters, ?User $user): int
    {
        $query = User::query()->whereNull('deleted_at');
        DashboardFilters::applyUserOrganisationFilter($query, $filters);
        DashboardFilters::applyDateFilter($query, $filters, 'created_at');

        if ($user) {
            $strictDescendantIds = UserAccessScope::getStrictDescendantsInOrganisation($user);
            $query->whereIn('id', $strictDescendantIds);
        }

        return $query->count();
    }

    public function fetchAccessibleOrganisations(array $filters, ?User $user): Collection
    {
        $organisationsQuery = Organisation::query()->orderBy('name');
        
        if (!empty($filters['organisation_ids'])) {
            $organisationsQuery->whereIn('id', $filters['organisation_ids']);
        } elseif ($user) {
            $accessibleOrgIds = UserAccessScope::getAccessibleOrganisationIds($user);
            if (!empty($accessibleOrgIds)) {
                $organisationsQuery->whereIn('id', $accessibleOrgIds);
            }
        }

        return $organisationsQuery->get(['id', 'name']);
    }

    public function fetchOrganisationChartRow(array $filters, ?User $user, int $organisationId, string $organisationName): array
    {
        $leadsQuery = Lead::query()->accessibleToUser($user)->whereNull('deleted_at');
        DashboardFilters::applyLeadDashboardFilters($leadsQuery, $filters);

        $preLeadsQuery = MissCampaign::query()->accessibleToUser($user)->notDeleted()->where('miss_campaigns.status', '1');
        DashboardFilters::applyMissCampaignDashboardFilters($preLeadsQuery, $filters);

        $briefsQuery = Brief::query()->accessibleToUser($user)->whereNull('deleted_at')->whereRaw('briefs.status != 15');
        DashboardFilters::applyBriefDashboardFilters($briefsQuery, $filters);

        return [
            'organisation_id' => $organisationId,
            'organisation_name' => $organisationName,
            'total_leads' => (int) (clone $leadsQuery)->count(),
            'pre_leads' => (int) (clone $preLeadsQuery)->count(),
            'briefs' => (int) (clone $briefsQuery)->count(),
            'brief_budget' => (float) (clone $briefsQuery)->sum('briefs.budget'),
        ];
    }

    public function fetchSalesPipelineCounts(array $filters, ?User $user): array
    {
        $leadQuery = Lead::query()->accessibleToUser($user)->whereNull('deleted_at');
        DashboardFilters::applyLeadDashboardFilters($leadQuery, $filters, 'leads');

        $briefQuery = Brief::query()->accessibleToUser($user)->whereNull('deleted_at')->whereRaw('briefs.status != 15');
        DashboardFilters::applyBriefDashboardFilters($briefQuery, $filters, 'briefs');

        return [
            'new_leads' => (int) (clone $leadQuery)->count(),
            'follow_up' => (int) (clone $leadQuery)->whereHas('callStatusRelation', function ($query) {
                $query->where('slug', 'follow-up');
            })->count(),
            'meeting_scheduled' => (int) (clone $leadQuery)->whereHas('callStatusRelation', function ($query) {
                $query->where('slug', 'meeting-schedule');
            })->count(),
            'briefs' => (int) (clone $briefQuery)->count(),
        ];
    }

    public function fetchPlannerBriefStatusCounts(array $filters, ?User $user): array
    {
        $briefQuery = Brief::query()->accessibleToUser($user)->whereNull('deleted_at')->whereRaw('briefs.status != 15');
        DashboardFilters::applyBriefDashboardFilters($briefQuery, $filters, 'briefs');

        return [
            'active_briefs' => (int) (clone $briefQuery)->whereDate('submission_date', '>=', now())->count(),
            'closed_briefs' => (int) (clone $briefQuery)->whereHas('briefStatus', function ($query) {
                $query->where('slug', 'closed');
            })->count(),
            'overdue_briefs' => (int) (clone $briefQuery)->where('submission_date', '<', now())->count(),
        ];
    }

    public function fetchPlannerOrganisationRow(array $filters, ?User $user, int $organisationId, string $organisationName): array
    {
        $assignedBriefQuery = Brief::query()
            ->accessibleToUser($user)
            ->whereNull('briefs.deleted_at')
            ->whereRaw('briefs.status != 15')
            ->whereNotNull('briefs.assign_user_id');
        DashboardFilters::applyBriefDashboardFilters($assignedBriefQuery, $filters, 'briefs');

        $assignedPlans = (int) (clone $assignedBriefQuery)->count();

        return [
            'organisation_id' => $organisationId,
            'organisation_name' => $organisationName,
            'assigned_plans' => $assignedPlans,
            'avg_assignment_days' => 0,
        ];
    }

    /**
     * Brief ids visible on the planner chart, grouped by contact-person organisation.
     *
     * @param array<string, mixed> $filters
     * @return array<int, array<int, int>>
     */
    public function fetchBriefIdsByOrganisation(array $filters, ?User $user): array
    {
        $query = Brief::query()
            ->accessibleToUser($user)
            ->whereNull('briefs.deleted_at')
            ->whereRaw('briefs.status != 15');
        DashboardFilters::applyBriefDashboardFilters($query, $filters, 'briefs');

        $briefs = $query
            ->with(['contactPerson:id,organisation_id'])
            ->get(['briefs.id', 'briefs.contact_person_id']);

        $grouped = [];

        foreach ($briefs as $brief) {
            $organisationId = $brief->contactPerson?->organisation_id;
            if ($organisationId === null) {
                continue;
            }

            $grouped[(int) $organisationId][] = (int) $brief->id;
        }

        return $grouped;
    }

    /**
     * Operations counts for one organisation: total, pending, live, and assigned.
     *
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function fetchOperationsOrganisationRow(array $filters, ?User $user, int $organisationId, string $organisationName): array
    {
        $query = $this->scopedOperationsQuery($filters, $user);

        return [
            'organisation_id' => $organisationId,
            'organisation_name' => $organisationName,
            'operations' => (int) (clone $query)->count(),
            'pending_operations' => $this->countOperationsByStatusSlug($query, 'pending'),
            'live_operations' => $this->countOperationsByStatusSlug($query, 'live'),
            'assigned_operations' => (int) (clone $query)->whereNotNull('operations.assign_to')->count(),
        ];
    }

    /**
     * Pending and live operation counts for the selected filters.
     *
     * @param array<string, mixed> $filters
     * @return array<string, int>
     */
    public function fetchOperationsStatusCounts(array $filters, ?User $user): array
    {
        $query = $this->scopedOperationsQuery($filters, $user);

        return [
            'pending' => $this->countOperationsByStatusSlug($query, 'pending'),
            'live' => $this->countOperationsByStatusSlug($query, 'live'),
        ];
    }

    /**
     * Latest operations visible for the selected filters.
     *
     * @param array<string, mixed> $filters
     * @return list<array<string, mixed>>
     */
    public function fetchRecentOperations(array $filters, ?User $user, int $limit = 5): array
    {
        $operations = $this->scopedOperationsQuery($filters, $user)
            ->with([
                'brief:id,name,product_name,campaign_start_date',
                'operationStatus:id,name,slug',
                'assignedTo:id,name',
            ])
            ->orderByDesc('operations.id')
            ->limit($limit)
            ->get();

        return $operations->map(function (Operation $operation) {
            $startDate = $operation->brief?->campaign_start_date;

            return [
                'id' => $operation->id,
                'brief_id' => $operation->brief_id,
                'brief_name' => $operation->brief?->name,
                'product_name' => $operation->brief?->product_name,
                'status' => $operation->operationStatus?->name,
                'assign_user' => $operation->assignedTo?->name,
                'campaign_start_date' => $startDate instanceof \DateTimeInterface
                    ? $startDate->format('Y-m-d')
                    : ($startDate ? (string) $startDate : null),
            ];
        })->all();
    }

    /**
     * Finance counts and purchase-order amount for one organisation.
     *
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function fetchFinanceOrganisationRow(array $filters, ?User $user, int $organisationId, string $organisationName): array
    {
        $query = $this->scopedFinanceRecordsQuery($filters, $user);

        return [
            'organisation_id' => $organisationId,
            'organisation_name' => $organisationName,
            'cost_sheets' => (int) (clone $query)->count(),
            'approved' => $this->countFinanceRecordsByStatusSlug($query, 'approved'),
            'denied' => $this->countFinanceRecordsByStatusSlug($query, 'denied'),
            'pending' => $this->countPendingFinanceRecords($query),
            'purchase_order_amount' => $this->sumPurchaseOrderAmount($filters, $user),
        ];
    }

    /**
     * Approved, denied, and pending finance counts for the selected filters.
     *
     * @param array<string, mixed> $filters
     * @return array<string, int>
     */
    public function fetchFinanceStatusCounts(array $filters, ?User $user): array
    {
        $query = $this->scopedFinanceRecordsQuery($filters, $user);

        return [
            'approved' => $this->countFinanceRecordsByStatusSlug($query, 'approved'),
            'denied' => $this->countFinanceRecordsByStatusSlug($query, 'denied'),
            'pending' => $this->countPendingFinanceRecords($query),
        ];
    }

    /**
     * Latest cost sheets visible for the selected filters.
     *
     * @param array<string, mixed> $filters
     * @return list<array<string, mixed>>
     */
    public function fetchRecentFinanceRecords(array $filters, ?User $user, int $limit = 5): array
    {
        $records = $this->scopedFinanceRecordsQuery($filters, $user)
            ->with([
                'brief:id,name',
                'planner.creator:id,name',
                'financeStatus:id,name,slug',
                'assignedTo:id,name',
                'latestPurchaseOrder',
            ])
            ->orderByDesc('finance_records.id')
            ->limit($limit)
            ->get();

        return $records->map(function (FinanceRecord $record) {
            return [
                'id' => $record->id,
                'brief_id' => $record->brief_id,
                'brief_name' => $record->brief?->name,
                'planner_name' => $record->planner?->creator?->name,
                'finance_status' => $record->financeStatus?->name ?? 'Pending',
                'assign_user' => $record->assignedTo?->name,
                'purchase_order_amount' => (float) ($record->latestPurchaseOrder?->total_amount ?? 0),
            ];
        })->all();
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function scopedOperationsQuery(array $filters, ?User $user): Builder
    {
        $operation = new Operation();
        $query = Operation::query()
            ->where('operations.status', '1')
            ->accessibleToUser($user)
            ->whereHas('brief', function (Builder $briefQuery) {
                $briefQuery->whereNull('briefs.deleted_at')
                    ->whereRaw('briefs.status != 15');
            });

        $operation->applyOrganisationValidation($query, $user, $filters);
        $operation->applyDepartmentFilter($query, $filters);

        $userIds = $filters['user_ids'] ?? [];
        if (!empty($userIds)) {
            $query->where(function (Builder $userQuery) use ($userIds) {
                $userQuery->whereIn('operations.assign_to', $userIds)
                    ->orWhereIn('operations.assign_by', $userIds)
                    ->orWhereHas('planner', function (Builder $plannerQuery) use ($userIds) {
                        $plannerQuery->whereIn('created_by', $userIds);
                    })
                    ->orWhereHas('brief', function (Builder $briefQuery) use ($userIds) {
                        $briefQuery->whereIn('created_by', $userIds)
                            ->orWhereIn('assign_user_id', $userIds);
                    });
            });
        }

        foreach (['assign_to', 'assign_by'] as $assignmentColumn) {
            if (!empty($filters[$assignmentColumn])) {
                $query->where("operations.{$assignmentColumn}", (int) $filters[$assignmentColumn]);
            }
        }

        return DashboardFilters::applyDateFilter($query, $filters, 'operations.created_at');
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function scopedFinanceRecordsQuery(array $filters, ?User $user): Builder
    {
        $financeRecord = new FinanceRecord();
        $query = FinanceRecord::query()
            ->where('finance_records.status', '1')
            ->accessibleToUser($user)
            ->whereHas('brief', function (Builder $briefQuery) {
                $briefQuery->whereNull('briefs.deleted_at')
                    ->whereRaw('briefs.status != 15');
            });

        $financeRecord->applyOrganisationValidation($query, $user, $filters);
        $financeRecord->applyDepartmentFilter($query, $filters);

        $userIds = $filters['user_ids'] ?? [];
        if (!empty($userIds)) {
            $query->where(function (Builder $userQuery) use ($userIds) {
                $userQuery->whereIn('finance_records.assign_to', $userIds)
                    ->orWhereIn('finance_records.assign_by', $userIds)
                    ->orWhereHas('planner', function (Builder $plannerQuery) use ($userIds) {
                        $plannerQuery->whereIn('created_by', $userIds);
                    })
                    ->orWhereHas('brief', function (Builder $briefQuery) use ($userIds) {
                        $briefQuery->whereIn('created_by', $userIds)
                            ->orWhereIn('assign_user_id', $userIds);
                    });
            });
        }

        foreach (['assign_to', 'assign_by'] as $assignmentColumn) {
            if (!empty($filters[$assignmentColumn])) {
                $query->where("finance_records.{$assignmentColumn}", (int) $filters[$assignmentColumn]);
            }
        }

        return DashboardFilters::applyDateFilter($query, $filters, 'finance_records.created_at');
    }

    private function countOperationsByStatusSlug(Builder $query, string $slug): int
    {
        return (int) (clone $query)->whereHas('operationStatus', function (Builder $statusQuery) use ($slug) {
            $statusQuery->where('slug', $slug);
        })->count();
    }

    private function countFinanceRecordsByStatusSlug(Builder $query, string $slug): int
    {
        return (int) (clone $query)->whereHas('financeStatus', function (Builder $statusQuery) use ($slug) {
            $statusQuery->where('slug', $slug);
        })->count();
    }

    private function countPendingFinanceRecords(Builder $query): int
    {
        return (int) (clone $query)->where(function (Builder $builder) {
            $builder->whereNull('finance_records.finance_status_id')
                ->orWhereHas('financeStatus', function (Builder $statusQuery) {
                    $statusQuery->where('slug', 'pending');
                });
        })->count();
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function sumPurchaseOrderAmount(array $filters, ?User $user): float
    {
        $financeRecordIds = $this->scopedFinanceRecordsQuery($filters, $user)->select('finance_records.id');
        $latestPurchaseOrderIds = PurchaseOrder::query()
            ->selectRaw('MAX(purchase_orders.id)')
            ->groupBy('purchase_orders.finance_record_id');
        $query = PurchaseOrder::query()
            ->whereIn('purchase_orders.finance_record_id', $financeRecordIds)
            ->whereIn('purchase_orders.id', $latestPurchaseOrderIds);

        DashboardFilters::applyDateFilter($query, $filters, 'purchase_orders.created_at');

        return (float) $query->sum('purchase_orders.total_amount');
    }
}
