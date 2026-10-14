<?php
/**
 * StatusController
 * 
 * @author Achal Sharma
 * @package App\Http\Controllers
 * @version 1.0.1
 */
namespace App\Http\Controllers;

use App\Http\Resources\StatusResource;
use App\Services\StatusService;
use App\Services\ResponseService;
use App\Traits\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Throwable;
use Illuminate\Validation\ValidationException;

class StatusController extends Controller
{
    use ValidatesRequests;

    /**
     * @var ResponseService
     */
    protected ResponseService $responseService;

    /**
     * @var StatusService
     */
    protected StatusService $statusService;

    /**
     * Create a new StatusController instance.
     *
     * @param ResponseService $responseService
     * @param StatusService $statusService
     */
    public function __construct(ResponseService $responseService, StatusService $statusService)
    {
        $this->responseService = $responseService;
        $this->statusService = $statusService;
    }

    // ============================================================================
    // READ OPERATIONS
    // ============================================================================

    /**
     * Display a listing of the statuses.
     *
     * GET /statuses
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

            $statuses = $this->statusService->getAllStatuses($perPage, $searchTerm);

            return $this->responseService->paginated(
                StatusResource::collection($statuses),
                'Statuses retrieved successfully'
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
     * Display the specified status.
     *
     * GET /statuses/{id}
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $status = $this->statusService->getStatus($id);

            if (!$status) {
                throw new \Illuminate\Database\Eloquent\ModelNotFoundException();
            }

            return $this->responseService->success(
                new StatusResource($status),
                'Status retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    // ============================================================================
    // WRITE OPERATIONS
    // ============================================================================

    /**
     * Store a newly created status in storage.
     *
     * POST /statuses
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $rules = [
                'name' => 'required|string|max:255|unique:statuses,name',
                'call_status' => 'nullable|json',
                'status' => 'nullable|in:1,2,15',
            ];

            $validatedData = $this->validate($request, $rules);

            // Add system-generated fields
            $validatedData['slug'] = Str::slug($request->name);
            $validatedData['status'] = $validatedData['status'] ?? '1';

            $status = $this->statusService->createStatus($validatedData);

            return $this->responseService->created(
                new StatusResource($status),
                'Status created successfully'
            );
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'Validation failed');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Update the specified status in storage.
     *
     * PUT /statuses/{id}
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $rules = [
                'name' => 'sometimes|required|string|max:255|unique:statuses,name,' . $id,
                'call_status' => 'sometimes|nullable|json',
                'status' => 'sometimes|required|in:1,2,15',
            ];

            $validatedData = $this->validate($request, $rules);

            // Update slug if name changed
            if ($request->has('name')) {
                $validatedData['slug'] = Str::slug($request->name);
            }

            $status = $this->statusService->updateStatus($id, $validatedData);

            return $this->responseService->updated(
                new StatusResource($status),
                'Status updated successfully'
            );
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'Validation failed');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Remove the specified status from storage (Soft Delete).
     *
     * DELETE /statuses/{id}
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->statusService->deleteStatus($id);

            return $this->responseService->deleted('Status deleted successfully');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }
}
