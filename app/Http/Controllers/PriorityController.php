<?php
/**
 * PriorityController
 * 
 * @author Achal Sharma
 * @package App\Http\Controllers
 * @version 1.0.1
 */
namespace App\Http\Controllers;

use App\Http\Resources\PriorityResource;
use App\Services\PriorityService;
use App\Services\ResponseService;
use App\Traits\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Throwable;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class PriorityController extends Controller
{
    use ValidatesRequests;

    /**
     * @var ResponseService
     */
    protected ResponseService $responseService;

    /**
     * @var PriorityService
     */
    protected PriorityService $priorityService;

    /**
     * Create a new PriorityController instance.
     *
     * @param ResponseService $responseService
     * @param PriorityService $priorityService
     */
    public function __construct(ResponseService $responseService, PriorityService $priorityService)
    {
        $this->responseService = $responseService;
        $this->priorityService = $priorityService;
    }

    // ============================================================================
    // READ OPERATIONS
    // ============================================================================

    /**
     * Display a listing of the priorities.
     *
     * GET /priorities
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $this->validate($request, [
                'per_page' => 'nullable|integer|min:1',
                'search' => 'nullable|string|max:255',
            ]);

            $perPage = (int) $request->input('per_page', 15);
            $searchTerm = $request->input('search', null);

            $priorities = $this->priorityService->getAllPriorities($perPage, $searchTerm);

            return $this->responseService->paginated(
                PriorityResource::collection($priorities),
                'Priorities retrieved successfully'
            );
        } catch (ValidationException $e) {
            return $this->responseService->validationError(
                $e->errors(),
                'Validation failed'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Display the specified priority.
     *
     * GET /priorities/{id}
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $priority = $this->priorityService->getPriority($id);

            if (!$priority) {
                throw new \Illuminate\Database\Eloquent\ModelNotFoundException();
            }

            return $this->responseService->success(
                new PriorityResource($priority),
                'Priority retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Get call statuses for a specific priority.
     *
     * GET /priorities/{id}/call-statuses
     *
     * @param int $id
     * @return JsonResponse
     */
    public function getCallStatuses(int $id): JsonResponse
    {
        try {
            $priority = $this->priorityService->getPriority($id);

            if (!$priority) {
                throw new \Illuminate\Database\Eloquent\ModelNotFoundException();
            }

            // Get call_status from priority (stored as array/JSON)
            $callStatusIds = $priority->call_status ?? [];
            
            if (empty($callStatusIds)) {
                return $this->responseService->success(
                    [],
                    'No call statuses associated with this priority'
                );
            }

            // Fetch actual call status records (only id and name)
            $callStatuses = DB::table('call_statuses')
                ->whereIn('id', $callStatusIds)
                ->where('status', '1')
                ->select('id', 'name')
                ->get();

            return $this->responseService->success(
                $callStatuses,
                'Call statuses retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Get lead count for a specific priority.
     *
     * GET /priorities/{id}/lead-count
     *
     * @param int $id
     * @return JsonResponse
     */
    public function getLeadCount(int $id, Request $request, \App\Services\LeadService $leadService): JsonResponse
    {
        try {
            $priority = $this->priorityService->getPriority($id);

            if (!$priority) {
                throw new \Illuminate\Database\Eloquent\ModelNotFoundException();
            }

            $filters = \App\Support\DashboardFilters::fromRequest($request);
            $stats = $leadService->getLeadCountStatsForPriority($id, $filters);

            $data = [
                'total_leads' => $stats['total_leads'],
                'priority_lead_count' => $stats['priority_lead_count'],
                'priority_id' => $priority->id,
                'priority_name' => $priority->name,
            ];

            return $this->responseService->success(
                $data,
                'Lead count for priority retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Get brief count for a specific priority.
     *
     * GET /priorities/{id}/brief-count
     *
     * @param int $id
     * @return JsonResponse
     */
    public function getBriefCount(int $id, Request $request, \App\Services\BriefService $briefService): JsonResponse
    {
        try {
            $priority = $this->priorityService->getPriority($id);

            if (!$priority) {
                throw new \Illuminate\Database\Eloquent\ModelNotFoundException();
            }

            $filters = \App\Support\DashboardFilters::fromRequest($request);
            $stats = $briefService->getBriefCountStatsForPriority($id, $filters);

            $data = [
                'total_briefs' => $stats['total_briefs'],
                'priority_brief_count' => $stats['priority_brief_count'],
                'priority_id' => $priority->id,
                'priority_name' => $priority->name,
            ];

            return $this->responseService->success(
                $data,
                'Brief count for priority retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }    // ============================================================================
    // WRITE OPERATIONS
    // ============================================================================

    /**
     * Store a newly created priority in storage.
     *
     * POST /priorities
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $rules = [
                'name' => 'required|string|max:255|unique:priorities,name',
                'call_status' => 'nullable|json',
                'status' => 'nullable|in:1,2,15',
            ];

            $validatedData = $this->validate($request, $rules);

            // Add system-generated fields
            $validatedData['slug'] = Str::slug($request->name);
            $validatedData['status'] = $validatedData['status'] ?? '1';

            $priority = $this->priorityService->createPriority($validatedData);

            return $this->responseService->created(
                new PriorityResource($priority),
                'Priority created successfully'
            );
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'Validation failed');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Update the specified priority in storage.
     *
     * PUT /priorities/{id}
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $rules = [
                'name' => 'sometimes|required|string|max:255|unique:priorities,name,' . $id,
                'call_status' => 'sometimes|nullable|json',
                'status' => 'sometimes|required|in:1,2,15',
            ];

            $validatedData = $this->validate($request, $rules);

            // Update slug if name changed
            if ($request->has('name')) {
                $validatedData['slug'] = Str::slug($request->name);
            }

            $priority = $this->priorityService->updatePriority($id, $validatedData);

            return $this->responseService->updated(
                new PriorityResource($priority),
                'Priority updated successfully'
            );
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'Validation failed');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Remove the specified priority from storage (Soft Delete).
     *
     * DELETE /priorities/{id}
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->priorityService->deletePriority($id);

            return $this->responseService->deleted('Priority deleted successfully');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }
}
