<?php

/**
 * CreateMissCampaignAssignedNotification
 * -----------------------------------------
 * Listener for creating notification when a miss campaign is assigned to a user
 *
 * @package App\Listeners
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-04-19
 */

namespace App\Listeners;

use App\Events\MissCampaignAssignedEvent;
use App\Services\NotificationService;
use App\Models\User;
use App\Models\MissCampaign;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Database\QueryException;

class CreateMissCampaignAssignedNotification
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
     * @param MissCampaignAssignedEvent $event
     * @return void
     */
    public function handle(MissCampaignAssignedEvent $event)
    {
        try {
            Log::info('MissCampaignAssignedEvent received', [
                'miss_campaign_id' => $event->getMissCampaignId(),
                'user_id' => $event->getUserId()
            ]);
            
            // Fetch the miss campaign
            $missCampaign = MissCampaign::find($event->getMissCampaignId());

            if (!$missCampaign) {
                Log::warning('Miss campaign not found for notification creation', ['miss_campaign_id' => $event->getMissCampaignId()]);
                return;
            }

            Log::info('Miss campaign found', ['miss_campaign_id' => $missCampaign->id, 'name' => $missCampaign->name]);

            // Fetch the assigned user
            $user = User::find($event->getUserId());

            if (!$user) {
                Log::warning('User not found for notification creation', ['user_id' => $event->getUserId()]);
                return;
            }

            Log::info('User found for notification', ['user_id' => $user->id, 'name' => $user->name]);

            // Create the notification
            $this->notificationService->createNotificationForNotifiable(
                User::class,
                $event->getUserId(),
                'miss_campaign_assigned',
                [
                    'title' => 'Pre Lead Assigned',
                    'message' => 'A new pre lead "' . ($missCampaign->name ?? 'Unknown') . '" has been assigned to you.',
                    'miss_campaign_id' => $missCampaign->id,
                    'assigned_at' => now()->format('Y-m-d h:i:s A'),
                ],
                'pre-lead'
            );

            Log::info('Miss campaign assigned notification created successfully', [
                'miss_campaign_id' => $event->getMissCampaignId(),
                'user_id' => $event->getUserId()
            ]);

        } catch (QueryException $e) {
            Log::error('Database error creating miss campaign assigned notification', [
                'miss_campaign_id' => $event->getMissCampaignId(),
                'user_id' => $event->getUserId(),
                'exception' => $e->getMessage()
            ]);
        } catch (Exception $e) {
            Log::error('Unexpected error creating miss campaign assigned notification', [
                'miss_campaign_id' => $event->getMissCampaignId(),
                'user_id' => $event->getUserId(),
                'exception' => $e->getMessage()
            ]);
        }
    }
}
