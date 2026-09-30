<?php

namespace App\Providers;

/**
 * Repository Service Provider
 * -----------------------------------------
 * Registers repository bindings in the service container,
 * mapping interfaces to their concrete implementations.
 * Supports dependency injection and repository pattern
 * for clean and maintainable architecture.
 *
 * @package App\Providers
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-05-05
 */

use Illuminate\Support\ServiceProvider;

use App\Contracts\Repositories\IndustryRepositoryInterface;
use App\Repositories\IndustryRepository;

use App\Repositories\DesignationRepository;
use App\Contracts\Repositories\DesignationRepositoryInterface;

use App\Repositories\DepartmentRepository;
use App\Contracts\Repositories\DepartmentRepositoryInterface;

use App\Repositories\LeadSourceRepository;
use App\Contracts\Repositories\LeadSourceRepositoryInterface;

use App\Repositories\LeadSubSourceRepository;
use App\Contracts\Repositories\LeadSubSourceRepositoryInterface;

use App\Contracts\Repositories\AgencyGroupRepositoryInterface;
use App\Repositories\AgencyGroupRepository;

use App\Contracts\Repositories\AgencyTypeRepositoryInterface;
use App\Repositories\AgencyTypeRepository;

use App\Contracts\Repositories\AgencyRepositoryInterface;


use App\Repositories\BrandTypeRepository;
use App\Contracts\Repositories\BrandTypeRepositoryInterface;

use App\Repositories\BrandRepository;
use App\Contracts\Repositories\BrandRepositoryInterface;
use App\Contracts\Repositories\BrandImportRepositoryInterface;
use App\Repositories\BrandImportRepository;

use App\Repositories\RegionRepository;
use App\Contracts\Repositories\RegionRepositoryInterface;

use App\Repositories\EloquentCountryRepository;
use App\Contracts\Repositories\CountryRepositoryInterface;

use App\Repositories\EloquentStateRepository;
use App\Contracts\Repositories\StateRepositoryInterface;

use App\Repositories\EloquentCityRepository;
use App\Contracts\Repositories\CityRepositoryInterface;

use App\Repositories\ZoneRepository;
use App\Contracts\Repositories\ZoneRepositoryInterface;

// use App\Repositories\RefreshTokenRepository;
// use App\Contracts\Repositories\RefreshTokenRepositoryInterface;

use App\Repositories\RoleRepository;
use App\Contracts\Repositories\RoleRepositoryInterface;

use App\Repositories\PermissionRepository;
use App\Contracts\Repositories\PermissionRepositoryInterface;
// Duplicate imports removed

use App\Contracts\Repositories\MissCampaignRepositoryInterface;
use App\Repositories\AgencyRepository;
use App\Repositories\MissCampaignRepository;

use App\Contracts\Repositories\CallStatusRepositoryInterface;
use App\Repositories\CallStatusRepository;

use App\Contracts\Repositories\StatusRepositoryInterface;
use App\Repositories\StatusRepository;

use App\Contracts\Repositories\PriorityRepositoryInterface;
use App\Repositories\PriorityRepository;

use App\Contracts\Repositories\LeadRepositoryInterface;
use App\Repositories\LeadRepository;

use App\Contracts\Repositories\BriefStatusRepositoryInterface;
use App\Repositories\BriefStatusRepository;

use App\Contracts\Repositories\BriefRepositoryInterface;
use App\Repositories\BriefRepository;

use App\Contracts\Repositories\BriefAssignHistoryRepositoryInterface;
use App\Repositories\BriefAssignHistoryRepository;

use App\Contracts\Repositories\MeetingRepositoryInterface;
use App\Repositories\EloquentMeetingRepository;

use App\Contracts\Repositories\ActivityLogRepositoryInterface;
use App\Repositories\ActivityLogRepository;

use App\Contracts\Repositories\PlannerHistoryRepositoryInterface;
use App\Repositories\PlannerHistoryRepository;

use App\Contracts\Repositories\PlannerRepositoryInterface;
use App\Repositories\PlannerRepository;
use App\Contracts\Repositories\PlannerStatusRepositoryInterface;
use App\Repositories\PlannerStatusRepository;

use App\Contracts\Repositories\UserParentRepositoryInterface;
use App\Repositories\UserParentRepository;

use App\Contracts\Repositories\NotificationRepositoryInterface;
use App\Repositories\NotificationRepository;

use App\Contracts\Repositories\MediaTypeRepositoryInterface;
use App\Repositories\MediaTypeRepository;

use App\Contracts\Repositories\OrganisationRepositoryInterface;
use App\Repositories\OrganisationRepository;
use App\Contracts\Repositories\LeadTypeRepositoryInterface;
use App\Repositories\LeadTypeRepository;

use App\Contracts\Repositories\DashboardRepositoryInterface;
use App\Repositories\DashboardRepository;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(
            DashboardRepositoryInterface::class,
            DashboardRepository::class
        );

        $this->app->bind(
            IndustryRepositoryInterface::class,
            IndustryRepository::class
        );

        $this->app->bind(
            DesignationRepositoryInterface::class,
            DesignationRepository::class
        );

        $this->app->bind(
            DepartmentRepositoryInterface::class,
            DepartmentRepository::class
        );

        $this->app->bind(
            LeadSourceRepositoryInterface::class,
            LeadSourceRepository::class
        );

        $this->app->bind(
            LeadSubSourceRepositoryInterface::class,
            LeadSubSourceRepository::class
        );

        $this->app->bind(
            AgencyRepositoryInterface::class,
            AgencyRepository::class
        );

        $this->app->bind(
            AgencyTypeRepositoryInterface::class, 
            AgencyTypeRepository::class
        );

        $this->app->bind(
            AgencyGroupRepositoryInterface::class,
            AgencyGroupRepository::class
        );

        $this->app->bind(
            CityRepositoryInterface::class,
            EloquentCityRepository::class
        );
        
        $this->app->bind(
            BrandTypeRepositoryInterface::class,
            BrandTypeRepository::class
        );

        $this->app->bind(
            BrandRepositoryInterface::class,
            BrandRepository::class
        );

        $this->app->bind(
            BrandImportRepositoryInterface::class,
            BrandImportRepository::class
        );

        $this->app->bind(
            RegionRepositoryInterface::class,
            RegionRepository::class
        );
        
        $this->app->bind(
            CountryRepositoryInterface::class, 
            EloquentCountryRepository::class
        );

        $this->app->bind(
            StateRepositoryInterface::class,
            EloquentStateRepository::class
        );

        $this->app->bind(
            ZoneRepositoryInterface::class,
            ZoneRepository::class
        );
        
        // $this->app->bind(
        //     RefreshTokenRepositoryInterface::class,
        //     RefreshTokenRepository::class
        // );

        $this->app->bind(
            RoleRepositoryInterface::class,
            RoleRepository::class
        );

        $this->app->bind(
            PermissionRepositoryInterface::class,
            PermissionRepository::class
        );

        // MissCampaign repository binding
        $this->app->bind(
            MissCampaignRepositoryInterface::class,
            MissCampaignRepository::class
        );

        $this->app->bind(
            CallStatusRepositoryInterface::class,
            CallStatusRepository::class
        );

        $this->app->bind(
            StatusRepositoryInterface::class,
            StatusRepository::class
        );

        $this->app->bind(
            PriorityRepositoryInterface::class,
            PriorityRepository::class
        );

        $this->app->bind(
            LeadRepositoryInterface::class,
            LeadRepository::class
        );

        $this->app->bind(
            BriefStatusRepositoryInterface::class,
            BriefStatusRepository::class
        );

        $this->app->bind(
            BriefRepositoryInterface::class,
            BriefRepository::class
        );

        $this->app->bind(
            BriefAssignHistoryRepositoryInterface::class,
            BriefAssignHistoryRepository::class
        );

        $this->app->bind(
            MeetingRepositoryInterface::class,
            EloquentMeetingRepository::class
        );

        $this->app->bind(
            ActivityLogRepositoryInterface::class,
            ActivityLogRepository::class
        );

        $this->app->bind(
            PlannerHistoryRepositoryInterface::class,
            PlannerHistoryRepository::class
        );

        $this->app->bind(
            PlannerRepositoryInterface::class,
            PlannerRepository::class
        );

        $this->app->bind(
            PlannerStatusRepositoryInterface::class,
            PlannerStatusRepository::class
        );

        $this->app->bind(
            UserParentRepositoryInterface::class,
            UserParentRepository::class
        );

        $this->app->bind(
            NotificationRepositoryInterface::class,
            NotificationRepository::class
        );

        $this->app->bind(
            MediaTypeRepositoryInterface::class,
            MediaTypeRepository::class
        );

        $this->app->bind(
            OrganisationRepositoryInterface::class,
            OrganisationRepository::class
        );

        $this->app->bind(
            LeadTypeRepositoryInterface::class,
            LeadTypeRepository::class
        );
    }
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        
    }
}