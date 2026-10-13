<?php

/**
 * SalesDashboardService
 * 
 * @author Achal Sharma
 * @package App\Services
 * @version 1.0.1
 */

namespace App\Services;

use App\Models\Lead;
use App\Models\Brief;
use Illuminate\Support\Facades\Auth;

class SalesDashboardService
{
    /**
     * Get dashboard metrics
     *
     * @return array
     */
    public function getDashboardMetrics(): array
    {
        $user = Auth::user();

        return [
            'total_leads' => $this->getTotalLeads($user),
            'total_briefs' => $this->getTotalBriefs($user),
            'business_forecast' => $this->getBusinessForecast($user),
            //'business_weightage' => $this->getBusinessWeightage($user),
        ];
    }

    /**
     * Get total leads count for the user
     *
     * @param mixed $user
     * @return int
     */
    private function getTotalLeads($user): int
    {
        return Lead::accessibleToUser($user)->count();
    }

    /**
     * Get total briefs count for the user
     *
     * @param mixed $user
     * @return int
     */
    private function getTotalBriefs($user): int
    {
        return Brief::accessibleToUser($user)->count();
    }

    /**
     * Get business forecast - total brief budget
     *
     * @param mixed $user
     * @return int|float
     */
    private function getBusinessForecast($user): int|float
    {
        // Return sum of total brief budget
        return Brief::accessibleToUser($user)->sum('budget') ?? 0;
    }

    /**
     * Get business weightage (distribution across brands and agencies)
     *
     * @param mixed $user
     * @return array
     */
    private function getBusinessWeightage($user): array
    {
        $totalLeads = Lead::accessibleToUser($user)->count();
        $totalBriefs = Brief::accessibleToUser($user)->count();

        // Get leads by brand
        $leadsByBrand = Lead::accessibleToUser($user)
            ->with('brand')
            ->get()
            ->groupBy('brand_id')
            ->map(function ($group) use ($totalLeads) {
                $brandName = $group->first()?->brand?->name ?? 'Unassigned';
                $count = count($group);
                $percentage = $totalLeads > 0 ? round(($count / $totalLeads) * 100, 2) : 0;
                return [
                    'count' => $count,
                    'percentage' => $percentage,
                ];
            });

        // Get leads by agency
        $leadsByAgency = Lead::accessibleToUser($user)
            ->with('agency')
            ->get()
            ->groupBy('agency_id')
            ->map(function ($group) use ($totalLeads) {
                $agencyName = $group->first()?->agency?->name ?? 'Unassigned';
                $count = count($group);
                $percentage = $totalLeads > 0 ? round(($count / $totalLeads) * 100, 2) : 0;
                return [
                    'count' => $count,
                    'percentage' => $percentage,
                ];
            });

        return [
            'by_brand' => $leadsByBrand,
            'by_agency' => $leadsByAgency,
            'total_leads' => $totalLeads,
            'total_briefs' => $totalBriefs,
        ];
    }
}
