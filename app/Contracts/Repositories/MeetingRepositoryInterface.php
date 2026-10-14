<?php
/**
 * Interface MeetingRepositoryInterface
 * 
 * @author Achal Sharma
 * @package App\Contracts\Repositories
 * @version 1.0.1
 */
namespace App\Contracts\Repositories;

use App\Models\Meeting;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface MeetingRepositoryInterface
{
    // ============================================================================
    // READ OPERATIONS
    // ============================================================================

    /**
     * Fetch paginated list of meetings with optional search.
     *
     * @param int $perPage The number of items per page.
     * @param string|null $searchTerm Optional search term to filter meetings.
     * @return LengthAwarePaginator
     */
    public function getAllMeetings(int $perPage = 10, ?string $searchTerm = null, array $filters = []): LengthAwarePaginator;

    /**
     * Fetch all meetings without pagination.
     *
     * @return Collection
     */
    public function getAll(): Collection;

    /**
     * Fetch a single meeting by its primary ID.
     *
     * @param int $id The meeting ID.
     * @return Meeting|null
     */
    public function getById(int $id): ?Meeting;

    /**
     * Fetch a single meeting by its UUID.
     *
     * @param string $uuid The meeting UUID.
     * @return Meeting|null
     */
    public function getByUuid(string $uuid): ?Meeting;

    /**
     * Fetch all meetings for a specific lead.
     *
     * @param int $leadId The lead ID.
     * @return Collection
     */
    public function getByLead(int $leadId): Collection;

    /**
     * Fetch all meetings for a specific attendee.
     *
     * @param int $attendeeId The user/attendee ID.
     * @return Collection
     */
    public function getByAttendee(int $attendeeId): Collection;

    /**
     * Fetch meetings by status.
     *
     * @param string $status The status value ('1', '2', '15').
     * @return Collection
     */
    public function getByStatus(string $status): Collection;

    // ============================================================================
    // WRITE OPERATIONS
    // ============================================================================

    /**
     * Create a new meeting.
     *
     * @param array $data The meeting data.
     * @return Meeting
     */
    public function create(array $data): Meeting;

    /**
     * Update an existing meeting.
     *
     * @param int $id The meeting ID.
     * @param array $data The updated data.
     * @return Meeting
     */
    public function update(int $id, array $data): Meeting;

    /**
     * Delete a meeting (hard delete).
     *
     * @param int $id The meeting ID.
     * @return bool
     */
    public function delete(int $id): bool;

    /**
     * Soft delete a meeting.
     *
     * @param int $id The meeting ID.
     * @return bool
     */
    public function softDelete(int $id): bool;

    /**
     * Restore a soft-deleted meeting.
     *
     * @param int $id The meeting ID.
     * @return bool
     */
    public function restore(int $id): bool;

    /**
     * Get email IDs for attendees
     * 
     * @param array $attendeesId
     * @return array
     */
    public function getEmailIdsForAttendees(array $attendeesId): array;
    /**
     * Get lead ID by email
     * 
     * @param int $id
     * @return array
     */
    public function getLeadidByEmail(int $id): array;
}