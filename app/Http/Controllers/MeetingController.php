<?php
/**
 * MeetingController
 * 
 * @author Achal Sharma
 * @package App\Http\Controllers
 * @version 1.0.1
 */
namespace App\Http\Controllers;

use App\Services\LeadService;
use App\Services\MeetingService;
use App\Services\ResponseService;
use App\Http\Resources\MeetingResource;
use App\Traits\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;
use DomainException;
use Illuminate\Validation\ValidationException;
use App\Services\GoogleCalendarService;

/**
 * Controller for managing meetings.
 * 
 * Handles CRUD operations for meetings and their relationships with leads and attendees.
 */
class MeetingController extends Controller
{
    use ValidatesRequests;

    protected $meetingService;
    protected $responseService;
    protected $googleCalendarService;
    protected $leadService;

    /**
     * Create a new MeetingController instance.
     *
     * @param MeetingService $meetingService Service for meeting operations
     * @param ResponseService $responseService Service for standardized API responses
     * @param GoogleCalendarService $googleCalendarService Service for Google Calendar integration
     * @param LeadService $leadService Service for lead workflow updates
     */
    public function __construct(
        MeetingService $meetingService,
        ResponseService $responseService,
        GoogleCalendarService $googleCalendarService,
        LeadService $leadService
    ) {
        $this->meetingService = $meetingService;
        $this->responseService = $responseService;
        $this->googleCalendarService = $googleCalendarService;
        $this->leadService = $leadService;
    }

    /**
     * Get paginated list of meetings
     * GET /api/v1/meetings
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = (int) $request->input('per_page', 15);
            $search = $request->input('search', null);
            $filters = \App\Support\DashboardFilters::fromRequest($request);

            $meetings = $this->meetingService->getAllMeetings($perPage, $search, $filters);
            return $this->responseService->paginated(
                MeetingResource::collection($meetings),
                'Meetings retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Get list of meetings with only id and title
     * GET /api/v1/meetings/list
     */
    public function list(): JsonResponse
    {
        try {
            $meetings = $this->meetingService->getAll();
            $data = $meetings->map(function ($meeting) {
                return [
                    'id' => $meeting->id,
                    'title' => $meeting->title,
                ];
            });
            return $this->responseService->success($data, 'Meetings list retrieved');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Get all meetings without pagination
     * GET /api/v1/meetings/all
     */
    public function getAll(): JsonResponse
    {
        try {
            $meetings = $this->meetingService->getAll();
            return $this->responseService->success(MeetingResource::collection($meetings), 'All meetings retrieved');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Get all meetings for a specific lead
     * GET /leads/{leadId}/meetings
     */
    public function getMeetingsByLead(int $leadId): JsonResponse
    {
        try {
            $meetings = $this->meetingService->getMeetingsByLead($leadId);
            $data = MeetingResource::collection($meetings);
            return $this->responseService->success($data, 'Meetings for lead retrieved');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Get all meetings for a specific attendee
     * GET /users/{attendeeId}/meetings
     */
    public function getMeetingsByAttendee(int $attendeeId): JsonResponse
    {
        try {
            $meetings = $this->meetingService->getMeetingsByAttendee($attendeeId);
            $data = MeetingResource::collection($meetings);
            return $this->responseService->success($data, 'Meetings for attendee retrieved');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Store a newly created meeting
     * POST /api/v1/meetings
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $rules = [
                'title' => 'required|string|max:255',
                'lead_id' => 'required|integer|exists:leads,id',
                'attendees_id' => 'required|array',
                'attendees_id.*' => 'integer|exists:users,id',
                'type' => 'required|in:face_to_face,online',
                'location' => 'nullable|string|max:255',
                'agenda' => 'nullable|string',
                // 'link' => 'nullable|string|url',
                'meeting_start_date' => 'required|date_format:Y-m-d H:i',
                'meeting_end_date' => 'required|date_format:Y-m-d H:i|after:meeting_start_date',
                'status' => 'nullable|in:1,2,15',
            ];

            $validatedData = $this->validate($request, $rules);

            // Handle attendees_id: if it's a string (JSON), convert to array
            if (isset($validatedData['attendees_id']) && is_string($validatedData['attendees_id'])) {
                $validatedData['attendees_id'] = json_decode($validatedData['attendees_id'], true);
            }

            if (!isset($validatedData['status'])) {
                $validatedData['status'] = '1';
            }

            // store the meeting in database
            $meeting = $this->meetingService->createMeeting($validatedData);

            /**
             *  Auto update lead status, call_status and priority when meeting is created
             */
            if (!empty($validatedData['lead_id'])) {
                try {
                    $this->leadService->applyMeetingScheduleWorkflow((int) $validatedData['lead_id']);
                } catch (Throwable $e) {
                    Log::error('Error updating lead details for meeting creation', [
                        'lead_id' => $validatedData['lead_id'],
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $getEmailIdsForAttendees = $this->meetingService->getEmailIdsForAttendees($validatedData['attendees_id']);
            $getLeadidByEmail = $this->meetingService->getLeadidByEmail($validatedData['lead_id']);

            if(!empty($request->type) && ($request->type == 'online')){
                $meetingData = $this->googleCalendarService->createEvent([
                    'summary' => $validatedData['title'],
                    'description' => $validatedData['agenda'] ?? '',
                    'start' => $validatedData['meeting_start_date'],
                    'end' => $validatedData['meeting_end_date'],
                    'attendees' => array_merge($getEmailIdsForAttendees, $getLeadidByEmail),
                ], 1);

                $meeting->update(['google_event' => json_encode($meetingData)]);
            }

            $data = new MeetingResource($meeting);
            
            return $this->responseService->created($data, 'Meeting created successfully');
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'Validation failed');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Display the specified meeting
     * GET /api/v1/meetings/{id}
     */
    public function show(int $id): JsonResponse
    {
        try {
            $meeting = $this->meetingService->getMeetingById($id);
            $data = new MeetingResource($meeting);
            return $this->responseService->success($data, 'Meeting retrieved successfully');
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'Validation failed');
        } catch (DomainException $e) {
            return $this->responseService->notFound($e->getMessage());
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Update the specified meeting
     * PUT /api/v1/meetings/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $rules = [
                'title' => 'sometimes|required|string|max:255',
                'lead_id' => 'sometimes|required|integer|exists:leads,id',
                'attendees_id' => 'sometimes|nullable|array',
                'attendees_id.*' => 'integer|exists:users,id',
                'type' => 'sometimes|required|in:face_to_face,online',
                'location' => 'sometimes|nullable|string|max:255',
                'agenda' => 'sometimes|nullable|string',
                'link' => 'sometimes|nullable|string|url',
                'meeting_start_date' => 'sometimes|nullable|date_format:Y-m-d H:i',
                'meeting_end_date' => 'sometimes|nullable|date_format:Y-m-d H:i|after:meeting_start_date',
                'status' => 'sometimes|nullable|in:1,2,15',
            ];

            $validatedData = $this->validate($request, $rules);

            // Handle attendees_id: if it's a string (JSON), convert to array
            if (isset($validatedData['attendees_id']) && is_string($validatedData['attendees_id'])) {
                $validatedData['attendees_id'] = json_decode($validatedData['attendees_id'], true);
            }

            // Fetch the meeting to get existing Google event ID
            $meeting = $this->meetingService->getMeetingById($id);

            // Update the meeting in database
            $updatedMeeting = $this->meetingService->updateMeeting($id, $validatedData);

            // Update lead status if meeting status is being changed to completed/done
            // Status values: 1 = scheduled, 2 = in progress, 15 = completed/done
            if (isset($validatedData['status']) && $validatedData['status'] == '15') {
                $leadIdToUpdate = isset($validatedData['lead_id']) ? $validatedData['lead_id'] : $meeting->lead_id;
                try {
                    $this->leadService->applyMeetingCompleteWorkflow((int) $leadIdToUpdate);
                } catch (Throwable $e) {
                    Log::error('Error updating lead on meeting complete', [
                        'lead_id' => $leadIdToUpdate,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Update Google Calendar event if it exists
            if ($meeting->google_event) {
                try {
                    $googleEvent = json_decode($meeting->google_event, true);

                    if (isset($googleEvent['event_id'])) {
                        $eventUpdateData = [];

                        // Prepare updated event data - same structure as store method
                        if (isset($validatedData['title'])) {
                            $eventUpdateData['summary'] = $validatedData['title'];
                        }

                        if (isset($validatedData['agenda'])) {
                            $eventUpdateData['description'] = $validatedData['agenda'];
                        }

                        // Update meeting time if changed
                        if (isset($validatedData['meeting_start_date'])) {
                            $eventUpdateData['start'] = $validatedData['meeting_start_date'];
                        }

                        if (isset($validatedData['meeting_end_date'])) {
                            $eventUpdateData['end'] = $validatedData['meeting_end_date'];
                        }

                        // Update attendees if changed - use same connection logic as store
                        // If attendees changed, get new emails; otherwise keep existing
                        if (isset($validatedData['attendees_id'])) {
                            $getEmailIdsForAttendees = $this->meetingService->getEmailIdsForAttendees($validatedData['attendees_id']);
                        } else {
                            // If attendees not changed, use existing from meeting
                            $getEmailIdsForAttendees = $this->meetingService->getEmailIdsForAttendees($updatedMeeting->attendees_id);
                        }

                        // Get lead email - use updated lead_id if changed, otherwise existing
                        $leadIdToUse = isset($validatedData['lead_id']) ? $validatedData['lead_id'] : $meeting->lead_id;
                        $getLeadidByEmail = $this->meetingService->getLeadidByEmail($leadIdToUse);

                        // Update attendees in Google Calendar (same format as store)
                        $eventUpdateData['attendees'] = array_merge($getEmailIdsForAttendees, $getLeadidByEmail);

                        // Update the Google Calendar event
                        $updatedEventData = $this->googleCalendarService->updateEvent(
                            $googleEvent['event_id'],
                            $eventUpdateData,
                            1
                        );

                        // Update the google_event field with new data
                        $updatedMeeting->update(['google_event' => json_encode($updatedEventData)]);
                    }
                } catch (Throwable $e) {
                    // Log Google Calendar update error but continue with database update
                    Log::warning('Failed to update Google Calendar event', [
                        'meeting_id' => $id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $data = new MeetingResource($updatedMeeting->fresh());
            return $this->responseService->updated($data, 'Meeting updated successfully');
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'Validation failed');
        } catch (DomainException $e) {
            return $this->responseService->notFound($e->getMessage());
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Remove the specified meeting from storage (soft delete)
     * DELETE /api/v1/meetings/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            // Get the meeting before deleting
            $meeting = $this->meetingService->getMeetingById($id);

            // Delete from Google Calendar if event exists
            if ($meeting->google_event) {
                try {
                    $googleEvent = json_decode($meeting->google_event, true);

                    if (isset($googleEvent['event_id'])) {
                        $this->googleCalendarService->deleteEvent($googleEvent['event_id'], 1);
                    }
                } catch (Throwable $e) {
                    // Log error but continue with soft delete
                    Log::warning('Failed to delete Google Calendar event', [
                        'meeting_id' => $id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Soft delete from database
            $this->meetingService->softDeleteMeeting($id);
            return $this->responseService->deleted('Meeting deleted successfully');
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'Validation failed');
        } catch (DomainException $e) {
            return $this->responseService->notFound($e->getMessage());
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Restore a soft-deleted meeting
     * PATCH /api/v1/meetings/{id}/restore
     */
    public function restore(int $id): JsonResponse
    {
        try {
            $this->meetingService->restoreMeeting($id);
            return $this->responseService->success(null, 'Meeting restored successfully');
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'Validation failed');
        } catch (DomainException $e) {
            return $this->responseService->notFound($e->getMessage());
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

}