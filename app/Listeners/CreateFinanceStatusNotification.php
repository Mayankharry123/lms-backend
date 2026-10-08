<?php
/**
 * CreateFinanceStatusNotification
 *
 * @package App\Listeners
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-08
 */

namespace App\Listeners;

use App\Events\FinanceStatusChangedEvent;
use App\Models\FinanceRecord;
use App\Models\User;
use App\Services\NotificationService;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

class CreateFinanceStatusNotification
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function handle(FinanceStatusChangedEvent $event): void
    {
        try {
            $financeRecord = FinanceRecord::with(['brief', 'planner', 'financeStatus', 'assignedTo', 'assignedBy'])
                ->find($event->getFinanceRecordId());

            if (!$financeRecord) {
                Log::warning('Finance record not found for status notification creation', [
                    'finance_record_id' => $event->getFinanceRecordId(),
                ]);
                return;
            }

            $briefName = $financeRecord->brief?->name ?? 'N/A';
            $updaterName = $event->getUpdatedByUserName() ?? 'System';
            $previousStatus = $event->getPreviousStatusName() ?? 'N/A';
            $newStatus = $event->getNewStatusName() ?? ($financeRecord->financeStatus?->name ?? 'Unknown');
            $timestamp = $event->getTimestamp()
                ? (is_string($event->getTimestamp()) ? $event->getTimestamp() : $event->getTimestamp()->format('Y-m-d h:i:s A'))
                : now()->format('Y-m-d h:i:s A');

            $notifiedUserIds = [];

            // 1. Notify assigned user if not the updater
            if ($financeRecord->assign_to && (int) $financeRecord->assign_to !== (int) $event->getUpdatedByUserId()) {
                $this->notificationService->createNotificationForNotifiable(
                    User::class,
                    $financeRecord->assign_to,
                    'finance_status_changed',
                    [
                        'title' => 'Finance Status Updated',
                        'message' => "Finance Record #{$financeRecord->id} (\"{$briefName}\") status was updated from \"{$previousStatus}\" to \"{$newStatus}\" by {$updaterName}.",
                        'finance_record_id' => $financeRecord->id,
                        'brief_id' => $financeRecord->brief_id,
                        'planner_id' => $financeRecord->planner_id,
                        'brief_name' => $briefName,
                        'previous_status' => $previousStatus,
                        'new_status' => $newStatus,
                        'updated_by' => $updaterName,
                        'updated_by_id' => $event->getUpdatedByUserId(),
                        'comment' => $event->getComment(),
                        'action_url' => "/cost-sheets/{$financeRecord->id}",
                        'timestamp' => $timestamp,
                    ],
                    'finance'
                );
                $notifiedUserIds[] = (int) $financeRecord->assign_to;
            }

            // 2. Notify planner creator (if different from updater and assigned user)
            $plannerCreatorId = $financeRecord->planner?->created_by;
            if ($plannerCreatorId && !in_array((int) $plannerCreatorId, $notifiedUserIds, true) && (int) $plannerCreatorId !== (int) $event->getUpdatedByUserId()) {
                $this->notificationService->createNotificationForNotifiable(
                    User::class,
                    $plannerCreatorId,
                    'finance_status_changed',
                    [
                        'title' => 'Finance Status Updated',
                        'message' => "Finance Record #{$financeRecord->id} for your plan (\"{$briefName}\") status was updated to \"{$newStatus}\" by {$updaterName}.",
                        'finance_record_id' => $financeRecord->id,
                        'brief_id' => $financeRecord->brief_id,
                        'planner_id' => $financeRecord->planner_id,
                        'brief_name' => $briefName,
                        'previous_status' => $previousStatus,
                        'new_status' => $newStatus,
                        'updated_by' => $updaterName,
                        'updated_by_id' => $event->getUpdatedByUserId(),
                        'comment' => $event->getComment(),
                        'action_url' => "/cost-sheets/{$financeRecord->id}",
                        'timestamp' => $timestamp,
                    ],
                    'finance'
                );
                $notifiedUserIds[] = (int) $plannerCreatorId;
            }

            // 3. Notify updater if not already notified
            if ($event->getUpdatedByUserId() && !in_array((int) $event->getUpdatedByUserId(), $notifiedUserIds, true)) {
                $this->notificationService->createNotificationForNotifiable(
                    User::class,
                    $event->getUpdatedByUserId(),
                    'finance_status_changed',
                    [
                        'title' => 'You updated a finance status',
                        'message' => "You changed Finance Record #{$financeRecord->id} (\"{$briefName}\") status from \"{$previousStatus}\" to \"{$newStatus}\".",
                        'finance_record_id' => $financeRecord->id,
                        'brief_id' => $financeRecord->brief_id,
                        'planner_id' => $financeRecord->planner_id,
                        'brief_name' => $briefName,
                        'previous_status' => $previousStatus,
                        'new_status' => $newStatus,
                        'updated_by' => $updaterName,
                        'updated_by_id' => $event->getUpdatedByUserId(),
                        'comment' => $event->getComment(),
                        'action_url' => "/cost-sheets/{$financeRecord->id}",
                        'timestamp' => $timestamp,
                    ],
                    'finance'
                );
                $notifiedUserIds[] = (int) $event->getUpdatedByUserId();
            }

            Log::info('Finance status notification created successfully', [
                'finance_record_id' => $financeRecord->id,
                'notified_users' => $notifiedUserIds,
            ]);
        } catch (QueryException $e) {
            Log::error('Database error creating finance status notification', [
                'finance_record_id' => $event->getFinanceRecordId(),
                'updated_by_user_id' => $event->getUpdatedByUserId(),
                'exception' => $e->getMessage(),
            ]);
        } catch (Exception $e) {
            Log::error('Unexpected error creating finance status notification', [
                'finance_record_id' => $event->getFinanceRecordId(),
                'updated_by_user_id' => $event->getUpdatedByUserId(),
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
