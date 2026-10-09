<?php

namespace App\Services;

use App\Contracts\Repositories\LeadRepositoryInterface;
use App\Contracts\Repositories\DashboardRepositoryInterface;
use App\Support\UserAccessScope;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Support\DashboardPermissionSupport;

class DashboardService
{
    protected LeadRepositoryInterface $leadRepository;
    protected DashboardRepositoryInterface $dashboardRepository;
    protected UserRepositoryInterface $userRepository;
    protected BriefAssignHistoryService $briefAssignHistoryService;

    public function __construct(
        LeadRepositoryInterface $leadRepository,
        DashboardRepositoryInterface $dashboardRepository,
        UserRepositoryInterface $userRepository,
        BriefAssignHistoryService $briefAssignHistoryService
    ) {
        $this->leadRepository = $leadRepository;
        $this->dashboardRepository = $dashboardRepository;
        $this->userRepository = $userRepository;
        $this->briefAssignHistoryService = $briefAssignHistoryService;
    }

    /**
     * @param array<string, mixed> $filters
     * @throws Exception
     */
    public function getDashboardData(array $filters = []): array
    {
        try {
            return [
                'total_user_count' => $this->getTotalUserCount($filters),
                'pending_lead_count' => $this->getPendingLeadCount($filters),
                'team_performance' => null,
                'open_alerts' => null,
            ];
        } catch (Exception $e) {
            Log::error('Error fetching dashboard data', ['exception' => $e]);
            throw new Exception('Unable to fetch dashboard data');
        }
    }

    /**
     * @param array<string, mixed> $filters
     * @throws Exception
     */
    public function getChartMetrics(array $filters = []): array
    {
        try {
            $user = Auth::user();

            if (!$user) {
                throw new Exception('User not authenticated');
            }

            if (
                empty($filters['organisation_ids'])
                && empty(UserAccessScope::getAccessibleOrganisationIds($user))
            ) {
                return $this->buildAggregateChartMetrics($user, $filters);
            }

            $organisations = $this->dashboardRepository->getAccessibleOrganisations($filters, $user);
            $rows = [];

            foreach ($organisations as $organisation) {
                $organisationFilter = array_merge($filters, [
                    'organisation_ids' => [(int) $organisation->id],
                ]);

                $rows[] = $this->buildOrganisationChartRow($user, $organisation->id, $organisation->name, $organisationFilter);
            }

            if ($rows === [] && empty(UserAccessScope::getAccessibleOrganisationIds($user))) {
                return $this->buildAggregateChartMetrics($user, $filters);
            }

            return [
                'by_organisation' => $rows,
                'totals' => [
                    'total_leads' => array_sum(array_column($rows, 'total_leads')),
                    'pre_leads' => array_sum(array_column($rows, 'pre_leads')),
                    'briefs' => array_sum(array_column($rows, 'briefs')),
                    'brief_budget' => array_sum(array_column($rows, 'brief_budget')),
                ],
            ];
        } catch (Exception $e) {
            Log::error('Error fetching dashboard chart metrics', ['exception' => $e]);
            throw new Exception('Unable to fetch dashboard chart metrics');
        }
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    private function buildAggregateChartMetrics($user, array $filters): array
    {
        $row = $this->buildOrganisationChartRow($user, 0, 'My Data', $filters);

        return [
            'by_organisation' => [$row],
            'totals' => [
                'total_leads' => $row['total_leads'],
                'pre_leads' => $row['pre_leads'],
                'briefs' => $row['briefs'],
                'brief_budget' => $row['brief_budget'],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    private function buildOrganisationChartRow($user, int $organisationId, string $organisationName, array $filters): array
    {
        return $this->dashboardRepository->getOrganisationChartRow($filters, $user, $organisationId, $organisationName);
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function getTotalUserCount(array $filters = []): int
    {
        try {
            // Get it directly from the Users API logic (UserRepository)
            $stats = $this->userRepository->getStatistics();
            return (int) ($stats['total'] ?? 0);
        } catch (Exception $e) {
            Log::error('Error fetching total user count', ['exception' => $e]);
            return 0;
        }
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function getPendingLeadCount(array $filters = []): int
    {
        try {
            $pendingLeads = $this->leadRepository->getPendingLeads(1, $filters);
            return $pendingLeads->total();
        } catch (Exception $e) {
            Log::error('Error fetching pending lead count', ['exception' => $e]);
            return 0;
        }
    }

    /**
     * Sales dashboard charts: organisation metrics + lead pipeline.
     *
     * @param array<string, mixed> $filters
     * @throws Exception
     */
    public function getSalesChartMetrics(array $filters = []): array
    {
        try {
            $user = Auth::user();
            $charts = $this->getChartMetrics($filters);
            $pipelineCounts = $this->dashboardRepository->getSalesPipelineCounts($filters, $user);

            $byOrganisation = array_map(static function (array $row) {
                return [
                    'organisation_id' => $row['organisation_id'],
                    'organisation_name' => $row['organisation_name'],
                    'total_leads' => $row['total_leads'],
                    'briefs' => $row['briefs'],
                    'brief_budget' => $row['brief_budget'],
                ];
            }, $charts['by_organisation']);

            return [
                'by_organisation' => $byOrganisation,
                'totals' => [
                    'total_leads' => $charts['totals']['total_leads'],
                    'briefs' => $charts['totals']['briefs'],
                    'brief_budget' => $charts['totals']['brief_budget'],
                ],
                'pipeline' => $pipelineCounts,
            ];
        } catch (Exception $e) {
            Log::error('Error fetching sales dashboard chart metrics', ['exception' => $e]);
            throw new Exception('Unable to fetch sales dashboard chart metrics');
        }
    }

    /**
     * Planner dashboard charts: organisation brief metrics + brief status breakdown.
     *
     * @param array<string, mixed> $filters
     * @throws Exception
     */
    public function getPlannerChartMetrics(array $filters = []): array
    {
        try {
            $user = Auth::user();
            $charts = $this->getChartMetrics($filters);
            $plannerRows = $this->getPlannerOrganisationMetrics($filters);
            $plannerByOrgId = collect($plannerRows)->keyBy('organisation_id');

            $briefStatusCounts = $this->dashboardRepository->getPlannerBriefStatusCounts($filters, $user);
            $briefIdsByOrganisation = $this->dashboardRepository->getBriefIdsByOrganisation($filters, $user);
            $completedDaysByBrief = $this->briefAssignHistoryService->getCompletedCycleDurationDaysByBrief(
                $this->flattenBriefIds($briefIdsByOrganisation)
            );
            $completedDaysForPlanSubmission = [];

            $byOrganisation = array_map(function (array $row) use (
                $plannerByOrgId,
                $briefIdsByOrganisation,
                $completedDaysByBrief,
                &$completedDaysForPlanSubmission
            ) {
                $planner = $plannerByOrgId->get($row['organisation_id'], []);
                $organisationDays = [];

                foreach ($briefIdsByOrganisation[(int) $row['organisation_id']] ?? [] as $briefId) {
                    foreach ($completedDaysByBrief[$briefId] ?? [] as $days) {
                        $organisationDays[] = $days;
                        $completedDaysForPlanSubmission[] = $days;
                    }
                }

                return [
                    'organisation_id' => $row['organisation_id'],
                    'organisation_name' => $row['organisation_name'],
                    'briefs' => $row['briefs'],
                    'brief_budget' => $row['brief_budget'],
                    'assigned_plans' => (int) ($planner['assigned_plans'] ?? 0),
                    'avg_plan_submission_days' => $this->averagePlanSubmissionDays($organisationDays),
                ];
            }, $charts['by_organisation']);

            $avgPlanSubmissionDays = $this->averagePlanSubmissionDays($completedDaysForPlanSubmission);
            $avgPlanSubmissionHours = $this->averagePlanSubmissionHours($completedDaysForPlanSubmission);

            return [
                'by_organisation' => $byOrganisation,
                'totals' => [
                    'briefs' => $charts['totals']['briefs'],
                    'brief_budget' => $charts['totals']['brief_budget'],
                    'assigned_plans' => (int) array_sum(array_column($byOrganisation, 'assigned_plans')),
                    'avg_plan_submission_days' => $avgPlanSubmissionDays,
                ],
                'brief_status' => $briefStatusCounts,
                'plan_submission' => [
                    'avg_submission_days' => $avgPlanSubmissionDays,
                    'avg_submission_hours' => $avgPlanSubmissionHours,
                    'submitted_plans' => count($completedDaysForPlanSubmission),
                ],
            ];
        } catch (Exception $e) {
            Log::error('Error fetching planner dashboard chart metrics', ['exception' => $e]);
            throw new Exception('Unable to fetch planner dashboard chart metrics');
        }
    }

    /**
     * @param array<string, mixed> $filters
     * @return list<array<string, mixed>>
     */
    private function getPlannerOrganisationMetrics(array $filters): array
    {
        $user = Auth::user();

        if (!$user) {
            return [];
        }

        if (
            empty($filters['organisation_ids'])
            && empty(UserAccessScope::getAccessibleOrganisationIds($user))
        ) {
            return [$this->dashboardRepository->getPlannerOrganisationRow($filters, $user, 0, 'My Data')];
        }

        $organisations = $this->dashboardRepository->getAccessibleOrganisations($filters, $user);
        $rows = [];
        foreach ($organisations as $organisation) {
            $organisationFilter = array_merge($filters, [
                'organisation_ids' => [(int) $organisation->id],
            ]);
            $rows[] = $this->dashboardRepository->getPlannerOrganisationRow(
                $organisationFilter,
                $user,
                (int) $organisation->id,
                (string) $organisation->name
            );
        }

        if ($rows === [] && empty(UserAccessScope::getAccessibleOrganisationIds($user))) {
            return [$this->dashboardRepository->getPlannerOrganisationRow($filters, $user, 0, 'My Data')];
        }

        return $rows;
    }

    /**
     * @param array<int, array<int, int>> $briefIdsByOrganisation
     * @return array<int, int>
     */
    private function flattenBriefIds(array $briefIdsByOrganisation): array
    {
        $briefIds = [];

        foreach ($briefIdsByOrganisation as $organisationBriefIds) {
            foreach ($organisationBriefIds as $briefId) {
                $briefIds[$briefId] = (int) $briefId;
            }
        }

        return array_values($briefIds);
    }

    /**
     * Plan submission days retain enough precision for short submission cycles.
     *
     * @param array<int, float> $dayValues
     */
    private function averagePlanSubmissionDays(array $dayValues): float
    {
        if ($dayValues === []) {
            return 0;
        }

        return round(array_sum($dayValues) / count($dayValues), 2);
    }

    /**
     * Calculate hours from the unrounded day values so short cycles do not become zero.
     *
     * @param array<int, float> $dayValues
     */
    private function averagePlanSubmissionHours(array $dayValues): float
    {
        if ($dayValues === []) {
            return 0;
        }

        return round((array_sum($dayValues) / count($dayValues)) * 24, 1);
    }

    /**
     * Operations dashboard charts: organisation counts, status mix, and recent rows.
     *
     * @param array<string, mixed> $filters
     * @throws Exception
     */
    public function getOperationsChartMetrics(array $filters = []): array
    {
        try {
            $user = Auth::user();
            if (!$user) {
                throw new Exception('User not authenticated');
            }

            $rows = $this->buildScopedOrganisationRows(
                $filters,
                $user,
                fn (array $organisationFilter, int $organisationId, string $organisationName) =>
                    $this->dashboardRepository->getOperationsOrganisationRow(
                        $organisationFilter,
                        $user,
                        $organisationId,
                        $organisationName
                    )
            );

            return [
                'by_organisation' => $rows,
                'totals' => $this->dashboardRepository->getOperationsTotals($filters, $user),
                'operation_status' => $this->dashboardRepository->getOperationsStatusCounts($filters, $user),
                'recent' => $this->dashboardRepository->getRecentOperations($filters, $user),
            ];
        } catch (Exception $e) {
            Log::error('Error fetching operations dashboard chart metrics', ['exception' => $e]);
            throw new Exception('Unable to fetch operations dashboard chart metrics');
        }
    }

    /**
     * Finance dashboard charts: cost sheets, decisions, and purchase order amounts.
     *
     * @param array<string, mixed> $filters
     * @throws Exception
     */
    public function getFinanceChartMetrics(array $filters = []): array
    {
        try {
            $user = Auth::user();
            if (!$user) {
                throw new Exception('User not authenticated');
            }

            $rows = $this->buildScopedOrganisationRows(
                $filters,
                $user,
                fn (array $organisationFilter, int $organisationId, string $organisationName) =>
                    $this->dashboardRepository->getFinanceOrganisationRow(
                        $organisationFilter,
                        $user,
                        $organisationId,
                        $organisationName
                    )
            );

            return [
                'by_organisation' => $rows,
                'totals' => $this->dashboardRepository->getFinanceTotals($filters, $user),
                'finance_status' => $this->dashboardRepository->getFinanceStatusCounts($filters, $user),
                'recent' => $this->dashboardRepository->getRecentFinanceRecords($filters, $user),
            ];
        } catch (Exception $e) {
            Log::error('Error fetching finance dashboard chart metrics', ['exception' => $e]);
            throw new Exception('Unable to fetch finance dashboard chart metrics');
        }
    }

    /**
     * Get finance summary metrics (voucher total and proforma invoice total).
     *
     * @param array<string, mixed> $filters
     * @return array{voucher_total_amount: float, proforma_invoice_total_amount: float}
     * @throws Exception
     */
    public function getFinanceSummary(array $filters = []): array
    {
        try {
            $user = Auth::user();
            return $this->dashboardRepository->getFinanceSummary($filters, $user);
        } catch (Exception $e) {
            Log::error('Error fetching finance summary', ['exception' => $e]);
            throw new Exception('Unable to fetch finance summary');
        }
    }

    /**
     * Build one chart row per accessible organisation, or a single aggregate row.
     *
     * @param array<string, mixed> $filters
     * @param callable(array<string, mixed>, int, string): array<string, mixed> $rowBuilder
     * @return list<array<string, mixed>>
     */
    private function buildScopedOrganisationRows(array $filters, $user, callable $rowBuilder): array
    {
        try {
            if (
                empty($filters['organisation_ids'])
                && empty(UserAccessScope::getAccessibleOrganisationIds($user))
            ) {
                return [$rowBuilder($filters, 0, 'My Data')];
            }

            $organisations = $this->dashboardRepository->getAccessibleOrganisations($filters, $user);
            $rows = [];

            foreach ($organisations as $organisation) {
                $organisationFilter = array_merge($filters, [
                    'organisation_ids' => [(int) $organisation->id],
                ]);
                $rows[] = $rowBuilder($organisationFilter, (int) $organisation->id, (string) $organisation->name);
            }

            if ($rows === [] && empty(UserAccessScope::getAccessibleOrganisationIds($user))) {
                return [$rowBuilder($filters, 0, 'My Data')];
            }

            return $rows;
        } catch (Exception $e) {
            Log::error('Error building scoped organisation rows', [
                'exception' => $e,
                'filters' => $filters,
            ]);
            throw new Exception('Unable to build scoped organisation rows: ' . $e->getMessage());
        }
    }

    /**
     * Unassigned work counts across all five modules (Sales, Briefs, Planner, Operations, Finance).
     *
     * @param array<string, mixed> $filters
     * @return array{unassigned_sales: int, unassigned_briefs: int, unassigned_planner: int, unassigned_operations: int, unassigned_finance: int, total_unassigned: int}
     * @throws Exception
     */
    public function getUnassignedWorkCounts(array $filters = []): array
    {
        try {
            $user = Auth::user();
            if (!$user) {
                throw new Exception('User not authenticated');
            }

            $filters = UserAccessScope::resolveOrganisationFilter($user, $filters);
            $counts = $this->dashboardRepository->getUnassignedCounts($filters, $user);

            // Apply role/permission restrictions: if user cannot view a section, mask to 0
            if (!DashboardPermissionSupport::can($user, DashboardPermissionSupport::SALES) && !DashboardPermissionSupport::canViewOverview($user)) {
                $counts['unassigned_sales'] = 0;
                $counts['unassigned_briefs'] = 0;
            }

            if (!DashboardPermissionSupport::can($user, DashboardPermissionSupport::PLANNER) && !DashboardPermissionSupport::canViewOverview($user)) {
                $counts['unassigned_planner'] = 0;
            }

            if (!DashboardPermissionSupport::can($user, DashboardPermissionSupport::OPERATIONS) && !DashboardPermissionSupport::canViewOverview($user)) {
                $counts['unassigned_operations'] = 0;
            }

            if (!DashboardPermissionSupport::can($user, DashboardPermissionSupport::FINANCE) && !DashboardPermissionSupport::canViewOverview($user)) {
                $counts['unassigned_finance'] = 0;
            }

            $counts['total_unassigned'] = array_sum([
                $counts['unassigned_sales'],
                $counts['unassigned_briefs'],
                $counts['unassigned_planner'],
                $counts['unassigned_operations'],
                $counts['unassigned_finance'],
            ]);

            return $counts;
        } catch (Exception $e) {
            Log::error('Error fetching unassigned work counts', [
                'exception' => $e,
                'filters' => $filters,
            ]);
            throw new Exception('Unable to fetch unassigned work counts: ' . $e->getMessage());
        }
    }
}
