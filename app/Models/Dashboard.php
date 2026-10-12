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
use App\Models\Voucher;
use App\Models\ProformaInvoice;
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
            'total_brief' => (int) (clone $briefQuery)->count(),
            'active_briefs' => (int) (clone $briefQuery)->whereDate('submission_date', '>=', now())->count(),
            'closed_briefs' => (int) (clone $briefQuery)->whereHas('briefStatus', function ($query) {
                $query->where('slug', 'closed');
            })->count(),
            'overdue_briefs' => (int) (clone $briefQuery)
                ->whereNotNull('submission_date')
                ->where('submission_date', '<', now())
                ->whereDoesntHave('planners', function ($query) {
                    $query->whereNull('deleted_at')
                        ->where('status', '!=', '15')
                        ->whereNotNull('planner_status_id');
                })
                ->count(),
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
     * Total operations counts from the operations table for the selected filters.
     *
     * @param array<string, mixed> $filters
     * @return array<string, int>
     */
    public function fetchOperationsTotals(array $filters, ?User $user): array
    {
        $query = $this->scopedOperationsQuery($filters, $user);

        return [
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
     * Total finance metrics from the finance_records table for the selected filters.
     *
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function fetchFinanceTotals(array $filters, ?User $user): array
    {
        $query = $this->scopedFinanceRecordsQuery($filters, $user);

        return [
            'cost_sheets' => (int) (clone $query)->count(),
            'approved' => $this->countFinanceRecordsByStatusSlug($query, 'approved'),
            'denied' => $this->countFinanceRecordsByStatusSlug($query, 'denied'),
            'pending' => $this->countPendingFinanceRecords($query),
            'purchase_order_amount' => $this->sumPurchaseOrderAmount($filters, $user),
        ];
    }

    /**
     * Sum of voucher total amounts and proforma invoice total amounts for the selected filters.
     *
     * @param array<string, mixed> $filters
     * @return array{voucher_total_amount: float, proforma_invoice_total_amount: float}
     */
    public function fetchFinanceSummary(array $filters, ?User $user): array
    {
        $voucherQuery = Voucher::query()
            ->whereNull('vouchers.deleted_at')
            ->where('vouchers.status', '!=', '15');

        $piQuery = ProformaInvoice::query()
            ->whereNull('proforma_invoices.deleted_at')
            ->where('proforma_invoices.status', '!=', '15');

        $dateFrom = $filters['date_from'] ?? $filters['from_date'] ?? null;
        $dateTo = $filters['date_to'] ?? $filters['to_date'] ?? null;

        if ($dateFrom) {
            $voucherQuery->whereDate('vouchers.created_at', '>=', $dateFrom);
            $piQuery->whereDate('proforma_invoices.created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $voucherQuery->whereDate('vouchers.created_at', '<=', $dateTo);
            $piQuery->whereDate('proforma_invoices.created_at', '<=', $dateTo);
        }

        if (!empty($filters['created_by'])) {
            $voucherQuery->where('vouchers.created_by', (int) $filters['created_by']);
            $piQuery->where('proforma_invoices.created_by', (int) $filters['created_by']);
        }

        if (!empty($filters['voucher_type_id'])) {
            $voucherQuery->where('vouchers.voucher_type_id', (int) $filters['voucher_type_id']);
        }

        if (!empty($filters['brand_id'])) {
            $piQuery->where('proforma_invoices.brand_id', (int) $filters['brand_id']);
        }

        if (!empty($filters['month'])) {
            $voucherQuery->where('vouchers.month', (string) $filters['month']);
        }

        return [
            'voucher_total_amount' => (float) round((float) $voucherQuery->sum('vouchers.total_amount'), 2),
            'proforma_invoice_total_amount' => (float) round((float) $piQuery->sum('proforma_invoices.total_amount'), 2),
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
            ->whereNull('operations.deleted_at')
            ->accessibleToUser($user)
            ->whereHas('brief', function (Builder $briefQuery) {
                $briefQuery->whereNull('briefs.deleted_at')
                    ->whereRaw('briefs.status != 15');
            });

        if (isset($filters['status']) && $filters['status'] !== null && $filters['status'] !== '') {
            $query->where('operations.status', (string) $filters['status']);
        } else {
            $query->where('operations.status', '1');
        }

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

        if (!empty($filters['operation_status_id'])) {
            $query->where('operations.operation_status_id', (int) $filters['operation_status_id']);
        }

        if (!empty($filters['brief_id'])) {
            $query->where('operations.brief_id', (int) $filters['brief_id']);
        }

        if (!empty($filters['planner_id'])) {
            $query->where('operations.planner_id', (int) $filters['planner_id']);
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
            ->whereNull('finance_records.deleted_at')
            ->accessibleToUser($user)
            ->whereHas('brief', function (Builder $briefQuery) {
                $briefQuery->whereNull('briefs.deleted_at')
                    ->whereRaw('briefs.status != 15');
            });

        if (isset($filters['status']) && $filters['status'] !== null && $filters['status'] !== '') {
            $query->where('finance_records.status', (string) $filters['status']);
        } else {
            $query->where('finance_records.status', '1');
        }

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

        if (!empty($filters['finance_status_id'])) {
            $query->where('finance_records.finance_status_id', (int) $filters['finance_status_id']);
        }

        if (!empty($filters['brief_id'])) {
            $query->where('finance_records.brief_id', (int) $filters['brief_id']);
        }

        if (!empty($filters['planner_id'])) {
            $query->where('finance_records.planner_id', (int) $filters['planner_id']);
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

    /**
     * Unassigned work counts across all five modules:
     * 1. Unassigned Sales (Leads without assigned user)
     * 2. Unassigned Briefs (Briefs without assigned user)
     * 3. Unassigned Planner (Active non-closed briefs without active plans)
     * 4. Unassigned Operations (Operations without assigned user)
     * 5. Unassigned Finance (Finance records without assigned user)
     *
     * @param array<string, mixed> $filters
     * @return array{unassigned_sales: int, unassigned_briefs: int, unassigned_planner: int, unassigned_operations: int, unassigned_finance: int}
     */
    public function fetchUnassignedCounts(array $filters, ?User $user): array
    {
        // 1. Unassigned Sales (Leads)
        $salesQuery = Lead::query()
            ->accessibleToUser($user)
            ->whereNull('leads.deleted_at')
            ->where('leads.status', '!=', '15')
            ->where(function (Builder $q) {
                $q->whereNull('leads.current_assign_user')
                    ->orWhere('leads.current_assign_user', 0);
            });
        DashboardFilters::applyLeadDashboardFilters($salesQuery, $filters, 'leads');
        $this->applyDepartmentFilterToLeadQuery($salesQuery, $filters);
        $unassignedSales = (int) (clone $salesQuery)->count();

        // 2. Unassigned Briefs
        $briefsQuery = Brief::query()
            ->accessibleToUser($user)
            ->whereNull('briefs.deleted_at')
            ->whereRaw('briefs.status != 15')
            ->where(function (Builder $q) {
                $q->whereNull('briefs.assign_user_id')
                    ->orWhere('briefs.assign_user_id', 0);
            });
        DashboardFilters::applyBriefDashboardFilters($briefsQuery, $filters, 'briefs');
        $this->applyDepartmentFilterToBriefQuery($briefsQuery, $filters);
        $unassignedBriefs = (int) (clone $briefsQuery)->count();

        // 3. Unassigned Planner (Active briefs in planning with no active planner created)
        $plannerQuery = Brief::query()
            ->accessibleToUser($user)
            ->whereNull('briefs.deleted_at')
            ->whereRaw('briefs.status != 15')
            ->whereDoesntHave('briefStatus', function (Builder $query) {
                $query->where('slug', 'closed');
            })
            ->whereDoesntHave('planners', function (Builder $query) {
                $query->whereNull('deleted_at')
                    ->where('status', '!=', '15');
            });
        DashboardFilters::applyBriefDashboardFilters($plannerQuery, $filters, 'briefs');
        $this->applyDepartmentFilterToBriefQuery($plannerQuery, $filters);
        $unassignedPlanner = (int) (clone $plannerQuery)->count();

        // 4. Unassigned Operations
        $operationsQuery = $this->scopedOperationsQuery($filters, $user)
            ->where(function (Builder $q) {
                $q->whereNull('operations.assign_to')
                    ->orWhere('operations.assign_to', 0);
            });
        $unassignedOperations = (int) (clone $operationsQuery)->count();

        // 5. Unassigned Finance
        $financeQuery = $this->scopedFinanceRecordsQuery($filters, $user)
            ->where(function (Builder $q) {
                $q->whereNull('finance_records.assign_to')
                    ->orWhere('finance_records.assign_to', 0);
            });
        $unassignedFinance = (int) (clone $financeQuery)->count();

        return [
            'unassigned_sales' => $unassignedSales,
            'unassigned_briefs' => $unassignedBriefs,
            'unassigned_planner' => $unassignedPlanner,
            'unassigned_operations' => $unassignedOperations,
            'unassigned_finance' => $unassignedFinance,
        ];
    }

    private function applyDepartmentFilterToLeadQuery(Builder $query, array $filters): void
    {
        $deptIds = $this->extractDepartmentIds($filters);
        if (!empty($deptIds)) {
            $query->whereIn('leads.department_id', $deptIds);
        }
    }

    private function applyDepartmentFilterToBriefQuery(Builder $query, array $filters): void
    {
        $deptIds = $this->extractDepartmentIds($filters);
        if (!empty($deptIds)) {
            $query->where(function (Builder $q) use ($deptIds) {
                $q->whereHas('contactPerson', fn (Builder $cp) => $cp->whereIn('department_id', $deptIds))
                    ->orWhereHas('assignedUser.departments', fn (Builder $d) => $d->whereIn('departments.id', $deptIds));
            });
        }
    }

    private function extractDepartmentIds(array $filters): array
    {
        $deptIds = [];
        if (!empty($filters['department_ids'])) {
            $deptIds = is_array($filters['department_ids'])
                ? $filters['department_ids']
                : explode(',', (string) $filters['department_ids']);
        } elseif (!empty($filters['department_id'])) {
            $deptIds = [$filters['department_id']];
        }

        return array_values(array_filter(array_map('intval', $deptIds)));
    }
}
