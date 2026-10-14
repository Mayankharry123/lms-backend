<?php
/**
 * RegionController
 * 
 * @author Achal Sharma
 * @package App\Http\Controllers
 * @version 1.0.1
 */
namespace App\Http\Controllers;

// --- Use your new Service and Resource ---
use App\Services\RegionService; 
use App\Http\Resources\RegionResource;
// --- Use your existing Response Service ---
use App\Services\ResponseService;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Throwable;

class RegionController extends Controller
{
    protected $responseService;
    protected $regionService;

    /**
     * Inject the RegionService and ResponseService.
     */
    public function __construct(RegionService $regionService, ResponseService $responseService)
    {
        $this->regionService = $regionService;
        $this->responseService = $responseService;
    }

    /**
     * Display a listing of the regions.
     */
    public function index()
    {
        try {
            $regions = $this->regionService->getAll();
            
            return $this->responseService->success(
                RegionResource::collection($regions), 
                'Regions retrieved successfully'
            );

        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Store a newly created region in storage.
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:100|unique:regions,name',
                'translations' => 'nullable|string',
                'flag' => 'sometimes|boolean',
                'wikiDataId' => 'nullable|string|max:255',
            ]);

            $validatedData = $validator->validate();

            $region = $this->regionService->create($validatedData);

            return $this->responseService->created(
                new RegionResource($region), 
                'Region created successfully'
            );

        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Display the specified region.
     */
    public function show($id)
    {
        try {
            $region = $this->regionService->findById($id); 

            return $this->responseService->success(
                new RegionResource($region), 
                'Region retrieved successfully'
            );

        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Update the specified region in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            // Find it first to throw 404 if not found
            $this->regionService->findById($id);

            $validator = Validator::make($request->all(), [
                'name' => [
                    'sometimes',
                    'required',
                    'string',
                    'max:100',
                    Rule::unique('regions')->ignore($id),
                ],
                'translations' => 'sometimes|nullable|string',
                'flag' => 'sometimes|boolean',
                'wikiDataId' => 'sometimes|nullable|string|max:255',
            ]);

            $validatedData = $validator->validate();

            $region = $this->regionService->update($id, $validatedData);

            return $this->responseService->updated(
                new RegionResource($region), 
                'Region updated successfully'
            );

        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Remove the specified region from storage.
     */
    public function destroy($id)
    {
        try {
            // The service handles the safety check
            $this->regionService->delete($id);

            return $this->responseService->deleted('Region deleted successfully');

        } catch (Throwable $e) {
            // This will catch the "Resource in use" exception
            return $this->responseService->handleException($e);
        }
    }
}
