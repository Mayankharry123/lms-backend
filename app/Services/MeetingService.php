<?php

/**
 * MeetingService
 * 
 * @author Achal Sharma
 * @package App\Services
 * @version 1.0.1
 */

namespace App\Services;

use App\Contracts\Repositories\MeetingRepositoryInterface;
use App\Models\Meeting;
use DomainException;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class MeetingService
{
    /**
     * @var MeetingRepositoryInterface
     */
    protected MeetingRepositoryInterface $repository;

    /**
     * @var LeadService
     */
    protected LeadService $leadService;

    /**
     * Create a new MeetingService instance.
     *
     * @param MeetingRepositoryInterface $repository
     * @param LeadService $leadService
     */
    public function __construct(
        MeetingRepositoryInterface $repository,
        LeadService $leadService
    ) {
        $this->repository = $repository;
        $this->leadService = $leadService;
    }

    // ============================================================================
    // READ OPERATIONS
    // ============================================================================

    /**
     * Get all meetings with pagination.
     *
     * @param int $perPage
     * @param string|null $searchTerm
     * @return LengthAwarePaginator
     * @throws DomainException
     */
    public function getAllMeetings(int $perPage = 10, ?string $searchTerm = null, array $filters = []): LengthAwarePaginator
    {
        try {
            return $this->repository->getAllMeetings($perPage, $searchTerm, $filters);
        } catch (QueryException $e) {
            Log::error('Database error fetching meetings', ['exception' => $e]);
            throw new DomainException('Database error while fetching meetings.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching meetings', ['exception' => $e]);
            throw new DomainException('Unexpected error while fetching meetings.');
        }
    }

    /**
     * Get all meetings without pagination.
     *
     * @return Collection
     * @throws DomainException
     */
    public function getAll(): Collection
    {
        try {
            return $this->repository->getAll();
        } catch (QueryException $e) {
            Log::error('Database error fetching all meetings', ['exception' => $e]);
            throw new DomainException('Database error while fetching all meetings.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching all meetings', ['exception' => $e]);
            throw new DomainException('Unexpected error while fetching all meetings.');
        }
    }

    /**
     * Get a meeting by ID.
     *
     * @param int $id
     * @return Meeting|null
     * @throws DomainException
     */
    public function getMeetingById(int $id): ?Meeting
    {
        try {
            $meeting = $this->repository->getById($id);
            if (!$meeting) {
                throw new DomainException("Meeting with ID {$id} not found.");
            }
            return $meeting;
        } catch (QueryException $e) {
            Log::error('Database error fetching meeting by ID', ['id' => $id, 'exception' => $e]);
            throw new DomainException('Database error while fetching meeting by ID.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching meeting by ID', ['id' => $id, 'exception' => $e]);
            throw new DomainException('Unexpected error while fetching meeting by ID.');
        }
    }

    /**
     * Get a meeting by UUID.
     *
     * @param string $uuid
     * @return Meeting|null
     * @throws DomainException
     */
    public function getMeetingByUuid(string $uuid): ?Meeting
    {
        try {
            $meeting = $this->repository->getByUuid($uuid);
            if (!$meeting) {
                throw new DomainException("Meeting with UUID {$uuid} not found.");
            }
            return $meeting;
        } catch (QueryException $e) {
            Log::error('Database error fetching meeting by UUID', ['uuid' => $uuid, 'exception' => $e]);
            throw new DomainException('Database error while fetching meeting by UUID.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching meeting by UUID', ['uuid' => $uuid, 'exception' => $e]);
            throw new DomainException('Unexpected error while fetching meeting by UUID.');
        }
    }

    /**
     * Get all meetings for a specific lead.
     *
     * @param int $leadId
     * @return Collection
     * @throws DomainException
     */
    public function getMeetingsByLead(int $leadId): Collection
    {
        try {
            return $this->repository->getByLead($leadId);
        } catch (QueryException $e) {
            Log::error('Database error fetching meetings by lead', ['lead_id' => $leadId, 'exception' => $e]);
            throw new DomainException('Database error while fetching meetings by lead.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching meetings by lead', ['lead_id' => $leadId, 'exception' => $e]);
            throw new DomainException('Unexpected error while fetching meetings by lead.');
        }
    }

    /**
     * Get all meetings for a specific attendee.
     *
     * @param int $attendeeId
     * @return Collection
     * @throws DomainException
     */
    public function getMeetingsByAttendee(int $attendeeId): Collection
    {
        try {
            return $this->repository->getByAttendee($attendeeId);
        } catch (QueryException $e) {
            Log::error('Database error fetching meetings by attendee', ['attendee_id' => $attendeeId, 'exception' => $e]);
            throw new DomainException('Database error while fetching meetings by attendee.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching meetings by attendee', ['attendee_id' => $attendeeId, 'exception' => $e]);
            throw new DomainException('Unexpected error while fetching meetings by attendee.');
        }
    }

    /**
     * Get meetings by status.
     *
     * @param string $status
     * @return Collection
     * @throws DomainException
     */
    public function getMeetingsByStatus(string $status): Collection
    {
        try {
            return $this->repository->getByStatus($status);
        } catch (QueryException $e) {
            Log::error('Database error fetching meetings by status', ['status' => $status, 'exception' => $e]);
            throw new DomainException('Database error while fetching meetings by status.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching meetings by status', ['status' => $status, 'exception' => $e]);
            throw new DomainException('Unexpected error while fetching meetings by status.');
        }
    }

    // ============================================================================
    // WRITE OPERATIONS
    // ============================================================================

    /**
     * Create a new meeting.
     *
     * @param array $data
     * @return Meeting
     * @throws DomainException
     */
    public function createMeeting(array $data): Meeting
    {
        try {
            return $this->repository->create($data);
        } catch (QueryException $e) {
            Log::error('Database error creating meeting', ['data' => $data, 'exception' => $e]);
            throw new DomainException('Database error while creating meeting.');
        } catch (Exception $e) {
            Log::error('Unexpected error creating meeting', ['data' => $data, 'exception' => $e]);
            throw new DomainException('Unexpected error while creating meeting.');
        }
    }

    /**
     * Update an existing meeting.
     *
     * @param int $id
     * @param array $data
     * @return Meeting
     * @throws DomainException
     */
    public function updateMeeting(int $id, array $data): Meeting
    {
        try {
            return $this->repository->update($id, $data);
        } catch (ModelNotFoundException $e) {
            Log::error('Meeting not found', ['id' => $id, 'data' => $data]);
            throw new DomainException('Meeting not found.');
        } catch (QueryException $e) {
            Log::error('Database error updating meeting', ['id' => $id, 'data' => $data, 'exception' => $e]);
            throw new DomainException('Database error while updating meeting.');
        } catch (Exception $e) {
            Log::error('Unexpected error updating meeting', ['id' => $id, 'data' => $data, 'exception' => $e]);
            throw new DomainException('Unexpected error while updating meeting.');
        }
    }

    /**
     * Delete a meeting (hard delete).
     *
     * @param int $id
     * @return bool
     * @throws DomainException
     */
    public function deleteMeeting(int $id): bool
    {
        try {
            return $this->repository->delete($id);
        } catch (ModelNotFoundException $e) {
            Log::error('Meeting not found', ['id' => $id]);
            throw new DomainException('Meeting not found.');
        } catch (QueryException $e) {
            Log::error('Database error deleting meeting', ['id' => $id, 'exception' => $e]);
            throw new DomainException('Database error while deleting meeting.');
        } catch (Exception $e) {
            Log::error('Unexpected error deleting meeting', ['id' => $id, 'exception' => $e]);
            throw new DomainException('Unexpected error while deleting meeting.');
        }
    }

    /**
     * Soft delete a meeting.
     *
     * @param int $id
     * @return bool
     * @throws DomainException
     */
    public function softDeleteMeeting(int $id): bool
    {
        try {
            return $this->repository->softDelete($id);
        } catch (ModelNotFoundException $e) {
            Log::error('Meeting not found', ['id' => $id]);
            throw new DomainException('Meeting not found.');
        } catch (QueryException $e) {
            Log::error('Database error soft deleting meeting', ['id' => $id, 'exception' => $e]);
            throw new DomainException('Database error while soft deleting meeting.');
        } catch (Exception $e) {
            Log::error('Unexpected error soft deleting meeting', ['id' => $id, 'exception' => $e]);
            throw new DomainException('Unexpected error while soft deleting meeting.');
        }
    }

    /**
     * Restore a soft-deleted meeting.
     *
     * @param int $id
     * @return bool
     * @throws DomainException
     */
    public function restoreMeeting(int $id): bool
    {
        try {
            return $this->repository->restore($id);
        } catch (ModelNotFoundException $e) {
            Log::error('Meeting not found', ['id' => $id]);
            throw new DomainException('Meeting not found.');
        } catch (QueryException $e) {
            Log::error('Database error restoring meeting', ['id' => $id, 'exception' => $e]);
            throw new DomainException('Database error while restoring meeting.');
        } catch (Exception $e) {
            Log::error('Unexpected error restoring meeting', ['id' => $id, 'exception' => $e]);
            throw new DomainException('Unexpected error while restoring meeting.', 0, $e);
        }
    }

    /**
     * Get attendee email addresses by attendee user IDs.
     *
     * @param array $attendeesIds
     * @return array
     * @throws DomainException
     */
    public function getEmailIdsForAttendees(array $attendeesIds): array
    {
        try {
            return $this->repository->getEmailIdsForAttendees($attendeesIds);
        } catch (QueryException $e) {
            Log::error('Database error fetching email IDs for attendees', ['attendees_ids' => $attendeesIds, 'exception' => $e]);
            throw new DomainException('Database error while fetching email IDs for attendees.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching email IDs for attendees', ['attendees_ids' => $attendeesIds, 'exception' => $e]);
            throw new DomainException('Unexpected error while fetching email IDs for attendees.');
        }
    }

    /**
     * Get lead emails by lead ID.
     *
     * @param int $leadId
     * @return array
     * @throws DomainException
     */
    public function getLeadidByEmail(int $leadId): array
    {
        try {
            return $this->repository->getLeadidByEmail($leadId);
        } catch (QueryException $e) {
            Log::error('Database error fetching lead ID by email', ['lead_id' => $leadId, 'exception' => $e]);
            throw new DomainException('Database error while fetching lead ID by email.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching lead ID by email', ['lead_id' => $leadId, 'exception' => $e]);
            throw new DomainException('Unexpected error while fetching lead ID by email.');
        }
    }

    /**
     * Automatically mark expired scheduled/in-progress meetings as completed (status = 15)
     * and transition connected leads to "Meeting Done".
     *
     * @return int Number of meetings transitioned
     */
    public function completeExpiredMeetings(): int
    {
        try {
            $expiredMeetings = $this->repository->getExpiredActiveMeetings();

            if ($expiredMeetings->isEmpty()) {
                return 0;
            }

            $count = 0;

            foreach ($expiredMeetings as $meeting) {
                $this->repository->markMeetingCompleted((int) $meeting->id);
                $count++;

                if ($meeting->lead_id) {
                    try {
                        $this->leadService->applyMeetingCompleteWorkflow((int) $meeting->lead_id);
                    } catch (\Throwable $e) {
                        Log::error('Error applying meeting complete workflow for lead', [
                            'meeting_id' => $meeting->id,
                            'lead_id' => $meeting->lead_id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }

            return $count;
        } catch (\Throwable $e) {
            Log::error('Error completing expired meetings', ['error' => $e->getMessage()]);
            return 0;
        }
    }
}