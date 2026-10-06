<?php

/**
 * Finance Record Observer
 * -----------------------------------------
 * Saves finance record snapshot to finance_record_histories.
 *
 * @package App\Observers
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-06
 */

namespace App\Observers;

use App\Models\FinanceRecord;
use App\Models\FinanceRecordHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class FinanceRecordObserver
{
    /**
     * Handle the FinanceRecord "created" event.
     */
    public function created(FinanceRecord $financeRecord): void
    {
        try {
            $this->saveHistory($financeRecord, 'created');
        } catch (\Throwable $e) {
            Log::error('Failed to save finance record history on created: ' . $e->getMessage());
        }
    }

    /**
     * Handle the FinanceRecord "updated" event.
     */
    public function updated(FinanceRecord $financeRecord): void
    {
        try {
            if ($financeRecord->wasChanged(['finance_status_id', 'assign_to', 'assign_by', 'cost_sheet', 'status']) || !empty($financeRecord->history_comment)) {
                $this->saveHistory($financeRecord, 'updated');
            }
        } catch (\Throwable $e) {
            Log::error('Failed to save finance record history on updated: ' . $e->getMessage());
        }
    }

    /**
     * Handle the FinanceRecord "deleted" event.
     */
    public function deleted(FinanceRecord $financeRecord): void
    {
        try {
            $this->saveHistory($financeRecord, 'deleted');
        } catch (\Throwable $e) {
            Log::error('Failed to save finance record history on deleted: ' . $e->getMessage());
        }
    }

    /**
     * Save finance record snapshot to finance_record_histories.
     */
    private function saveHistory(FinanceRecord $financeRecord, string $action): void
    {
        $comment = $financeRecord->history_comment ?? null;
        if (!$comment) {
            if ($action === 'created') {
                $comment = 'Cost sheet uploaded';
            } elseif ($action === 'deleted') {
                $comment = 'Finance record deleted';
            }
        }

        FinanceRecordHistory::create([
            'finance_record_id' => $financeRecord->id,
            'brief_id' => $financeRecord->brief_id,
            'planner_id' => $financeRecord->planner_id,
            'finance_status_id' => $financeRecord->finance_status_id,
            'cost_sheet' => $financeRecord->cost_sheet,
            'assign_by' => $financeRecord->assign_by ?? (Auth::id() ? (int) Auth::id() : null),
            'assign_to' => $financeRecord->assign_to,
            'comment' => $comment,
            'status' => $action === 'deleted' ? '15' : ($financeRecord->status ?? '1'),
        ]);

        Log::info("Finance record history saved for action: {$action}", [
            'finance_record_id' => $financeRecord->id,
            'action' => $action,
        ]);
    }
}
