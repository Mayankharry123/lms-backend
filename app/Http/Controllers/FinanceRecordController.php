<?php

/**
 * FinanceRecord Controller
 * -----------------------------------------
 * Handles the cost sheet upload for a brief whose plan is approved.
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
     * Upload a cost sheet for a brief.
     *
     * POST /briefs/{briefId}/upload-cost-sheet
     */
    public function uploadCostSheet(Request $request, int $briefId): JsonResponse
    {
        try {
            $this->validate($request, [
                'cost_sheet' => 'required|file|mimes:xls,xlsx,csv,pdf|max:10240',
            ]);

            $financeRecord = $this->financeRecordService->uploadCostSheet($briefId, $request->file('cost_sheet'));

            if (!$financeRecord) {
                return $this->responseService->notFound('Brief not found');
            }

            $resource = new FinanceRecordResource($financeRecord);

            if ($financeRecord->wasRecentlyCreated) {
                return $this->responseService->created($resource, 'Cost sheet uploaded successfully');
            }

            return $this->responseService->updated($resource, 'Cost sheet uploaded successfully');
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'Validation failed');
        } catch (DomainException $e) {
            return $this->responseService->error($e->getMessage(), null, 422, 'DOMAIN_ERROR');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }
}
