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

use App\Http\Resources\CostSheetResource;
use App\Http\Resources\FinanceRecordHistoryResource;
use App\Http\Resources\FinanceRecordResource;
use App\Services\FinanceRecordHistoryService;
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
    protected FinanceRecordHistoryService $financeRecordHistoryService;
    protected ResponseService $responseService;

    /**
     * Inject the finance record service, history service, and response service.
     */
    public function __construct(
        FinanceRecordService $financeRecordService,
        FinanceRecordHistoryService $financeRecordHistoryService,
        ResponseService $responseService
    ) {
        $this->financeRecordService = $financeRecordService;
        $this->financeRecordHistoryService = $financeRecordHistoryService;
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
                'organisation_id' => 'nullable|integer|exists:organisations,id',
                'organisation_ids' => 'nullable',
                'department_id' => 'nullable|integer|exists:departments,id',
                'department_ids' => 'nullable',
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date',
                'search' => 'nullable|string',
            ]);

            $perPage = (int) ($validated['per_page'] ?? 15);
            $criteria = array_filter([
                'brief_id' => $validated['brief_id'] ?? null,
                'planner_id' => $validated['planner_id'] ?? null,
                'finance_status_id' => $validated['finance_status_id'] ?? null,
                'assign_by' => $validated['assign_by'] ?? null,
                'assign_to' => $validated['assign_to'] ?? null,
                'status' => $validated['status'] ?? null,
                'organisation_id' => $validated['organisation_id'] ?? null,
                'organisation_ids' => $validated['organisation_ids'] ?? null,
                'department_id' => $validated['department_id'] ?? null,
                'department_ids' => $validated['department_ids'] ?? null,
                'date_from' => $validated['date_from'] ?? null,
                'date_to' => $validated['date_to'] ?? null,
                'search' => $validated['search'] ?? null,
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

    /**
     * List cost sheets.
     *
     * GET /cost-sheets
     */
    public function costSheets(Request $request): JsonResponse
    {
        try {
            $validated = $this->validate($request, [
                'per_page' => 'nullable|integer|min:1',
                'brief_id' => 'nullable|integer|exists:briefs,id',
                'planner_id' => 'nullable|integer|exists:planners,id',
                'finance_status_id' => 'nullable|integer|exists:finance_statuses,id',
                'assign_by' => 'nullable|integer|exists:users,id',
                'assign_to' => 'nullable|integer|exists:users,id',
                'organisation_id' => 'nullable|integer|exists:organisations,id',
                'organisation_ids' => 'nullable',
                'department_id' => 'nullable|integer|exists:departments,id',
                'department_ids' => 'nullable',
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date',
                'search' => 'nullable|string',
            ]);

            $perPage = (int) ($validated['per_page'] ?? 15);
            $criteria = array_filter([
                'brief_id' => $validated['brief_id'] ?? null,
                'planner_id' => $validated['planner_id'] ?? null,
                'finance_status_id' => $validated['finance_status_id'] ?? null,
                'assign_by' => $validated['assign_by'] ?? null,
                'assign_to' => $validated['assign_to'] ?? null,
                'organisation_id' => $validated['organisation_id'] ?? null,
                'organisation_ids' => $validated['organisation_ids'] ?? null,
                'department_id' => $validated['department_id'] ?? null,
                'department_ids' => $validated['department_ids'] ?? null,
                'date_from' => $validated['date_from'] ?? null,
                'date_to' => $validated['date_to'] ?? null,
                'search' => $validated['search'] ?? null,
            ], fn ($value) => $value !== null && $value !== '');

            $costSheets = $this->financeRecordService->listCostSheets($criteria, $perPage);

            return $this->responseService->paginated(
                CostSheetResource::collection($costSheets),
                'Cost sheets retrieved successfully'
            );
        } catch (Throwable $e) {
            if ($e instanceof ValidationException) {
                return $this->responseService->validationError($e->errors(), 'Validation failed');
            }

            return $this->responseService->handleException($e);
        }
    }

    /**
     * Show one cost sheet.
     *
     * GET /cost-sheets/{id}
     */
    public function showCostSheet(int $id): JsonResponse
    {
        try {
            $costSheet = $this->financeRecordService->findCostSheet($id);

            if (!$costSheet) {
                return $this->responseService->notFound('Cost sheet not found');
            }

            return $this->responseService->success(
                new CostSheetResource($costSheet),
                'Cost sheet retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Update assignment, statuses, and the cost sheet file.
     *
     * PUT /cost-sheets/{id}
     */
    public function updateCostSheet(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $this->validate($request, [
                'assign_by' => 'sometimes|nullable|integer|exists:users,id',
                'assign_to' => 'sometimes|nullable|integer|exists:users,id',
                'finance_status_id' => 'sometimes|required|integer|exists:finance_statuses,id',
                'cost_sheet_status_id' => 'sometimes|required|integer|exists:cost_sheet_statuses,id',
                'cost_sheet' => 'sometimes|file|mimes:xls,xlsx,csv,pdf|max:10240',
            ]);

            $costSheet = $this->financeRecordService->updateCostSheetRecord(
                $id,
                $validated,
                $request->file('cost_sheet')
            );

            if (!$costSheet) {
                return $this->responseService->notFound('Cost sheet not found');
            }

            return $this->responseService->updated(
                new CostSheetResource($costSheet),
                'Cost sheet updated successfully'
            );
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

    /**
     * Update the finance status for one cost sheet.
     *
     * POST /cost-sheets/{id}/update-finance-status
     */
    public function updateFinanceStatus(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $this->validate($request, [
                'finance_status_id' => 'required|integer|exists:finance_statuses,id',
                'comment' => 'nullable|string',
            ]);

            $costSheet = $this->financeRecordService->updateFinanceStatus(
                $id,
                (int) $validated['finance_status_id'],
                $validated['comment'] ?? null
            );

            if (!$costSheet) {
                return $this->responseService->notFound('Cost sheet not found');
            }

            return $this->responseService->updated(
                new CostSheetResource($costSheet),
                'Finance status updated successfully'
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
     * Assign or reassign a finance record / cost sheet.
     *
     * PUT /cost-sheets/{id}/update-assign-user
     * PUT /finance-records/{id}/update-assign-user
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
                'comment' => 'nullable|string',
            ]);

            $costSheet = $this->financeRecordService->updateAssignUser(
                $id,
                (int) $validated['assign_to'],
                (int) $assignBy,
                $validated['comment'] ?? null
            );

            if (!$costSheet) {
                return $this->responseService->notFound('Finance record not found');
            }

            return $this->responseService->success(
                new CostSheetResource($costSheet),
                'Finance record assigned user updated successfully'
            );
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'Validation failed');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Display history for one finance record / cost sheet.
     *
     * GET /finance-records/{id}/histories
     * GET /cost-sheets/{id}/histories
     */
    public function getHistories(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $this->validate($request, [
                'per_page' => 'nullable|integer|min:1',
            ]);

            $perPage = (int) ($validated['per_page'] ?? 15);

            $financeRecord = $this->financeRecordService->find($id);
            if (!$financeRecord) {
                return $this->responseService->notFound('Finance record not found');
            }

            $histories = $this->financeRecordHistoryService->getByFinanceRecordId($id, $perPage);

            return $this->responseService->paginated(
                FinanceRecordHistoryResource::collection($histories),
                'Finance record histories retrieved successfully'
            );
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'Validation failed');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Soft delete a cost sheet.
     *
     * DELETE /cost-sheets/{id}
     */
    public function deleteCostSheet(int $id): JsonResponse
    {
        try {
            $deleted = $this->financeRecordService->deleteCostSheet($id);

            if (!$deleted) {
                return $this->responseService->notFound('Cost sheet not found');
            }

            return $this->responseService->deleted('Cost sheet deleted successfully');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }
}
