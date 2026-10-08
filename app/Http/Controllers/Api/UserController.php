<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use App\Services\BriefService;
use App\Services\LeadService;
use App\Services\OrganisationService;
use App\Services\ResponseService;
use App\Http\Resources\UserResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use App\Models\Lead;
use App\Models\Zone;

class UserController extends Controller
{
    /**
     * The user service instance
     *
     * @var UserService
     */
    protected $userService;

    /**
     * The response service instance
     *
     * @var ResponseService
     */
    protected $responseService;

    /**
     * @var OrganisationService
     */
    protected OrganisationService $organisationService;

    /**
     * @var BriefService
     */
    protected BriefService $briefService;

    /**
     * @var LeadService
     */
    protected LeadService $leadService;

    /**
     * Constructor
     *
     * @param UserService $userService
     * @param ResponseService $responseService
     * @param OrganisationService $organisationService
     * @param BriefService $briefService
     * @param LeadService $leadService
     */
    public function __construct(
        UserService $userService,
        ResponseService $responseService,
        OrganisationService $organisationService,
        BriefService $briefService,
        LeadService $leadService
    ) {
        $this->userService = $userService;
        $this->responseService = $responseService;
        $this->organisationService = $organisationService;
        $this->briefService = $briefService;
        $this->leadService = $leadService;
    }

    /**
     * Get all users
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = (int) $request->get('per_page', 15);
            $criteria = $request->except(['per_page', 'page']);
            
            if (!empty($criteria)) {
                $users = $this->userService->searchUsers($criteria, $perPage);
            } else {
                $users = $this->userService->getAllUsers($perPage);
            }
            
            // Apply resource collection to paginated results
            $resource = UserResource::collection($users);
            
            return $this->responseService->paginated(
                $resource,
                'Users retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->responseService->serverError('Failed to retrieve users: ' . $e->getMessage());
        }
    }

    /**
     * Get list of users with only id and name (e.g., /api/v1/users/list)
     */
    /**
     * Added users list API with support for department-based filtering
     * using department IDs and slugs.
     */

    /**
     * Added child planning users API to return the authenticated user's
     * hierarchy filtered by the planning department.
     */

    /**
     * Added helper methods to normalize department IDs and department slugs
     * from multiple supported request parameter formats.
     */

    /**
     * Added recursive child hierarchy builder with department filtering.
     */
    public function list(Request $request): JsonResponse
    {
        try {
            $criteria = $request->except(['per_page', 'page']);
            if (!empty($criteria)) {
                $users = $this->userService->searchUsers($criteria, perPage: 10000);
            } else {
                $users = $this->userService->getAllUsers(perPage: 10000);
            }

            $data = $users->items() ? collect($users->items())->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                ];
            }) : collect([]);
            return $this->responseService->success($data, 'Users list retrieved');
        } catch (\Exception $e) {
            return $this->responseService->serverError('Failed to retrieve users list: ' . $e->getMessage());
        }
    }

    /**
     * Get user by ID
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $user = $this->userService->getUserById($id);
            
            if (!$user) {
                return $this->responseService->notFound('User not found');
            }
            
            return $this->responseService->success(
                new UserResource($user),
                'User retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->responseService->serverError('Failed to retrieve user: ' . $e->getMessage());
        }
    }

    /**
     * Create new user
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Validate required fields first
            $rules = [
                'name' => 'required|string|max:255',
                'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->whereNull('deleted_at')],
                'password' => 'required|string|min:8',
                'phone' => 'nullable|integer|digits:10',
                'role_id' => 'required|array',
                'role_id.*' => 'integer|exists:roles,id',
                'status' => 'sometimes|in:1,2,3',
                'is_parent' => 'nullable|array',
                'is_parent.*' => 'integer|exists:users,id',
                'organisation_id' => 'required_without:organisation_ids|nullable|integer|exists:organisations,id',
                'organisation_ids' => 'required_without:organisation_id|nullable|array|min:1',
                'organisation_ids.*' => 'integer|exists:organisations,id',
                'zone_id' => 'required_without:zone_ids|nullable',
                'zone_id.*' => 'integer|exists:zones,id',
                'zone_ids' => 'nullable|array|min:1',
                'zone_ids.*' => 'integer|exists:zones,id',
                'department_ids' => 'nullable|array',
                'department_ids.*' => 'integer|exists:departments,id',
            ];

            $validated = \Illuminate\Support\Facades\Validator::make($request->all(), $rules);
            
            if ($validated->fails()) {
                return $this->responseService->validationError(
                    $validated->errors()->toArray(),
                    'Validation failed'
                );
            }

            $user = $this->userService->createUser($request->all());
            
            return $this->responseService->created(
                new UserResource($user),
                'User created successfully'
            );
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'User creation validation failed');
        } catch (\Exception $e) {
            return $this->responseService->serverError('Failed to create user: ' . $e->getMessage());
        }
    }

    /**
     * Update user
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $id = (int) trim($id);
            
             // Validate fields (only validate if they are present in the request)
            $success = $this->userService->updateUser($id, $request->all());
            
            if (!$success) {
                return $this->responseService->notFound('User not found');
            }
            
            $user = $this->userService->getUserById($id);
            
            return $this->responseService->updated(
                new UserResource($user),
                'User updated successfully'
            );
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'User update validation failed');
        } catch (\Exception $e) {
            return $this->responseService->serverError('Failed to update user: ' . $e->getMessage());
        }
    }

    /**
     * Delete user
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $success = $this->userService->deleteUser($id);
            
            if (!$success) {
                return $this->responseService->notFound('User not found');
            }
            
            return $this->responseService->deleted('User deleted successfully');
        } catch (\Exception $e) {
            return $this->responseService->serverError('Failed to delete user: ' . $e->getMessage());
        }
    }

    /**
     * Search users
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $criteria = $request->only(['name', 'email', 'role', 'status', 'created_at']);
            $perPage = $request->get('per_page', 15);
            
            $users = $this->userService->searchUsers($criteria, $perPage);
            
            return $this->responseService->paginated(
                UserResource::collection($users),
                'Search results retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->responseService->serverError('Failed to search users: ' . $e->getMessage());
        }
    }

    /**
     * Get user statistics
     *
     * @return JsonResponse
     */
    public function statistics(): JsonResponse
    {
        try {
            $statistics = $this->userService->getUserStatistics();
            
            return $this->responseService->success($statistics, 'User statistics retrieved successfully');
        } catch (\Exception $e) {
            return $this->responseService->serverError('Failed to retrieve user statistics: ' . $e->getMessage());
        }
    }

    /**
     * Change user password
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function changePassword(Request $request, int $id): JsonResponse
    {
        try {
            $request->validate([
                'current_password' => 'required|string',
                'password' => 'required|string|min:8|confirmed'
            ]);

            $success = $this->userService->changePassword(
                $id,
                $request->current_password,
                $request->password
            );
            
            if (!$success) {
                return $this->responseService->notFound('User not found');
            }
            
            return $this->responseService->success(null, 'Password changed successfully');
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'Password change validation failed');
        } catch (\Exception $e) {
            return $this->responseService->serverError('Failed to change password: ' . $e->getMessage());
        }
    }

    /**
     * Get current authenticated user profile
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function me(Request $request): JsonResponse
    {
        try {
            $user = $request->user ?? auth()->user();
            
            if (!$user) {
                return $this->responseService->unauthorized('User not authenticated');
            }
            
            $user = $this->userService->getUserById($user->id);
            
            return $this->responseService->success(
                new UserResource($user),
                'User profile retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->responseService->serverError('Failed to retrieve user profile: ' . $e->getMessage());
        }
    }

    /**
     * Update current authenticated user profile
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateProfile(Request $request): JsonResponse
    {
        try {
            $user = $request->user ?? auth()->user();
            
            if (!$user) {
                return $this->responseService->unauthorized('User not authenticated');
            }

            // Remove password from update data if present (use changePassword endpoint instead)
            $data = $request->except(['password', 'password_confirmation']);
            
            $success = $this->userService->updateUser($user->id, $data);
            
            if (!$success) {
                return $this->responseService->notFound('User not found');
            }
            
            $updatedUser = $this->userService->getUserById($user->id);
            
            return $this->responseService->updated(
                new UserResource($updatedUser),
                'Profile updated successfully'
            );
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'Profile update validation failed');
        } catch (\Exception $e) {
            return $this->responseService->serverError('Failed to update profile: ' . $e->getMessage());
        }
    }

    /**
     * Get login history for current authenticated user
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getLoginHistory(Request $request): JsonResponse
    {
        try {
            $user = $request->user ?? auth()->user();
            
            if (!$user) {
                return $this->responseService->unauthorized('User not authenticated');
            }

            $loginLogs = $user->loginLogs()->paginate($request->get('per_page', 15));
            
            return $this->responseService->paginated(
                $loginLogs,
                'Login history retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->responseService->serverError('Failed to retrieve login history: ' . $e->getMessage());
        }
    }

    /**
     * Get zone-wise assigned lead counts for an organisation.
     */
    public function getOrganisationZone(Request $request): JsonResponse
    {
        try {
            $user = $request->user ?? auth()->user();

            if (!$user) {
                return $this->responseService->unauthorized('User not authenticated');
            }

            $organisationId = $request->input('organisation_id') ?? $user->organisation_id;
            if (!$organisationId) {
                return $this->responseService->validationError([
                    'organisation_id' => ['Organisation ID is required'],
                ]);
            }

            $organisationIds = is_array($organisationId)
                ? $organisationId
                : explode(',', (string) $organisationId);
            $organisationIds = array_values(array_filter(
                array_unique(array_map('intval', $organisationIds)),
                fn ($id) => $id > 0
            ));

            $zones = Zone::getOrganisationLeadCounts($organisationIds);

            return $this->responseService->success(
                $zones,
                'Organisation zone data retrieved successfully.'
            );
        } catch (\Throwable $e) {
            return $this->responseService->serverError('Failed to retrieve organisation zone data: ' . $e->getMessage());
        }
    }

    /**
     * Get child users list for the currently authenticated user with nested hierarchy (id and name only)
     * @param Request $request
     * @return JsonResponse
     */
    /**
     * Updated child-user hierarchy API to support optional filtering by
     * department IDs and department slugs while preserving the nested hierarchy.
     */
    public function getChildUsers(Request $request): JsonResponse
    {
        try {
            $user = $request->user ?? auth()->user();
            
            if (!$user) {
                return $this->responseService->unauthorized('User not authenticated');
            }

            $departmentIds = $this->extractDepartmentIds($request);
            $departmentSlugs = $this->extractDepartmentSlugs($request);

            // Get all descendants in nested tree format (optionally filtered by departments_id / departments_slug)
            $childTree = $user->getChildTreeByDepartments($departmentIds, $departmentSlugs);
            
            return $this->responseService->success(
                $childTree,
                'Child users hierarchy retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->responseService->serverError('Failed to retrieve child users: ' . $e->getMessage());
        }
    }

    /**
     * Child planning users for the authenticated user.
     * Optional brief_id or contact_person_id limits the hierarchy to that record's organisation.
     * Optional Organisation_Id is used only when neither id is sent.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getChildPlaningUsers(Request $request): JsonResponse
    {
        try {
            $user = $request->user ?? auth()->user();
            
            if (!$user) {
                return $this->responseService->unauthorized('User not authenticated');
            }

            $briefFilter = $this->extractPositiveQueryId($request, 'brief_id');
            $contactPersonFilter = $this->extractPositiveQueryId($request, 'contact_person_id');

            if ($briefFilter['invalid']) {
                return $this->responseService->validationError(
                    ['brief_id' => ['The brief id must be a positive integer.']],
                    'Validation failed'
                );
            }

            if ($contactPersonFilter['invalid']) {
                return $this->responseService->validationError(
                    ['contact_person_id' => ['The contact person id must be a positive integer.']],
                    'Validation failed'
                );
            }

            $organisationFilter = $this->extractOrganisationId($request);
            $hasRecordFilter = $briefFilter['id'] !== null || $contactPersonFilter['id'] !== null;

            if (!$hasRecordFilter && $organisationFilter['invalid']) {
                return $this->responseService->success(
                    [],
                    'Invalid Organisation_Id.'
                );
            }

            $organisationId = $hasRecordFilter ? null : $organisationFilter['id'];

            if ($organisationId !== null && !$this->organisationService->organisationExists($organisationId)) {
                return $this->responseService->success(
                    [],
                    'Organisation not found.'
                );
            }

            if ($briefFilter['id'] !== null) {
                try {
                    $organisationId = $this->briefService->getOrganisationIdForBrief($briefFilter['id']);
                } catch (ModelNotFoundException $e) {
                    return $this->responseService->notFound('Brief not found');
                }

                if (!$organisationId) {
                    return $this->responseService->validationError(
                        ['brief_id' => ['The associated lead for this brief does not have an organisation assigned']],
                        'Validation failed'
                    );
                }
            } elseif ($contactPersonFilter['id'] !== null) {
                try {
                    $organisationId = $this->leadService->getOrganisationIdForContactPerson($contactPersonFilter['id']);
                } catch (ModelNotFoundException $e) {
                    return $this->responseService->notFound('Contact person not found');
                }

                if (!$organisationId) {
                    return $this->responseService->validationError(
                        ['contact_person_id' => ['The contact person does not have an organisation assigned']],
                        'Validation failed'
                    );
                }
            }

            $departmentIds = $this->extractDepartmentIds($request);
            $departmentSlugs = $this->extractDepartmentSlugs($request);

            if (empty($departmentSlugs) && empty($departmentIds)) {
                $departmentSlugs = ['planing'];
            }

            $childTree = $user->getChildTreeByDepartments($departmentIds, $departmentSlugs, $organisationId, true);
            
            return $this->responseService->success(
                $childTree,
                'Child planing users hierarchy retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->responseService->serverError('Failed to retrieve child planing users: ' . $e->getMessage());
        }
    }

    /**
     * Read a positive integer query parameter.
     * A missing or blank value means the filter is not applied.
     *
     * @param Request $request
     * @param string $parameter
     * @return array{invalid: bool, id: int|null}
     */
    protected function extractPositiveQueryId(Request $request, string $parameter): array
    {
        $raw = null;
        $provided = false;

        foreach ($request->query() as $key => $value) {
            if (strcasecmp((string) $key, $parameter) !== 0) {
                continue;
            }

            $provided = true;
            $raw = $value;
            break;
        }

        if (!$provided || $raw === null || (is_string($raw) && trim($raw) === '')) {
            return ['invalid' => false, 'id' => null];
        }

        if (is_array($raw) || !preg_match('/^[1-9]\d*$/', trim((string) $raw))) {
            return ['invalid' => true, 'id' => null];
        }

        return ['invalid' => false, 'id' => (int) trim((string) $raw)];
    }

    /**
     * Read Organisation_Id from the query string.
     * A missing or blank value keeps the unfiltered hierarchy.
     *
     * @param Request $request
     * @return array{invalid: bool, id: int|null}
     */
    protected function extractOrganisationId(Request $request): array
    {
        $raw = null;
        $provided = false;

        foreach ($request->query() as $key => $value) {
            if (strcasecmp((string) $key, 'Organisation_Id') !== 0) {
                continue;
            }

            $provided = true;
            $raw = $value;
            break;
        }

        if (!$provided || $raw === null || (is_string($raw) && trim($raw) === '')) {
            return ['invalid' => false, 'id' => null];
        }

        if (is_array($raw) || !preg_match('/^[1-9]\d*$/', trim((string) $raw))) {
            return ['invalid' => true, 'id' => null];
        }

        return ['invalid' => false, 'id' => (int) trim((string) $raw)];
    }

    /**
     * Extract department IDs from request
     *
     * @param Request $request
     * @return array
     */
    /**
     * Added department ID filter normalization to support multiple request
     * parameter formats and convert them into a validated integer array.
     */

    protected function extractDepartmentIds(Request $request): array
    {
        $raw = $request->input('departments_id')
            ?? $request->input('departments_ids')
            ?? $request->input('department_id')
            ?? $request->input('department_ids');

        if ($raw === null || $raw === '') {
            return [];
        }

        if (is_string($raw)) {
            $raw = explode(',', $raw);
        }

        if (!is_array($raw)) {
            $raw = [$raw];
        }

        return array_values(array_filter(array_map('intval', $raw), fn($id) => $id > 0));
    }

    /**
     * Extract department slugs from request
     *
     * @param Request $request
     * @return array
     */
    /**
     * Added department slug filter normalization to support multiple request
     * parameter formats and convert them into a cleaned slug array.
     */
    protected function extractDepartmentSlugs(Request $request): array
    {
        $raw = $request->input('departments_slug')
            ?? $request->input('departments_slugs')
            ?? $request->input('department_slug')
            ?? $request->input('department_slugs');

        if ($raw === null || $raw === '') {
            return [];
        }

        if (is_string($raw)) {
            $raw = explode(',', $raw);
        }

        if (!is_array($raw)) {
            $raw = [$raw];
        }

        return array_values(array_filter(array_map('trim', $raw), fn($slug) => $slug !== ''));
    }

    /**
     * Get child users list filtered by organisation
     * @param Request $request
     * @return JsonResponse
     */
    public function getChildUsersByOrganisation(Request $request): JsonResponse
    {
        try {
            $user = $request->user ?? auth()->user();
            
            if (!$user) {
                return $this->responseService->unauthorized('User not authenticated');
            }

            $organisationIds = $request->input('organisation_id')
                ?? $request->input('organisation_user');
            $zoneIds = $request->input('zone_ids') ?? $request->input('zone_id');

            // Preserve duplicate query parameters such as organisation_id=1&organisation_id=2.
            $rawOrganisationIds = [];
            foreach (explode('&', (string) $request->getQueryString()) as $parameter) {
                [$key, $value] = array_pad(explode('=', $parameter, 2), 2, null);
                $key = urldecode((string) $key);
                if (in_array(rtrim($key, '[]'), ['organisation_id', 'organisation_user'], true)) {
                    $rawOrganisationIds[] = urldecode((string) $value);
                }
            }

            if (is_string($organisationIds)) {
                $organisationIds = explode(',', $organisationIds);
            }
            if (!is_array($organisationIds)) {
                $organisationIds = [$organisationIds];
            }
            $organisationIds = array_merge($organisationIds, $rawOrganisationIds);
            $organisationIds = array_values(array_filter(
                array_unique(array_map('intval', $organisationIds)),
                fn ($id) => $id > 0
            ));

            if (is_string($zoneIds)) {
                $zoneIds = explode(',', $zoneIds);
            }
            if (!is_array($zoneIds)) {
                $zoneIds = [$zoneIds];
            }

            // Preserve duplicate query parameters such as zone_id=1&zone_id=2.
            $rawZoneIds = [];
            foreach (explode('&', (string) $request->getQueryString()) as $parameter) {
                [$key, $value] = array_pad(explode('=', $parameter, 2), 2, null);
                $key = urldecode((string) $key);
                if (in_array(rtrim($key, '[]'), ['zone_id', 'zone_ids'], true)) {
                    $rawZoneIds[] = urldecode((string) $value);
                }
            }

            $zoneIds = array_values(array_filter(
                array_unique(array_map('intval', array_merge($zoneIds, $rawZoneIds))),
                fn ($id) => $id > 0
            ));

            if (empty($organisationIds) && empty($zoneIds)) {
                return $this->responseService->validationError([
                    'organisation_id' => ['Organisation ID or zone ID is required'],
                ]);
            }

            $leadFilters = [
                'call_status' => $request->input('call_status'),
                'lead_status' => $request->input('lead_status'),
                'priority' => $request->input('priority') ?? $request->input('priority_id'),
                'name' => trim((string) $request->input('name', '')),
            ];

            // Get all descendants in nested tree format filtered by organisation.
            // Planner roles are omitted at every level, including their nested children.
            $childTree = $user->getChildTreeByOrganisation($organisationIds, $leadFilters, $zoneIds, ['planner-admin', 'planner']);

            $perPage = max(1, (int) $request->input('per_page', 5));
            $currentPage = max(1, (int) $request->input('page', 1));
            $childTree = new \Illuminate\Pagination\LengthAwarePaginator(
                array_slice($childTree, ($currentPage - 1) * $perPage, $perPage),
                count($childTree),
                $perPage,
                $currentPage,
                [
                    'path' => $request->url(),
                    'query' => $request->query(),
                ]
            );
            
            return $this->responseService->paginated(
                $childTree,
                'Child users hierarchy filtered by organisation retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->responseService->serverError('Failed to retrieve child users: ' . $e->getMessage());
        }
    }

    public function getChildUsersByLead(Request $request, int $leadId): JsonResponse
    {
        try {
            /** @var \App\Models\User $user */
            $user = $request->user ?? auth()->user();
            
            if (!$user) {
                return $this->responseService->unauthorized('User not authenticated');
            }

            $lead = \App\Models\Lead::find($leadId);
            if (!$lead) {
                return $this->responseService->notFound('Lead not found');
            }

            $organisationId = $lead->organisation_id;
            if (!$organisationId) {
                return $this->responseService->validationError(['lead_id' => ['Lead does not have an organisation assigned']]);
            }

            // Get all descendants of the current logged-in user in nested tree format filtered by the lead's organisation.
            // Planner roles are omitted at every level, including their nested children.
            $childTree = $user->getChildTreeByOrganisation($organisationId, [], [], ['planner-admin', 'planner']);
            
            return $this->responseService->success(
                $childTree,
                'Child users hierarchy for lead retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->responseService->serverError('Failed to retrieve child users for lead: ' . $e->getMessage());
        }
    }

    public function getChildUsersForBriefCreation(Request $request, int $leadId): JsonResponse
    {
        try {
            $user = $request->user ?? auth()->user();
            
            if (!$user) {
                return $this->responseService->unauthorized('User not authenticated');
            }

            $lead = \App\Models\Lead::find($leadId);
            if (!$lead) {
                return $this->responseService->notFound('Lead not found');
            }

            $organisationId = $lead->organisation_id;
            if (!$organisationId) {
                return $this->responseService->validationError(['lead_id' => ['Lead does not have an organisation assigned']]);
            }

            // Get all descendants of the AUTH user in nested tree format filtered by the lead's organisation
            $childTree = $user->getChildTreeByOrganisation($organisationId);
            
            return $this->responseService->success(
                $childTree,
                'Child users hierarchy for brief creation retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->responseService->serverError('Failed to retrieve child users for brief creation: ' . $e->getMessage());
        }
    }

    public function getChildUsersByMissCampaign(Request $request, int $campaignId): JsonResponse
    {
        try {
            /** @var \App\Models\User $user */
            $user = $request->user ?? auth()->user();
            
            if (!$user) {
                return $this->responseService->unauthorized('User not authenticated');
            }

            $campaign = \App\Models\MissCampaign::with('lead')->find($campaignId);
            if (!$campaign) {
                return $this->responseService->notFound('Miss campaign not found');
            }

            // Miss campaigns store organisation_id in their connected Lead
            $lead = $campaign->lead;
            if (!$lead || !$lead->organisation_id) {
                return $this->responseService->validationError(['campaign_id' => ['Miss campaign does not have an organisation assigned']]);
            }
            
            $organisationId = $lead->organisation_id;

            // Get all descendants of the current logged-in user in nested tree format filtered by the organisation
            $childTree = $user->getChildTreeByOrganisation($organisationId);
            
            return $this->responseService->success(
                $childTree,
                'Child users hierarchy for miss campaign retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->responseService->serverError('Failed to retrieve child users for miss campaign: ' . $e->getMessage());
        }
    }

    public function getChildUsersByBrief(Request $request, int $briefId): JsonResponse
    {
        try {
            $user = $request->user ?? auth()->user();
            
            if (!$user) {
                return $this->responseService->unauthorized('User not authenticated');
            }

            $brief = \App\Models\Brief::with('contactPerson')->find($briefId);
            if (!$brief) {
                return $this->responseService->notFound('Brief not found');
            }

            // The brief is linked to a lead via contact_person_id
            $lead = $brief->contactPerson;
            if (!$lead || !$lead->organisation_id) {
                return $this->responseService->validationError(['brief_id' => ['The associated lead for this brief does not have an organisation assigned']]);
            }
            
            $organisationId = $lead->organisation_id;

            // Get all descendants of the AUTH user in nested tree format filtered by the lead's organisation
            $childTree = $user->getChildTreeByOrganisation($organisationId);
            
            return $this->responseService->success(
                $childTree,
                'Child users hierarchy for brief retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->responseService->serverError('Failed to retrieve child users for brief: ' . $e->getMessage());
        }
    }


    /**
     * Get child planners filtered by brief's lead's organisation and planner department slug
     * 
     * @param Request $request
     * @param int $briefId
     * @return JsonResponse
     */
    public function getChildPlannersByBrief(Request $request, int $briefId): JsonResponse
    {
        try {
            $user = $request->user ?? auth()->user();
            
            if (!$user) {
                return $this->responseService->unauthorized('User not authenticated');
            }

            $brief = \App\Models\Brief::with('contactPerson')->find($briefId);
            if (!$brief) {
                return $this->responseService->notFound('Brief not found');
            }

            // The brief is linked to a lead via contact_person_id
            $lead = $brief->contactPerson;
            if (!$lead || !$lead->organisation_id) {
                return $this->responseService->validationError(['brief_id' => ['The associated lead for this brief does not have an organisation assigned']]);
            }
            
            $organisationId = $lead->organisation_id;

            // Get all descendants of the AUTH user in nested tree format filtered by the lead's organisation and the planner department slug
            $childTree = $user->getChildTreeByOrganisationAndDepartmentSlug($organisationId, 'planner');
            
            return $this->responseService->success(
                $childTree,
                'Child planners hierarchy for brief retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->responseService->serverError('Failed to retrieve child planners for brief: ' . $e->getMessage());
        }
    }

    /**
     * Get child operations users for a brief's organisation.
     *
     * GET /profile/child-ops-by-brief/{briefId}
     */
    public function getChildOpsByBrief(Request $request, int $briefId): JsonResponse
    {
        return $this->childUsersByBriefDepartment($request, $briefId, ['operations', 'ops-admin', 'ops-user'], 'operations users');
    }

    /**
     * Get child finance users for a brief's organisation.
     *
     * GET /profile/child-finance-by-brief/{briefId}
     */
    public function getChildFinanceByBrief(Request $request, int $briefId): JsonResponse
    {
        return $this->childUsersByBriefDepartment($request, $briefId, ['finance', 'finance-admin', 'finance-user'], 'finance users');
    }

    /**
     * Descendants of the authenticated user in one department for the brief's lead organisation.
     */
    private function childUsersByBriefDepartment(Request $request, int $briefId, array $departmentSlug, string $label): JsonResponse
    {
        try {
            $user = $request->user ?? auth()->user();

            if (!$user) {
                return $this->responseService->unauthorized('User not authenticated');
            }

            $brief = \App\Models\Brief::with('contactPerson')->find($briefId);
            if (!$brief) {
                return $this->responseService->notFound('Brief not found');
            }

            $lead = $brief->contactPerson;
            if (!$lead || !$lead->organisation_id) {
                return $this->responseService->validationError(['brief_id' => ['The associated lead for this brief does not have an organisation assigned']]);
            }

            $childTree = $user->getChildTreeByOrganisationAndDepartmentSlug((int) $lead->organisation_id, $departmentSlug);

            return $this->responseService->success(
                $childTree,
                'Child ' . $label . ' hierarchy for brief retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->responseService->serverError('Failed to retrieve child ' . $label . ' for brief: ' . $e->getMessage());
        }
    }

    /**
     * Get child planners filtered by lead's organisation and planner department slug
     * 
     * @param Request $request
     * @param int $leadId
     * @return JsonResponse
     */
    public function getChildPlannersByLead(Request $request, int $leadId): JsonResponse
    {
        try {
            $user = $request->user ?? auth()->user();
            
            if (!$user) {
                return $this->responseService->unauthorized('User not authenticated');
            }

            $lead = \App\Models\Lead::find($leadId);
            if (!$lead) {
                return $this->responseService->notFound('Lead not found');
            }

            $organisationId = $lead->organisation_id;
            if (!$organisationId) {
                return $this->responseService->validationError(['lead_id' => ['Lead does not have an organisation assigned']]);
            }

            // Get all descendants of the AUTH user in nested tree format filtered by the lead's organisation and the planner department slug
            $childTree = $user->getChildTreeByOrganisationAndDepartmentSlug($organisationId, 'planner');
            
            return $this->responseService->success(
                $childTree,
                'Child planners hierarchy for lead retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->responseService->serverError('Failed to retrieve child planners for lead: ' . $e->getMessage());
        }
    }
}