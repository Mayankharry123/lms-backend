<?php
/**
 * Planner Observer
 *
 * @package App\Observers
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-04-19
 */
namespace App\Observers;

use App\Models\Planner;
use App\Models\PlannerHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PlannerObserver
{
    /**
     * Handle the Planner "created" event.
     */
    public function created(Planner $planner): void
    {
        try {
            $this->saveHistory($planner, 'created');
        } catch (\Exception $e) {
            Log::error('Failed to save planner history on created: ' . $e->getMessage());
        }
    }

    /**
     * Handle the Planner "updated" event.
     */
    public function updated(Planner $planner): void
    {
        try {
            Log::info('PlannerObserver updated triggered for planner: ' . $planner->id);
            $this->saveHistory($planner, 'updated');
        } catch (\Exception $e) {
            Log::error('Failed to save planner history on updated: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
        }
    }

    /**
     * Handle the Planner "deleted" event (soft delete).
     */
    public function deleted(Planner $planner): void
    {
        try {
            $this->saveHistory($planner, 'deleted');
        } catch (\Exception $e) {
            Log::error('Failed to save planner history on deleted: ' . $e->getMessage());
        }
    }

    /**
     * Handle the Planner "restored" event.
     */
    public function restored(Planner $planner): void
    {
        try {
            $this->saveHistory($planner, 'restored');
        } catch (\Exception $e) {
            Log::error('Failed to save planner history on restored: ' . $e->getMessage());
        }
    }

    /**
     * Save planner data as history record.
     *
     * @param Planner $planner
     * @param string $action
     * @return void
     */
    private function saveHistory(Planner $planner, string $action): void
    {
        $historyData = [
            'planner_id' => $planner->id,
            'brief_id' => $planner->brief_id,
            'created_by' => $planner->created_by,
            'planner_status_id' => $planner->planner_status_id,
            'status' => $planner->status,
            'submitted_plan' => $planner->submitted_plan,
            'backup_plan' => $planner->backup_plan,
        ];

        // Create history record directly
        try {
            PlannerHistory::create($historyData);
            
            Log::info("Planner history saved for action: {$action}", [
                'planner_id' => $planner->id,
                'planner_uuid' => $planner->uuid,
                'action' => $action,
            ]);
        } catch (\Exception $e) {
            Log::error('Error creating planner history: ' . $e->getMessage());
            throw $e;
        }
    }
}
