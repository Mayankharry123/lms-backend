<?php

/**
 * FinanceStatus Controller
 * -----------------------------------------
 * Handles HTTP requests for finance status management, providing CRUD API endpoints.
 *
 * @package App\Http\Controllers
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-04
 */

namespace App\Http\Controllers;

use App\Http\Resources\FinanceStatusResource;
use App\Services\FinanceStatusService;
use App\Services\ResponseService;
use App\Traits\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class FinanceStatusController extends Controller
{
    use ValidatesRequests;

    protected FinanceStatusService $financeStatusService;
    protected ResponseService $responseService;

    /**
     * Inject the finance status service and response service.
     */
    public function __construct(FinanceStatusService $financeStatusService, ResponseService $responseService)
    {
        $this->financeStatusService = $financeStatusService;
        $this->responseService = $responseService;
    }

    /**
     * Display a listing of the finance statuses.
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

            $financeStatuses = $this->financeStatusService->list($criteria, $perPage);

            return $this->responseService->paginated(
                FinanceStatusResource::collection($financeStatuses),
                'Finance statuses retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Store a newly created finance status.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $this->validate($request, [
                'name' => 'required|string|max:255|unique:finance_statuses,name',
                'slug' => 'nullable|string|max:255|unique:finance_statuses,slug',
                'status' => 'nullable|in:1,2,15',
            ]);

            $financeStatus = $this->financeStatusService->create($validatedData);

            return $this->responseService->created(
                new FinanceStatusResource($financeStatus),
                'Finance status created successfully'
            );
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'Validation failed');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Display the specified finance status.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $financeStatus = $this->financeStatusService->find($id);

            if (!$financeStatus) {
                return $this->responseService->notFound('Finance status not found');
            }

            return $this->responseService->success(
                new FinanceStatusResource($financeStatus),
                'Finance status retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Update the specified finance status.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $financeStatus = $this->financeStatusService->find($id);

            if (!$financeStatus) {
                return $this->responseService->notFound('Finance status not found');
            }

            $validatedData = $this->validate($request, [
                'name' => 'sometimes|required|string|max:255|unique:finance_statuses,name,' . $id,
                'slug' => 'sometimes|nullable|string|max:255|unique:finance_statuses,slug,' . $id,
                'status' => 'sometimes|nullable|in:1,2,15',
            ]);

            $this->financeStatusService->update($id, $validatedData);
            $financeStatus = $this->financeStatusService->find($id);

            return $this->responseService->updated(
                new FinanceStatusResource($financeStatus),
                'Finance status updated successfully'
            );
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'Validation failed');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Soft delete the specified finance status.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $financeStatus = $this->financeStatusService->find($id);

            if (!$financeStatus) {
                return $this->responseService->notFound('Finance status not found');
            }

            $this->financeStatusService->delete($id);

            return $this->responseService->deleted('Finance status deleted successfully');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }
}
