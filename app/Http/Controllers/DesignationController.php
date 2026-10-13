<?php
/**
 * DesignationController
 * 
 * @author Achal Sharma
 * @package App\Http\Controllers
 * @version 1.0.1
 */
namespace App\Http\Controllers;

use App\Services\DesignationService;
use App\Services\ResponseService;
use App\Traits\ValidatesRequests;
use App\Http\Resources\DesignationResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Throwable;
use Illuminate\Validation\ValidationException;

class DesignationController extends Controller
{
    use ValidatesRequests;

    /**
     * @var DesignationService
     */
    protected $designationService;

    /**
     * @var ResponseService
     */
    protected $responseService;
    
    /**
     * Create a new DesignationController instance.
     *
     * @param DesignationService $designationService
     * @param ResponseService $responseService
     */
    public function __construct(DesignationService $designationService, ResponseService $responseService)
    {
        $this->designationService = $designationService;
        $this->responseService = $responseService;
    }

    /**
     * Display a listing of designations.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 10);
            $searchTerm = $request->get('search', null);

            $designations = $this->designationService->getAllDesignations((int) $perPage, $searchTerm);
            
            if ($designations->isEmpty() && !($designations instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator && $designations->total() > 0)) {
                return $this->responseService->success([], 'No designations found.');
            }

            return $this->responseService->paginated(
                DesignationResource::collection($designations),
                'Designations fetched successfully.'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Get list of designations with only id and title (e.g., /api/v1/designations/list)
     */
    public function list(): JsonResponse
    {
        try {
            $designations = $this->designationService->getAllDesignations(perPage: 10000);
            $data = $designations->items() ? collect($designations->items())->map(function ($designation) {
                return [
                    'id' => $designation->id,
                    'title' => $designation->title,
                ];
            }) : collect([]);
            return $this->responseService->success($data, 'Designations list retrieved');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Store a newly created designation in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $rules = [
                'title' => 'required|string|max:255|unique:designations,title,NULL,id,deleted_at,NULL',
            ];

            $validatedData = $this->validate($request, $rules);

            $designation = $this->designationService->createNewDesignation($validatedData);
            return $this->responseService->created(new DesignationResource($designation), 'Designation created successfully');
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors());
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Display the specified designation.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $designation = $this->designationService->getDesignation($id);
            return $this->responseService->success(new DesignationResource($designation), 'Designation fetched successfully');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Update the specified designation in storage.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $rules = [
                'title' => 'required|string|max:255|unique:designations,title,' . $id . ',id,deleted_at,NULL',
            ];

            $validatedData = $this->validate($request, $rules);

            $designation = $this->designationService->updateDesignation($id, $validatedData);
            return $this->responseService->updated(new DesignationResource($designation), 'Designation updated successfully');
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors());
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Remove the specified designation from storage.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->designationService->deleteDesignation($id);
            return $this->responseService->deleted('Designation deleted successfully.');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }
}
