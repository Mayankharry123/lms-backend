<?php

/**
 * MissCampaign Controller
 * -----------------------------------------
 * Handles HTTP requests for miss campaign management, including CRUD operations and API responses.
 *
 * @package App\Http\Controllers
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-04-08
 */

namespace App\Http\Controllers;

use App\Http\Resources\MissCampaignResource;
use App\Services\MissCampaignService;
use App\Services\ResponseService;
use App\Traits\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Throwable;
use Illuminate\Validation\ValidationException;
use App\Models\Lead;

class MissCampaignController extends Controller
{
    use ValidatesRequests;

    /**
     * @var ResponseService
     */
    protected ResponseService $responseService;

    /**
     * @var MissCampaignService
     */
    protected MissCampaignService $missCampaignService;

    /**
     * Create a new MissCampaignController instance.
     *
     * @param ResponseService $responseService
     * @param MissCampaignService $missCampaignService
     */
    public function __construct(ResponseService $responseService, MissCampaignService $missCampaignService)
    {
        $this->responseService = $responseService;
        $this->missCampaignService = $missCampaignService;
    }

    /**     * Validate location hierarchy consistency (country -> state -> city).
     *
     * @param array $data Validated data containing country_id, state_id, city_id
     * @return array|null Returns null on success, or validation error array on failure
     */
    protected function validateLocationHierarchy(array $data): ?array
    {
        $countryId = $data['country_id'];
        $stateId = $data['state_id'] ?? null;
        $cityId = $data['city_id'] ?? null;

        // Validate state belongs to country
        if (!empty($stateId)) {
            $state = \App\Models\State::find($stateId);
            if (!$state || (int) $state->country_id !== (int) $countryId) {
                return [
                    'state_id' => ['The selected state does not belong to the selected country.']
                ];
            }
        }

        // Validate city requires state and belongs to both state and country
        if (!empty($cityId)) {
            if (empty($stateId)) {
                return [
                    'city_id' => ['A state must be selected before selecting a city.']
                ];
            }

            $city = \App\Models\City::find($cityId);
            if (!$city || (int) $city->state_id !== (int) $stateId || (int) $city->country_id !== (int) $countryId) {
                return [
                    'city_id' => ['The selected city does not belong to the selected state and country.']
                ];
            }
        }

        return null; // No validation errors
    }

    /**     * Display a listing of the miss campaigns.
     *
     * GET /miss-campaigns
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
            ]);

            $perPage = (int) $request->input('per_page', 15);
            $searchTerm = $request->input('search');

            $campaigns = $this->missCampaignService->getAllMissCampaigns($perPage, $searchTerm);

            return $this->responseService->paginated(
                MissCampaignResource::collection($campaigns),
                'Miss campaigns retrieved successfully'
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
     * Export miss campaigns to CSV in public folder and return download URL.
     *
     * GET /miss-campaigns/export
     *
     * Query: search (optional)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function export(Request $request): JsonResponse
    {
        try {
            $this->validate($request, [
                'search' => 'nullable|string|max:255',
            ]);

            $export = $this->missCampaignService->exportMissCampaignsToCsvFile(
                $request->input('search')
            );

            return $this->responseService->success(
                $export,
                'Miss campaigns exported successfully'
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
     * Display the specified miss campaign.
     * Authorization: Super Admin, assign_by user, or assign_to user can view assignment fields
     *
     * GET /miss-campaigns/{id}
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $campaign = $this->missCampaignService->getMissCampaign($id);

            if (!$campaign) {
                throw new \Illuminate\Database\Eloquent\ModelNotFoundException();
            }

            // Get authenticated user
            /** @var \App\Models\User $user */
            $user = auth()->user();

            if (!$user) {
                return $this->responseService->unauthorized('User not authenticated');
            }

            // Authorization: Allow if Super Admin role OR assign_by OR assign_to
            $isSuperAdmin = $user->hasRole('Super Admin');
            $isAssignBy = $user->id == $campaign->assign_by;
            $isAssignTo = $user->id == $campaign->assign_to;

            if (!$isSuperAdmin && !$isAssignBy && !$isAssignTo) {
                return $this->responseService->forbidden(
                    'You are not authorized to view this miss campaign. Only Super Admin, the user who assigned this campaign, or the user assigned to this campaign can view it.'
                );
            }

            return $this->responseService->success(
                new MissCampaignResource($campaign),
                'Miss campaign retrieved successfully'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->responseService->notFound('Miss campaign not found');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Store a newly created miss campaign in storage.
     *
     * POST /miss-campaigns
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $rules = [
                'name' => 'required|string|max:255',
                'brand_id' => 'required|integer|exists:brands,id',
                'lead_source_id' => 'required|integer|exists:lead_source,id',
                'lead_sub_source_id' => 'nullable|integer|exists:lead_sub_source,id',
                'media_type_id' => 'nullable|integer|exists:media_types,id',
                'industry_id' => 'nullable|integer|exists:industries,id',
                'country_id' => 'required|integer|exists:countries,id',
                'state_id' => 'nullable|integer|exists:states,id',
                'city_id' => 'nullable|integer|exists:cities,id',
                'assign_to' => 'nullable|integer|exists:users,id',
                'image_path' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp,svg|max:51200',
            ];

            $user = auth()->user();
            $orgIds = \App\Support\UserAccessScope::getAccessibleOrganisationIds($user);

            if (count($orgIds) > 1) {
                $rules['organisation_id'] = 'required|integer|in:' . implode(',', $orgIds);
            } elseif (count($orgIds) === 1) {
                $rules['organisation_id'] = 'nullable|integer|in:' . $orgIds[0];
            } else {
                $rules['organisation_id'] = 'nullable|integer';
            }

            $validatedData = $this->validate($request, $rules);

            if (count($orgIds) === 1) {
                $validatedData['organisation_id'] = $orgIds[0];
            }

            // Validate location hierarchy consistency
            $locationErrors = $this->validateLocationHierarchy($validatedData);
            if ($locationErrors) {
                return $this->responseService->validationError($locationErrors, 'Validation failed');
            }

            // Add system-generated fields
            $validatedData['slug'] = Str::slug($request->name) . '-' . uniqid();
            $validatedData['status'] = '1'; // Default active status
            $validatedData['assign_by'] = Auth::id(); // Set assign_by to the authenticated user

            // Handle image upload if present
            if ($request->hasFile('image_path')) {
                $uploadResult = $this->missCampaignService->uploadImage($request->file('image_path'));
                $validatedData['image_path'] = $uploadResult['path'] ?? null;
            }
        
            $leads = new Lead();
            $leads->name = $validatedData['name'];
            $leads->uuid = Str::uuid();
            $leads->brand_id = $validatedData['brand_id'];  
            $leads->organisation_id = $validatedData['organisation_id'] ?? null;
            $leads->sub_source_id = $validatedData['lead_sub_source_id'];
            $leads->country_id = $validatedData['country_id'];
            $leads->state_id = $validatedData['state_id'];
            $leads->city_id = $validatedData['city_id'];
            $leads->current_assign_user = $validatedData['assign_to'] ?? null;
            $leads->slug = $validatedData['slug'];
            $leads->statuses = 6; // Assuming 6 represents a specific status for leads created from miss campaigns
            $leads->created_by = Auth::id();
            $leads->save();
            
            $validatedData['leads_id'] = $leads->id;
            $campaign = $this->missCampaignService->createMissCampaign($validatedData);
            Lead::where('id', $leads->id)->update(['pre_lead_id' => $campaign->id]);

            return $this->responseService->created(
                new MissCampaignResource($campaign),
                'Miss campaign created successfully'
            );
        } catch (ValidationException $e) {
            dd($e->errors());
            return $this->responseService->validationError(
                $e->errors(),
                'Validation failed'
            );
        } catch (Throwable $e) {
            dd($e->getMessage());
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Update the specified miss campaign in storage.
     *
     * PUT /miss-campaigns/{id}
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $rules = [
                'name' => 'sometimes|required|string|max:255',
                'brand_id' => 'sometimes|required|integer|exists:brands,id',
                'lead_source_id' => 'sometimes|required|integer|exists:lead_source,id',
                'lead_sub_source_id' => 'sometimes|nullable|integer|exists:lead_sub_source,id',
                'media_type_id' => 'sometimes|nullable|integer|exists:media_types,id',
                'industry_id' => 'sometimes|nullable|integer|exists:industries,id',
                'country_id' => 'sometimes|required|integer|exists:countries,id',
                'state_id' => 'sometimes|nullable|integer|exists:states,id',
                'city_id' => 'sometimes|nullable|integer|exists:cities,id',
                'assign_to' => 'sometimes|nullable|integer|exists:users,id',
                'image_path' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp,svg|max:51200',
                'status' => 'sometimes|required|in:1,2,15',
            ];

            $user = auth()->user();
            $orgIds = \App\Support\UserAccessScope::getAccessibleOrganisationIds($user);

            if (count($orgIds) > 1) {
                $rules['organisation_id'] = 'sometimes|required|integer|in:' . implode(',', $orgIds);
            } elseif (count($orgIds) === 1) {
                $rules['organisation_id'] = 'sometimes|nullable|integer|in:' . $orgIds[0];
            } else {
                $rules['organisation_id'] = 'sometimes|nullable|integer';
            }

            $validatedData = $this->validate($request, $rules);

            if (count($orgIds) === 1) {
                $validatedData['organisation_id'] = $orgIds[0];
            }

            $campaign = $this->missCampaignService->getMissCampaign($id);
            if (!$campaign) {
                throw new \Illuminate\Database\Eloquent\ModelNotFoundException();
            }

            // If assign_to is being updated, ensure assign_by is set to current user
            if(!empty($request->assign_to)){
                if (array_key_exists('assign_to', $validatedData)) {
                    $currentAssignTo = $campaign->assign_to;
                    $newAssignTo = $validatedData['assign_to'];
                    
                    // Only update assign_by if assign_to actually changes
                    if ($currentAssignTo != $newAssignTo) {
                        $validatedData['assign_by'] = $newAssignTo ? Auth::id() : null;
                    }
                }
            }

            // Prepare location data for validation (merge validated data with existing campaign data)
            $locationData = [
                'country_id' => array_key_exists('country_id', $validatedData) ? $validatedData['country_id'] : $campaign->country_id,
                'state_id' => array_key_exists('state_id', $validatedData) ? $validatedData['state_id'] : $campaign->state_id,
                'city_id' => array_key_exists('city_id', $validatedData) ? $validatedData['city_id'] : $campaign->city_id,
            ];

            // Clear city_id when state_id is being set to null to prevent hierarchy validation errors
            if (array_key_exists('state_id', $validatedData) && $validatedData['state_id'] === null && !array_key_exists('city_id', $validatedData)) {
                $locationData['city_id'] = null;
                $validatedData['city_id'] = null;
            }

            // Validate location hierarchy consistency
            $locationErrors = $this->validateLocationHierarchy($locationData);
            if ($locationErrors) {
                return $this->responseService->validationError($locationErrors, 'Validation failed');
            }

            // Update slug if name changed
            if ($request->has('name')) {
                $validatedData['slug'] = Str::slug($request->name) . '-' . $id;
            }

            // Handle image upload if present
            if ($request->hasFile('image_path')) {
                $uploadResult = $this->missCampaignService->uploadImage($request->file('image_path'));
                $validatedData['image_path'] = $uploadResult['path'] ?? null;
            }

            $this->missCampaignService->updateMissCampaign($id, $validatedData);

            if (isset($validatedData['organisation_id'])) {
                Lead::where('pre_lead_id', $id)->update([
                    'organisation_id' => $validatedData['organisation_id']
                ]);
            }

            // Fetch updated campaign with relationships
            $campaign = $this->missCampaignService->getMissCampaign($id);

            if (!$campaign) {
                throw new \Illuminate\Database\Eloquent\ModelNotFoundException();
            }

            return $this->responseService->updated(
                new MissCampaignResource($campaign),
                'Miss campaign updated successfully'
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
     * Remove the specified miss campaign from storage (Soft Delete).
     *
     * DELETE /miss-campaigns/{id}
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->missCampaignService->deleteMissCampaign($id);

            return $this->responseService->deleted('Miss campaign deleted successfully');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Get list of miss campaigns (for dropdowns)
     *
     * @return JsonResponse
     */
    public function list(): JsonResponse
    {
        try {
            $campaignsList = $this->missCampaignService->getMissCampaignList();

            return $this->responseService->success(
                $campaignsList,
                'Miss campaign list retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Assign a user to the specified miss campaign.
     *
     * PUT /miss-campaigns/{id}/assign-user
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function assignUser(Request $request, int $id): JsonResponse
    {
        try {
            $rules = [
                'user_id' => 'required|integer|exists:users,id',
            ];

            $validatedData = $this->validate($request, $rules);

            $campaign = $this->missCampaignService->getMissCampaign($id);
            if (!$campaign) {
                throw new \Illuminate\Database\Eloquent\ModelNotFoundException();
            }

            $this->missCampaignService->assignUserToMissCampaign($id, $validatedData['user_id'], Auth::id());

            // Fetch updated campaign with relationships
            $campaign = $this->missCampaignService->getMissCampaign($id);

            if (!$campaign) {
                throw new \Illuminate\Database\Eloquent\ModelNotFoundException();
            }

            return $this->responseService->updated(
                new MissCampaignResource($campaign),
                'User assigned to miss campaign successfully'
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
}