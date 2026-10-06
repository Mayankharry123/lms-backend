<?php

/**
 * Operation Controller
 * -----------------------------------------
 * Handles the operations index request and returns the standard API response.
 *
 * @package App\Http\Controllers
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-04
 */

namespace App\Http\Controllers;

use App\Http\Resources\OperationResource;
use App\Services\OperationService;
use App\Services\ResponseService;
use App\Traits\ValidatesRequests;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class OperationController extends Controller
{
    use ValidatesRequests;

    protected OperationService $operationService;
    protected ResponseService $responseService;

    public function __construct(OperationService $operationService, ResponseService $responseService)
    {
        $this->operationService = $operationService;
        $this->responseService = $responseService;
    }

    /**
     * Display a paginated list of operations.
     *
     * GET /operations
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $validated = $this->validate($request, [
                'per_page' => 'nullable|integer|min:1',
                'brief_id' => 'nullable|integer|exists:briefs,id',
                'planner_id' => 'nullable|integer|exists:planners,id',
                'operation_status_id' => 'nullable|integer|exists:operation_statuses,id',
                'assign_by' => 'nullable|integer|exists:users,id',
                'assign_to' => 'nullable|integer|exists:users,id',
                'status' => 'nullable|in:1,2,15',
            ]);

            $perPage = (int) ($validated['per_page'] ?? 15);
            $criteria = array_filter([
                'brief_id' => $validated['brief_id'] ?? null,
                'planner_id' => $validated['planner_id'] ?? null,
                'operation_status_id' => $validated['operation_status_id'] ?? null,
                'assign_by' => $validated['assign_by'] ?? null,
                'assign_to' => $validated['assign_to'] ?? null,
                'status' => $validated['status'] ?? null,
            ], fn ($value) => $value !== null && $value !== '');

            $operations = $this->operationService->list($criteria, $perPage);

            return $this->responseService->paginated(
                OperationResource::collection($operations),
                'Operations retrieved successfully'
            );
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'Validation failed');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Display one operation.
     *
     * GET /operations/{id}
     */
    public function show(int $id): JsonResponse
    {
        try {
            $operation = $this->operationService->find($id);

            if (!$operation) {
                return $this->responseService->notFound('Operation not found');
            }

            return $this->responseService->success(
                new OperationResource($operation),
                'Operation retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Update the operation status for one operation.
     *
     * POST /operations/{id}
     * PUT /operations/{id}
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $this->validate($request, [
                'status' => 'required|integer|exists:operation_statuses,id',
            ]);

            $operation = $this->operationService->updateStatus(
                $id,
                (int) $validated['status']
            );

            if (!$operation) {
                return $this->responseService->notFound('Operation not found');
            }

            return $this->responseService->updated(
                new OperationResource($operation),
                'Operation status updated successfully'
            );
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'Validation failed');
        } catch (DomainException $e) {
            return $this->responseService->error($e->getMessage(), null, 422, 'DOMAIN_ERROR');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Assign or reassign an operation to a user.
     *
     * PUT /operations/{id}/update-assign-user
     */
    public function updateAssignUser(Request $request, int $id): JsonResponse
    {
        try {
            $assignBy = auth()->id();
            if (!$assignBy) {
                return $this->responseService->unauthorized('User not authenticated');
            }

            $validated = $this->validate($request, [
                'assign_to' => 'required|integer|exists:users,id',
            ]);

            $operation = $this->operationService->updateAssignUser(
                $id,
                (int) $validated['assign_to'],
                (int) $assignBy
            );

            if (!$operation) {
                return $this->responseService->notFound('Operation not found');
            }

            return $this->responseService->success(
                new OperationResource($operation),
                'Operation assigned user updated successfully'
            );
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'Validation failed');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Soft delete one operation.
     *
     * DELETE /operations/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $deleted = $this->operationService->delete($id);

            if (!$deleted) {
                return $this->responseService->notFound('Operation not found');
            }

            return $this->responseService->deleted('Operation deleted successfully');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Download the planner backup file for an operation.
     *
     * GET /operations/{id}/backup-plan
     */
    public function downloadBackupPlan(int $id)
    {
        try {
            $operation = $this->operationService->find($id);

            if (!$operation) {
                return $this->responseService->notFound('Operation not found');
            }

            $storedPath = $operation->planner?->backup_plan;
            $fullPath = is_string($storedPath) ? $this->resolveBackupPlanFile($storedPath) : null;

            if (!$fullPath) {
                return $this->responseService->notFound('Backup plan file not found');
            }

            return response()->download($fullPath, basename($fullPath));
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Backup plans are stored under storage/app, sometimes inside the public folder.
     */
    private function resolveBackupPlanFile(string $storedPath): ?string
    {
        try {
            $storedPath = ltrim(str_replace('\\', '/', $storedPath), '/');
            $candidates = [
                storage_path('app/' . $storedPath),
                storage_path('app/public/' . $storedPath),
            ];

            if (str_starts_with($storedPath, 'public/')) {
                $candidates[] = storage_path('app/public/' . substr($storedPath, strlen('public/')));
            }

            $storageRoot = realpath(storage_path('app'));

            foreach ($candidates as $candidate) {
                if (!is_file($candidate)) {
                    continue;
                }

                $realFile = realpath($candidate);

                if ($storageRoot && $realFile && str_starts_with($realFile, $storageRoot)) {
                    return $realFile;
                }
            }

            return null;
        } catch (Throwable $e) {
            return null;
        }
    }
}
