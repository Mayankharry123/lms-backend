<?php
/**
 * CreateOperationAssignedNotification
 *
 * @package App\Listeners
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-08
 */

namespace App\Listeners;

use App\Events\OperationAssignedEvent;
use App\Models\Operation;
use App\Models\User;
use App\Services\NotificationService;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

class CreateOperationAssignedNotification
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function handle(OperationAssignedEvent $event): void
    {
        try {
            $operation = Operation::with(['brief', 'planner', 'operationStatus', 'assignedBy', 'assignedTo'])
                ->find($event->getOperationId());

            if (!$operation) {
                Log::warning('Operation not found for notification creation', [
                    'operation_id' => $event->getOperationId(),
                ]);
                return;
            }

            $assignee = User::find($event->getAssignToUserId());
            if (!$assignee) {
                Log::warning('Assignee user not found for operation notification', [
                    'user_id' => $event->getAssignToUserId(),
                ]);
                return;
            }

            $assignedByUser = $event->getAssignedByUserId()
                ? User::find($event->getAssignedByUserId())
                : ($operation->assignedBy ?? null);

            $assignedByName = $assignedByUser?->name ?? 'System';
            $briefName = $operation->brief?->name ?? 'N/A';
            $statusName = $operation->operationStatus?->name ?? 'Pending';
            $isReassignment = $event->getPreviousAssignToUserId()
                && (int) $event->getPreviousAssignToUserId() !== (int) $event->getAssignToUserId();

            // 1. Notify the new assignee
            $title = $isReassignment ? 'Operation Reassigned' : 'Operation Assigned';
            $message = $isReassignment
                ? "Operation #{$operation->id} (\"{$briefName}\") has been reassigned to you by {$assignedByName}."
                : "A new operation #{$operation->id} (\"{$briefName}\") has been assigned to you by {$assignedByName}.";

            $this->notificationService->createNotificationForNotifiable(
                User::class,
                $assignee->id,
                'operation_assigned',
                [
                    'title' => $title,
                    'message' => $message,
                    'operation_id' => $operation->id,
                    'brief_id' => $operation->brief_id,
                    'planner_id' => $operation->planner_id,
                    'brief_name' => $briefName,
                    'status_name' => $statusName,
                    'assigned_by' => $assignedByName,
                    'assigned_by_id' => $assignedByUser?->id,
                    'comment' => $event->getComment(),
                    'action_url' => "/operations/{$operation->id}",
                    'assigned_at' => now()->format('Y-m-d h:i:s A'),
                ],
                'operations'
            );

            // 2. If reassigned from another user, notify the previous assignee
            if ($isReassignment) {
                $prevUser = User::find($event->getPreviousAssignToUserId());
                if ($prevUser) {
                    $this->notificationService->createNotificationForNotifiable(
                        User::class,
                        $prevUser->id,
                        'operation_reassigned',
                        [
                            'title' => 'Operation Reassigned',
                            'message' => "Operation #{$operation->id} (\"{$briefName}\") previously assigned to you has been reassigned to {$assignee->name} by {$assignedByName}.",
                            'operation_id' => $operation->id,
                            'brief_id' => $operation->brief_id,
                            'planner_id' => $operation->planner_id,
                            'brief_name' => $briefName,
                            'reassigned_to' => $assignee->name,
                            'reassigned_to_id' => $assignee->id,
                            'assigned_by' => $assignedByName,
                            'assigned_by_id' => $assignedByUser?->id,
                            'comment' => $event->getComment(),
                            'action_url' => "/operations/{$operation->id}",
                            'reassigned_at' => now()->format('Y-m-d h:i:s A'),
                        ],
                        'operations'
                    );
                }
            }

            Log::info('Operation assigned notification created successfully', [
                'operation_id' => $operation->id,
                'user_id' => $assignee->id,
                'is_reassignment' => $isReassignment,
            ]);
        } catch (QueryException $e) {
            Log::error('Database error creating operation assigned notification', [
                'operation_id' => $event->getOperationId(),
                'user_id' => $event->getAssignToUserId(),
                'exception' => $e->getMessage(),
            ]);
        } catch (Exception $e) {
            Log::error('Unexpected error creating operation assigned notification', [
                'operation_id' => $event->getOperationId(),
                'user_id' => $event->getAssignToUserId(),
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
