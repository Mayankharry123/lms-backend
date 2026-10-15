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

use App\Events\BriefStatusChangedEvent;
use App\Models\Brief;
use App\Models\BriefStatus;
use App\Models\Operation;
use App\Models\OperationHistory;
use App\Models\OperationStatus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

class OperationObserver
{
    /**
     * Handle the Operation "created" event.
     */
    public function created(Operation $operation): void
    {
        try {
            $this->saveHistory($operation, 'created');
            $this->closeBriefIfOperationLive($operation);
        } catch (\Throwable $e) {
            Log::error('Failed to handle operation created: ' . $e->getMessage());
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

            if ($operation->wasChanged('operation_status_id')) {
                $this->closeBriefIfOperationLive($operation);
            }
        } catch (\Throwable $e) {
            Log::error('Failed to handle operation updated: ' . $e->getMessage());
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
        try {
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
        } catch (Throwable $e) {
            Log::error("Failed to save operation history for action {$action}: " . $e->getMessage(), [
                'operation_id' => $operation->id,
                'action' => $action,
                'exception' => $e,
            ]);
        }
    }

    /**
     * Close the linked brief when the operation status is changed to Live.
     */
    private function closeBriefIfOperationLive(Operation $operation): void
    {
        try {
            if (empty($operation->brief_id) || empty($operation->operation_status_id)) {
                return;
            }

            // Verify if the current operation status is 'Live'
            $operationStatus = $operation->operationStatus ?? OperationStatus::find($operation->operation_status_id);
            if (!$operationStatus) {
                return;
            }

            $isLive = strtolower(trim((string) ($operationStatus->slug ?? ''))) === 'live' 
                || strtolower(trim((string) ($operationStatus->name ?? ''))) === 'live';

            if (!$isLive) {
                return;
            }

            $brief = Brief::with('briefStatus')->find($operation->brief_id);
            if (!$brief) {
                return;
            }

            $closedStatus = BriefStatus::where('slug', 'closed')
                ->orWhereRaw('LOWER(name) = ?', ['closed'])
                ->first();

            if (!$closedStatus) {
                Log::warning('Closed brief status not found when closing brief for live operation', [
                    'operation_id' => $operation->id,
                    'brief_id' => $brief->id,
                ]);
                return;
            }

            // If brief is already closed, no action needed
            if ((int) $brief->brief_status_id === (int) $closedStatus->id) {
                return;
            }

            $previousStatusId = $brief->brief_status_id ? (int) $brief->brief_status_id : null;
            $previousStatusName = $brief->briefStatus?->name;

            $brief->brief_status_id = $closedStatus->id;
            if ($closedStatus->priority_id) {
                $brief->priority_id = $closedStatus->priority_id;
            }
            $brief->save();

            Log::info("Brief #{$brief->id} status automatically changed to Closed because Operation #{$operation->id} status became Live", [
                'operation_id' => $operation->id,
                'brief_id' => $brief->id,
                'previous_status_id' => $previousStatusId,
                'closed_status_id' => $closedStatus->id,
            ]);

            $authUser = Auth::user();
            event(new BriefStatusChangedEvent(
                $brief->id,
                $brief->name,
                $previousStatusId,
                $previousStatusName,
                $closedStatus->id,
                $closedStatus->name,
                $authUser ? (int) $authUser->id : null,
                $authUser?->name,
                now()
            ));
        } catch (Throwable $e) {
            Log::error('Failed to close brief for live operation', [
                'operation_id' => $operation->id,
                'brief_id' => $operation->brief_id,
                'error' => $e->getMessage(),
                'exception' => $e,
            ]);
        }
    }
}
