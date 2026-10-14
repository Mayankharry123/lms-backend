<?php
/**
 * BrandAgencyRelationshipController
 * 
 * @author Achal Sharma
 * @package App\Http\Controllers
 * @version 1.0.1
 */
namespace App\Http\Controllers;

use App\Models\BrandAgencyRelationship;
use App\Services\ResponseService;
use App\Traits\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Throwable;

class BrandAgencyRelationshipController extends Controller
{
    use ValidatesRequests;

    /**
     * The response service instance
     *
     * @var ResponseService
     */
    protected $responseService;

    /**
     * Constructor
     *
     * @param ResponseService $responseService
     */
    public function __construct(ResponseService $responseService)
    {
        $this->responseService = $responseService;
    }

    /**
     * Display a listing of all Brand-Agency relationships
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            $relationships = BrandAgencyRelationship::with(['brand', 'agency'])->get();
            return $this->responseService->success($relationships, 'Brand-Agency relationships retrieved successfully');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Store a newly created relationship (Attach Brand to Agency)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $rules = [
                'brand_id' => 'required|integer|exists:brands,id', 
                'agency_id' => 'required|integer|exists:agency,id', 
            ];

            $validatedData = $this->validate($request, $rules);

            // Check for existing relationship to prevent duplicate entries
            $exists = BrandAgencyRelationship::where('brand_id', $validatedData['brand_id'])
                                              ->where('agency_id', $validatedData['agency_id'])
                                              ->exists();

            if ($exists) {
                return $this->responseService->error(
                    'Relationship already exists between this Brand and Agency',
                    null,
                    409,
                    'RELATIONSHIP_EXISTS'
                );
            }

            // Create the relationship record
            $relationship = BrandAgencyRelationship::create($validatedData);
            $relationship->load(['brand', 'agency']);

            return $this->responseService->created($relationship, 'Brand and Agency successfully linked');
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'Validation failed');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }
    
    /**
     * Display the specified relationship by its ID
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $relationship = BrandAgencyRelationship::with(['brand', 'agency'])->findOrFail($id);
            return $this->responseService->success($relationship, 'Relationship retrieved successfully');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Update operation is not supported for pivot table
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        return $this->responseService->error(
            'Update is not supported for this pivot resource. Use POST for store/attach and DELETE for destroy/detach.',
            null,
            405,
            'METHOD_NOT_ALLOWED'
        );
    }

    /**
     * Remove the specified relationship (Detach Brand from Agency)
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $relationship = BrandAgencyRelationship::findOrFail($id);
            $relationship->delete(); // Soft Delete

            return $this->responseService->deleted('Relationship successfully detached');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }
}