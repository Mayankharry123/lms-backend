<?php

/**
 * DashboardRepository
 *
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-08
 */

namespace App\Repositories;

use App\Contracts\Repositories\DashboardRepositoryInterface;
use App\Models\Dashboard;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class DashboardRepository implements DashboardRepositoryInterface
{
    /**
     * @var Dashboard
     */
    protected Dashboard $model;

    /**
     * DashboardRepository constructor.
     *
     * @param Dashboard $model
     */
    public function __construct(Dashboard $model)
    {
        $this->model = $model;
    }

    /**
     * Get the total count of visible users based on filters and access scope.
     *
     * @param array $filters The filters to apply.
     * @param User|null $user The authenticated user.
     * @return int
     */
    public function getTotalUserCount(array $filters, ?User $user): int
    {
        return $this->model->fetchTotalUserCount($filters, $user);
    }

    /**
     * Get a collection of organisations accessible to the given user, optionally filtered.
     *
     * @param array $filters The filters to apply.
     * @param User|null $user The authenticated user.
     * @return Collection
     */
    public function getAccessibleOrganisations(array $filters, ?User $user): Collection
    {
        return $this->model->fetchAccessibleOrganisations($filters, $user);
    }

    /**
     * Build an aggregated chart row for a specific organisation containing Leads, Pre-leads, and Briefs metrics.
     *
     * @param array $filters The filters to apply.
     * @param User|null $user The authenticated user.
     * @param int $organisationId The ID of the organisation.
     * @param string $organisationName The name of the organisation.
     * @return array
     */
    public function getOrganisationChartRow(array $filters, ?User $user, int $organisationId, string $organisationName): array
    {
        return $this->model->fetchOrganisationChartRow($filters, $user, $organisationId, $organisationName);
    }

    /**
     * Get the counts for the sales pipeline (e.g., New Leads, Follow-ups, Meetings, Briefs).
     *
     * @param array $filters The filters to apply.
     * @param User|null $user The authenticated user.
     * @return array
     */
    public function getSalesPipelineCounts(array $filters, ?User $user): array
    {
        return $this->model->fetchSalesPipelineCounts($filters, $user);
    }

    /**
     * Get the breakdown of planner brief statuses (Active, Closed, Overdue).
     *
     * @param array $filters The filters to apply.
     * @param User|null $user The authenticated user.
     * @return array
     */
    public function getPlannerBriefStatusCounts(array $filters, ?User $user): array
    {
        return $this->model->fetchPlannerBriefStatusCounts($filters, $user);
    }

    /**
     * Build a planner metric row for a specific organisation (Assigned plans, Avg days).
     *
     * @param array $filters The filters to apply.
     * @param User|null $user The authenticated user.
     * @param int $organisationId The ID of the organisation.
     * @param string $organisationName The name of the organisation.
     * @return array
     */
    public function getPlannerOrganisationRow(array $filters, ?User $user, int $organisationId, string $organisationName): array
    {
        return $this->model->fetchPlannerOrganisationRow($filters, $user, $organisationId, $organisationName);
    }

    /**
     * Brief ids for the planner chart, grouped by the contact person's organisation.
     *
     * @param array $filters The filters to apply.
     * @param User|null $user The authenticated user.
     * @return array<int, array<int, int>>
     */
    public function getBriefIdsByOrganisation(array $filters, ?User $user): array
    {
        return $this->model->fetchBriefIdsByOrganisation($filters, $user);
    }

    /**
     * Operations metrics for one organisation.
     *
     * @param array $filters
     * @param User|null $user
     * @param int $organisationId
     * @param string $organisationName
     * @return array<string, mixed>
     */
    public function getOperationsOrganisationRow(array $filters, ?User $user, int $organisationId, string $organisationName): array
    {
        return $this->model->fetchOperationsOrganisationRow($filters, $user, $organisationId, $organisationName);
    }

    /**
     * Total operations metrics directly from the operations table.
     *
     * @param array $filters
     * @param User|null $user
     * @return array<string, int>
     */
    public function getOperationsTotals(array $filters, ?User $user): array
    {
        return $this->model->fetchOperationsTotals($filters, $user);
    }

    /**
     * Get operations status counts.
     *
     * @param array $filters
     * @param User|null $user
     * @return array<string, int>
     */
    public function getOperationsStatusCounts(array $filters, ?User $user): array
    {
        return $this->model->fetchOperationsStatusCounts($filters, $user);
    }

    /**
     * Get recent operations records.
     *
     * @param array $filters
     * @param User|null $user
     * @return list<array<string, mixed>>
     */
    public function getRecentOperations(array $filters, ?User $user): array
    {
        return $this->model->fetchRecentOperations($filters, $user);
    }

    /**
     * Finance metrics for one organisation.
     *
     * @param array $filters
     * @param User|null $user
     * @param int $organisationId
     * @param string $organisationName
     * @return array<string, mixed>
     */
    public function getFinanceOrganisationRow(array $filters, ?User $user, int $organisationId, string $organisationName): array
    {
        return $this->model->fetchFinanceOrganisationRow($filters, $user, $organisationId, $organisationName);
    }

    /**
     * Total finance metrics directly from the finance_records table.
     *
     * @param array $filters
     * @param User|null $user
     * @return array<string, mixed>
     */
    public function getFinanceTotals(array $filters, ?User $user): array
    {
        return $this->model->fetchFinanceTotals($filters, $user);
    }

    /**
     * Get finance status counts.
     *
     * @param array $filters
     * @param User|null $user
     * @return array<string, int>
     */
    public function getFinanceStatusCounts(array $filters, ?User $user): array
    {
        return $this->model->fetchFinanceStatusCounts($filters, $user);
    }

    /**
     * Get recent finance records.
     *
     * @param array $filters
     * @param User|null $user
     * @return list<array<string, mixed>>
     */
    public function getRecentFinanceRecords(array $filters, ?User $user): array
    {
        return $this->model->fetchRecentFinanceRecords($filters, $user);
    }

    /**
     * Financial summary metrics: sum of vouchers total amount and sum of proforma invoices total amount.
     *
     * @param array $filters
     * @param User|null $user
     * @return array{voucher_total_amount: float, proforma_invoice_total_amount: float}
     */
    public function getFinanceSummary(array $filters, ?User $user): array
    {
        return $this->model->fetchFinanceSummary($filters, $user);
    }

    /**
     * Get unassigned work counts across all five modules.
     *
     * @param array $filters
     * @param User|null $user
     * @return array{unassigned_sales: int, unassigned_briefs: int, unassigned_planner: int, unassigned_operations: int, unassigned_finance: int}
     */
    public function getUnassignedCounts(array $filters, ?User $user): array
    {
        return $this->model->fetchUnassignedCounts($filters, $user);
    }
}
