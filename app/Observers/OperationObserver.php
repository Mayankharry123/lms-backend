<?php

/**
 * Operation Observer
 * -----------------------------------------
 * OperationObserver save operation snapshot to operation_histories.
 *
 * @package App\Observers
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-06
 */

namespace App\Observers;

use App\Models\Operation;
use App\Models\OperationHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class OperationObserver
{
    /**
     * Handle the Operation "created" event.
     */
    public function created(Operation $operation): void
    {
        try {
            $this->saveHistory($operation, 'created');
        } catch (\Throwable $e) {
            Log::error('Failed to save operation history on created: ' . $e->getMessage());
        }
    }

    /**
     * Handle the Operation "updated" event.
     */
    public function updated(Operation $operation): void
    {
        try {
            if ($operation->wasChanged(['operation_status_id', 'assign_to', 'assign_by', 'status']) || !empty($operation->history_comment)) {
                $this->saveHistory($operation, 'updated');
            }
        } catch (\Throwable $e) {
            Log::error('Failed to save operation history on updated: ' . $e->getMessage());
        }
    }

    /**
     * Handle the Operation "deleted" event.
     */
    public function deleted(Operation $operation): void
    {
        try {
            $this->saveHistory($operation, 'deleted');
        } catch (\Throwable $e) {
            Log::error('Failed to save operation history on deleted: ' . $e->getMessage());
        }
    }

    /**
     * Save operation snapshot to operation_histories.
     */
    private function saveHistory(Operation $operation, string $action): void
    {
        $comment = $operation->history_comment ?? null;
        if (!$comment) {
            if ($action === 'created') {
                $comment = 'Operation created';
            } elseif ($action === 'deleted') {
                $comment = 'Operation deleted';
            }
        }

        OperationHistory::create([
            'operation_id' => $operation->id,
            'brief_id' => $operation->brief_id,
            'planner_id' => $operation->planner_id,
            'operation_status_id' => $operation->operation_status_id,
            'assign_by' => $operation->assign_by ?? (Auth::id() ? (int) Auth::id() : null),
            'assign_to' => $operation->assign_to,
            'comment' => $comment,
            'status' => $action === 'deleted' ? '15' : ($operation->status ?? '1'),
        ]);

        Log::info("Operation history saved for action: {$action}", [
            'operation_id' => $operation->id,
            'action' => $action,
        ]);
    }
}
