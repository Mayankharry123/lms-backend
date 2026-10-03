<?php

/**
 * OperationStatus Controller
 * -----------------------------------------
 * Handles HTTP requests for operation status management, providing CRUD API endpoints.
 *
 * @package App\Http\Controllers
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-03
 */

namespace App\Http\Controllers;

use App\Http\Resources\OperationStatusResource;
use App\Services\OperationStatusService;
use App\Services\ResponseService;
use App\Traits\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class OperationStatusController extends Controller
{
    use ValidatesRequests;

    protected OperationStatusService $operationStatusService;
    protected ResponseService $responseService;

    public function __construct(OperationStatusService $operationStatusService, ResponseService $responseService)
    {
        $this->operationStatusService = $operationStatusService;
        $this->responseService = $responseService;
    }

    /**
     * Display a listing of the operation statuses.
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

            $operationStatuses = $this->operationStatusService->list($criteria, $perPage);
            $resource = OperationStatusResource::collection($operationStatuses);

            return $this->responseService->paginated($resource, 'Operation statuses retrieved successfully');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Store a newly created operation status.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $rules = [
                'name' => 'required|string|max:255|unique:operation_statuses,name',
                'slug' => 'nullable|string|max:255|unique:operation_statuses,slug',
                'status' => 'nullable|in:1,2,15',
            ];
            $validatedData = $this->validate($request, $rules);

            $operationStatus = $this->operationStatusService->create($validatedData);

            return $this->responseService->created(
                new OperationStatusResource($operationStatus),
                'Operation status created successfully'
            );
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'Validation failed');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Display the specified operation status.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $operationStatus = $this->operationStatusService->find($id);

            if (!$operationStatus) {
                return $this->responseService->notFound('Operation status not found');
            }

            return $this->responseService->success(
                new OperationStatusResource($operationStatus),
                'Operation status retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Update the specified operation status.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $operationStatus = $this->operationStatusService->find($id);

            if (!$operationStatus) {
                return $this->responseService->notFound('Operation status not found');
            }

            $rules = [
                'name' => 'sometimes|required|string|max:255|unique:operation_statuses,name,' . $id,
                'slug' => 'sometimes|nullable|string|max:255|unique:operation_statuses,slug,' . $id,
                'status' => 'sometimes|nullable|in:1,2,15',
            ];
            $validatedData = $this->validate($request, $rules);

            $this->operationStatusService->update($id, $validatedData);
            $operationStatus = $this->operationStatusService->find($id);

            return $this->responseService->updated(
                new OperationStatusResource($operationStatus),
                'Operation status updated successfully'
            );
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'Validation failed');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Soft delete the specified operation status.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $operationStatus = $this->operationStatusService->find($id);

            if (!$operationStatus) {
                return $this->responseService->notFound('Operation status not found');
            }

            $this->operationStatusService->delete($id);

            return $this->responseService->deleted('Operation status deleted successfully');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }
}
