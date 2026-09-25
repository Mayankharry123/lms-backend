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
}
