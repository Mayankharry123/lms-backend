<?php

/**
 * ActivityLogService
 * 
 * @author Achal Sharma
 * @package App\Services
 * @version 1.0.1
 */

namespace App\Services;

use App\Repositories\ActivityLogRepository;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;

class ActivityLogService
{
    /**
     * @var ActivityLogRepository
     */
    protected ActivityLogRepository $repository;

    /**
     * @var ResponseService
     */
    protected ResponseService $responseService;
    /**
     * ActivityLogService constructor.
     *
     * @param ActivityLogRepository $repository
     * @param ResponseService $responseService
     */
    public function __construct(ActivityLogRepository $repository, ResponseService $responseService)
    {
        $this->repository = $repository;
        $this->responseService = $responseService;
    }

    /**
     * Get all activity logs with filters
     *
     * @param int $perPage
     * @param array $filters
     * @return mixed
     */
    public function getAllActivityLogs(int $perPage = 15, array $filters = [])
    {
        try {
            return $this->repository->getAllActivityLogs($perPage, $filters);
        } catch (\Exception $e) {
            return $this->responseService->serverError('Failed to fetch activity logs: ' . $e->getMessage());
        }
    }

    /**
     * Get activity logs for a specific model
     *
     * @param string $modelName
     * @param int $modelId
     * @param int $perPage
     * @return mixed
     */
    public function getModelActivityLogs(string $modelName, int $modelId, int $perPage = 15)
    {
        try {
            return $this->repository->getModelActivityLogs($modelName, $modelId, $perPage);
        } catch (\Exception $e) {
            return $this->responseService->serverError('Failed to fetch model activity logs: ' . $e->getMessage());
        }
    }

    /**
     * Get activity logs for a specific user
     *
     * @param int $userId
     * @param int $perPage
     * @return mixed
     */
    public function getUserActivityLogs(int $userId, int $perPage = 15)
    {
        try {
            return $this->repository->getUserActivityLogs($userId, $perPage);
        } catch (\Exception $e) {
            return $this->responseService->serverError('Failed to fetch user activity logs: ' . $e->getMessage());
        }
    }

    /**
     * Log an activity for a model
     *
     * @param Model $model
     * @param string $action
     * @param array|null $oldData
     * @param array|null $newData
     * @param string|null $description
     * @return ActivityLog|null
     */
    public function logModelActivity(
        Model $model,
        string $action,
        ?array $oldData = null,
        ?array $newData = null,
        ?string $description = null
    ): ?ActivityLog {
        try {
            $userId = Auth::check() ? Auth::id() : null;
            $modelName = class_basename($model);

            return $this->repository->logActivity(
                $userId,
                $modelName,
                $model->id,
                $action,
                $description,
                $oldData,
                $newData
            );
        } catch (\Exception $e) {
            Log::error('Failed to log activity: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Log a create activity
     *
     * @param Model $model
     * @param array|null $data
     * @param string|null $description
     * @return ActivityLog|null
     */
    public function logCreated(Model $model, ?array $data = null, ?string $description = null): ?ActivityLog
    {
        return $this->logModelActivity(
            $model,
            'created',
            null,
            $data,
            $description ?? class_basename($model) . ' created'
        );
    }

    /**
     * Log an update activity
     *
     * @param Model $model
     * @param array $oldData
     * @param array $newData
     * @param string|null $description
     * @return ActivityLog|null
     */
    public function logUpdated(Model $model, array $oldData, array $newData, ?string $description = null): ?ActivityLog
    {
        return $this->logModelActivity(
            $model,
            'updated',
            $oldData,
            $newData,
            $description ?? class_basename($model) . ' updated'
        );
    }

    /**
     * Log a delete activity
     *
     * @param Model $model
     * @param array|null $data
     * @param string|null $description
     * @return ActivityLog|null
     */
    public function logDeleted(Model $model, ?array $data = null, ?string $description = null): ?ActivityLog
    {
        return $this->logModelActivity(
            $model,
            'deleted',
            $data,
            null,
            $description ?? class_basename($model) . ' deleted'
        );
    }

    /**
     * Log a restore activity (for soft deletes)
     *
     * @param Model $model
     * @param string|null $description
     * @return ActivityLog|null
     */
    public function logRestored(Model $model, ?string $description = null): ?ActivityLog
    {
        return $this->logModelActivity(
            $model,
            'restored',
            null,
            null,
            $description ?? class_basename($model) . ' restored'
        );
    }

    /**
     * Log a custom activity
     *
     * @param Model $model
     * @param string $action
     * @param string|null $description
     * @param array|null $data
     * @return ActivityLog|null
     */
    public function logCustomAction(
        Model $model,
        string $action,
        ?string $description = null,
        ?array $data = null
    ): ?ActivityLog {
        return $this->logModelActivity(
            $model,
            $action,
            null,
            $data,
            $description
        );
    }

    /**
     * Get recent activities
     *
     * @param int $limit
     * @return mixed
     */
    public function getRecentActivities(int $limit = 10)
    {
        try {
            return $this->repository->getRecentActivityLogs($limit);
        } catch (\Exception $e) {
            return $this->responseService->serverError('Failed to fetch recent activities: ' . $e->getMessage());
        }
    }

    /**
     * Get activity logs by action
     *
     * @param string $action
     * @param int $perPage
     * @return mixed
     */
    public function getActivityLogsByAction(string $action, int $perPage = 15)
    {
        try {
            return $this->repository->getActivityLogsByAction($action, $perPage);
        } catch (\Exception $e) {
            return $this->responseService->serverError('Failed to fetch activity logs by action: ' . $e->getMessage());
        }
    }

    /**
     * Delete old activity logs
     *
     * @param int $days
     * @return int
     */
    public function deleteOldActivityLogs(int $days = 90): int
    {
        try {
            return $this->repository->deleteOldActivityLogs($days);
        } catch (\Exception $e) {
            Log::error('Failed to delete old activity logs: ' . $e->getMessage());
            return 0;
        }
    }
}
