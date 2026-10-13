<?php
/**
 * AgencyTypeController
 * 
 * @author Achal Sharma
 * @package App\Http\Controllers
 * @version 1.0.1
 */
namespace App\Http\Controllers;

use App\Http\Resources\AgencyTypeResource;
use App\Services\AgencyTypeService;
use App\Services\ResponseService;
use App\Traits\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Throwable;
use Illuminate\Validation\ValidationException;

class AgencyTypeController extends Controller
{
    use ValidatesRequests;

    /**
     * @var ResponseService
     */
    protected ResponseService $responseService;

    /**
     * @var AgencyTypeService
     */
    protected AgencyTypeService $agencyTypeService;

    /**
     * Create a new AgencyTypeController instance.
     *
     * @param ResponseService $responseService
     * @param AgencyTypeService $agencyTypeService
     */
    public function __construct(ResponseService $responseService, AgencyTypeService $agencyTypeService)
    {
        $this->responseService = $responseService;
        $this->agencyTypeService = $agencyTypeService;
    }

    // ============================================================================
    // READ OPERATIONS
    // ============================================================================

    /**
     * Display a listing of the agency types.
     *
     * GET /agency-types
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
            $searchTerm = $request->input('search', null);

            $agencyTypes = $this->agencyTypeService->getAllAgencyTypes($perPage, $searchTerm);

            return $this->responseService->paginated(
                AgencyTypeResource::collection($agencyTypes),
                'Agency types retrieved successfully'
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
     * Display the specified agency type.
     *
     * GET /agency-types/{id}
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $agencyType = $this->agencyTypeService->getAgencyType($id);

            if (!$agencyType) {
                throw new \Illuminate\Database\Eloquent\ModelNotFoundException();
            }

            return $this->responseService->success(
                new AgencyTypeResource($agencyType),
                'Agency type retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    // ============================================================================
    // WRITE OPERATIONS
    // ============================================================================

    /**
     * Store a newly created agency type in storage.
     *
     * POST /agency-types
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $rules = [
                'name' => 'required|string|max:255|unique:agency_type,name',
                'status' => 'nullable|in:1,2,15',
            ];

            $validatedData = $this->validate($request, $rules);

            // Add system-generated fields
            $validatedData['slug'] = Str::slug($request->name);
            $validatedData['status'] = $validatedData['status'] ?? '1';

            $agencyType = $this->agencyTypeService->createAgencyType($validatedData);

            return $this->responseService->created(
                new AgencyTypeResource($agencyType),
                'Agency type created successfully'
            );
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'Validation failed');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Update the specified agency type in storage.
     *
     * PUT /agency-types/{id}
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $rules = [
                'name' => 'sometimes|required|string|max:255|unique:agency_type,name,' . $id,
                'status' => 'sometimes|required|in:1,2,15',
            ];

            $validatedData = $this->validate($request, $rules);

            // Update slug if name changed
            if ($request->has('name')) {
                $validatedData['slug'] = Str::slug($request->name);
            }

            $agencyType = $this->agencyTypeService->updateAgencyType($id, $validatedData);

            return $this->responseService->updated(
                new AgencyTypeResource($agencyType),
                'Agency type updated successfully'
            );
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'Validation failed');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Remove the specified agency type from storage (Soft Delete).
     *
     * DELETE /agency-types/{id}
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->agencyTypeService->deleteAgencyType($id);

            return $this->responseService->deleted('Agency type deleted successfully');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }
}