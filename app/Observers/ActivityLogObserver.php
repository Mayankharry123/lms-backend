<?php
/**
 * Activity Log Observer
 *
 * @package App\Observers
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-04-19
 */
namespace App\Observers;

use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;


class ActivityLogObserver
{
    /**
     * @var ActivityLogService
     */
    protected ActivityLogService $activityLogService;

    public function __construct(ActivityLogService $activityLogService)
    {
        $this->activityLogService = $activityLogService;
    }

    /**
     * Handle the model "created" event.
     */
    public function created(Model $model): void
    {
        try {
            $this->activityLogService->logCreated(
                $model,
                $model->getAttributes(),
                null
            );
        } catch (\Exception $e) {
            Log::error('Failed to log activity on created: ' . $e->getMessage());
        }
    }

    /**
     * Handle the model "updated" event.
     */
    public function updated(Model $model): void
    {
        try {
            // Get the original data before update
            $oldData = $model->getOriginal();
            $newData = $model->getAttributes();

            // Only log if there are actual changes
            if ($model->isDirty()) {
                $changes = [];
                foreach ($model->getChanges() as $key => $value) {
                    $changes[$key] = [
                        'old' => $oldData[$key] ?? null,
                        'new' => $value
                    ];
                }

                $this->activityLogService->logUpdated(
                    $model,
                    $oldData,
                    $newData,
                    null
                );
            }
        } catch (\Exception $e) {
            Log::error('Failed to log activity on updated: ' . $e->getMessage());
        }
    }

    /**
     * Handle the model "deleted" event.
     */
    public function deleted(Model $model): void
    {
        try {
            $this->activityLogService->logDeleted(
                $model,
                $model->getAttributes(),
                null
            );
        } catch (\Exception $e) {
            Log::error('Failed to log activity on deleted: ' . $e->getMessage());
        }
    }

    /**
     * Handle the model "restored" event.
     */
    public function restored(Model $model): void
    {
        try {
            $this->activityLogService->logRestored(
                $model,
                null
            );
        } catch (\Exception $e) {
            Log::error('Failed to log activity on restored: ' . $e->getMessage());
        }
    }

    /**
     * Handle the model "force deleted" event.
     */
    public function forceDeleted(Model $model): void
    {
        try {
            $this->activityLogService->logModelActivity(
                $model,
                'force_deleted',
                $model->getAttributes(),
                null,
                class_basename($model) . ' force deleted'
            );
        } catch (\Exception $e) {
            Log::error('Failed to log activity on force deleted: ' . $e->getMessage());
        }
    }
}
