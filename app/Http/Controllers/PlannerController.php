<?php

namespace App\Http\Controllers;

use App\Http\Resources\PlannerResource;
use App\Models\Planner;
use App\Services\PlannerService;
use App\Services\ResponseService;
use App\Traits\ValidatesRequests;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Throwable;
use Illuminate\Validation\ValidationException;

class PlannerController extends Controller
{
    use ValidatesRequests;

    /**
     * @var ResponseService
     */
    protected ResponseService $responseService;

    /**
     * @var PlannerService
     */
    protected PlannerService $plannerService;

    /**
     * Create a new PlannerController instance.
     *
     * @param ResponseService $responseService
     * @param PlannerService $plannerService
     */
    public function __construct(ResponseService $responseService, PlannerService $plannerService)
    {
        $this->responseService = $responseService;
        $this->plannerService = $plannerService;
    }

    /**
     * Display a listing of planners.
     *
     * GET /planners
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $this->validate($request, [
                'per_page' => 'nullable|integer|min:1',
                'brief_id' => 'nullable|integer|exists:briefs,id',
                'created_by' => 'nullable|integer|exists:users,id',
                'status' => 'nullable|in:1,2,15',
                'search' => 'nullable|string|max:255',
            ]);

            $perPage = (int) $request->input('per_page', 10);
            $filters = array_filter([
                'brief_id' => $request->input('brief_id'),
                'created_by' => $request->input('created_by'),
                'status' => $request->input('status'),
                'search' => $request->input('search'),
            ], fn($value) => $value !== null && $value !== '');

            $planners = $this->plannerService->getPlannersByFilters($filters, $perPage);

            return $this->responseService->paginated(
                PlannerResource::collection($planners),
                'Planners retrieved successfully'
            );
        } catch (ValidationException $e) {
            return $this->responseService->validationError(
                $e->errors(),
                'Validation failed'
            );
        } catch (DomainException $e) {
            return $this->responseService->error($e->getMessage(), null, 422, 'DOMAIN_ERROR');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    public function getSubmittedPlans(Request $request): JsonResponse
    {
        try {
            $this->validate($request, [
                'per_page' => 'nullable|integer|min:1',
                'organisation_id' => 'nullable|integer|exists:organisations,id',
                'organisation_ids' => 'nullable|array',
                'organisation_ids.*' => 'integer|exists:organisations,id',
                'department_id' => 'nullable|integer|exists:departments,id',
                'department_ids' => 'nullable|array',
                'department_ids.*' => 'integer|exists:departments,id',
                'brief_id' => 'nullable|integer|exists:briefs,id',
                'created_by' => 'nullable|integer|exists:users,id',
                'planner_status_id' => 'nullable|integer|exists:planner_statuses,id',
                'status' => 'nullable|in:1,2,15',
                'date_from' => 'nullable|date_format:Y-m-d',
                'date_to' => 'nullable|date_format:Y-m-d',
                'search' => 'nullable|string|max:255',
            ]);

            $perPage = (int) ($request->input('limit') ?? $request->input('per_page') ?? 5);
            $filters = array_filter([
                'organisation_id' => $request->input('organisation_id'),
                'organisation_ids' => $request->input('organisation_ids'),
                'department_id' => $request->input('department_id'),
                'department_ids' => $request->input('department_ids'),
                'brief_id' => $request->input('brief_id'),
                'created_by' => $request->input('created_by'),
                'planner_status_id' => $request->input('planner_status_id'),
                'status' => $request->input('status'),
                'date_from' => $request->input('date_from'),
                'date_to' => $request->input('date_to'),
                'search' => $request->input('search'),
            ], fn($value) => $value !== null && $value !== '');

            $submittedPlans = $this->plannerService->getSubmittedPlans($filters, $perPage);

            return $this->responseService->paginated(
                PlannerResource::collection($submittedPlans),
                'Submitted plans retrieved successfully'
            );
        } catch (ValidationException $e) {
            return $this->responseService->validationError(
                $e->errors(),
                'Validation failed'
            );
        } catch (DomainException $e) {
            return $this->responseService->error($e->getMessage(), null, 422, 'DOMAIN_ERROR');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Get latest 5 submitted plans with organisation and department filtering.
     *
     * GET /planners/submitted-plans/latest-five
     *
     * @author Achal Sharma
     * @version 1.0.0
     * @since 2026-10-06
     * @param Request $request
     * @return JsonResponse
     */
    public function getLatestFiveSubmittedPlans(Request $request): JsonResponse
    {
        try {
            $this->validate($request, [
                'limit' => 'nullable|integer|min:1|max:50',
                'organisation_id' => 'nullable|integer|exists:organisations,id',
                'organisation_ids' => 'nullable|array',
                'organisation_ids.*' => 'integer|exists:organisations,id',
                'department_id' => 'nullable|integer|exists:departments,id',
                'department_ids' => 'nullable|array',
                'department_ids.*' => 'integer|exists:departments,id',
                'brief_id' => 'nullable|integer|exists:briefs,id',
                'created_by' => 'nullable|integer|exists:users,id',
                'planner_status_id' => 'nullable|integer|exists:planner_statuses,id',
                'status' => 'nullable|in:1,2,15',
                'date_from' => 'nullable|date_format:Y-m-d',
                'date_to' => 'nullable|date_format:Y-m-d',
                'search' => 'nullable|string|max:255',
            ]);

            $limit = (int) $request->input('limit', 5);
            $filters = array_filter([
                'organisation_id' => $request->input('organisation_id'),
                'organisation_ids' => $request->input('organisation_ids'),
                'department_id' => $request->input('department_id'),
                'department_ids' => $request->input('department_ids'),
                'brief_id' => $request->input('brief_id'),
                'created_by' => $request->input('created_by'),
                'planner_status_id' => $request->input('planner_status_id'),
                'status' => $request->input('status'),
                'date_from' => $request->input('date_from'),
                'date_to' => $request->input('date_to'),
                'search' => $request->input('search'),
            ], fn($value) => $value !== null && $value !== '');

            $submittedPlans = $this->plannerService->getLatestSubmittedPlans($limit, $filters);

            return $this->responseService->success(
                PlannerResource::collection($submittedPlans),
                'Latest submitted plans retrieved successfully'
            );
        } catch (ValidationException $e) {
            return $this->responseService->validationError(
                $e->errors(),
                'Validation failed'
            );
        } catch (DomainException $e) {
            return $this->responseService->error($e->getMessage(), null, 422, 'DOMAIN_ERROR');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Create planner for a specific brief.
     *
     * POST /briefs/{briefId}/planners
     *
     * @param int $briefId
     * @param Request $request
     * @return JsonResponse
     */
    public function createForBrief(int $briefId, Request $request): JsonResponse
    {
        try {
            // Verify brief exists
            $brief = \App\Models\Brief::find($briefId);
            if (!$brief) {
                return $this->responseService->notFound('Brief not found');
            }

            $this->validate($request, [
                'planner_status_id' => 'nullable|integer|exists:planner_statuses,id',
                'submitted_plan' => 'nullable|array|max:2',
                'submitted_plan.*' => 'file|mimes:xls,xlsx,ppt,pptx|max:51200',
                'backup_plan' => 'nullable|file|mimes:xls,xlsx|max:51200',
                'status' => 'nullable|in:1,2',
            ]);

            // Add brief_id to request data
            $data = $request->all();
            $data['brief_id'] = $briefId;

            // Default "Plan Submitted" status is applied in PlannerService::createPlanner
            // so PlannerObserver only records one history row (on created).
            $planner = $this->plannerService->createPlanner(
                $data,
                Auth::id()
            );

            return $this->responseService->success(
                new PlannerResource($planner),
                'Planner created successfully for brief',
                201
            );
        } catch (ValidationException $e) {
            return $this->responseService->validationError(
                $e->errors(),
                'Validation failed'
            );
        } catch (DomainException $e) {
            return $this->responseService->error($e->getMessage(), null, 422, 'DOMAIN_ERROR');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Store a newly created planner.
     *
     * POST /planners
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $this->validate($request, [
                'brief_id' => 'required|integer|exists:briefs,id',
                'planner_status_id' => 'nullable|integer|exists:planner_statuses,id',
                'submitted_plan' => 'nullable|array|max:2',
                'submitted_plan.*' => 'file|mimes:xls,xlsx,ppt,pptx|max:51200',
                'backup_plan' => 'nullable|file|mimes:xls,xlsx|max:51200',
                'status' => 'nullable|in:1,2',
            ]);

            $planner = $this->plannerService->createPlanner(
                $request->all(),
                Auth::id()
            );

            return $this->responseService->success(
                new PlannerResource($planner),
                'Planner created successfully',
                201
            );
        } catch (ValidationException $e) {
            return $this->responseService->validationError(
                $e->errors(),
                'Validation failed'
            );
        } catch (DomainException $e) {
            return $this->responseService->error($e->getMessage(), null, 422, 'DOMAIN_ERROR');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Display the specified planner.
     *
     * GET /planners/{id}
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $planner = $this->plannerService->getPlannerById($id);

            if (!$planner) {
                return $this->responseService->notFound('Planner not found');
            }

            return $this->responseService->success(
                new PlannerResource($planner),
                'Planner retrieved successfully'
            );
        } catch (DomainException $e) {
            return $this->responseService->error($e->getMessage(), null, 422, 'DOMAIN_ERROR');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Update the specified planner.
     *
     * PUT /planners/{id}
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $this->validate($request, [
                'brief_id' => 'nullable|integer|exists:briefs,id',
                'submitted_plan' => 'nullable|array|max:2',
                'submitted_plan.*' => 'file|mimes:xls,xlsx,ppt,pptx|max:51200',
                'backup_plan' => 'nullable|file|mimes:xls,xlsx|max:51200',
                'status' => 'nullable|in:1,2',
            ]);

            $planner = $this->plannerService->updatePlanner($id, $request->all());

            if (!$planner) {
                return $this->responseService->notFound('Planner not found');
            }

            return $this->responseService->success(
                new PlannerResource($planner),
                'Planner updated successfully'
            );
        } catch (ValidationException $e) {
            return $this->responseService->validationError(
                $e->errors(),
                'Validation failed'
            );
        } catch (DomainException $e) {
            return $this->responseService->error($e->getMessage(), null, 422, 'DOMAIN_ERROR');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Delete the specified planner.
     *
     * DELETE /planners/{id}
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $deleted = $this->plannerService->deletePlanner($id);

            if (!$deleted) {
                return $this->responseService->notFound('Planner not found');
            }

            return $this->responseService->success(
                null,
                'Planner deleted successfully'
            );
        } catch (DomainException $e) {
            return $this->responseService->error($e->getMessage(), null, 422, 'DOMAIN_ERROR');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Update planner within brief context.
     *
     * PUT /briefs/{briefId}/planners/{id}
     *
     * @param int $briefId
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function updateForBrief(int $briefId, int $id, Request $request): JsonResponse
    {
        try {
            // Verify brief exists
            $brief = \App\Models\Brief::find($briefId);
            if (!$brief) {
                return $this->responseService->notFound('Brief not found');
            }

            // Verify planner belongs to this brief
            $planner = Planner::where('id', $id)->where('brief_id', $briefId)->first();
            if (!$planner) {
                return $this->responseService->notFound('Planner not found in this brief');
            }

            $this->validate($request, [
                'submitted_plan' => 'nullable|array|max:2',
                'submitted_plan.*' => 'file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx|max:51200',
                'backup_plan' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx|max:51200',
                'status' => 'nullable|in:1,2',
            ]);

            $updatedPlanner = $this->plannerService->updatePlanner($id, $request->all());

            return $this->responseService->success(
                new PlannerResource($updatedPlanner),
                'Planner updated successfully'
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
     * Delete planner within brief context.
     *
     * DELETE /briefs/{briefId}/planners/{id}
     *
     * @param int $briefId
     * @param int $id
     * @return JsonResponse
     */
    public function destroyForBrief(int $briefId, int $id): JsonResponse
    {
        try {
            // Verify brief exists
            $brief = \App\Models\Brief::find($briefId);
            if (!$brief) {
                return $this->responseService->notFound('Brief not found');
            }

            // Verify planner belongs to this brief
            $planner = Planner::where('id', $id)->where('brief_id', $briefId)->first();
            if (!$planner) {
                return $this->responseService->notFound('Planner not found in this brief');
            }

            $deleted = $this->plannerService->deletePlanner($id);

            if (!$deleted) {
                return $this->responseService->notFound('Failed to delete planner');
            }

            return $this->responseService->success(
                null,
                'Planner deleted successfully'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Get single planner by brief ID and planner ID.
     *
     * GET /briefs/{briefId}/planners/{id}
     *
     * @param int $briefId
     * @param int $id
     * @return JsonResponse
     */
    public function showForBrief(int $briefId, int $id): JsonResponse
    {
        try {
            // Verify brief exists
            $brief = \App\Models\Brief::find($briefId);
            if (!$brief) {
                return $this->responseService->notFound('Brief not found');
            }

            // Get planner and verify it belongs to this brief
            $planner = Planner::where('id', $id)->where('brief_id', $briefId)->first();
            if (!$planner) {
                return $this->responseService->notFound('Planner not found in this brief');
            }

            return $this->responseService->success(
                new PlannerResource($planner),
                'Planner retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Get planners by brief ID.
     *
     * GET /briefs/{briefId}/planners
     *
     * @param int $briefId
     * @param Request $request
     * @return JsonResponse
     */
    public function getPlannersByBrief(int $briefId, Request $request): JsonResponse
    {
        try {
            $this->validate($request, [
                'per_page' => 'nullable|integer|min:1',
                'status' => 'nullable|in:1,2,15',
            ]);

            $perPage = (int) $request->input('per_page', 10);
            $status = $request->input('status');

            $planners = $this->plannerService->getPlannersByBriefId($briefId, $perPage, $status);

            return $this->responseService->paginated(
                PlannerResource::collection($planners),
                'Planners retrieved successfully'
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
     * Upload submitted plan files.
     *
     * POST /planners/{id}/upload-submitted-plans
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function uploadSubmittedPlans(Request $request, int $id): JsonResponse
    {
        try {
            $this->validate($request, [
                'files' => 'required|array|max:2',
                'files.*' => 'file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx|max:51200',
            ]);

            $planner = $this->plannerService->addSubmittedPlanFiles($id, $request->file('files'));

            if (!$planner) {
                return $this->responseService->notFound('Planner not found');
            }

            return $this->responseService->success(
                new PlannerResource($planner),
                'Submitted plan files uploaded successfully'
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
     * Upload backup plan file.
     *
     * POST /planners/{id}/upload-backup-plan
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function uploadBackupPlan(Request $request, int $id): JsonResponse
    {
        try {
            $this->validate($request, [
                'file' => 'required|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx|max:51200',
            ]);

            $planner = $this->plannerService->uploadBackupPlanFile($id, $request->file('file'));

            if (!$planner) {
                return $this->responseService->notFound('Planner not found');
            }

            return $this->responseService->success(
                new PlannerResource($planner),
                'Backup plan file uploaded successfully'
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
     * Update planner status.
     *
     * PUT /planners/{id}/update-status
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        try {
            $this->validate($request, [
                'planner_status_id' => 'required|integer|exists:planner_statuses,id',
            ]);

            $planner = $this->plannerService->updatePlannerStatus(
                $id,
                $request->input('planner_status_id')
            );

            if (!$planner) {
                return $this->responseService->notFound('Planner not found');
            }

            return $this->responseService->success(
                new PlannerResource($planner),
                'Planner status updated successfully'
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
}
