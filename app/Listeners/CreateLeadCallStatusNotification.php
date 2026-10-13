<?php
/**
 * Listener CreateLeadCallStatusNotification
 * 
 * @author Achal Sharma
 * @package App\Listeners
 * @version 1.0.1
 */
namespace App\Listeners;

use App\Events\LeadCallStatusAddedEvent;
use App\Services\NotificationService;
use App\Models\Lead;
use App\Models\Status;
use App\Models\Priority;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Database\QueryException;

class CreateLeadCallStatusNotification
{
    /**
     * The notification service instance.
     *
     * @var NotificationService
     */
    protected $notificationService;

    /**
     * Create the event listener.
     *
     * @param NotificationService $notificationService
     * @return void
     */
    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle the event.
     *
     * @param LeadCallStatusAddedEvent $event
     * @return void
     */
    public function handle(LeadCallStatusAddedEvent $event)
    {
        try {
            $lead = Lead::find($event->getLeadId());
            if (!$lead) {
                return;
            }

            $callStatus = \App\Models\CallStatus::find($event->getCallStatusId());
            if (!$callStatus) {
                Log::warning('CallStatus not found for lead call status notification', [
                    'call_status_id' => $event->getCallStatusId(),
                    'lead_id' => $event->getLeadId(),
                    'updated_by_user_id' => $event->getUpdatedByUserId()
                ]);
                return;
            }
            $prevCallStatus = $event->getPreviousCallStatusId() ? \App\Models\CallStatus::find($event->getPreviousCallStatusId()) : null;
            $status = $lead->lead_status ? Status::find($lead->lead_status) : null;
            $priority = $lead->priority_id ? Priority::find($lead->priority_id) : null;
            $updater = $event->getUpdatedByUserId() ? \App\Models\User::find($event->getUpdatedByUserId()) : null;
            $timestamp = $event->getTimestamp();

            $notifiedUserIds = [];

            // Notify assigned user if not the updater
            $assignedUser = $lead->current_assign_user ? \App\Models\User::find($lead->current_assign_user) : null;
            if ($assignedUser && $assignedUser->id != $event->getUpdatedByUserId()) {
                $this->notificationService->createNotificationForNotifiable(
                    \App\Models\User::class,
                    $assignedUser->id,
                    'lead_call_status_added',
                    [
                        'title' => 'Call Status Updated',
                        'lead_id' => $lead->id,
                        'previous_status' => $prevCallStatus ? $prevCallStatus->name : null,
                        'new_status' => $callStatus->name,
                        'updated_by' => $updater ? $updater->name : null,
                        'updated_by_id' => $updater ? $updater->id : null,
                        'timestamp' => $timestamp,
                        'message' => 'Lead #' . $lead->id . ' call status changed from "' . ($prevCallStatus ? $prevCallStatus->name : 'N/A') . '" to "' . $callStatus->name . '" by ' . ($updater ? $updater->name : 'Unknown') . ' at ' . ($timestamp ? $timestamp : now()) . '.',
                        'name' => $lead->name,
                        'status_name' => $status ? $status->name : null,
                        'priority_name' => $priority ? $priority->name : null
                    ],
                    'lead'
                );
                $notifiedUserIds[] = $assignedUser->id;
            }

            // Notify updater if not already notified
            if ($updater && !in_array($updater->id, $notifiedUserIds)) {
                $this->notificationService->createNotificationForNotifiable(
                    \App\Models\User::class,
                    $updater->id,
                    'lead_call_status_added',
                    [
                        'title' => 'You updated a lead call status',
                        'lead_id' => $lead->id,
                        'previous_status' => $prevCallStatus ? $prevCallStatus->name : null,
                        'new_status' => $callStatus->name,
                        'updated_by' => $updater ? $updater->name : null,
                        'updated_by_id' => $updater ? $updater->id : null,
                        'timestamp' => $timestamp,
                        'message' => 'You changed lead #' . $lead->id . ' call status from "' . ($prevCallStatus ? $prevCallStatus->name : 'N/A') . '" to "' . $callStatus->name . '" at ' . ($timestamp ? $timestamp : now()) . '.',
                        'name' => $lead->name,
                        'status_name' => $status ? $status->name : null,
                        'priority_name' => $priority ? $priority->name : null
                    ],
                    'lead'
                );
                $notifiedUserIds[] = $updater->id;
            }

            // Log when no recipients found for notification
            if (empty($notifiedUserIds)) {
                Log::info('Call status update recorded but no eligible recipients for notification', [
                    'lead_id' => $lead->id,
                    'call_status_id' => $event->getCallStatusId(),
                    'has_assigned_user' => $assignedUser ? 'yes' : 'no',
                    'assigned_user_id' => $lead->current_assign_user,
                    'has_updater' => $updater ? 'yes' : 'no',
                    'updated_by_user_id' => $event->getUpdatedByUserId(),
                    'timestamp' => $timestamp
                ]);
            }
            
            // Optionally: Add logic here to notify other stakeholders (e.g., admins/managers) if needed
        } catch (QueryException $e) {
            Log::error('Database error creating lead call status notification', [
                'lead_id' => $event->getLeadId(),
                'call_status_id' => $event->getCallStatusId(),
                'updated_by_user_id' => $event->getUpdatedByUserId(),
                'exception' => $e->getMessage()
            ]);
        } catch (Exception $e) {
            Log::error('Unexpected error creating lead call status notification', [
                'lead_id' => $event->getLeadId(),
                'call_status_id' => $event->getCallStatusId(),
                'updated_by_user_id' => $event->getUpdatedByUserId(),
                'exception' => $e->getMessage()
            ]);
        }
    }
}