<?php

namespace App\Repositories;

use App\Contracts\Repositories\DashboardRepositoryInterface;
use App\Models\Dashboard;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class DashboardRepository implements DashboardRepositoryInterface
{
    protected Dashboard $model;

    public function __construct(Dashboard $model)
    {
        $this->model = $model;
    }

    public function getTotalUserCount(array $filters, ?User $user): int
    {
        return $this->model->fetchTotalUserCount($filters, $user);
    }

    public function getAccessibleOrganisations(array $filters, ?User $user): Collection
    {
        return $this->model->fetchAccessibleOrganisations($filters, $user);
    }

    public function getOrganisationChartRow(array $filters, ?User $user, int $organisationId, string $organisationName): array
    {
        return $this->model->fetchOrganisationChartRow($filters, $user, $organisationId, $organisationName);
    }

    public function getSalesPipelineCounts(array $filters, ?User $user): array
    {
        return $this->model->fetchSalesPipelineCounts($filters, $user);
    }

    public function getPlannerBriefStatusCounts(array $filters, ?User $user): array
    {
        return $this->model->fetchPlannerBriefStatusCounts($filters, $user);
    }

    public function getPlannerOrganisationRow(array $filters, ?User $user, int $organisationId, string $organisationName): array
    {
        return $this->model->fetchPlannerOrganisationRow($filters, $user, $organisationId, $organisationName);
    }

    public function getBriefIdsByOrganisation(array $filters, ?User $user): array
    {
        return $this->model->fetchBriefIdsByOrganisation($filters, $user);
    }

    public function getOperationsOrganisationRow(array $filters, ?User $user, int $organisationId, string $organisationName): array
    {
        return $this->model->fetchOperationsOrganisationRow($filters, $user, $organisationId, $organisationName);
    }

    public function getOperationsTotals(array $filters, ?User $user): array
    {
        return $this->model->fetchOperationsTotals($filters, $user);
    }

    public function getOperationsStatusCounts(array $filters, ?User $user): array
    {
        return $this->model->fetchOperationsStatusCounts($filters, $user);
    }

    public function getRecentOperations(array $filters, ?User $user): array
    {
        return $this->model->fetchRecentOperations($filters, $user);
    }

    public function getFinanceOrganisationRow(array $filters, ?User $user, int $organisationId, string $organisationName): array
    {
        return $this->model->fetchFinanceOrganisationRow($filters, $user, $organisationId, $organisationName);
    }

    public function getFinanceTotals(array $filters, ?User $user): array
    {
        return $this->model->fetchFinanceTotals($filters, $user);
    }

    public function getFinanceStatusCounts(array $filters, ?User $user): array
    {
        return $this->model->fetchFinanceStatusCounts($filters, $user);
    }

    public function getRecentFinanceRecords(array $filters, ?User $user): array
    {
        return $this->model->fetchRecentFinanceRecords($filters, $user);
    }

    public function getFinanceSummary(array $filters, ?User $user): array
    {
        return $this->model->fetchFinanceSummary($filters, $user);
    }
}
