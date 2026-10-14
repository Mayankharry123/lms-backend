<?php
/**
 * Listener CreateLeadStatusNotification
 * 
 * @author Achal Sharma
 * @package App\Listeners
 * @version 1.0.1
 */
namespace App\Listeners;

use App\Events\LeadStatusChangedEvent;
use App\Services\NotificationService;
use App\Models\User;
use App\Models\Lead;
use App\Models\Status;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Database\QueryException;

class CreateLeadStatusNotification
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
     * @param LeadStatusChangedEvent $event
     * @return void
     */
    public function handle(LeadStatusChangedEvent $event)
    {
        try {
            $lead = Lead::find($event->getLeadId());

            if (!$lead || !$lead->current_assign_user) {
                return;
            }

            $status = Status::find($event->getStatus());

            $this->notificationService->createNotificationForNotifiable(
                User::class,
                $lead->current_assign_user,
                'lead_status_changed',
                [
                    'title' => 'Lead Status Updated',
                    'message' => 'Lead status has been updated to "' . ($status ? $status->name : 'Unknown') . '" for lead "' . $lead->name . '".',
                    'name' => $lead->name,
                    'status_name' => $status ? $status->name : 'Unknown'
                ],
                'lead'
            );
        } catch (QueryException $e) {
            Log::error('Database error creating lead status notification', [
                'lead_id' => $event->getLeadId(),
                'status' => $event->getStatus(),
                'exception' => $e->getMessage()
            ]);
        } catch (Exception $e) {
            Log::error('Unexpected error creating lead status notification', [
                'lead_id' => $event->getLeadId(),
                'status' => $event->getStatus(),
                'exception' => $e->getMessage()
            ]);
        }
    }
}