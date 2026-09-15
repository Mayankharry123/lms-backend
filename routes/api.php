<?php

/**
 * API Routes Definition
 * -----------------------------------------
 * Defines all API routes for the application, including authentication, resources, and business logic endpoints.
 *
 * @package Routes
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-05-06
 */

/** @var \Laravel\Lumen\Routing\Router $router */

use Carbon\Carbon;

// Handle CORS preflight requests
$router->options('{any:.*}', function () {
    return response('', 200);
});

// -------------------------------------------------------
// Public routes (no authentication required)
// -------------------------------------------------------

$router->group(['prefix' => 'v1'], function () use ($router) {
    
    // Authentication routes
    $router->group(['prefix' => 'auth'], function () use ($router) {     
        $router->post('register', 'Api\AuthController@register');
        $router->post('login', 'Api\AuthController@login');
        $router->post('refresh', 'Api\AuthController@refresh');
        $router->post('forgot-password','Api\AuthController@forgotPassword');
        $router->post('reset-password', 'Api\AuthController@resetPassword');
    });
});

// -------------------------------------------------------
// Protected routes (JWT authentication required)
// -------------------------------------------------------

$router->group(['prefix' => 'v1', 'middleware' => 'jwt.auth'], function () use ($router) {

    // Auth routes
    $router->group(['prefix' => 'auth'], function () use ($router) {
        $router->post('logout', 'Api\AuthController@logout');
        $router->get('me', 'Api\AuthController@me');
    });

    // User routes (permission-protected)
    $router->group(['prefix' => 'users', 'middleware' => 'permission:users.read'], function () use ($router) {
        $router->get('/', 'Api\UserController@index');
        $router->get('list', 'Api\UserController@list');
        $router->get('search', 'Api\UserController@search');
        $router->get('statistics', 'Api\UserController@statistics');
        $router->get('{id}', 'Api\UserController@show');
    });

    $router->group(['prefix' => 'users', 'middleware' => 'permission:users.create'], function () use ($router) {
        $router->post('/', 'Api\UserController@store');
    });

    $router->group(['prefix' => 'users', 'middleware' => 'permission:users.update'], function () use ($router) {
        $router->put('{id}', 'Api\UserController@update');
        $router->post('{id}', 'Api\UserController@update');
        $router->post('{id}/change-password', 'Api\UserController@changePassword');
    });

    $router->group(['prefix' => 'users', 'middleware' => 'permission:users.delete'], function () use ($router) {
        $router->delete('{id}', 'Api\UserController@destroy');
    });

    // Profile routes
    $router->group(['prefix' => 'profile', 'middleware' => 'permission:profile.read'], function () use ($router) {
        $router->get('/', 'Api\UserController@me');
        $router->get('login-history', 'Api\UserController@getLoginHistory');
        /**
         * Added API routes for user hierarchy and planning department users.
         */
        $router->get('child-users', 'Api\UserController@getChildUsers');
        $router->get('child-planing-users', 'Api\UserController@getChildPlaningUsers');
        $router->get('child-users-by-organisation', 'Api\UserController@getChildUsersByOrganisation');
        $router->get('child-users-by-lead/{leadId:[0-9]+}', 'Api\UserController@getChildUsersByLead');
        $router->get('child-users-for-brief-creation/{leadId:[0-9]+}', 'Api\UserController@getChildUsersForBriefCreation');
        $router->get('child-users-by-miss-campaign/{campaignId:[0-9]+}', 'Api\UserController@getChildUsersByMissCampaign');
        $router->get('child-users-by-brief/{briefId:[0-9]+}', 'Api\UserController@getChildUsersByBrief');
        $router->get('child-planners-by-brief/{briefId:[0-9]+}', 'Api\UserController@getChildPlannersByBrief');
        $router->get('child-planners-by-lead/{leadId:[0-9]+}', 'Api\UserController@getChildPlannersByLead');
    });

    $router->group(['prefix' => 'profile', 'middleware' => 'permission:profile.update'], function () use ($router) {
        $router->put('/', 'Api\UserController@updateProfile');
    });

    // Industry routes
    $router->group(['prefix' => 'industries'], function () use ($router) {
    
        $router->get('/', 'IndustryController@index');
        $router->get('list', 'IndustryController@list');
        $router->post('/', 'IndustryController@store');
        $router->get('{id}', 'IndustryController@show');
        $router->put('{id}', 'IndustryController@update');
        $router->delete('{id}', 'IndustryController@destroy');
    });

    $router->group(['prefix' => 'designations'], function () use ($router) {
        $router->get('/', 'DesignationController@index');
        $router->get('list', 'DesignationController@list');
        $router->post('/', 'DesignationController@store');
        $router->get('{id}', 'DesignationController@show');
        $router->put('{id}', 'DesignationController@update');
        $router->delete('{id}', 'DesignationController@destroy');
    });

    $router->group(['prefix' => 'departments'], function () use ($router) {
        $router->get('/', 'DepartmentController@index');
        $router->get('list', 'DepartmentController@list');
        $router->post('/', 'DepartmentController@store');
        $router->get('{id}', 'DepartmentController@show');
        $router->put('{id}', 'DepartmentController@update');
        $router->delete('{id}', 'DepartmentController@destroy');
    });

    $router->group(['prefix' => 'lead-sources'], function () use ($router) {
        $router->get('/', 'LeadSourceController@index');
        $router->get('list', 'LeadSourceController@list');
        $router->post('/', 'LeadSourceController@store');
        $router->get('{id}', 'LeadSourceController@show');
        $router->put('{id}', 'LeadSourceController@update');
        $router->delete('{id}', 'LeadSourceController@destroy');
    });

    $router->group(['prefix' => 'lead-sub-sources'], function () use ($router) {
        $router->get('/', 'LeadSubSourceController@index');
        $router->get('list', 'LeadSubSourceController@list');
        $router->get('by-source/{sourceId}', 'LeadSubSourceController@getBySourceId');
        $router->post('name', 'LeadSubSourceController@storeUnderDirect');
        $router->post('/', 'LeadSubSourceController@store');
        $router->get('{id}', 'LeadSubSourceController@show');
        $router->put('{id}', 'LeadSubSourceController@update');
        $router->delete('{id}', 'LeadSubSourceController@destroy');
    });

    $router->group(['prefix' => 'lead-types'], function () use ($router) {
        $router->get('/', 'LeadTypeController@index');
        $router->get('list', 'LeadTypeController@list');
        $router->post('/', 'LeadTypeController@store');
    });

    // Media Type routes
    $router->group(['prefix' => 'media-types'], function () use ($router) {
        $router->get('/', 'MediaTypeController@index');
        $router->get('list', 'MediaTypeController@list');
        $router->post('/', 'MediaTypeController@store');
        $router->get('{id:[0-9]+}', 'MediaTypeController@show');
        $router->put('{id:[0-9]+}', 'MediaTypeController@update');
        $router->patch('{id:[0-9]+}', 'MediaTypeController@update');
        $router->delete('{id:[0-9]+}', 'MediaTypeController@destroy');
    });

    // Organisation routes
    $router->group(['prefix' => 'organisations'], function () use ($router) {
        $router->get('/', 'OrganisationController@index');
        $router->get('list', 'OrganisationController@list');
    });

    $router->group(['prefix' => 'countries'], function () use ($router) {
        $router->get('/', 'CountryController@index');
        $router->get('list', 'CountryController@list');
        $router->get('{id:[0-9]+}', 'CountryController@show');
        $router->get('{id:[0-9]+}/states', 'StateController@getStatesByCountry');
        $router->get('{id:[0-9]+}/cities', 'CityController@getCitiesByCountry');
    });
    
    $router->group(['prefix' => 'agency-groups'], function () use ($router) {
        $router->get('/', 'AgencyGroupController@index');
        $router->post('/', 'AgencyGroupController@store');
        $router->get('{id:[0-9]+}', 'AgencyGroupController@show');
        $router->put('{id:[0-9]+}', 'AgencyGroupController@update');
        $router->patch('{id:[0-9]+}', 'AgencyGroupController@update');
        $router->delete('{id:[0-9]+}', 'AgencyGroupController@destroy');
    });
    
    // Agency Type routes
    $router->group(['prefix' => 'agency-types'], function () use ($router) {
        $router->get('/', 'AgencyTypeController@index');
        $router->post('/', 'AgencyTypeController@store');
        $router->get('/{id:[0-9]+}', 'AgencyTypeController@show');
        $router->put('/{id:[0-9]+}', 'AgencyTypeController@update');
        $router->patch('/{id:[0-9]+}', 'AgencyTypeController@update');
        $router->delete('/{id:[0-9]+}', 'AgencyTypeController@destroy');
    });
    
    $router->group(['prefix' => 'agencies'], function () use ($router) {
        $router->get('/', 'AgencyController@index');
        $router->get('list', 'AgencyController@list');
        $router->post('name', 'AgencyController@storeByName');
        $router->get('create-data', 'AgencyController@create');
        $router->post('batch', 'AgencyController@storeBatch');
        $router->post('/', 'AgencyController@store');
        // Get brands for a specific agency
        $router->get('{id:[0-9]+}/brands', 'AgencyController@getBrands');

        $router->get('{id:[0-9]+}', 'AgencyController@show');
        $router->put('{id:[0-9]+}', 'AgencyController@update');
        $router->patch('{id:[0-9]+}', 'AgencyController@update');
        $router->delete('{id:[0-9]+}', 'AgencyController@destroy');
    });
    
    // Brand routes
    $router->group(['prefix' => 'brands'], function () use ($router) {
        $router->get('/', 'BrandController@index');
        $router->post('name', 'BrandController@storeByName');
        $router->post('/', 'BrandController@store');
        $router->get('/list', 'BrandController@list');
        $router->get('/{id:[0-9]+}/agencies', 'BrandController@agencies');
        $router->get('/{id:[0-9]+}', 'BrandController@show');      
        $router->put('/{id:[0-9]+}', 'BrandController@update');     
        $router->patch('/{id:[0-9]+}', 'BrandController@update'); 
        $router->delete('/{id:[0-9]+}', 'BrandController@destroy');
    });
    
    // --- Brand Type Routes ---
    $router->group(['prefix' => 'brand-types'], function () use ($router) {
        $router->get('/', 'BrandTypeController@index');
        $router->post('/', 'BrandTypeController@store');
        $router->get('/{id:[0-9]+}', 'BrandTypeController@show');
        $router->put('/{id:[0-9]+}', 'BrandTypeController@update');
        $router->patch('/{id:[0-9]+}', 'BrandTypeController@update');
        $router->delete('/{id:[0-9]+}', 'BrandTypeController@destroy');
    });
    
    $router->group(['prefix' => 'regions'], function () use ($router) {
        $router->get('/', 'RegionController@index');
        $router->post('/', 'RegionController@store');
        $router->get('/{id:[0-9]+}', 'RegionController@show');
        $router->put('/{id:[0-9]+}', 'RegionController@update');
        $router->patch('/{id:[0-9]+}', 'RegionController@update');
        $router->delete('/{id:[0-9]+}', 'RegionController@destroy');
    });

    // --- BRAND AGENCY RELATIONSHIP ROUTES (NEW) ---
    $router->group(['prefix' => 'brand-agency-relationships'], function () use ($router) {
        $router->get('/', 'BrandAgencyRelationshipController@index');
        $router->post('/', 'BrandAgencyRelationshipController@store'); // Attach/Create
        $router->get('{id:[0-9]+}', 'BrandAgencyRelationshipController@show');
        // We explicitly skip PUT/PATCH as update is not meaningful for simple pivot tables
        $router->delete('{id:[0-9]+}', 'BrandAgencyRelationshipController@destroy'); // Detach/Delete
    });

    // States routes
    $router->group(['prefix' => 'states'], function () use ($router) {
        $router->get('/', 'StateController@index');
        $router->get('list', 'StateController@list');
        $router->get('all', 'StateController@getAll');
        $router->post('/', 'StateController@store');
        $router->get('{id:[0-9]+}', 'StateController@show');
        $router->put('{id:[0-9]+}', 'StateController@update');
        $router->patch('{id:[0-9]+}', 'StateController@update');
        $router->delete('{id:[0-9]+}', 'StateController@destroy');
        $router->get('{id:[0-9]+}/cities', 'CityController@getCitiesByState');
    });

    // Cities routes
    $router->group(['prefix' => 'cities'], function () use ($router) {
        $router->get('/', 'CityController@index');
        $router->get('list', 'CityController@list');
        $router->get('all', 'CityController@getAll');
        $router->post('/', 'CityController@store');
        $router->get('{id:[0-9]+}', 'CityController@show');
        $router->put('{id:[0-9]+}', 'CityController@update');
        $router->patch('{id:[0-9]+}', 'CityController@update');
        $router->delete('{id:[0-9]+}', 'CityController@destroy');
    });

    $router->group(['prefix' => 'zones'], function () use ($router) {
        $router->get('/', 'ZoneController@index');
        $router->get('all', 'ZoneController@getAll');
        $router->post('/', 'ZoneController@store');
        $router->get('{id:[0-9]+}', 'ZoneController@show');
        $router->put('{id:[0-9]+}', 'ZoneController@update');
        $router->patch('{id:[0-9]+}', 'ZoneController@update');
        $router->delete('{id:[0-9]+}', 'ZoneController@destroy');
    });

    // Roles routes (permission-protected)
    $router->group(['prefix' => 'roles', 'middleware' => 'permission:roles.read'], function () use ($router) {
        $router->get('/', 'RoleController@index');
        $router->get('list', 'RoleController@list');
        $router->get('{id:[0-9]+}', 'RoleController@show');
        $router->get('{id:[0-9]+}/users', 'RoleController@getUsers');
    });

    $router->group(['prefix' => 'roles', 'middleware' => 'permission:roles.create'], function () use ($router) {
        $router->post('/', 'RoleController@store');
    });

    $router->group(['prefix' => 'roles', 'middleware' => 'permission:roles.update'], function () use ($router) {
        $router->put('{id:[0-9]+}', 'RoleController@update');
        $router->patch('{id:[0-9]+}', 'RoleController@update');
        $router->post('{id:[0-9]+}/permissions', 'RoleController@syncPermissions');
        $router->post('{id:[0-9]+}/permissions/attach', 'RoleController@attachPermission');
        $router->post('{id:[0-9]+}/permissions/detach', 'RoleController@detachPermission');
    });

    $router->group(['prefix' => 'roles', 'middleware' => 'permission:roles.delete'], function () use ($router) {
        $router->delete('{id:[0-9]+}', 'RoleController@destroy');
    });

    // Permissions routes (reads available to all authenticated users for sidebar/navigation)
    $router->group(['prefix' => 'permissions'], function () use ($router) {
        $router->get('/', 'PermissionController@index');
        $router->get('list', 'PermissionController@list');
        $router->get('sidebar', 'PermissionController@sidebar');
        $router->get('all-permission-tree', 'PermissionController@allPermissionTree');
        $router->get('{id:[0-9]+}', 'PermissionController@show');
    });

    $router->group(['prefix' => 'permissions', 'middleware' => 'permission:roles.create'], function () use ($router) {
        $router->post('/', 'PermissionController@store');
    });

    $router->group(['prefix' => 'permissions', 'middleware' => 'permission:roles.update'], function () use ($router) {
        $router->put('{id:[0-9]+}', 'PermissionController@update');
        $router->patch('{id:[0-9]+}', 'PermissionController@update');
        $router->post('{id:[0-9]+}', 'PermissionController@update');
    });

    $router->group(['prefix' => 'permissions', 'middleware' => 'permission:roles.delete'], function () use ($router) {
        $router->delete('{id:[0-9]+}', 'PermissionController@destroy');
    });
    
    // Call Status routes
    $router->group(['prefix' => 'call-statuses'], function () use ($router) {
        $router->get('/', 'CallStatusController@index');
        //$router->post('/', 'CallStatusController@store');
        $router->get('{id:[0-9]+}', 'CallStatusController@show');
        $router->get('{id:[0-9]+}/priorities', 'CallStatusController@getPriorities');
        //$router->put('{id:[0-9]+}', 'CallStatusController@update');
        //$router->patch('{id:[0-9]+}', 'CallStatusController@update');
        //$router->delete('{id:[0-9]+}', 'CallStatusController@destroy');
    });

    // Status routes
    $router->group(['prefix' => 'statuses'], function () use ($router) {
        $router->get('/', 'StatusController@index');
        //$router->post('/', 'StatusController@store');
        $router->get('{id:[0-9]+}', 'StatusController@show');
        //$router->put('{id:[0-9]+}', 'StatusController@update');
        //$router->patch('{id:[0-9]+}', 'StatusController@update');
        //$router->delete('{id:[0-9]+}', 'StatusController@destroy');
    });

    // Priority routes
    $router->group(['prefix' => 'priorities'], function () use ($router) {
        $router->get('/', 'PriorityController@index');
        $router->post('/', 'PriorityController@store');
        $router->get('{id:[0-9]+}', 'PriorityController@show');
        $router->put('{id:[0-9]+}', 'PriorityController@update');
        $router->patch('{id:[0-9]+}', 'PriorityController@update');
        $router->delete('{id:[0-9]+}', 'PriorityController@destroy');
        
        // Get call statuses for a specific priority
        $router->get('{id:[0-9]+}/call-statuses', 'PriorityController@getCallStatuses');
        
        // Get lead count for a specific priority
        $router->get('{id:[0-9]+}/lead-count', 'PriorityController@getLeadCount');
        
        // Get brief count for a specific priority
        $router->get('{id:[0-9]+}/brief-count', 'PriorityController@getBriefCount');
    });

    // Lead routes
    $router->group(['prefix' => 'leads'], function () use ($router) {
        // List and filter routes first (specific routes before generic {id})
        $router->get('list', 'LeadController@list');
        $router->get('latest/two-leads', 'LeadController@latestTwo');
        $router->get('latest/follow-up-two', 'LeadController@latestTwoFollowUp');
        $router->get('latest/meeting-scheduled-two', 'LeadController@latestTwoMeetingScheduled');
        $router->get('filter', 'LeadController@filter');
        $router->get('pending', 'LeadController@pendingLeads');
        $router->get('activity-leads', 'LeadController@activity');
        $router->get('contact-persons/by-brand/{brandId:[0-9]+}', 'LeadController@getContactPersonsByBrand');
        $router->get('contact-persons/by-agency/{agencyId:[0-9]+}', 'LeadController@getContactPersonsByAgency');
        
        // Generic CRUD operations
        $router->get('/', 'LeadController@index');
        $router->post('/', 'LeadController@store');
        $router->get('{id:[0-9]+}', 'LeadController@show');
        $router->put('{id:[0-9]+}', 'LeadController@update');
        $router->patch('{id:[0-9]+}', 'LeadController@update');
        $router->delete('{id:[0-9]+}', 'LeadController@destroy');
        
        // Additional Lead routes (specific routes after generic CRUD)
        $router->get('{id:[0-9]+}/history', 'LeadController@getHistory');
        $router->post('{id:[0-9]+}/assign', 'LeadController@assign');
        $router->put('{id:[0-9]+}/assign-user', 'LeadController@updateAssignedUser');
        $router->post('{id:[0-9]+}/priority', 'LeadController@updatePriority');
        $router->post('{id:[0-9]+}/status', 'LeadController@updateStatus');
        $router->put('{id:[0-9]+}/call-status', 'LeadController@addCallStatus');
        $router->delete('{id:[0-9]+}/call-status/{callStatusId:[0-9]+}', 'LeadController@removeCallStatus');
    });

    // Miss Campaign routes
    $router->group(['prefix' => 'miss-campaigns'], function () use ($router) {
        $router->get('/', 'MissCampaignController@index');
        $router->get('/export', 'MissCampaignController@export');
        $router->post('/', 'MissCampaignController@store');
        $router->get('/list', 'MissCampaignController@list');
        $router->get('/{id:[0-9]+}', 'MissCampaignController@show');      
        $router->put('/{id:[0-9]+}/assign-user', 'MissCampaignController@assignUser');
        $router->put('/{id:[0-9]+}', 'MissCampaignController@update');     
        $router->patch('/{id:[0-9]+}', 'MissCampaignController@update'); 
        $router->delete('/{id:[0-9]+}', 'MissCampaignController@destroy');
    });

    // Brief Status routes
    $router->group(['prefix' => 'brief-statuses'], function () use ($router) {
        $router->get('/', 'BriefStatusController@index');
        $router->post('/', 'BriefStatusController@store');
        $router->get('priorities', 'BriefStatusController@getPrioritiesByBriefStatus');
        $router->get('by-priority', 'BriefStatusController@getBriefStatusesByPriority');
        $router->get('{id:[0-9]+}', 'BriefStatusController@show');
        $router->put('{id:[0-9]+}', 'BriefStatusController@update');
        $router->patch('{id:[0-9]+}', 'BriefStatusController@update');
        $router->delete('{id:[0-9]+}', 'BriefStatusController@destroy');
    });

    // Brief routes
    $router->group(['prefix' => 'briefs'], function () use ($router) {
        // List and filter routes first (specific routes before generic {id})
        $router->get('latest/two-briefs', 'BriefController@getLatestTwo');
        $router->get('recent', 'BriefController@getRecentBriefs');
        $router->get('list', 'BriefController@index');
        $router->get('filter', 'BriefController@index');
        $router->get('latest/five', 'BriefController@getLatestFive');
        $router->get('planner-dashboard-card', 'BriefController@getPlannerDashboardCardData');
        $router->get('brief-logs', 'BriefController@getBriefLogs');
        $router->get('business-forecast', 'BriefController@getBusinessForecast');
        

        // Generic CRUD operations
        $router->get('/', 'BriefController@index');
        $router->post('/', 'BriefController@store');
        $router->get('{id:[0-9]+}', 'BriefController@show');
        $router->put('{id:[0-9]+}', 'BriefController@update');
        $router->patch('{id:[0-9]+}', 'BriefController@update');
        $router->delete('{id:[0-9]+}', 'BriefController@destroy');
        
        // Additional Brief routes
        $router->put('{id:[0-9]+}/update-status', 'BriefController@updateStatus');
        $router->put('{id:[0-9]+}/update-assign-user', 'BriefController@updateAssignUser');
        $router->get('{briefId:[0-9]+}/assign-histories', 'BriefAssignHistoryController@getByBriefId');
        $router->get('brand/{brandId:[0-9]+}', 'BriefController@getByBrand');
        $router->get('agency/{agencyId:[0-9]+}', 'BriefController@getByAgency');
        $router->get('user/{userId:[0-9]+}', 'BriefController@getByAssignedUser');
    });

    // // Brief Assign Histories by Brief
    // $router->group(['prefix' => 'briefs'], function () use ($router) {
    //     $router->get('{briefId:[0-9]+}/assign-histories', 'Api\BriefAssignHistoryController@getByBriefId');
    // });

    // // Brief Assign Histories by User
    // $router->group(['prefix' => 'users'], function () use ($router) {
    //     $router->get('{userId:[0-9]+}/assigned-briefs', 'Api\BriefAssignHistoryController@getByAssignBy');
    //     $router->get('{userId:[0-9]+}/assigned-to-me', 'Api\BriefAssignHistoryController@getByAssignTo');
    // });

    // Brief Assign Histories CRUD routes
    $router->group(['prefix' => 'brief-assign-histories'], function () use ($router) {
        $router->get('/', 'BriefAssignHistoryController@index');
        // $router->post('/', 'BriefAssignHistoryController@store');
        // $router->get('{id:[0-9]+}', 'BriefAssignHistoryController@show');
        // $router->put('{id:[0-9]+}', 'BriefAssignHistoryController@update');
        // $router->patch('{id:[0-9]+}', 'BriefAssignHistoryController@patch');
        // $router->delete('{id:[0-9]+}', 'BriefAssignHistoryController@destroy');
    });

    // Meetings routes
    $router->group(['prefix' => 'meetings'], function () use ($router) {
        $router->get('/', 'MeetingController@index');
        $router->get('list', 'MeetingController@list');
        $router->get('all', 'MeetingController@getAll');
        $router->post('/', 'MeetingController@store');
        $router->get('{id:[0-9]+}', 'MeetingController@show');
        $router->put('{id:[0-9]+}', 'MeetingController@update');
        $router->patch('{id:[0-9]+}', 'MeetingController@update');
        $router->delete('{id:[0-9]+}', 'MeetingController@destroy');
        $router->patch('{id:[0-9]+}/restore', 'MeetingController@restore');
    });

    // Meetings by lead (e.g., /api/v1/leads/1/meetings)
    $router->group(['prefix' => 'leads'], function () use ($router) {
        $router->get('{leadId:[0-9]+}/meetings', 'MeetingController@getMeetingsByLead');
    });

    // Meetings by attendee (e.g., /api/v1/users/1/meetings)
    $router->group(['prefix' => 'users'], function () use ($router) {
        $router->get('{attendeeId:[0-9]+}/meetings', 'MeetingController@getMeetingsByAttendee');
    });

    // Planners routes
    $router->group(['prefix' => 'planners'], function () use ($router) {
        // List and filter routes first (specific routes before generic {id})
        $router->get('/', 'PlannerController@index');
        $router->post('/', 'PlannerController@store');
        
        // Additional Planner routes (specific routes BEFORE generic CRUD)
        $router->post('{id:[0-9]+}/upload-submitted-plans', 'PlannerController@uploadSubmittedPlans');
        $router->post('{id:[0-9]+}/upload-backup-plan', 'PlannerController@uploadBackupPlan');
        $router->put('{id:[0-9]+}/update-status', 'PlannerController@updateStatus');
        
        // Generic CRUD operations
        $router->get('{id:[0-9]+}', 'PlannerController@show');
        $router->put('{id:[0-9]+}', 'PlannerController@update');
        $router->patch('{id:[0-9]+}', 'PlannerController@update');
        $router->delete('{id:[0-9]+}', 'PlannerController@destroy');
    });

    // Planners by brief (e.g., /api/v1/briefs/1/planners)
    $router->group(['prefix' => 'briefs'], function () use ($router) {
        $router->post('{briefId:[0-9]+}/planners', 'PlannerController@createForBrief');
        $router->get('{briefId:[0-9]+}/planners', 'PlannerController@getPlannersByBrief');
        $router->get('{briefId:[0-9]+}/planners/{id:[0-9]+}', 'PlannerController@showForBrief');
        $router->put('{briefId:[0-9]+}/planners/{id:[0-9]+}', 'PlannerController@updateForBrief');
        $router->patch('{briefId:[0-9]+}/planners/{id:[0-9]+}', 'PlannerController@updateForBrief');
        $router->delete('{briefId:[0-9]+}/planners/{id:[0-9]+}', 'PlannerController@destroyForBrief');
    });

    // Planner Histories routes
    $router->group(['prefix' => 'planner-histories'], function () use ($router) {
        // List and filter routes
        $router->get('/', 'PlannerHistoryController@index');
        $router->get('recent', 'PlannerHistoryController@getRecentHistories');
        
        // Get histories for a specific planner
        $router->get('planner/{plannerId:[0-9]+}', 'PlannerHistoryController@getPlannerHistories');
        
        // Get histories by status
        $router->get('status/{status}', 'PlannerHistoryController@getByStatus');
    });

    // Planner Statuses routes
    $router->group(['prefix' => 'planner-statuses'], function () use ($router) {
        // List and filter routes first (specific routes before generic {id})
        $router->get('/', 'PlannerStatusController@index');
        $router->post('/', 'PlannerStatusController@store');
        
        // Generic CRUD operations
        // $router->get('{id:[0-9]+}', 'PlannerStatusController@show');
        // $router->put('{id:[0-9]+}', 'PlannerStatusController@update');
        // $router->patch('{id:[0-9]+}', 'PlannerStatusController@update');
        // $router->delete('{id:[0-9]+}', 'PlannerStatusController@destroy');
    });

    // Planner Histories by brief (e.g., /api/v1/briefs/1/planner-histories)
    $router->group(['prefix' => 'briefs'], function () use ($router) {
        $router->get('{briefId:[0-9]+}/planner-histories', 'PlannerHistoryController@getBriefPlannerHistories');
    });

    // Super Admin Dashboard routes
    $router->group(['prefix' => 'dashboard'], function () use ($router) {
        $router->get('/', 'Api\DashboardController@getDashboard');
        $router->get('/charts', 'Api\DashboardController@getCharts');
        $router->get('/sales-charts', 'Api\DashboardController@getSalesCharts');
        $router->get('/planner-charts', 'Api\DashboardController@getPlannerCharts');
    });

    // Activity Log routes
    $router->group(['prefix' => 'activity-logs'], function () use ($router) {
        $router->get('/', 'ActivityLogController@index');
        $router->get('recent', 'ActivityLogController@getRecentActivities');
        $router->get('by-action/{action}', 'ActivityLogController@getActivityLogsByAction');
        $router->get('model/{model}/{modelId:[0-9]+}', 'ActivityLogController@getModelActivityLogs');
        $router->get('user/{userId:[0-9]+}', 'ActivityLogController@getUserActivityLogs');
        $router->get('{id:[0-9]+}', 'ActivityLogController@show');
        $router->delete('old-logs', 'ActivityLogController@deleteOldActivityLogs');
        $router->delete('{id:[0-9]+}', 'ActivityLogController@destroy');
    });

    // Notification routes
    $router->group(['prefix' => 'notifications'], function () use ($router) {
        $router->get('/', 'NotificationController@index');
        $router->post('{id:[0-9]+}/read', 'NotificationController@markAsRead');
        $router->post('read-all', 'NotificationController@markAllAsRead');
        $router->get('unread-count', 'NotificationController@unreadCount');
          
        $router->get('unread', 'NotificationController@unreadNotifications');
        $router->get('latest', 'NotificationController@latestNotifications');
        $router->delete('clear-all', 'NotificationController@clearAllNotifications');
        $router->delete('{id:[0-9]+}', 'NotificationController@deleteNotification');
    });
});

$router->group(['prefix' => 'v1/admin', 'middleware' => ['jwt.auth', 'role:admin']], function () use ($router) {
    $router->get('dashboard', function () {
        return response()->json([
            'success' => true,
            'message' => 'Admin dashboard accessed successfully',
            'data' => [
                'admin_panel' => true,
                'timestamp' => Carbon::now()->toIso8601String()
            ]
        ]);
    });
});

// -------------------------------------------------------
// Fallback route for undefined API endpoints
// -------------------------------------------------------

$router->get('{any:.*}', function () {
    return response()->json([
        'success' => false,
        'message' => 'API endpoint not found',
        'error_code' => 'NOT_FOUND',
        'meta' => [
            'timestamp' => Carbon::now()->toIso8601String(),
            'status_code' => 404,
        ]
    ], 404);
});