<?php
/**
 * PlannerHistoryController
 * 
 * @author Achal Sharma
 * @package App\Http\Controllers
 * @version 1.0.1
 */
namespace App\Http\Controllers;

use App\Services\PlannerHistoryService;
use App\Services\ResponseService;
use App\Traits\ValidatesRequests;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Throwable;
use Illuminate\Validation\ValidationException;

class PlannerHistoryController extends Controller
{
    use ValidatesRequests;

    /**
     * @var ResponseService
     */
    protected ResponseService $responseService;

    /**
     * @var PlannerHistoryService
     */
    protected PlannerHistoryService $plannerHistoryService;

    /**
     * Create a new PlannerHistoryController instance.
     *
     * @param ResponseService $responseService
     * @param PlannerHistoryService $plannerHistoryService
     */
    public function __construct(ResponseService $responseService, PlannerHistoryService $plannerHistoryService)
    {
        $this->responseService = $responseService;
        $this->plannerHistoryService = $plannerHistoryService;
    }

    /**
     * Get all planner histories with optional filters
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $this->validate($request, [
                'per_page' => 'nullable|integer|min:1|max:100',
                'planner_id' => 'nullable|integer|exists:planners,id',
                'brief_id' => 'nullable|integer|exists:briefs,id',
                'status' => 'nullable|in:1,2,15',
                'created_by' => 'nullable|integer|exists:users,id',
            ]);

            $perPage = $request->input('per_page', 10);

            $filters = [
                'planner_id' => $request->input('planner_id'),
                'brief_id' => $request->input('brief_id'),
                'status' => $request->input('status'),
                'created_by' => $request->input('created_by'),
            ];

            $histories = $this->plannerHistoryService->getAllPlannerHistories($perPage, array_filter($filters));

            return $this->responseService->paginated($histories, 'Planner histories retrieved successfully');
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'Validation failed');
        } catch (DomainException $e) {
            return $this->responseService->error($e->getMessage(), null, 422, 'DOMAIN_ERROR');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Get planner histories for a specific planner
     *
     * @param Request $request
     * @param int $plannerId
     * @return JsonResponse
     */
    public function getPlannerHistories(Request $request, int $plannerId): JsonResponse
    {
        try {
            $this->validate($request, [
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            $perPage = $request->input('per_page', 10);

            $histories = $this->plannerHistoryService->getPlannerHistories($plannerId, $perPage);

            return $this->responseService->paginated($histories, 'Planner histories retrieved successfully');
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'Validation failed');
        } catch (DomainException $e) {
            return $this->responseService->error($e->getMessage(), null, 422, 'DOMAIN_ERROR');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Get planner histories for a specific brief
     *
     * @param Request $request
     * @param int $briefId
     * @return JsonResponse
     */
    public function getBriefPlannerHistories(Request $request, int $briefId): JsonResponse
    {
        try {
            $this->validate($request, [
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            $perPage = $request->input('per_page', 10);

            $histories = $this->plannerHistoryService->getBriefPlannerHistories($briefId, $perPage);

            return $this->responseService->paginated($histories, 'Brief planner histories retrieved successfully');
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'Validation failed');
        } catch (DomainException $e) {
            return $this->responseService->error($e->getMessage(), null, 422, 'DOMAIN_ERROR');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Get planner histories by status
     *
     * @param Request $request
     * @param string $status
     * @return JsonResponse
     */
    public function getByStatus(Request $request, string $status): JsonResponse
    {
        try {
            $this->validate($request, [
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            // Validate status
            if (!in_array($status, ['1', '2', '15'])) {
                return $this->responseService->error('Invalid status. Allowed values: 1, 2, 15', null, 400, 'INVALID_STATUS');
            }

            $perPage = $request->input('per_page', 10);

            $histories = $this->plannerHistoryService->getByStatus($status, $perPage);

            return $this->responseService->paginated($histories, "Planner histories with status '{$status}' retrieved successfully");
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'Validation failed');
        } catch (DomainException $e) {
            return $this->responseService->error($e->getMessage(), null, 422, 'DOMAIN_ERROR');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Get recent planner histories
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getRecentHistories(Request $request): JsonResponse
    {
        try {
            $this->validate($request, [
                'limit' => 'nullable|integer|min:1|max:100',
            ]);

            $limit = $request->input('limit', 10);

            $histories = $this->plannerHistoryService->getRecentHistories($limit);

            return $this->responseService->success($histories, 'Recent planner histories retrieved successfully');
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'Validation failed');
        } catch (DomainException $e) {
            return $this->responseService->error($e->getMessage(), null, 422, 'DOMAIN_ERROR');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }
}
