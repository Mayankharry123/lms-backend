<?php
/**
 * ActivityLogController
 * 
 * @author Achal Sharma
 * @package App\Http\Controllers
 * @version 1.0.1
 */
namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Services\ActivityLogService;
use App\Services\ResponseService;
use App\Traits\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Throwable;

class ActivityLogController extends Controller
{
    use ValidatesRequests;

    /**
     * @var ResponseService
     */
    protected ResponseService $responseService;

    /**
     * @var ActivityLogService
     */
    protected ActivityLogService $activityLogService;

    /**
     * Create a new ActivityLogController instance.
     *
     * @param ResponseService $responseService
     * @param ActivityLogService $activityLogService
     */
    public function __construct(ResponseService $responseService, ActivityLogService $activityLogService)
    {
        $this->responseService = $responseService;
        $this->activityLogService = $activityLogService;
    }

    /**
     * Get all activity logs with optional filters
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $this->validate($request, [
                'per_page' => 'nullable|integer|min:1|max:100',
                'user_id' => 'nullable|integer|exists:users,id',
                'model' => 'nullable|string|max:100',
                'action' => 'nullable|string|max:50',
                'model_id' => 'nullable|integer',
                'from_date' => 'nullable|date',
                'to_date' => 'nullable|date|after_or_equal:from_date',
            ]);

            $perPage = $request->input('per_page', 15);
            
            $filters = [
                'user_id' => $request->input('user_id'),
                'model' => $request->input('model'),
                'action' => $request->input('action'),
                'model_id' => $request->input('model_id'),
                'from_date' => $request->input('from_date'),
                'to_date' => $request->input('to_date'),
            ];

            $activityLogs = $this->activityLogService->getAllActivityLogs($perPage, array_filter($filters));

            return $this->responseService->paginated($activityLogs, 'Activity logs retrieved successfully');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Get activity logs for a specific model
     *
     * @param Request $request
     * @param string $model
     * @param int $modelId
     * @return JsonResponse
     */
    public function getModelActivityLogs(Request $request, string $model, int $modelId): JsonResponse
    {
        try {
            $this->validate($request, [
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            $perPage = $request->input('per_page', 15);

            $activityLogs = $this->activityLogService->getModelActivityLogs($model, $modelId, $perPage);

            return $this->responseService->paginated($activityLogs, "Activity logs for {$model} retrieved successfully");
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Get activity logs for a specific user
     *
     * @param Request $request
     * @param int $userId
     * @return JsonResponse
     */
    public function getUserActivityLogs(Request $request, int $userId): JsonResponse
    {
        try {
            $this->validate($request, [
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            $perPage = $request->input('per_page', 15);

            $activityLogs = $this->activityLogService->getUserActivityLogs($userId, $perPage);

            return $this->responseService->paginated($activityLogs, 'User activity logs retrieved successfully');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Get activity logs by action
     *
     * @param Request $request
     * @param string $action
     * @return JsonResponse
     */
    public function getActivityLogsByAction(Request $request, string $action): JsonResponse
    {
        try {
            $this->validate($request, [
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            $perPage = $request->input('per_page', 15);

            $activityLogs = $this->activityLogService->getActivityLogsByAction($action, $perPage);

            return $this->responseService->paginated($activityLogs, "Activity logs for action '{$action}' retrieved successfully");
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Get a single activity log
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $activityLog = ActivityLog::with('user')->find($id);

            if (!$activityLog) {
                return $this->responseService->notFound('Activity log not found');
            }

            return $this->responseService->success($activityLog, 'Activity log retrieved successfully');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Get recent activity logs
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getRecentActivities(Request $request): JsonResponse
    {
        try {
            $this->validate($request, [
                'limit' => 'nullable|integer|min:1|max:100',
            ]);

            $limit = $request->input('limit', 10);

            $activityLogs = $this->activityLogService->getRecentActivities($limit);

            return $this->responseService->success($activityLogs, 'Recent activities retrieved successfully');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Delete old activity logs (cleanup)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteOldActivityLogs(Request $request): JsonResponse
    {
        try {
            $this->validate($request, [
                'days' => 'nullable|integer|min:1',
            ]);

            $days = $request->input('days', 90);

            $deletedCount = $this->activityLogService->deleteOldActivityLogs($days);

            return $this->responseService->success(
                ['deleted_count' => $deletedCount],
                "Deleted {$deletedCount} activity logs older than {$days} days"
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Delete a specific activity log
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $activityLog = ActivityLog::find($id);

            if (!$activityLog) {
                return $this->responseService->notFound('Activity log not found');
            }

            $activityLog->delete();

            return $this->responseService->success(null, 'Activity log deleted successfully');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }
}
