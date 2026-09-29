<?php

namespace App\Http\Controllers;

use App\Http\Resources\BriefAssignHistoryResource;
use App\Models\Brief;
use App\Models\User;
use App\Services\BriefAssignHistoryService;
use App\Services\ResponseService;
use App\Traits\HandlesFileUploads;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use App\Http\Resources\BriefActivityResource;
use Throwable;

class BriefAssignHistoryController extends Controller
{
    use HandlesFileUploads;

    /**
     * @var ResponseService
     */
    protected ResponseService $responseService;

    /**
     * @var BriefAssignHistoryService
     */
    protected BriefAssignHistoryService $briefAssignHistoryService;

    /**
     * Create a new BriefAssignHistoryController instance.
     *
     * @param ResponseService $responseService
     * @param BriefAssignHistoryService $briefAssignHistoryService
     */
    public function __construct(ResponseService $responseService, BriefAssignHistoryService $briefAssignHistoryService)
    {
        $this->responseService = $responseService;
        $this->briefAssignHistoryService = $briefAssignHistoryService;
    }

    // ============================================================================
    // READ OPERATIONS
    // ============================================================================

    /**
     * Display a listing of brief assign histories.
     *
     * GET /brief-assign-histories
     *
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 10);
            $searchTerm = $request->input('search');

            $briefAssignHistories = $this->briefAssignHistoryService->getAllBriefAssignHistories($perPage, $searchTerm);

            return $this->responseService->success(
                BriefAssignHistoryResource::collection($briefAssignHistories),
                'Brief assign histories retrieved successfully.'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Display a specific brief assign history by ID.
     *
     * GET /brief-assign-histories/{id}
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $briefAssignHistory = $this->briefAssignHistoryService->getBriefAssignHistory($id);

            if (!$briefAssignHistory) {
                return $this->responseService->notFound('Brief assign history not found');
            }

            return $this->responseService->success(
                new BriefAssignHistoryResource($briefAssignHistory),
                'Brief assign history retrieved successfully.'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Display a specific brief assign history by UUID.
     *
     * GET /brief-assign-histories/uuid/{uuid}
     *
     * @param string $uuid
     * @return JsonResponse
     */
    public function showByUuid(string $uuid): JsonResponse
    {
        try {
            $briefAssignHistory = $this->briefAssignHistoryService->getBriefAssignHistoryByUuid($uuid);

            if (!$briefAssignHistory) {
                return $this->responseService->notFound('Brief assign history not found');
            }

            return $this->responseService->success(
                new BriefAssignHistoryResource($briefAssignHistory),
                'Brief assign history retrieved successfully.'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Get all assign histories for a specific brief.
     *
     * GET /briefs/{briefId}/assign-histories
     *
     * @param int $briefId
     * @return JsonResponse
     */
    /**
     * Updated brief assignment history API to apply authenticated-user
     * visibility rules while retrieving assignment history.
     */
    public function getByBriefId(int $briefId, Request $request): JsonResponse
    {
        try {
            $perPage = (int) $request->input('per_page', 10);
            $user = auth()->user();
            $briefAssignHistories = $this->briefAssignHistoryService->getBriefAssignHistoriesByBriefId($briefId, $perPage, $user);

            return $this->responseService->success(
                BriefAssignHistoryResource::collection($briefAssignHistories),
                'Brief assign histories retrieved successfully.'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Get compact assignment history entries for the brief chat.
     *
     * GET /briefs/{briefId}/assign-histories-chat
     *
     * @param int $briefId
     * @return JsonResponse
     */
    public function getChatByBriefId(int $briefId): JsonResponse
    {
        try {
            $histories = $this->briefAssignHistoryService->getBriefAssignHistoryChat($briefId);
            $chatHistory = $histories->map(function ($history) {
                return [
                    'current_user_id' => $history->assign_by_id,
                    'current_user_name' => $history->assignedBy?->name,
                    'brief_comment' => $history->comment,
                    'created_at' => $history->created_at?->format('Y-m-d H:i:s'),
                ];
            })->values();

            return $this->responseService->success(
                $chatHistory,
                'Brief assignment chat history retrieved successfully.'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Get activity history for a brief.
     *
     * GET /briefs/{briefId}/activity
     *
     * @param int $briefId
     * @return JsonResponse
     */
    public function getActivityByBriefId(int $briefId): JsonResponse
    {
        try {
            $activities = $this->briefAssignHistoryService->getBriefAssignHistoryChat($briefId);

            return $this->responseService->success(
                BriefActivityResource::collection($activities),
                'Brief activity retrieved successfully.'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Save brief activity and optional reminder details.
     *
     * POST /briefs/{briefId}/activity
     *
     * @param Request $request
     * @param int $briefId
     * @return JsonResponse
     */
    public function createActivity(Request $request, int $briefId): JsonResponse
    {
        try {
            if ($request->exists('reminder')) {
                $request->merge([
                    'reminder' => filter_var($request->input('reminder'), FILTER_VALIDATE_BOOLEAN),
                ]);
            }

            $reminderEnabled = filter_var($request->input('reminder', false), FILTER_VALIDATE_BOOLEAN);
            $validated = $this->validate($request, [
                'comment' => 'required|string|max:1000',
                'reminder' => 'sometimes|nullable|boolean',
                'reminder_at' => ($reminderEnabled ? 'required' : 'nullable') . '|date',
                'reminder_before' => ($reminderEnabled ? 'required' : 'nullable') . '|integer|min:1',
                'reminder_before_unit' => ($reminderEnabled ? 'required' : 'nullable') . '|in:minutes,hours,days',
            ]);

            $validated['reminder'] = $reminderEnabled;
            if (!$reminderEnabled) {
                $validated['reminder_at'] = null;
                $validated['reminder_before'] = null;
                $validated['reminder_before_unit'] = null;
            }

            $history = $this->briefAssignHistoryService->createBriefActivity(
                $briefId,
                (int) auth()->id(),
                $validated
            );

            return $this->responseService->success(
                new BriefActivityResource($history),
                'Brief activity saved successfully.'
            );
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'Validation failed');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->responseService->notFound('Brief not found');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Duration from each planner assignment to that planner's plan submission.
     *
     * GET /briefs/{briefId}/assignment-submission-durations
     *
     * @param int $briefId
     * @return JsonResponse
     */
    public function getAssignmentSubmissionDurations(int $briefId): JsonResponse
    {
        try {
            $brief = Brief::query()->where('status', '!=', '15')->find($briefId);

            if (!$brief) {
                return $this->responseService->notFound('Brief not found');
            }

            $durations = $this->briefAssignHistoryService->getAssignmentSubmissionDurations($briefId);

            return $this->responseService->success(
                $durations,
                'Planner assignment submission durations retrieved successfully.'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Assignment-to-submission durations for every brief cycle of one planner.
     *
     * GET /users/{userId}/assignment-submission-durations?page=1
     * assignment_cycles are returned 5 per page.
     *
     * @param int $userId
     * @param Request $request
     * @return JsonResponse
     */
    public function getUserAssignmentSubmissionDurations(int $userId, Request $request): JsonResponse
    {
        try {
            $user = User::query()->find($userId);

            if (!$user) {
                return $this->responseService->notFound('User not found');
            }

            $cycles = $this->briefAssignHistoryService->getUserAssignmentSubmissionDurations($userId);
            $perPage = 5;
            $page = max(1, (int) $request->input('page', 1));
            $total = count($cycles);

            $paginator = new LengthAwarePaginator(
                array_slice($cycles, ($page - 1) * $perPage, $perPage),
                $total,
                $perPage,
                $page,
                [
                    'path' => $request->url(),
                    'query' => $request->query(),
                ]
            );

            $response = $this->responseService->paginated(
                $paginator,
                'User assignment submission durations retrieved successfully.'
            );
            $payload = $response->getData(true);
            $payload['data'] = [
                'user_id' => (int) $user->id,
                'user_name' => $user->name,
                'assignment_cycles' => $payload['data'],
            ];

            return response()->json($payload, $response->status());
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Get all assign histories assigned by a specific user.
     *
     * GET /users/{userId}/assigned-briefs
     *
     * @param int $userId
     * @return JsonResponse
     */
    public function getByAssignBy(int $userId, Request $request): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 10);
            $briefAssignHistories = $this->briefAssignHistoryService->getBriefAssignHistoriesByAssignBy($userId, $perPage);

            return $this->responseService->success(
                BriefAssignHistoryResource::collection($briefAssignHistories),
                'Brief assign histories retrieved successfully.'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Get all assign histories assigned to a specific user.
     *
     * GET /users/{userId}/assigned-to-me
     *
     * @param int $userId
     * @return JsonResponse
     */
    public function getByAssignTo(int $userId, Request $request): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 10);
            $briefAssignHistories = $this->briefAssignHistoryService->getBriefAssignHistoriesByAssignTo($userId, $perPage);

            return $this->responseService->success(
                BriefAssignHistoryResource::collection($briefAssignHistories),
                'Brief assign histories retrieved successfully.'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    // ============================================================================
    // CREATE OPERATIONS
    // ============================================================================

    /**
     * Store a new brief assign history.
     *
     * POST /brief-assign-histories
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $this->validate($request, [
                'brief_id' => 'required|integer|exists:briefs,id',
                'assign_by_id' => 'required|integer|exists:users,id',
                'assign_to_id' => 'required|integer|exists:users,id',
                'brief_status_id' => 'nullable|integer|exists:brief_statuses,id',
                'brief_status_time' => 'nullable|date_format:Y-m-d H:i:s',
                'submission_date' => 'nullable|date_format:Y-m-d H:i:s',
                'comment' => 'nullable|string',
                'attachment' => 'nullable|file|max:10240',
                'status' => 'nullable|in:1,2,15',
            ]);

            $data = $request->all();
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $fileType = $this->detectFileType($file);
                $uploaded = $this->uploadFile(
                    $file,
                    $fileType,
                    'uploads/brief-attachments',
                    ['sizeLimit' => 10240]
                );
                $data['attachment'] = $uploaded['path'];
            }
            $data['status'] = $data['status'] ?? '2';

            $briefAssignHistory = $this->briefAssignHistoryService->createBriefAssignHistory($data);

            return $this->responseService->success(
                BriefAssignHistoryResource::make($briefAssignHistory),
                'Brief assign history created successfully.'
            );
        } catch (ValidationException $e) {
            return $this->responseService->validationError(
                $e->errors(),
                'Validation failed'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    // ============================================================================
    // UPDATE OPERATIONS
    // ============================================================================

    /**
     * Update a brief assign history.
     *
     * PUT /brief-assign-histories/{id}
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function update(int $id, Request $request): JsonResponse
    {
        try {
            $briefAssignHistory = $this->briefAssignHistoryService->getBriefAssignHistory($id);

            if (!$briefAssignHistory) {
                return $this->responseService->notFound('Brief assign history not found');
            }

            $this->validate($request, [
                'brief_id' => 'sometimes|integer|exists:briefs,id',
                'assign_by_id' => 'sometimes|integer|exists:users,id',
                'assign_to_id' => 'sometimes|integer|exists:users,id',
                'brief_status_id' => 'nullable|integer|exists:brief_statuses,id',
                'brief_status_time' => 'nullable|date_format:Y-m-d H:i:s',
                'submission_date' => 'nullable|date_format:Y-m-d H:i:s',
                'comment' => 'nullable|string',
                'attachment' => 'nullable|file|max:10240',
                'status' => 'nullable|in:1,2,15',
            ]);

            $data = $request->all();
            if ($request->hasFile('attachment')) {
                // Delete old attachment if it exists
                if (!empty($briefAssignHistory->attachment)) {
                    $this->deleteFile($briefAssignHistory->attachment);
                }

                $file = $request->file('attachment');
                $fileType = $this->detectFileType($file);
                $uploaded = $this->uploadFile(
                    $file,
                    $fileType,
                    'uploads/brief-attachments',
                    ['sizeLimit' => 10240]
                );

                if (!empty($briefAssignHistory->attachment)) {
                    Storage::delete($briefAssignHistory->attachment);
                }

                $data['attachment'] = $uploaded['path'];
            }

            $briefAssignHistory = $this->briefAssignHistoryService->updateBriefAssignHistory($id, $data);

            return $this->responseService->success(
                BriefAssignHistoryResource::make($briefAssignHistory),
                'Brief assign history updated successfully.'
            );
        } catch (ValidationException $e) {
            return $this->responseService->validationError(
                $e->errors(),
                'Validation failed'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    // ============================================================================
    // DELETE OPERATIONS
    // ============================================================================

    /**
     * Delete a brief assign history (soft delete).
     *
     * DELETE /brief-assign-histories/{id}
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $briefAssignHistory = $this->briefAssignHistoryService->getBriefAssignHistory($id);

            if (!$briefAssignHistory) {
                return $this->responseService->notFound('Brief assign history not found');
            }

            $this->briefAssignHistoryService->deleteBriefAssignHistory($id);

            return $this->responseService->success(
                null,
                'Brief assign history deleted successfully.'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Force delete a brief assign history.
     *
     * DELETE /brief-assign-histories/{id}/force
     *
     * @param int $id
     * @return JsonResponse
     */
    public function forceDelete(int $id): JsonResponse
    {
        try {
            $briefAssignHistory = $this->briefAssignHistoryService->getBriefAssignHistory($id);

            if (!$briefAssignHistory) {
                return $this->responseService->notFound('Brief assign history not found');
            }

            $this->briefAssignHistoryService->forceDeleteBriefAssignHistory($id);

            return $this->responseService->success(
                null,
                'Brief assign history permanently deleted successfully.'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Restore a soft deleted brief assign history.
     *
     * POST /brief-assign-histories/{id}/restore
     *
     * @param int $id
     * @return JsonResponse
     */
    public function restore(int $id): JsonResponse
    {
        try {
            $briefAssignHistory = $this->briefAssignHistoryService->restoreBriefAssignHistory($id);

            if (!$briefAssignHistory) {
                return $this->responseService->notFound('Brief assign history not found');
            }

            return $this->responseService->success(
                BriefAssignHistoryResource::make($briefAssignHistory),
                'Brief assign history restored successfully.'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }
}