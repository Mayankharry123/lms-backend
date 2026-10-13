<?php
/**
 * Listener CreateBriefStatusNotification
 * 
 * @author Achal Sharma
 * @package App\Listeners
 * @version 1.0.1
 */
namespace App\Listeners;

use App\Events\BriefStatusChangedEvent;
use App\Services\NotificationService;
use App\Models\Brief;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Database\QueryException;

class CreateBriefStatusNotification
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
     * @param BriefStatusChangedEvent $event
     * @return void
     */
    public function handle(BriefStatusChangedEvent $event)
    {
        try {
            $brief = Brief::find($event->getBriefId());
            if (!$brief) {
                return;
            }

            $notifiedUserIds = [];

            // Notify assigned user if not the updater
            if ($brief->assign_user_id && $brief->assign_user_id != $event->getUpdatedByUserId()) {
                $this->notificationService->createNotificationForNotifiable(
                    User::class,
                    $brief->assign_user_id,
                    'brief_status_changed',
                    [
                        'title' => 'Brief Status Updated',
                        'brief_id' => $brief->id,
                        'brief_name' => $brief->name,
                        'new_status' => $event->getNewStatusName(),
                        'updated_by' => $event->getUpdatedByUserName(),
                        'updated_by_id' => $event->getUpdatedByUserId(),
                        'message' => 'Brief #' . $brief->id . ' ("' . $brief->name . '") status changed from "' . ($event->getPreviousStatusName() ?? 'N/A') . '" to "' . ($event->getNewStatusName() ?? 'Unknown') . '" by ' . ($event->getUpdatedByUserName() ?? 'Unknown') . ' at ' . ($event->getTimestamp() ?? now()) . '.',
                    ],
                    'brief'
                );
                $notifiedUserIds[] = $brief->assign_user_id;
            }

            // Notify updater if not already notified
            if ($event->getUpdatedByUserId() && !in_array($event->getUpdatedByUserId(), $notifiedUserIds)) {
                $this->notificationService->createNotificationForNotifiable(
                    User::class,
                    $event->getUpdatedByUserId(),
                    'brief_status_changed',
                    [
                        'title' => 'You updated a brief status',
                        'brief_id' => $brief->id,
                        'brief_name' => $brief->name,
                        'previous_status' => $event->getPreviousStatusName(),
                        'new_status' => $event->getNewStatusName(),
                        'updated_by' => $event->getUpdatedByUserName(),
                        'updated_by_id' => $event->getUpdatedByUserId(),
                        'timestamp' => $event->getTimestamp(),
                        'message' => 'You changed brief #' . $brief->id . ' ("' . $brief->name . '") status from "' . ($event->getPreviousStatusName() ?? 'N/A') . '" to "' . ($event->getNewStatusName() ?? 'Unknown') . '" at ' . ($event->getTimestamp() ?? now()) . '.',
                    ],
                    'brief'
                );
                $notifiedUserIds[] = $event->getUpdatedByUserId();
            }

            // Optionally: Add logic here to notify other stakeholders (e.g., admins/managers) if needed
        } catch (QueryException $e) {
            Log::error('Database error creating brief status notification', [
                'brief_id' => $event->getBriefId(),
                'updated_by_user_id' => $event->getUpdatedByUserId(),
                'exception' => $e->getMessage()
            ]);
        } catch (Exception $e) {
            Log::error('Unexpected error creating brief status notification', [
                'brief_id' => $event->getBriefId(),
                'updated_by_user_id' => $event->getUpdatedByUserId(),
                'exception' => $e->getMessage()
            ]);
        }
    }
}
