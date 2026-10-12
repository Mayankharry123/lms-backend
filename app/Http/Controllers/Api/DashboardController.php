<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use App\Services\ResponseService;
use App\Support\DashboardFilters;
use App\Support\DashboardPermissionSupport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

class DashboardController extends Controller
{
    /**
     * The dashboard service instance
     *
     * @var DashboardService
     */
    protected $dashboardService;

    /**
     * The response service instance
     *
     * @var ResponseService
     */
    protected $responseService;

    /**
     * Constructor
     *
     * @param DashboardService $dashboardService
     * @param ResponseService $responseService
     */
    public function __construct(DashboardService $dashboardService, ResponseService $responseService)
    {
        $this->dashboardService = $dashboardService;
        $this->responseService = $responseService;
    }

    /**
     * Get dashboard data
     *
     * @return JsonResponse
     */
    public function getDashboard(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user || !DashboardPermissionSupport::canViewOverview($user)) {
                return $this->responseService->forbidden('Insufficient permissions to view dashboard overview.');
            }

            $filters = DashboardFilters::fromRequest($request);
            $dashboardData = $this->dashboardService->getDashboardData($filters);

            return $this->responseService->success(
                $dashboardData,
                'Dashboard data retrieved successfully'
            );
        } catch (Exception $e) {
            return $this->responseService->serverError(
                'Failed to retrieve dashboard data: ' . $e->getMessage()
            );
        }
    }

    /**
     * Get organisation-based dashboard chart metrics.
     */
    public function getCharts(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user || !DashboardPermissionSupport::canViewOverview($user)) {
                return $this->responseService->forbidden('Insufficient permissions to view dashboard charts.');
            }

            $filters = DashboardFilters::fromRequest($request);
            $chartData = $this->dashboardService->getChartMetrics($filters);
            $chartData = DashboardPermissionSupport::filterOverviewChartPayload($user, $chartData);

            return $this->responseService->success(
                $chartData,
                'Dashboard chart data retrieved successfully'
            );
        } catch (Exception $e) {
            return $this->responseService->serverError(
                'Failed to retrieve dashboard chart data: ' . $e->getMessage()
            );
        }
    }

    public function getSalesCharts(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user || !DashboardPermissionSupport::canViewSales($user)) {
                return $this->responseService->forbidden('Insufficient permissions to view sales dashboard charts.');
            }

            $filters = DashboardFilters::fromRequest($request);
            $chartData = $this->dashboardService->getSalesChartMetrics($filters);
            $chartData = DashboardPermissionSupport::filterSalesChartPayload($user, $chartData);

            return $this->responseService->success(
                $chartData,
                'Sales dashboard chart data retrieved successfully'
            );
        } catch (Exception $e) {
            return $this->responseService->serverError(
                'Failed to retrieve sales dashboard chart data: ' . $e->getMessage()
            );
        }
    }

    public function getPlannerCharts(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user || !DashboardPermissionSupport::canViewPlanner($user)) {
                return $this->responseService->forbidden('Insufficient permissions to view planner dashboard charts.');
            }

            $filters = DashboardFilters::fromRequest($request);
            $chartData = $this->dashboardService->getPlannerChartMetrics($filters);
            $chartData = DashboardPermissionSupport::filterPlannerChartPayload($user, $chartData);

            return $this->responseService->success(
                $chartData,
                'Planner dashboard chart data retrieved successfully'
            );
        } catch (Exception $e) {
            return $this->responseService->serverError(
                'Failed to retrieve planner dashboard chart data: ' . $e->getMessage()
            );
        }
    }

    public function getOperationsCharts(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user || !DashboardPermissionSupport::canViewOperations($user)) {
                return $this->responseService->forbidden('Insufficient permissions to view operations dashboard charts.');
            }

            $filters = DashboardFilters::fromRequest($request);
            $chartData = $this->dashboardService->getOperationsChartMetrics($filters);

            return $this->responseService->success(
                $chartData,
                'Operations dashboard chart data retrieved successfully'
            );
        } catch (Exception $e) {
            return $this->responseService->serverError(
                'Failed to retrieve operations dashboard chart data: ' . $e->getMessage()
            );
        }
    }

    public function getFinanceCharts(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user || !DashboardPermissionSupport::canViewFinance($user)) {
                return $this->responseService->forbidden('Insufficient permissions to view finance dashboard charts.');
            }

            $filters = DashboardFilters::fromRequest($request);
            $chartData = $this->dashboardService->getFinanceChartMetrics($filters);

            return $this->responseService->success(
                $chartData,
                'Finance dashboard chart data retrieved successfully'
            );
        } catch (Exception $e) {
            return $this->responseService->serverError(
                'Failed to retrieve finance dashboard chart data: ' . $e->getMessage()
            );
        }
    }

    public function getFinanceSummary(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user || !DashboardPermissionSupport::canViewFinance($user)) {
                return $this->responseService->forbidden('Insufficient permissions to view finance summary.');
            }

            $filters = DashboardFilters::fromRequest($request);
            $summaryData = $this->dashboardService->getFinanceSummary($filters);

            return $this->responseService->success(
                $summaryData,
                'Finance summary retrieved successfully'
            );
        } catch (Exception $e) {
            return $this->responseService->serverError(
                'Failed to retrieve finance summary: ' . $e->getMessage()
            );
        }
    }

    /**
     * Get unassigned work counts across all modules.
     *
     * GET /dashboard/unassigned-counts
     */
    public function getUnassignedCounts(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return $this->responseService->unauthorized('User not authenticated');
            }

            if (
                !DashboardPermissionSupport::canViewOverview($user) &&
                !DashboardPermissionSupport::canViewSales($user) &&
                !DashboardPermissionSupport::canViewPlanner($user) &&
                !DashboardPermissionSupport::canViewOperations($user) &&
                !DashboardPermissionSupport::canViewFinance($user)
            ) {
                return $this->responseService->forbidden('Insufficient permissions to view unassigned work counts.');
            }

            $filters = DashboardFilters::fromRequest($request);
            $counts = $this->dashboardService->getUnassignedWorkCounts($filters);

            return $this->responseService->success(
                $counts,
                'Unassigned work counts retrieved successfully'
            );
        } catch (Exception $e) {
            return $this->responseService->serverError(
                'Failed to retrieve unassigned work counts: ' . $e->getMessage()
            );
        }
    }
}
