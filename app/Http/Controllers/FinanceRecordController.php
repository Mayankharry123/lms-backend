<?php

/**
 * FinanceRecord Controller
 * -----------------------------------------
 * Handles the cost sheet upload for a planner whose plan is approved.
 *
 * @package App\Http\Controllers
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-04
 */

namespace App\Http\Controllers;

use App\Http\Resources\FinanceRecordResource;
use App\Services\FinanceRecordService;
use App\Services\ResponseService;
use App\Traits\ValidatesRequests;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class FinanceRecordController extends Controller
{
    use ValidatesRequests;

    protected FinanceRecordService $financeRecordService;
    protected ResponseService $responseService;

    /**
     * Inject the finance record service and response service.
     */
    public function __construct(FinanceRecordService $financeRecordService, ResponseService $responseService)
    {
        $this->financeRecordService = $financeRecordService;
        $this->responseService = $responseService;
    }

    /**
     * Display a paginated list of finance records.
     *
     * GET /finance-records
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $validated = $this->validate($request, [
                'per_page' => 'nullable|integer|min:1',
                'brief_id' => 'nullable|integer|exists:briefs,id',
                'planner_id' => 'nullable|integer|exists:planners,id',
                'finance_status_id' => 'nullable|integer|exists:finance_statuses,id',
                'assign_by' => 'nullable|integer|exists:users,id',
                'assign_to' => 'nullable|integer|exists:users,id',
                'status' => 'nullable|in:1,2,15',
            ]);

            $perPage = (int) ($validated['per_page'] ?? 15);
            $criteria = array_filter([
                'brief_id' => $validated['brief_id'] ?? null,
                'planner_id' => $validated['planner_id'] ?? null,
                'finance_status_id' => $validated['finance_status_id'] ?? null,
                'assign_by' => $validated['assign_by'] ?? null,
                'assign_to' => $validated['assign_to'] ?? null,
                'status' => $validated['status'] ?? null,
            ], fn ($value) => $value !== null && $value !== '');

            $financeRecords = $this->financeRecordService->list($criteria, $perPage);

            return $this->responseService->paginated(
                FinanceRecordResource::collection($financeRecords),
                'Finance records retrieved successfully'
            );
        } catch (Throwable $e) {
            if ($e instanceof ValidationException) {
                return $this->responseService->validationError($e->errors(), 'Validation failed');
            }

            return $this->responseService->handleException($e);
        }
    }

    /**
     * Display one finance record.
     *
     * GET /finance-records/{id}
     */
    public function show(int $id): JsonResponse
    {
        try {
            $financeRecord = $this->financeRecordService->find($id);

            if (!$financeRecord) {
                return $this->responseService->notFound('Finance record not found');
            }

            return $this->responseService->success(
                new FinanceRecordResource($financeRecord),
                'Finance record retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Upload a cost sheet for a planner.
     *
     * POST /planners/{plannerId}/upload-cost-sheet
     */
    public function uploadCostSheet(Request $request, int $plannerId): JsonResponse
    {
        try {
            $this->validate($request, [
                'cost_sheet' => 'required|file|mimes:xls,xlsx,csv,pdf|max:10240',
            ]);

            $financeRecord = $this->financeRecordService->uploadCostSheet($plannerId, $request->file('cost_sheet'));

            if (!$financeRecord) {
                return $this->responseService->notFound('Planner not found');
            }

            $resource = new FinanceRecordResource($financeRecord);

            if ($financeRecord->wasRecentlyCreated) {
                return $this->responseService->created($resource, 'Cost sheet uploaded successfully');
            }

            return $this->responseService->updated($resource, 'Cost sheet uploaded successfully');
        } catch (Throwable $e) {
            if ($e instanceof ValidationException) {
                return $this->responseService->validationError($e->errors(), 'Validation failed');
            }

            if ($e instanceof DomainException) {
                return $this->responseService->error($e->getMessage(), null, 422, 'DOMAIN_ERROR');
            }

            return $this->responseService->handleException($e);
        }
    }
}
