<?php
/**
 * CreateOperationStatusNotification
 *
 * @package App\Listeners
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-08
 */

namespace App\Listeners;

use App\Events\OperationStatusChangedEvent;
use App\Models\Operation;
use App\Models\User;
use App\Services\NotificationService;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

class CreateOperationStatusNotification
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function handle(OperationStatusChangedEvent $event): void
    {
        try {
            $operation = Operation::with(['brief', 'planner', 'operationStatus', 'assignedTo', 'assignedBy'])
                ->find($event->getOperationId());

            if (!$operation) {
                Log::warning('Operation not found for status notification creation', [
                    'operation_id' => $event->getOperationId(),
                ]);
                return;
            }

            $briefName = $operation->brief?->name ?? 'N/A';
            $updaterName = $event->getUpdatedByUserName() ?? 'System';
            $previousStatus = $event->getPreviousStatusName() ?? 'N/A';
            $newStatus = $event->getNewStatusName() ?? ($operation->operationStatus?->name ?? 'Unknown');
            $timestamp = $event->getTimestamp()
                ? (is_string($event->getTimestamp()) ? $event->getTimestamp() : $event->getTimestamp()->format('Y-m-d h:i:s A'))
                : now()->format('Y-m-d h:i:s A');

            $notifiedUserIds = [];

            // 1. Notify assigned user if not the updater
            if ($operation->assign_to && (int) $operation->assign_to !== (int) $event->getUpdatedByUserId()) {
                $this->notificationService->createNotificationForNotifiable(
                    User::class,
                    $operation->assign_to,
                    'operation_status_changed',
                    [
                        'title' => 'Operation Status Updated',
                        'message' => "Operation #{$operation->id} (\"{$briefName}\") status was updated from \"{$previousStatus}\" to \"{$newStatus}\" by {$updaterName}.",
                        'operation_id' => $operation->id,
                        'brief_id' => $operation->brief_id,
                        'planner_id' => $operation->planner_id,
                        'brief_name' => $briefName,
                        'previous_status' => $previousStatus,
                        'new_status' => $newStatus,
                        'updated_by' => $updaterName,
                        'updated_by_id' => $event->getUpdatedByUserId(),
                        'comment' => $event->getComment(),
                        'action_url' => "/operations/{$operation->id}",
                        'timestamp' => $timestamp,
                    ],
                    'operations'
                );
                $notifiedUserIds[] = (int) $operation->assign_to;
            }

            // 2. Notify planner creator (if different from updater and assigned user)
            $plannerCreatorId = $operation->planner?->created_by;
            if ($plannerCreatorId && !in_array((int) $plannerCreatorId, $notifiedUserIds, true) && (int) $plannerCreatorId !== (int) $event->getUpdatedByUserId()) {
                $this->notificationService->createNotificationForNotifiable(
                    User::class,
                    $plannerCreatorId,
                    'operation_status_changed',
                    [
                        'title' => 'Operation Status Updated',
                        'message' => "Operation #{$operation->id} for your plan (\"{$briefName}\") status was updated to \"{$newStatus}\" by {$updaterName}.",
                        'operation_id' => $operation->id,
                        'brief_id' => $operation->brief_id,
                        'planner_id' => $operation->planner_id,
                        'brief_name' => $briefName,
                        'previous_status' => $previousStatus,
                        'new_status' => $newStatus,
                        'updated_by' => $updaterName,
                        'updated_by_id' => $event->getUpdatedByUserId(),
                        'comment' => $event->getComment(),
                        'action_url' => "/operations/{$operation->id}",
                        'timestamp' => $timestamp,
                    ],
                    'operations'
                );
                $notifiedUserIds[] = (int) $plannerCreatorId;
            }

            // 3. Notify updater if not already notified
            if ($event->getUpdatedByUserId() && !in_array((int) $event->getUpdatedByUserId(), $notifiedUserIds, true)) {
                $this->notificationService->createNotificationForNotifiable(
                    User::class,
                    $event->getUpdatedByUserId(),
                    'operation_status_changed',
                    [
                        'title' => 'You updated an operation status',
                        'message' => "You changed Operation #{$operation->id} (\"{$briefName}\") status from \"{$previousStatus}\" to \"{$newStatus}\".",
                        'operation_id' => $operation->id,
                        'brief_id' => $operation->brief_id,
                        'planner_id' => $operation->planner_id,
                        'brief_name' => $briefName,
                        'previous_status' => $previousStatus,
                        'new_status' => $newStatus,
                        'updated_by' => $updaterName,
                        'updated_by_id' => $event->getUpdatedByUserId(),
                        'comment' => $event->getComment(),
                        'action_url' => "/operations/{$operation->id}",
                        'timestamp' => $timestamp,
                    ],
                    'operations'
                );
                $notifiedUserIds[] = (int) $event->getUpdatedByUserId();
            }

            Log::info('Operation status notification created successfully', [
                'operation_id' => $operation->id,
                'notified_users' => $notifiedUserIds,
            ]);
        } catch (QueryException $e) {
            Log::error('Database error creating operation status notification', [
                'operation_id' => $event->getOperationId(),
                'updated_by_user_id' => $event->getUpdatedByUserId(),
                'exception' => $e->getMessage(),
            ]);
        } catch (Exception $e) {
            Log::error('Unexpected error creating operation status notification', [
                'operation_id' => $event->getOperationId(),
                'updated_by_user_id' => $event->getUpdatedByUserId(),
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
