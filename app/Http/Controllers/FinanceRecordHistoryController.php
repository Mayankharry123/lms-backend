<?php

/**
 * Finance Record History Controller
 * -----------------------------------------
 * Handles queries and listing of finance record history records.
 *
 * @package App\Http\Controllers
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-06
 */

namespace App\Http\Controllers;

use App\Http\Resources\FinanceRecordHistoryResource;
use App\Services\FinanceRecordHistoryService;
use App\Services\ResponseService;
use App\Traits\ValidatesRequests;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class FinanceRecordHistoryController extends Controller
{
    use ValidatesRequests;

    protected FinanceRecordHistoryService $financeRecordHistoryService;
    protected ResponseService $responseService;

    public function __construct(
        FinanceRecordHistoryService $financeRecordHistoryService,
        ResponseService $responseService
    ) {
        $this->financeRecordHistoryService = $financeRecordHistoryService;
        $this->responseService = $responseService;
    }

    /**
     * Get all finance record histories with optional filters.
     *
     * GET /finance-record-histories
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $validated = $this->validate($request, [
                'per_page' => 'nullable|integer|min:1|max:100',
                'finance_record_id' => 'nullable|integer|exists:finance_records,id',
                'brief_id' => 'nullable|integer|exists:briefs,id',
                'planner_id' => 'nullable|integer|exists:planners,id',
                'finance_status_id' => 'nullable|integer|exists:finance_statuses,id',
                'assign_by' => 'nullable|integer|exists:users,id',
                'assign_to' => 'nullable|integer|exists:users,id',
                'status' => 'nullable|in:1,2,15',
            ]);

            $perPage = (int) ($validated['per_page'] ?? 15);

            $criteria = array_filter([
                'finance_record_id' => $validated['finance_record_id'] ?? null,
                'brief_id' => $validated['brief_id'] ?? null,
                'planner_id' => $validated['planner_id'] ?? null,
                'finance_status_id' => $validated['finance_status_id'] ?? null,
                'assign_by' => $validated['assign_by'] ?? null,
                'assign_to' => $validated['assign_to'] ?? null,
                'status' => $validated['status'] ?? null,
            ], fn ($val) => $val !== null && $val !== '');

            $histories = $this->financeRecordHistoryService->list($criteria, $perPage);

            return $this->responseService->paginated(
                FinanceRecordHistoryResource::collection($histories),
                'Finance record histories retrieved successfully'
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
     * Get histories for a specific finance record.
     *
     * GET /finance-record-histories/finance-record/{financeRecordId}
     */
    public function getByFinanceRecord(Request $request, int $financeRecordId): JsonResponse
    {
        try {
            $validated = $this->validate($request, [
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            $perPage = (int) ($validated['per_page'] ?? 15);

            $histories = $this->financeRecordHistoryService->getByFinanceRecordId($financeRecordId, $perPage);

            return $this->responseService->paginated(
                FinanceRecordHistoryResource::collection($histories),
                'Finance record histories retrieved successfully'
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
     * Get single finance record history record by ID.
     *
     * GET /finance-record-histories/{id}
     */
    public function show(int $id): JsonResponse
    {
        try {
            $history = $this->financeRecordHistoryService->find($id);

            if (!$history) {
                return $this->responseService->notFound('Finance record history not found');
            }

            return $this->responseService->success(
                new FinanceRecordHistoryResource($history),
                'Finance record history retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }
}
