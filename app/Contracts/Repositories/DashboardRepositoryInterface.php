<?php

namespace App\Contracts\Repositories;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * Interface DashboardRepositoryInterface
 *
 * Defines the contract for retrieving aggregated dashboard metrics and data.
 *
 * @package App\Contracts\Repositories
 */
interface DashboardRepositoryInterface
{
    /**
     * Get the total count of visible users based on filters and access scope.
     *
     * @param array $filters The filters to apply.
     * @param User|null $user The authenticated user.
     * @return int
     */
    public function getTotalUserCount(array $filters, ?User $user): int;

    /**
     * Get a collection of organisations accessible to the given user, optionally filtered.
     *
     * @param array $filters The filters to apply.
     * @param User|null $user The authenticated user.
     * @return Collection
     */
    public function getAccessibleOrganisations(array $filters, ?User $user): Collection;

    /**
     * Build an aggregated chart row for a specific organisation containing Leads, Pre-leads, and Briefs metrics.
     *
     * @param array $filters The filters to apply.
     * @param User|null $user The authenticated user.
     * @param int $organisationId The ID of the organisation.
     * @param string $organisationName The name of the organisation.
     * @return array
     */
    public function getOrganisationChartRow(array $filters, ?User $user, int $organisationId, string $organisationName): array;

    /**
     * Get the counts for the sales pipeline (e.g., New Leads, Follow-ups, Meetings, Briefs).
     *
     * @param array $filters The filters to apply.
     * @param User|null $user The authenticated user.
     * @return array
     */
    public function getSalesPipelineCounts(array $filters, ?User $user): array;

    /**
     * Get the breakdown of planner brief statuses (Active, Closed, Overdue).
     *
     * @param array $filters The filters to apply.
     * @param User|null $user The authenticated user.
     * @return array
     */
    public function getPlannerBriefStatusCounts(array $filters, ?User $user): array;

    /**
     * Build a planner metric row for a specific organisation (Assigned plans, Avg days).
     *
     * @param array $filters The filters to apply.
     * @param User|null $user The authenticated user.
     * @param int $organisationId The ID of the organisation.
     * @param string $organisationName The name of the organisation.
     * @return array
     */
    public function getPlannerOrganisationRow(array $filters, ?User $user, int $organisationId, string $organisationName): array;

    /**
     * Brief ids for the planner chart, grouped by the contact person's organisation.
     * Uses the same brief filters as the organisation chart rows.
     *
     * @param array $filters The filters to apply.
     * @param User|null $user The authenticated user.
     * @return array<int, array<int, int>>
     */
    public function getBriefIdsByOrganisation(array $filters, ?User $user): array;

    /**
     * Operations metrics for one organisation.
     *
     * @param array $filters
     * @return array<string, mixed>
     */
    public function getOperationsOrganisationRow(array $filters, ?User $user, int $organisationId, string $organisationName): array;

    /**
     * Total operations metrics directly from the operations table.
     *
     * @param array $filters
     * @param User|null $user
     * @return array<string, int>
     */
    public function getOperationsTotals(array $filters, ?User $user): array;

    /**
     * @param array $filters
     * @return array<string, int>
     */
    public function getOperationsStatusCounts(array $filters, ?User $user): array;

    /**
     * @param array $filters
     * @return list<array<string, mixed>>
     */
    public function getRecentOperations(array $filters, ?User $user): array;

    /**
     * Finance metrics for one organisation.
     *
     * @param array $filters
     * @return array<string, mixed>
     */
    public function getFinanceOrganisationRow(array $filters, ?User $user, int $organisationId, string $organisationName): array;

    /**
     * Total finance metrics directly from the finance_records table.
     *
     * @param array $filters
     * @param User|null $user
     * @return array<string, mixed>
     */
    public function getFinanceTotals(array $filters, ?User $user): array;

    /**
     * Financial summary metrics: sum of vouchers total amount and sum of proforma invoices total amount.
     *
     * @param array $filters
     * @param User|null $user
     * @return array{voucher_total_amount: float, proforma_invoice_total_amount: float}
     */
    public function getFinanceSummary(array $filters, ?User $user): array;

    /**
     * @param array $filters
     * @return array<string, int>
     */
    public function getFinanceStatusCounts(array $filters, ?User $user): array;

    /**
     * @param array $filters
     * @return list<array<string, mixed>>
     */
    public function getRecentFinanceRecords(array $filters, ?User $user): array;

    /**
     * Get unassigned work counts across all five modules (Sales, Briefs, Planner, Operations, Finance).
     *
     * @param array $filters
     * @param User|null $user
     * @return array{unassigned_sales: int, unassigned_briefs: int, unassigned_planner: int, unassigned_operations: int, unassigned_finance: int}
     */
    public function getUnassignedCounts(array $filters, ?User $user): array;
}
