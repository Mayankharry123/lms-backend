<?php
/**
 * Interface ActivityLogRepositoryInterface
 * 
 * @author Achal Sharma
 * @package App\Contracts\Repositories
 * @version 1.0.2
 */
namespace App\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use App\Models\ActivityLog;

interface ActivityLogRepositoryInterface
{
    /**
     * Get all activity logs with pagination and filters
     *
     * @param int $perPage
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function getAllActivityLogs(int $perPage = 15, array $filters = []): LengthAwarePaginator;

    /**
     * Get activity logs for a specific model
     *
     * @param string $modelName
     * @param int $modelId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getModelActivityLogs(string $modelName, int $modelId, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get activity logs by a specific user
     *
     * @param int $userId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getUserActivityLogs(int $userId, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get activity logs by action type
     *
     * @param string $action
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getActivityLogsByAction(string $action, int $perPage = 15): LengthAwarePaginator;

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
    ): ActivityLog;

    /**
     * Get recent activity logs
     *
     * @param int $limit
     * @return Collection
     */
    public function getRecentActivityLogs(int $limit = 10): Collection;

    /**
     * Delete old activity logs (for cleanup)
     *
     * @param int $days
     * @return int
     */
    public function deleteOldActivityLogs(int $days = 90): int;
}
