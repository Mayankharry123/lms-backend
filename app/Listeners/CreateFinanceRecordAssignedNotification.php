<?php
/**
 * CreateFinanceRecordAssignedNotification
 *
 * @package App\Listeners
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-08
 */

namespace App\Listeners;

use App\Events\FinanceRecordAssignedEvent;
use App\Models\FinanceRecord;
use App\Models\User;
use App\Services\NotificationService;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

class CreateFinanceRecordAssignedNotification
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function handle(FinanceRecordAssignedEvent $event): void
    {
        try {
            $financeRecord = FinanceRecord::with(['brief', 'planner', 'financeStatus', 'assignedBy', 'assignedTo'])
                ->find($event->getFinanceRecordId());

            if (!$financeRecord) {
                Log::warning('Finance record not found for notification creation', [
                    'finance_record_id' => $event->getFinanceRecordId(),
                ]);
                return;
            }

            $assignee = User::find($event->getAssignToUserId());
            if (!$assignee) {
                Log::warning('Assignee user not found for finance record notification', [
                    'user_id' => $event->getAssignToUserId(),
                ]);
                return;
            }

            $assignedByUser = $event->getAssignedByUserId()
                ? User::find($event->getAssignedByUserId())
                : ($financeRecord->assignedBy ?? null);

            $assignedByName = $assignedByUser?->name ?? 'System';
            $briefName = $financeRecord->brief?->name ?? 'N/A';
            $statusName = $financeRecord->financeStatus?->name ?? 'Pending';
            $isReassignment = $event->getPreviousAssignToUserId()
                && (int) $event->getPreviousAssignToUserId() !== (int) $event->getAssignToUserId();

            // 1. Notify the new assignee
            $title = $isReassignment ? 'Finance Record Reassigned' : 'Finance Record Assigned';
            $message = $isReassignment
                ? "Finance Record #{$financeRecord->id} (\"{$briefName}\") has been reassigned to you by {$assignedByName}."
                : "A new Finance Record #{$financeRecord->id} (\"{$briefName}\") has been assigned to you by {$assignedByName}.";

            $this->notificationService->createNotificationForNotifiable(
                User::class,
                $assignee->id,
                'finance_record_assigned',
                [
                    'title' => $title,
                    'message' => $message,
                    'finance_record_id' => $financeRecord->id,
                    'brief_id' => $financeRecord->brief_id,
                    'planner_id' => $financeRecord->planner_id,
                    'brief_name' => $briefName,
                    'status_name' => $statusName,
                    'assigned_by' => $assignedByName,
                    'assigned_by_id' => $assignedByUser?->id,
                    'comment' => $event->getComment(),
                    'action_url' => "/cost-sheets/{$financeRecord->id}",
                    'assigned_at' => now()->format('Y-m-d h:i:s A'),
                ],
                'finance'
            );

            // 2. If reassigned from another user, notify the previous assignee
            if ($isReassignment) {
                $prevUser = User::find($event->getPreviousAssignToUserId());
                if ($prevUser) {
                    $this->notificationService->createNotificationForNotifiable(
                        User::class,
                        $prevUser->id,
                        'finance_record_reassigned',
                        [
                            'title' => 'Finance Record Reassigned',
                            'message' => "Finance Record #{$financeRecord->id} (\"{$briefName}\") previously assigned to you has been reassigned to {$assignee->name} by {$assignedByName}.",
                            'finance_record_id' => $financeRecord->id,
                            'brief_id' => $financeRecord->brief_id,
                            'planner_id' => $financeRecord->planner_id,
                            'brief_name' => $briefName,
                            'reassigned_to' => $assignee->name,
                            'reassigned_to_id' => $assignee->id,
                            'assigned_by' => $assignedByName,
                            'assigned_by_id' => $assignedByUser?->id,
                            'comment' => $event->getComment(),
                            'action_url' => "/cost-sheets/{$financeRecord->id}",
                            'reassigned_at' => now()->format('Y-m-d h:i:s A'),
                        ],
                        'finance'
                    );
                }
            }

            Log::info('Finance record assigned notification created successfully', [
                'finance_record_id' => $financeRecord->id,
                'user_id' => $assignee->id,
                'is_reassignment' => $isReassignment,
            ]);
        } catch (QueryException $e) {
            Log::error('Database error creating finance record assigned notification', [
                'finance_record_id' => $event->getFinanceRecordId(),
                'user_id' => $event->getAssignToUserId(),
                'exception' => $e->getMessage(),
            ]);
        } catch (Exception $e) {
            Log::error('Unexpected error creating finance record assigned notification', [
                'finance_record_id' => $event->getFinanceRecordId(),
                'user_id' => $event->getAssignToUserId(),
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
