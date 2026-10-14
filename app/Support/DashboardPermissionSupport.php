<?php

namespace App\Support;

use App\Models\User;

class DashboardPermissionSupport
{
    public const OVERVIEW = 'dashboard.overview';
    public const SALES = 'dashboard.sales';
    public const PLANNER = 'dashboard.planner';
    public const OPERATIONS = 'dashboard.operations';
    public const FINANCE = 'dashboard.finance';

    public const OVERVIEW_STATS = 'dashboard.overview.stats';
    public const OVERVIEW_ASSIGNMENTS = 'dashboard.overview.assignments';
    public const OVERVIEW_MEETINGS = 'dashboard.overview.meetings';

    public const CHART_LEADS = 'dashboard.charts.leads';
    public const CHART_PRE_LEADS = 'dashboard.charts.pre-leads';
    public const CHART_BRIEFS = 'dashboard.charts.briefs';
    public const CHART_BRIEF_BUDGET = 'dashboard.charts.brief-budget';
    public const CHART_PIPELINE = 'dashboard.charts.pipeline';
    public const CHART_BRIEF_STATUS = 'dashboard.charts.brief-status';

    public const LEGACY_READ = 'dashboard.read';

    /** @var array<string, string> */
    private const METRIC_PERMISSIONS = [
        'total_leads' => self::CHART_LEADS,
        'pre_leads' => self::CHART_PRE_LEADS,
        'briefs' => self::CHART_BRIEFS,
        'brief_budget' => self::CHART_BRIEF_BUDGET,
    ];

    public static function can(User $user, string $permission): bool
    {
        if ($user->hasRole('admin') || $user->hasRole('Super Admin')) {
            return true;
        }

        if ($user->hasPermission(self::LEGACY_READ)) {
            return true;
        }

        return $user->hasPermission($permission);
    }

    public static function canViewOverview(User $user): bool
    {
        return self::can($user, self::OVERVIEW);
    }

    public static function canViewSales(User $user): bool
    {
        return self::can($user, self::SALES);
    }

    public static function canViewPlanner(User $user): bool
    {
        return self::can($user, self::PLANNER);
    }

    public static function canViewOperations(User $user): bool
    {
        return self::can($user, self::OPERATIONS);
    }

    public static function canViewFinance(User $user): bool
    {
        return self::can($user, self::FINANCE);
    }

    public static function canViewChart(User $user, string $permission): bool
    {
        return self::can($user, $permission);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public static function filterOverviewChartPayload(User $user, array $payload): array
    {
        $rows = array_map(
            fn (array $row) => self::filterMetricRow($user, $row),
            $payload['by_organisation'] ?? []
        );

        $totals = self::filterMetricRow($user, $payload['totals'] ?? []);

        return [
            'by_organisation' => $rows,
            'totals' => $totals,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public static function filterSalesChartPayload(User $user, array $payload): array
    {
        $result = self::filterOverviewChartPayload($user, $payload);

        if (self::canViewChart($user, self::CHART_PIPELINE)) {
            $result['pipeline'] = $payload['pipeline'] ?? [];
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public static function filterPlannerChartPayload(User $user, array $payload): array
    {
        $result = self::filterOverviewChartPayload($user, $payload);

        if (self::canViewChart($user, self::CHART_BRIEF_STATUS)) {
            $result['brief_status'] = $payload['brief_status'] ?? [];
        }

        $result['plan_submission'] = $payload['plan_submission'] ?? [
            'avg_submission_days' => 0,
            'avg_submission_hours' => 0,
            'submitted_plans' => 0,
        ];

        return $result;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private static function filterMetricRow(User $user, array $row): array
    {
        foreach (self::METRIC_PERMISSIONS as $metricKey => $permission) {
            if (!self::canViewChart($user, $permission)) {
                $row[$metricKey] = 0;
            }
        }

        return $row;
    }
}
