<?php

/**
 * ActivityLogRepository
 * 
 * @author Achal Sharma
 * @package App\Repositories
 * @version 1.0.1
 */

namespace App\Repositories;

use App\Contracts\Repositories\ActivityLogRepositoryInterface;
use App\Models\ActivityLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ActivityLogRepository extends BaseRepository implements ActivityLogRepositoryInterface
{
    /**
     * Get the model class
     */
    protected function getModelClass(): string
    {
        return ActivityLog::class;
    }

    /**
     * Get all activity logs with pagination and filters
     *
     * @param int $perPage
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function getAllActivityLogs(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = ActivityLog::with('user');

        // Filter by user
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        // Filter by model
        if (isset($filters['model'])) {
            $query->where('model', $filters['model']);
        }

        // Filter by action
        if (isset($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        // Filter by model_id
        if (isset($filters['model_id'])) {
            $query->where('model_id', $filters['model_id']);
        }

        // Filter by date range
        if (isset($filters['from_date']) && isset($filters['to_date'])) {
            $query->whereBetween('created_at', [
                $filters['from_date'],
                $filters['to_date']
            ]);
        }

        return $query
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->appends(request()->query());
    }

    /**
     * Get activity logs for a specific model
     *
     * @param string $modelName
     * @param int $modelId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getModelActivityLogs(string $modelName, int $modelId, int $perPage = 15): LengthAwarePaginator
    {
        return ActivityLog::with('user')
            ->forModel($modelName)
            ->where('model_id', $modelId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get activity logs by a specific user
     *
     * @param int $userId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getUserActivityLogs(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return ActivityLog::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get activity logs by action type
     *
     * @param string $action
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getActivityLogsByAction(string $action, int $perPage = 15): LengthAwarePaginator
    {
        return ActivityLog::byAction($action)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Log an activity
     *
     * @param int|null $userId
     * @param string $model
     * @param int $modelId
     * @param string $action
     * @param string|null $description
     * @param array|null $oldData
     * @param array|null $newData
     * @return ActivityLog
     */
    public function logActivity(
        ?int $userId,
        string $model,
        int $modelId,
        string $action,
        ?string $description = null,
        ?array $oldData = null,
        ?array $newData = null
    ): ActivityLog {
        return ActivityLog::create([
            'user_id' => $userId,
            'model' => $model,
            'model_id' => $modelId,
            'action' => $action,
            'description' => $description,
            'old_data' => $oldData,
            'new_data' => $newData,
            'status' => '1', // active by default
        ]);
    }

    /**
     * Get recent activity logs
     *
     * @param int $limit
     * @return Collection
     */
    public function getRecentActivityLogs(int $limit = 10): Collection
    {
        return ActivityLog::with('user')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Delete old activity logs (for cleanup)
     *
     * @param int $days
     * @return int
     */
    public function deleteOldActivityLogs(int $days = 90): int
    {
        return ActivityLog::where('created_at', '<', now()->subDays($days))->delete();
    }
}
