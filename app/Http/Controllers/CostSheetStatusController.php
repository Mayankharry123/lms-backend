<?php

/**
 * CostSheetStatus Controller
 * -----------------------------------------
 * Handles HTTP requests for cost sheet status management, providing CRUD API endpoints.
 *
 * @package App\Http\Controllers
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-05
 */

namespace App\Http\Controllers;

use App\Http\Resources\CostSheetStatusResource;
use App\Services\CostSheetStatusService;
use App\Services\ResponseService;
use App\Traits\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class CostSheetStatusController extends Controller
{
    use ValidatesRequests;

    protected CostSheetStatusService $costSheetStatusService;
    protected ResponseService $responseService;

    /**
     * Inject the cost sheet status service and response service.
     */
    public function __construct(CostSheetStatusService $costSheetStatusService, ResponseService $responseService)
    {
        $this->costSheetStatusService = $costSheetStatusService;
        $this->responseService = $responseService;
    }

    /**
     * Display a listing of the cost sheet statuses.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = (int) $request->get('per_page', 15);
            $criteria = array_filter([
                'q' => $request->input('search'),
                'name' => $request->input('name'),
                'slug' => $request->input('slug'),
                'status' => $request->input('status'),
            ], fn ($value) => $value !== null);

            $costSheetStatuses = $this->costSheetStatusService->list($criteria, $perPage);

            return $this->responseService->paginated(
                CostSheetStatusResource::collection($costSheetStatuses),
                'Cost sheet statuses retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Store a newly created cost sheet status.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $this->validate($request, [
                'name' => 'required|string|max:255|unique:cost_sheet_statuses,name',
                'slug' => 'nullable|string|max:255|unique:cost_sheet_statuses,slug',
                'status' => 'nullable|in:1,2,15',
            ]);

            $costSheetStatus = $this->costSheetStatusService->create($validatedData);

            return $this->responseService->created(
                new CostSheetStatusResource($costSheetStatus),
                'Cost sheet status created successfully'
            );
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'Validation failed');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Display the specified cost sheet status.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $costSheetStatus = $this->costSheetStatusService->find($id);

            if (!$costSheetStatus) {
                return $this->responseService->notFound('Cost sheet status not found');
            }

            return $this->responseService->success(
                new CostSheetStatusResource($costSheetStatus),
                'Cost sheet status retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Update the specified cost sheet status.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $costSheetStatus = $this->costSheetStatusService->find($id);

            if (!$costSheetStatus) {
                return $this->responseService->notFound('Cost sheet status not found');
            }

            $validatedData = $this->validate($request, [
                'name' => 'sometimes|required|string|max:255|unique:cost_sheet_statuses,name,' . $id,
                'slug' => 'sometimes|nullable|string|max:255|unique:cost_sheet_statuses,slug,' . $id,
                'status' => 'sometimes|nullable|in:1,2,15',
            ]);

            $this->costSheetStatusService->update($id, $validatedData);
            $costSheetStatus = $this->costSheetStatusService->find($id);

            return $this->responseService->updated(
                new CostSheetStatusResource($costSheetStatus),
                'Cost sheet status updated successfully'
            );
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'Validation failed');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Soft delete the specified cost sheet status.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $costSheetStatus = $this->costSheetStatusService->find($id);

            if (!$costSheetStatus) {
                return $this->responseService->notFound('Cost sheet status not found');
            }

            $this->costSheetStatusService->delete($id);

            return $this->responseService->deleted('Cost sheet status deleted successfully');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }
}
