<?php
/**
 * BrandTypeController
 * 
 * @author Achal Sharma
 * @package App\Http\Controllers
 * @version 1.0.1
 */
namespace App\Http\Controllers;

// --- Use your new Service and Resource ---
use App\Services\BrandTypeService; 
use App\Http\Resources\BrandTypeResource;
// --- Use your existing Response Service ---
use App\Services\ResponseService;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class BrandTypeController extends Controller
{
    protected $responseService;
    protected $brandTypeService;

    /**
     * Create a new BrandTypeController instance.
     * 
     * @param BrandTypeService $brandTypeService Service for brand type operations
     * @param ResponseService $responseService Service for standardized API responses
     * @return void
     */
    public function __construct(BrandTypeService $brandTypeService, ResponseService $responseService)
    {
        $this->brandTypeService = $brandTypeService;
        $this->responseService = $responseService;
    }

    /**
     * Display a listing of the brand types.
     * 
     * GET /brand-types
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            // Validate request parameters
            $this->validate($request, [
                'per_page' => 'nullable|integer|min:1',
                'search'   => 'nullable|string|max:100'
            ]);

            // 1. Get pagination and search parameters
            $perPage = $request->get('per_page', 10);
            $searchTerm = $request->get('search', null);

            // 2. Pass parameters to service layer
            $brandTypes = $this->brandTypeService->getAll((int) $perPage, $searchTerm);
            
            // 3. Wrap data with Resource (preserving pagination metadata)
            $resource = BrandTypeResource::collection($brandTypes);

            // 4. Return paginated response using proper method
            return $this->responseService->paginated(
                $resource,
                'Brand types retrieved successfully'
            );

        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors());
        } catch (Throwable $e) {
            return $this->responseService->handleException($e); // Aapka original exception handler
        }
    }

    /**
     * Store a newly created brand type in storage.
     * 
     * POST /brand-types
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255|unique:brand_types,name,NULL,id,deleted_at,NULL',
                'status' => 'null|in:1,2,15',
            ]);

            $validatedData = $validator->validate();

            // Call the service with validated data
            $brandType = $this->brandTypeService->create($validatedData);

            // Format the new model using the Resource
            return $this->responseService->created(
                new BrandTypeResource($brandType), 
                'Brand type created successfully'
            );

        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Display the specified brand type.
     * 
     * GET /brand-types/{id}
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            // Call the service
            $brandType = $this->brandTypeService->findById($id); 

            // Format the model using the Resource
            return $this->responseService->success(
                new BrandTypeResource($brandType), 
                'Brand type retrieved successfully'
            );

        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Update the specified brand type in storage.
     * 
     * PUT /brand-types/{id}
     * 
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            // We find it first to ensure it exists before validating
            $this->brandTypeService->findById($id);

            $validator = Validator::make($request->all(), [
                'name' => [
                    'sometimes',
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('brand_types')->ignore($id)->whereNull('deleted_at'),
                ],
                'status' => 'sometimes|null|in:1,2,15',
            ]);

            $validatedData = $validator->validate();

            // Call the service
            $brandType = $this->brandTypeService->update($id, $validatedData);

            // Format the updated model
            return $this->responseService->updated(
                new BrandTypeResource($brandType), 
                'Brand type updated successfully'
            );

        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Remove the specified brand type from storage (Soft Delete).
     * 
     * DELETE /brand-types/{id}
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            // The service handles the safety check
            $this->brandTypeService->delete($id);

            return $this->responseService->deleted('Brand type deleted successfully');

        } catch (Throwable $e) {
            // This will catch the "Resource in use" exception from the service
            return $this->responseService->handleException($e);
        }
    }
}

