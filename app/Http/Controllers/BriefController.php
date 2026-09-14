<?php

namespace App\Http\Controllers;

use App\Http\Resources\BriefResource;
use App\Http\Resources\RecentBriefResource;
use App\Models\Brief;
use App\Models\BriefStatus;
use App\Services\BriefService;
use App\Services\ResponseService;
use App\Traits\HandlesFileUploads;
use App\Traits\ValidatesRequests;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Throwable;
use Illuminate\Validation\ValidationException;


class BriefController extends Controller
{
    use ValidatesRequests, HandlesFileUploads;

    /**
     * @var ResponseService
     */
    protected ResponseService $responseService;

    /**
     * @var BriefService
     */
    protected BriefService $briefService;

    /**
     * Create a new BriefController instance.
     *
     * @param ResponseService $responseService
     * @param BriefService $briefService
     */
    public function __construct(ResponseService $responseService, BriefService $briefService)
    {
        $this->responseService = $responseService;
        $this->briefService = $briefService;
    }

    /**
     * Display a listing of the briefs.
     *
     * GET /briefs
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $this->validate($request, [
                'per_page' => 'nullable|integer|min:1',
                'search' => 'nullable|string|max:255',
                'brand_id' => 'nullable|integer|exists:brands,id',
                'agency_id' => 'nullable|integer|exists:agency,id',
                'assign_user_id' => 'nullable|integer|exists:users,id',
                'brief_status_id' => 'nullable|integer|exists:brief_statuses,id',
                'priority_id' => 'nullable|integer|exists:priorities,id',
                'status' => 'nullable|in:1,2,15',
            ]);

            $perPage = (int) $request->input('per_page', 15);
            $searchTerm = $request->input('search', null);

            // If filters are provided, use the filter method
            if ($request->anyFilled(['brand_id', 'agency_id', 'assign_user_id', 'brief_status_id', 'priority_id', 'status'])) {
                $filters = array_filter([
                    'brand_id' => $request->input('brand_id'),
                    'agency_id' => $request->input('agency_id'),
                    'assign_user_id' => $request->input('assign_user_id'),
                    'brief_status_id' => $request->input('brief_status_id'),
                    'priority_id' => $request->input('priority_id'),
                    'status' => $request->input('status'),
                    'search' => $searchTerm,
                ], fn($value) => $value !== null && $value !== '');
                $briefs = $this->briefService->getBriefsByFilters($filters, $perPage);
            } else {
                $briefs = $this->briefService->getAllBriefs($perPage, $searchTerm);
            }

            return $this->responseService->paginated(
                BriefResource::collection($briefs),
                'Briefs retrieved successfully'
            );
        } catch (ValidationException $e) {
            return $this->responseService->validationError(
                $e->errors(),
                'Validation failed'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Get the latest two briefs.
     *
     * GET /briefs/latest/two
     *
     * @return JsonResponse
     */
    public function getLatestTwo(Request $request): JsonResponse
    {
        try {
            $filters = \App\Support\DashboardFilters::fromRequest($request);
            $briefs = $this->briefService->getLatestTwoBriefs($filters);

            return $this->responseService->success(
                BriefResource::collection($briefs),
                'Latest two briefs retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }
    /**
     * Get the latest five briefs.
     *
     * GET /briefs/latest/five
     *
     * @return JsonResponse
     */
    public function getLatestFive(Request $request): JsonResponse
    {
        try {
            $filters = \App\Support\DashboardFilters::fromRequest($request);
            $briefs = $this->briefService->getLatestFiveBriefs($filters);

            // Format briefs with only required fields
            $formattedBriefs = $briefs->map(function ($brief) {
                $leftTime = null;
                if ($brief->submission_date) {
                    $now = now();
                    $diff = $brief->submission_date->diff($now);
                    if ($brief->submission_date > $now) {
                        $leftTime = $diff->format('%d days %h hours %i minutes left');
                    } else {
                        $leftTime = 'Expired';
                    }
                }

                return [
                    'id' => $brief->id,
                    'brief_name' => $brief->name,
                    'status' => isset($brief->briefStatus) ? $brief->briefStatus->name : null,
                    'product_name' => $brief->product_name,
                    'brand_name' => isset($brief->brand) ? $brief->brand->name : null,
                    'comment' => $brief->comment,
                    'submission_date' => $brief->submission_date ? $brief->submission_date->format('Y-m-d H:i:s A') : null,
                    'budget' => $brief->budget,
                    'left_time' => $leftTime,
                ];
            });

            return $this->responseService->success(
                $formattedBriefs,
                'Latest five briefs retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Get the latest five briefs.
     *
     * GET /briefs/planner-dashboard-card
     *
     * @return JsonResponse
     */
    public function getPlannerDashboardCardData(Request $request): JsonResponse
    {
        try {
            $filters = \App\Support\DashboardFilters::fromRequest($request);
            $data = $this->briefService->getPlannerDashboardCardData($filters);

            return $this->responseService->success(
                $data,
                'Planner dashboard card data retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Get brief logs with pagination (Latest briefs formatted like getLatestFive)
     *
     * GET /briefs/brief-logs
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getBriefLogs(Request $request): JsonResponse
    {
        try {
            $this->validate($request, [
                'per_page' => 'nullable|integer|min:1',
                'search' => 'nullable|string|max:255',
            ]);

            $perPage = (int) $request->input('per_page', 10);
            $searchTerm = $request->input('search', null);
            $briefs = $this->briefService->getBriefLogs($perPage, $searchTerm);

            return $this->responseService->paginated(
                BriefResource::collection($briefs),
                'Brief logs retrieved successfully'
            );
        } catch (ValidationException $e) {
            return $this->responseService->validationError(
                $e->errors(),
                'Validation failed'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Display the specified brief.
     * Authorization: Super Admin (id=8), user created_by, or user assigned_to
     *
     * GET /briefs/{id}
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            // Fetch brief with all relationships
            $brief = $this->briefService->getBrief($id);

            // Check if brief exists
            if (!$brief) {
                return $this->responseService->notFound('Brief not found');
            }

            // Get authenticated user
            /** @var \App\Models\User $user */
            $user = auth()->user();

            if (!$user) {
                return $this->responseService->unauthorized('User not authenticated');
            }

            // Authorization: Allow if Super Admin / Admin role OR has brief.view permission OR created_by OR assigned_to
            $isSuperAdmin = $user->hasRole('Super Admin') || $user->hasRole('admin');
            $hasViewPermission = $user->hasPermission('brief.view');
            $isCreatedBy = $user->id == $brief->created_by;
            $isAssignedTo = $user->id == $brief->assign_user_id;

            if (!$isSuperAdmin && !$hasViewPermission && !$isCreatedBy && !$isAssignedTo) {
                return $this->responseService->forbidden(
                    'You are not authorized to view this brief.'
                );
            }

            // Return brief with resource transformation
            return $this->responseService->success(
                new BriefResource($brief),
                'Brief retrieved successfully'
            );

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->responseService->notFound('Brief not found');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Store a newly created brief in storage.
     *
     * POST /briefs
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $rules = [
                'name' => 'required|string|max:255|unique:briefs,name,NULL,id,deleted_at,NULL',
                'product_name' => 'required|string|max:255',
                'contact_person_id' => 'required|integer|exists:leads,id',
                'brand_id' => 'nullable|integer|exists:brands,id',
                'agency_id' => 'nullable|integer|exists:agency,id',
                'mode_of_campaign' => 'nullable|in:programmatic,non_programmatic',
                'media_type' => 'nullable|string|max:255',
                'budget' => 'required|numeric|min:0',
                'assign_user_id' => 'nullable|integer|exists:users,id',
                'brief_status_id' => 'nullable|integer|exists:brief_statuses,id',
                'priority_id' => 'nullable|integer|exists:priorities,id',
                'comment' => 'nullable|string',
                'attachment' => 'nullable|file|max:10240',
                'submission_date' => 'required|date_format:Y-m-d H:i:s',
                'status' => 'nullable|in:1,2,15',
                'campaign_start_date' => 'required|date_format:Y-m-d',
                'campaign_end_date' => 'required|date_format:Y-m-d|after_or_equal:campaign_start_date',
            ];

            $this->validate($request, $rules);

            // Validate campaign mode and media_type combination
            if ($request->has('mode_of_campaign') && $request->has('media_type')) {
                $mode = $request->input('mode_of_campaign');
                $mediaType = $request->input('media_type');
                $validTypes = Brief::getCampaignTypesByMode($mode);
                
                if (!in_array($mediaType, $validTypes)) {
                    return $this->responseService->validationError(
                        ['media_type' => ["Invalid media type for mode '{$mode}'. Valid types are: " . implode(', ', array_map('strtoupper', $validTypes))]],
                        'Validation failed'
                    );
                }
            }

            $data = $request->all();
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $fileType = $this->detectFileType($file);
                $uploaded = $this->uploadFile(
                    $file,
                    $fileType,
                    'uploads/brief-attachments',
                    ['sizeLimit' => 10240]
                );
                $data['attachment'] = $uploaded['path'];
            }
            $data['uuid'] = (string) Str::uuid();
            $data['slug'] = Str::slug($request->input('name')) . '-' . uniqid();
            $data['created_by'] = Auth::id();
            // Set default status if not provided (1 = active)
            if (!isset($data['status'])) {
                $data['status'] = '1';
            }
            // Fetch priority_id from brief status if not provided
            if (!isset($data['priority_id']) || is_null($data['priority_id'])) {
                $briefStatus = isset($data['brief_status_id']) ? BriefStatus::find($data['brief_status_id']) : null;
                if ($briefStatus && $briefStatus->priority_id) {
                    $data['priority_id'] = $briefStatus->priority_id;
                } else {
                    $data['priority_id'] = 3; // Fallback default
                }
            }

            $brief = $this->briefService->createBrief($data);

            return $this->responseService->success(
                new BriefResource($brief),
                'Brief created successfully',
                201
            );
        } catch (ValidationException $e) {
            return $this->responseService->validationError(
                $e->errors(),
                'Validation failed'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Update the specified brief in storage.
     *
     * PUT /briefs/{id}
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $rules = [
                'name' => 'required|string|max:255|unique:briefs,name,' . $id . ',id,deleted_at,NULL',
                'product_name' => 'sometimes|required|string|max:255',
                'contact_person_id' => 'sometimes|required|integer|exists:leads,id',
                'brand_id' => 'nullable|integer|exists:brands,id',
                'agency_id' => 'nullable|integer|exists:agency,id',
                'mode_of_campaign' => 'nullable|in:programmatic,non_programmatic',
                'media_type' => 'nullable|string|max:255',
                'budget' => 'sometimes|required|numeric|min:0',
                'assign_user_id' => 'nullable|integer|exists:users,id',
                'brief_status_id' => 'nullable|integer|exists:brief_statuses,id',
                'priority_id' => 'nullable|integer|exists:priorities,id',
                'comment' => 'nullable|string',
                'attachment' => 'nullable|file|max:10240',
                'submission_date' => 'sometimes|required|date_format:Y-m-d H:i:s',
                'status' => 'nullable|in:1,2,15',
                'campaign_start_date' => 'nullable|date_format:Y-m-d',
                'campaign_end_date' => 'nullable|date_format:Y-m-d|after_or_equal:campaign_start_date',
            ];

            $this->validate($request, $rules);

            // Validate campaign end date against existing start date if needed
            if ($request->has('campaign_end_date') && !$request->exists('campaign_start_date')) {
                $brief = $this->briefService->getBrief($id);
                if ($brief && $brief->campaign_start_date) {
                    $newEndDate = \Carbon\Carbon::createFromFormat('Y-m-d', $request->input('campaign_end_date'));
                    if ($newEndDate->isBefore($brief->campaign_start_date)) {
                        return $this->responseService->validationError(
                            ['campaign_end_date' => ['The campaign end date must be on or after the existing campaign start date.']],
                            'Validation failed'
                        );
                    }
                }
            }

            // Validate campaign mode and media_type combination
            if ($request->has('mode_of_campaign') && $request->has('media_type')) {
                $mode = $request->input('mode_of_campaign');
                $mediaType = $request->input('media_type');
                $validTypes = Brief::getCampaignTypesByMode($mode);
                
                if (!in_array($mediaType, $validTypes)) {
                    return $this->responseService->validationError(
                        ['media_type' => ["Invalid media type for mode '{$mode}'. Valid types are: " . implode(', ', array_map('strtoupper', $validTypes))]],
                        'Validation failed'
                    );
                }
            }

            $data = $request->all();
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $fileType = $this->detectFileType($file);
                $uploaded = $this->uploadFile(
                    $file,
                    $fileType,
                    'uploads/brief-attachments',
                    ['sizeLimit' => 10240]
                );
                $data['attachment'] = $uploaded['path'];
            }
            // Fetch priority_id from brief status if not provided
            if (!isset($data['priority_id']) || is_null($data['priority_id'])) {
                $briefStatus = isset($data['brief_status_id']) ? BriefStatus::find($data['brief_status_id']) : null;
                if ($briefStatus && $briefStatus->priority_id) {
                    $data['priority_id'] = $briefStatus->priority_id;
                } else {
                    $data['priority_id'] = 3; // Fallback default
                }
            }

            $brief = $this->briefService->updateBrief($id, $data);

            if (!$brief) {
                throw new \Illuminate\Database\Eloquent\ModelNotFoundException();
            }

            return $this->responseService->success(
                new BriefResource($brief),
                'Brief updated successfully'
            );
        } catch (ValidationException $e) {
            return $this->responseService->validationError(
                $e->errors(),
                'Validation failed'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Remove the specified brief from storage.
     *
     * DELETE /briefs/{id}
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $brief = $this->briefService->getBrief($id);

            if (!$brief) {
                throw new \Illuminate\Database\Eloquent\ModelNotFoundException();
            }

            // Authorize the user to delete this brief
            // $this->authorize('delete', $brief);

            $deleted = $this->briefService->deleteBrief($id);

            if (!$deleted) {
                throw new \Illuminate\Database\Eloquent\ModelNotFoundException();
            }

            return $this->responseService->success(
                null,
                'Brief deleted successfully'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Get briefs by brand.
     *
     * GET /briefs/brand/{brandId}
     *
     * @param int $brandId
     * @param Request $request
     * @return JsonResponse
     */
    public function getByBrand(int $brandId, Request $request): JsonResponse
    {
        try {
            $perPage = (int) $request->input('per_page', 15);
            $briefs = $this->briefService->getBriefsByBrand($brandId, $perPage);

            return $this->responseService->paginated(
                BriefResource::collection($briefs),
                'Briefs retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Get briefs by agency.
     *
     * GET /briefs/agency/{agencyId}
     *
     * @param int $agencyId
     * @param Request $request
     * @return JsonResponse
     */
    public function getByAgency(int $agencyId, Request $request): JsonResponse
    {
        try {
            $perPage = (int) $request->input('per_page', 15);
            $briefs = $this->briefService->getBriefsByAgency($agencyId, $perPage);

            return $this->responseService->paginated(
                BriefResource::collection($briefs),
                'Briefs retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Get briefs by assigned user.
     *
     * GET /briefs/user/{userId}
     *
     * @param int $userId
     * @param Request $request
     * @return JsonResponse
     */
    public function getByAssignedUser(int $userId, Request $request): JsonResponse
    {
        try {
            $perPage = (int) $request->input('per_page', 15);
            $briefs = $this->briefService->getBriefsByAssignedUser($userId, $perPage);

            return $this->responseService->paginated(
                BriefResource::collection($briefs),
                'Briefs retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Update brief status.
     *
     * PUT /briefs/{id}/update-status
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        try {
            $rules = [
                'brief_status_id' => 'required|integer|exists:brief_statuses,id',
            ];

            $this->validate($request, $rules);

            $brief = $this->briefService->getBrief($id);

            if (!$brief) {
                throw new \Illuminate\Database\Eloquent\ModelNotFoundException();
            }

            $previousStatusId = $brief->brief_status_id;
            $previousStatusName = $brief->briefStatus ? $brief->briefStatus->name : null;

            // Get the brief status to fetch its associated priority
            $briefStatus = \App\Models\BriefStatus::find($request->input('brief_status_id'));

            $updateData = [
                'brief_status_id' => $request->input('brief_status_id'),
            ];

            // Auto-update priority_id from brief status if it exists
            if ($briefStatus && $briefStatus->priority_id) {
                $updateData['priority_id'] = $briefStatus->priority_id;
            }

            $brief = $this->briefService->updateBrief($id, $updateData);

            // Fire event for notification
            if (!$brief) {
                throw new \Illuminate\Database\Eloquent\ModelNotFoundException();
            }

            $user = auth()->user();
            
            // Only fire event if status actually changed
            if ($previousStatusId !== $brief->brief_status_id) {
                event(new \App\Events\BriefStatusChangedEvent(
                    $brief->id,
                    $brief->name,
                    $previousStatusId,
                    $previousStatusName,
                    $briefStatus ? $briefStatus->id : null,
                    $briefStatus ? $briefStatus->name : null,
                    $user?->id,
                    $user?->name,
                    now()
                ));
            }

            return $this->responseService->success(
                new BriefResource($brief),
                'Brief status updated successfully'
            );
        } catch (ValidationException $e) {
            return $this->responseService->validationError(
                $e->errors(),
                'Validation failed'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Update assigned user for a brief.
     *
     * PUT /briefs/{id}/update-assign-user
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function updateAssignUser(int $id, Request $request): JsonResponse
    {
        try {
            $rules = [
                'assign_user_id' => 'required|integer|exists:users,id',
            ];

            $this->validate($request, $rules);

            $brief = $this->briefService->getBrief($id);

            if (!$brief) {
                throw new \Illuminate\Database\Eloquent\ModelNotFoundException();
            }

            // Use the updateBrief method which will fire the event if assign_user_id changes
            $brief = $this->briefService->updateBrief($id, ['assign_user_id' => $request->input('assign_user_id')]);

            if (!$brief) {
                return $this->responseService->error(
                    'Failed to assign brief to user',
                    ['Assignment failed']
                );
            }

            return $this->responseService->success(
                new BriefResource($brief),
                'Brief assigned user updated successfully'
            );
        } catch (ValidationException $e) {
            return $this->responseService->validationError(
                $e->errors(),
                'Validation failed'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Get recent briefs with detailed information.
     *
     * GET /briefs/recent
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getRecentBriefs(Request $request): JsonResponse
    {
        try {
            $this->validate($request, [
                'limit' => 'nullable|integer|min:1|max:100',
            ]);

            $limit = (int) $request->input('limit', 5);
            $filters = \App\Support\DashboardFilters::fromRequest($request);

            $briefs = $this->briefService->getRecentBriefs($limit, $filters);

            return $this->responseService->success(
                RecentBriefResource::collection($briefs),
                'Recent briefs retrieved successfully'
            );
        } catch (ValidationException $e) {
            return $this->responseService->validationError(
                $e->errors(),
                'Validation failed'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Get business forecast data (total budget and business weightage).
     *
     * GET /briefs/business-forecast
     *
     * @return JsonResponse
     */
    public function getBusinessForecast(Request $request): JsonResponse
    {
        try {
            $filters = \App\Support\DashboardFilters::fromRequest($request);
            $businessForecast = $this->briefService->getBusinessForecast($filters);

            return $this->responseService->success(
                $businessForecast,
                'Business forecast data retrieved successfully'
            );
        } catch (DomainException $e) {
            return $this->responseService->error($e->getMessage(), null, 422, 'DOMAIN_ERROR');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    
}
    