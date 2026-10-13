<?php
/**
 * NotificationController
 * 
 * @author Achal Sharma
 * @package App\Http\Controllers
 * @version 1.0.1
 */
namespace App\Http\Controllers;

use App\Http\Resources\NotificationResource;
use App\Services\NotificationService;
use App\Services\ResponseService;
use App\Traits\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Throwable;
use Illuminate\Validation\ValidationException;

class NotificationController extends Controller
{
    use ValidatesRequests;

    /**
     * @var ResponseService
     */
    protected ResponseService $responseService;

    /**
     * @var NotificationService
     */
    protected NotificationService $notificationService;

    /**
     * Create a new NotificationController instance.
     *
     * @param ResponseService $responseService
     * @param NotificationService $notificationService
     */
    public function __construct(ResponseService $responseService, NotificationService $notificationService)
    {
        $this->responseService = $responseService;
        $this->notificationService = $notificationService;
    }

    /**
     * List notifications for the authenticated user.
     *
     * GET /notifications
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $this->validate($request, [
                'per_page' => 'nullable|integer|min:1',
            ]);

            $perPage = (int) $request->input('per_page', 10);
            $notifications = $this->notificationService->getNotificationsForCurrentUser($perPage);

            return $this->responseService->paginated(
                NotificationResource::collection($notifications),
                'Notifications retrieved successfully'
            );
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'Validation failed');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Mark a single notification as read.
     *
     * POST /notifications/{id}/read
     */
    public function markAsRead(int $id): JsonResponse
    {
        try {
            $success = $this->notificationService->markAsRead($id);

            if (!$success) {
                return $this->responseService->notFound('Notification not found');
            }

            return $this->responseService->updated(null, 'Notification marked as read');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Mark all notifications for the current user as read.
     *
     * POST /notifications/read-all
     */
    public function markAllAsRead(): JsonResponse
    {
        try {
            $count = $this->notificationService->markAllAsReadForCurrentUser();
            return $this->responseService->success(
                ['updated' => $count],
                'All notifications marked as read'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Get unread notification count for the current user.
     *
     * GET /notifications/unread-count
     */
    public function unreadCount(): JsonResponse
    {
        try {
            $count = $this->notificationService->getUnreadCountForCurrentUser();
            return $this->responseService->success(['unread_count' => $count]);
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * List unread notifications for the authenticated user.
     *
     * GET /notifications/unread
     */
    public function unreadNotifications(Request $request): JsonResponse
    {
        try {
            $this->validate($request, [
                'per_page' => 'nullable|integer|min:1',
            ]);

            $perPage = (int) $request->input('per_page', 10);
            $notifications = $this->notificationService->getUnreadNotificationsForCurrentUser($perPage);

            return $this->responseService->paginated(
                NotificationResource::collection($notifications),
                'Unread notifications retrieved successfully'
            );
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'Validation failed');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Get the latest notifications for the current user (non-paginated).
     *
     * GET /notifications/latest
     */
    public function latestNotifications(Request $request): JsonResponse
    {
        try {
            $this->validate($request, [
                'limit' => 'nullable|integer|min:1|max:100',
            ]);

            $limit = (int) $request->input('limit', 5);
            $notifications = $this->notificationService->getLatestNotificationsForCurrentUser($limit);

            return $this->responseService->success(
                NotificationResource::collection($notifications),
                'Latest notifications retrieved successfully'
            );
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'Validation failed');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Delete a specific notification.
     *
     * DELETE /notifications/{id}
     */
    public function deleteNotification(int $id): JsonResponse
    {
        try {
            $deleted = $this->notificationService->deleteNotification($id);
            if (! $deleted) {
                return $this->responseService->notFound('Notification not found');
            }
            return $this->responseService->deleted('Notification deleted successfully');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Clear (delete) all notifications for the current user.
     *
     * DELETE /notifications/clear-all
     */
    public function clearAllNotifications(): JsonResponse
    {
        try {
            $count = $this->notificationService->clearAllNotificationsForCurrentUser();
            return $this->responseService->success(
                ['deleted' => $count],
                'All notifications cleared'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }
}

