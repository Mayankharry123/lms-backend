<?php
/**
 * Listener CreateLeadAssignedNotification
 * 
 * @author Achal Sharma
 * @package App\Listeners
 * @version 1.0.1
 */
namespace App\Listeners;

use App\Events\LeadAssignedEvent;
use App\Services\NotificationService;
use App\Models\User;
use App\Models\Lead;
use Illuminate\Support\Facades\Log;
use Exception;
use DomainException;
use Illuminate\Database\QueryException;

class CreateLeadAssignedNotification
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
     * @param LeadAssignedEvent $event
     * @return void
     */
    public function handle(LeadAssignedEvent $event)
    {
        try {
            // Fetch the lead with necessary relationships
            $lead = Lead::with(['assignedUser'])->find($event->getLeadId());

            if (!$lead) {
                Log::warning('Lead not found for notification creation', ['lead_id' => $event->getLeadId()]);
                return;
            }

            // Fetch the assigned user
            $user = User::find($event->getUserId());

            if (!$user) {
                Log::warning('User not found for notification creation', ['user_id' => $event->getUserId()]);
                return;
            }

            // Create the notification
            $this->notificationService->createNotificationForNotifiable(
                User::class,
                $event->getUserId(),
                'lead_assigned',
                [
                    'title' => 'Lead Assigned',
                    'message' => 'A new lead "' . ($lead->name ?? 'Unknown') . '" has been assigned to you.',
                    'lead_id' => $lead->id,
                    'lead_name' => $lead->name ?? 'Unknown',
                    'assigned_at' => now()->format('Y-m-d h:i:s A'),
                ],
                'lead'
            );

            Log::info('Lead assigned notification created successfully', [
                'lead_id' => $event->getLeadId(),
                'user_id' => $event->getUserId()
            ]);

        } catch (QueryException $e) {
            Log::error('Database error creating lead assigned notification', [
                'lead_id' => $event->getLeadId(),
                'user_id' => $event->getUserId(),
                'exception' => $e->getMessage()
            ]);
            // Don't re-throw to prevent breaking the lead assignment flow
        } catch (Exception $e) {
            Log::error('Unexpected error creating lead assigned notification', [
                'lead_id' => $event->getLeadId(),
                'user_id' => $event->getUserId(),
                'exception' => $e->getMessage()
            ]);
            // Don't re-throw to prevent breaking the lead assignment flow
        }
    }
}