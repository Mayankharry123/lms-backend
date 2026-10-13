<?php
/**
 * BriefStatusController
 * 
 * @author Achal Sharma
 * @package App\Http\Controllers
 * @version 1.0.1
 */
namespace App\Http\Controllers;

use App\Http\Resources\BriefStatusResource;
use App\Services\BriefStatusService;
use App\Services\ResponseService;
use App\Traits\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Throwable;
use Illuminate\Validation\ValidationException;

class BriefStatusController extends Controller
{
    use ValidatesRequests;

    /**
     * @var ResponseService
     */
    protected ResponseService $responseService;

    /**
     * @var BriefStatusService
     */
    protected BriefStatusService $briefStatusService;

    /**
     * Create a new BriefStatusController instance.
     *
     * @param ResponseService $responseService
     * @param BriefStatusService $briefStatusService
     */
    public function __construct(ResponseService $responseService, BriefStatusService $briefStatusService)
    {
        $this->responseService = $responseService;
        $this->briefStatusService = $briefStatusService;
    }

    // ============================================================================
    // READ OPERATIONS
    // ============================================================================

    /**
     * Display a listing of brief statuses.
     *
     * GET /brief-statuses
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            $briefStatuses = $this->briefStatusService->getAllBriefStatuses();

            return $this->responseService->success(
                BriefStatusResource::collection($briefStatuses),
                'Brief statuses retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Display the specified brief status.
     *
     * GET /brief-statuses/{id}
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $briefStatus = $this->briefStatusService->getBriefStatus($id);

            if (!$briefStatus) {
                return $this->responseService->notFound('Brief status not found');
            }

            return $this->responseService->success(
                new BriefStatusResource($briefStatus),
                'Brief status retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Get priorities filtered by brief status ID via query parameter.
     *
     * GET /brief-statuses?brief_status_id={id}
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getPrioritiesByBriefStatus(Request $request): JsonResponse
    {
        try {
            $this->validate($request, [
                'brief_status_id' => 'required|integer|exists:brief_statuses,id',
            ]);

            $briefStatusId = (int) $request->input('brief_status_id');

            // Verify that the brief status exists
            $briefStatus = $this->briefStatusService->getBriefStatus($briefStatusId);
            if (!$briefStatus) {
                return $this->responseService->notFound('Brief status not found');
            }

            // Get the priority associated with this brief status
            $priorities = $this->briefStatusService->getPrioritiesByBriefStatusId($briefStatusId);

            return $this->responseService->success(
                $priorities,
                'Priorities retrieved successfully'
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

    // ============================================================================
    // WRITE OPERATIONS
    // ============================================================================

    /**
     * Store a newly created brief status.
     *
     * POST /brief-statuses
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $this->validate($request, [
                'name' => 'required|string|max:255|unique:brief_statuses,name',
                'slug' => 'required|string|max:255|unique:brief_statuses,slug',
                'priority_id' => 'nullable|integer|exists:priorities,id',
                'status' => 'nullable|in:1,2,15',
            ]);

            $briefStatus = $this->briefStatusService->createBriefStatus($request->all());

            return $this->responseService->created(
                new BriefStatusResource($briefStatus),
                'Brief status created successfully'
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
     * Update the specified brief status.
     *
     * PUT /brief-statuses/{id}
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $this->validate($request, [
                'name' => 'sometimes|string|max:255|unique:brief_statuses,name,' . $id,
                'slug' => 'sometimes|string|max:255|unique:brief_statuses,slug,' . $id,
                'priority_id' => 'nullable|integer|exists:priorities,id',
                'status' => 'nullable|in:1,2,15',
            ]);

            $briefStatus = $this->briefStatusService->updateBriefStatus($id, $request->all());

            if (!$briefStatus) {
                return $this->responseService->notFound('Brief status not found');
            }

            return $this->responseService->success(
                new BriefStatusResource($briefStatus),
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
     * Delete the specified brief status.
     *
     * DELETE /brief-statuses/{id}
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $deleted = $this->briefStatusService->deleteBriefStatus($id);

            if (!$deleted) {
                return $this->responseService->notFound('Brief status not found');
            }

            return $this->responseService->success(
                null,
                'Brief status deleted successfully'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Get brief statuses by priority ID via query parameter.
     *
     * GET /brief-statuses/by-priority?priority_id={id}
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getBriefStatusesByPriority(Request $request): JsonResponse
    {
        try {
            $this->validate($request, [
                'priority_id' => 'required|integer|exists:priorities,id',
                'per_page' => 'nullable|integer|min:1',
            ]);

            $priorityId = (int) $request->input('priority_id');
            $perPage = (int) $request->input('per_page', 10);

            // Get brief statuses associated with this priority
            $briefStatuses = $this->briefStatusService->getBriefStatusesByPriorityId($priorityId, $perPage);

            return $this->responseService->paginated(
                BriefStatusResource::collection($briefStatuses),
                'Brief statuses retrieved successfully'
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

