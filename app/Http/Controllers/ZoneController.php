<?php
/**
 * ZoneController
 * 
 * @author Achal Sharma
 * @package App\Http\Controllers
 * @version 1.0.1
 */
namespace App\Http\Controllers;

use App\Models\Zone;
use App\Services\ZoneService;       
use App\Services\ResponseService;  
use App\Http\Resources\ZoneResource; 
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Throwable;

class ZoneController extends Controller
{
    /**
     * @var ZoneService
     */
    protected $zoneService;

    /**
     * @var ResponseService
     */
    protected $responseService;

    /**
     * Create a new ZoneController instance.
     *
     * @param ZoneService $zoneService
     * @param ResponseService $responseService
     */
    public function __construct(ZoneService $zoneService, ResponseService $responseService)
    {
        $this->zoneService = $zoneService;
        $this->responseService = $responseService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $this->validate($request, [
            'per_page' => 'nullable|integer|min:1',
            'search'   => 'nullable|string|max:100' // <-- Search validation
        ]);
            $perPage = (int) $request->query('per_page', 10);
            if ($perPage <= 0) { $perPage = 10; }
            $searchTerm = $request->query('search', null);
            $zones = $this->zoneService->getAllZones($perPage, $searchTerm);
            $data = ZoneResource::collection($zones);
            
            // Use the 'paginated' method from the response service
            return $this->responseService->paginated($data, 'Zones retrieved successfully.');
        
        } catch (ValidationException $e) { // <-- Handle validation errors
            return $this->responseService->validationError($e->errors());
        }   catch (Throwable $e) {
            // Send error to response service handler
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Get all active zones (non-paginated) for dropdowns
     */
    public function getAll(): JsonResponse
    {
        try {
            $zones = $this->zoneService->getActiveZonesList();
            $data = ZoneResource::collection($zones);
            return $this->responseService->success($data, 'Zones list retrieved successfully.');
        } catch (Throwable $e) {
            
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // 1. Validation
            $validatedData = $request->validate([
                'name' => 'required|string|max:255|unique:zones',
                'status' => 'required|in:1,2,15',
            ]);

            // 2. Pass logic to the service layer
            $zone = $this->zoneService->createZone($validatedData);
            $data = new ZoneResource($zone);

            // 3. Use the 'created' method from the response service
            return $this->responseService->created($data, 'Zone created successfully.');

        } catch (Throwable $e) {
            // Handler will manage both ValidationException and other exceptions
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id): JsonResponse
    {
            // Route-model binding will handle 'ModelNotFoundException' here
        // which is already covered in 'handleException'
        try {
            $zone = $this->zoneService->getZoneById($id);
            $data = new ZoneResource($zone);
            return $this->responseService->success($data, 'Zone retrieved successfully.');
        
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $zone = $this->zoneService->getZoneById($id);
            // 1. Validation
            $validatedData = $request->validate([
                'name' => 'required|string|max:255|unique:zones,name,' . $zone->id,
                'status' => 'required|in:1,2,15',
            ]);

            // 2. Pass logic to service layer
            $this->zoneService->updateZone($zone, $validatedData);
            $data = new ZoneResource($zone->fresh()); // fresh() loads the updated data

            // 3. Use the 'updated' method from the response service
            return $this->responseService->updated($data, 'Zone updated successfully.');

        } catch (Throwable $e) {
            // Handler will manage both ValidationException and other exceptions
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $zone = $this->zoneService->getZoneById($id);
            $this->zoneService->deleteZone($zone);
            
            // Use the 'deleted' method from the response service
            return $this->responseService->deleted('Zone deleted successfully.');

        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }
}