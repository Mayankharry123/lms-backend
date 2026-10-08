<?php

/**
 * Operation History Controller
 * -----------------------------------------
 * Handles queries and listing of operation history records.
 *
 * @package App\Http\Controllers
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-06
 */

namespace App\Http\Controllers;

use App\Http\Resources\OperationHistoryResource;
use App\Services\OperationHistoryService;
use App\Services\ResponseService;
use App\Traits\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class OperationHistoryController extends Controller
{
    use ValidatesRequests;

    protected OperationHistoryService $operationHistoryService;
    protected ResponseService $responseService;

    public function __construct(
        OperationHistoryService $operationHistoryService,
        ResponseService $responseService
    ) {
        $this->operationHistoryService = $operationHistoryService;
        $this->responseService = $responseService;
    }

    /**
     * Get all operation histories with optional filters.
     *
     * GET /operation-histories
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $validated = $this->validate($request, [
                'per_page' => 'nullable|integer|min:1|max:100',
                'operation_id' => 'nullable|integer|exists:operations,id',
                'brief_id' => 'nullable|integer|exists:briefs,id',
                'planner_id' => 'nullable|integer|exists:planners,id',
                'operation_status_id' => 'nullable|integer|exists:operation_statuses,id',
                'assign_by' => 'nullable|integer|exists:users,id',
                'assign_to' => 'nullable|integer|exists:users,id',
                'status' => 'nullable|in:1,2,15',
            ]);

            $perPage = (int) ($validated['per_page'] ?? 15);

            $criteria = array_filter([
                'operation_id' => $validated['operation_id'] ?? null,
                'brief_id' => $validated['brief_id'] ?? null,
                'planner_id' => $validated['planner_id'] ?? null,
                'operation_status_id' => $validated['operation_status_id'] ?? null,
                'assign_by' => $validated['assign_by'] ?? null,
                'assign_to' => $validated['assign_to'] ?? null,
                'status' => $validated['status'] ?? null,
            ], fn ($val) => $val !== null && $val !== '');

            $histories = $this->operationHistoryService->list($criteria, $perPage);

            return $this->responseService->paginated(
                OperationHistoryResource::collection($histories),
                'Operation histories retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Get histories for a specific operation.
     *
     * GET /operation-histories/operation/{operationId}
     */
    public function getOperationHistories(Request $request, int $operationId): JsonResponse
    {
        try {
            $validated = $this->validate($request, [
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            $perPage = (int) ($validated['per_page'] ?? 15);

            $histories = $this->operationHistoryService->getByOperationId($operationId, $perPage);

            return $this->responseService->paginated(
                OperationHistoryResource::collection($histories),
                'Operation histories retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Get single operation history record by ID.
     *
     * GET /operation-histories/{id}
     */
    public function show(int $id): JsonResponse
    {
        try {
            $history = $this->operationHistoryService->find($id);

            if (!$history) {
                return $this->responseService->notFound('Operation history not found');
            }

            return $this->responseService->success(
                new OperationHistoryResource($history),
                'Operation history retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }
}
