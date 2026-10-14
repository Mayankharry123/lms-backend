<?php
/**
 * PlannerStatusController
 * 
 * @author Achal Sharma
 * @package App\Http\Controllers
 * @version 1.0.1
 */
namespace App\Http\Controllers;

use App\Models\PlannerStatus;
use App\Services\PlannerStatusService;
use App\Services\ResponseService;
use App\Http\Resources\PlannerStatusResource;
use App\Traits\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Throwable;
use Illuminate\Validation\ValidationException;

class PlannerStatusController extends Controller
{
    use ValidatesRequests;

    /**
     * @var PlannerStatusService
     */
    protected PlannerStatusService $plannerStatusService;

    /**
     * @var ResponseService
     */
    protected ResponseService $responseService;

    /**
     * Create a new PlannerStatusController instance.
     *
     * @param PlannerStatusService $plannerStatusService
     * @param ResponseService $responseService
     */
    public function __construct(PlannerStatusService $plannerStatusService, ResponseService $responseService)
    {
        $this->plannerStatusService = $plannerStatusService;
        $this->responseService = $responseService;
    }

    /**
     * Display a listing of the planner statuses.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = (int) $request->get('per_page', 15);
            $criteria = array_filter([
                'q' => $request->input('search'),
                'name' => $request->input('name'),
                'status' => $request->input('status'),
            ], fn($value) => $value !== null);

            $plannerStatuses = $this->plannerStatusService->list($criteria, $perPage);

            // Apply resource collection to paginated results
            $resource = PlannerStatusResource::collection($plannerStatuses);

            return $this->responseService->paginated($resource, 'Planner statuses retrieved successfully');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Store a newly created planner status in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $rules = [
                'name' => 'required|string|max:255|unique:planner_statuses,name',
                'slug' => 'nullable|string|max:255|unique:planner_statuses,slug',
                'status' => 'nullable|in:1,2,15',
            ];
            $validatedData = $this->validate($request, $rules);

            $plannerStatus = $this->plannerStatusService->create($validatedData);

            return $this->responseService->created(
                new PlannerStatusResource($plannerStatus),
                'Planner status created successfully'
            );
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'Validation failed');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Display the specified planner status.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $plannerStatus = $this->plannerStatusService->find($id);

            if (!$plannerStatus) {
                return $this->responseService->notFound('Planner status not found');
            }

            return $this->responseService->success(
                new PlannerStatusResource($plannerStatus),
                'Planner status retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Update the specified planner status in storage.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $plannerStatus = $this->plannerStatusService->find($id);

            if (!$plannerStatus) {
                return $this->responseService->notFound('Planner status not found');
            }

            $rules = [
                'name' => 'sometimes|required|string|max:255|unique:planner_statuses,name,' . $id,
                'slug' => 'sometimes|nullable|string|max:255|unique:planner_statuses,slug,' . $id,
                'status' => 'sometimes|nullable|in:1,2,15',
            ];
            $validatedData = $this->validate($request, $rules);

            $this->plannerStatusService->update($id, $validatedData);

            // Fetch updated planner status
            $plannerStatus = $this->plannerStatusService->find($id);

            return $this->responseService->updated(
                new PlannerStatusResource($plannerStatus),
                'Planner status updated successfully'
            );
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'Validation failed');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Remove the specified planner status from storage.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $plannerStatus = $this->plannerStatusService->find($id);

            if (!$plannerStatus) {
                return $this->responseService->notFound('Planner status not found');
            }

            $this->plannerStatusService->delete($id);

            return $this->responseService->deleted('Planner status deleted successfully');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }
}
