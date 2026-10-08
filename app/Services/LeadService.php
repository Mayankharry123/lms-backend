<?php

namespace App\Services;

use App\Contracts\Repositories\LeadRepositoryInterface;
use App\Models\Lead;
use App\Models\LeadMobileNumber;
use App\Models\LeadAssignHistory;
use DomainException;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Events\LeadAssignedEvent;
use App\Events\LeadCallStatusAddedEvent;
use App\Events\LeadStatusChangedEvent;
use App\Models\CallStatus;
use App\Models\Priority;
use App\Models\Status;

class LeadService
{
    /**
     * @var LeadRepositoryInterface
     */
    protected LeadRepositoryInterface $leadRepository;

    /**
     * Create a new LeadService instance.
     *
     * @param LeadRepositoryInterface $leadRepository
     */
    public function __construct(LeadRepositoryInterface $leadRepository)
    {
        $this->leadRepository = $leadRepository;
    }

    /**
     * Get all leads with pagination.
     *
     * @param int $perPage
     * @param string|null $searchTerm
     * @return LengthAwarePaginator
     * @throws DomainException
     */
    public function getAllLeads(int $perPage = 10, ?string $searchTerm = null): LengthAwarePaginator
    {
        try {
            return $this->leadRepository->getAllLeads($perPage, $searchTerm);
        } catch (QueryException $e) {
            Log::error('Database error fetching leads', ['exception' => $e]);
            throw new DomainException('Database error while fetching leads.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching leads', ['exception' => $e]);
            throw new DomainException('Unexpected error while fetching leads.');
        }
    }

    /**
     * Get a lead by ID.
     *
     * @param int $id
     * @return Lead|null
     * @throws DomainException
     */
    public function getLead(int $id): ?Lead
    {
        try {
            return $this->leadRepository->getLeadById($id);
        } catch (QueryException $e) {
            Log::error('Database error fetching lead', ['id' => $id, 'exception' => $e]);
            throw new DomainException('Database error while fetching lead.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching lead', ['id' => $id, 'exception' => $e]);
            throw new DomainException('Unexpected error while fetching lead.');
        }
    }

    /**
     * Organisation stored on a contact person (lead).
     *
     * @param int $contactPersonId
     * @return int|null
     * @throws ModelNotFoundException
     * @throws DomainException
     */
    public function getOrganisationIdForContactPerson(int $contactPersonId): ?int
    {
        try {
            $lead = Lead::query()->find($contactPersonId);

            if (!$lead || (string) $lead->status === '15') {
                throw (new ModelNotFoundException())->setModel(Lead::class, [$contactPersonId]);
            }

            return $lead->organisation_id ? (int) $lead->organisation_id : null;
        } catch (ModelNotFoundException $e) {
            throw $e;
        } catch (QueryException $e) {
            Log::error('Database error resolving contact person organisation', [
                'contact_person_id' => $contactPersonId,
                'exception' => $e,
            ]);
            throw new DomainException('Database error while fetching contact person.');
        } catch (Exception $e) {
            Log::error('Unexpected error resolving contact person organisation', [
                'contact_person_id' => $contactPersonId,
                'exception' => $e,
            ]);
            throw new DomainException('Unexpected error while fetching contact person.');
        }
    }

    /**
     * Get leads by brand ID.
     *
     * @param int $brandId
     * @param int $perPage
     * @return LengthAwarePaginator
     * @throws DomainException
     */
    public function getLeadsByBrand(int $brandId, int $perPage = 10): LengthAwarePaginator
    {
        try {
            return $this->leadRepository->getLeadsByBrandId($brandId, $perPage);
        } catch (QueryException $e) {
            Log::error('Database error fetching leads by brand', ['brand_id' => $brandId, 'exception' => $e]);
            throw new DomainException('Database error while fetching leads by brand.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching leads by brand', ['brand_id' => $brandId, 'exception' => $e]);
            throw new DomainException('Unexpected error while fetching leads by brand.');
        }
    }

    /**
     * Get leads by agency ID.
     *
     * @param int $agencyId
     * @param int $perPage
     * @return LengthAwarePaginator
     * @throws DomainException
     */
    public function getLeadsByAgency(int $agencyId, int $perPage = 10): LengthAwarePaginator
    {
        try {
            return $this->leadRepository->getLeadsByAgencyId($agencyId, $perPage);
        } catch (QueryException $e) {
            Log::error('Database error fetching leads by agency', ['agency_id' => $agencyId, 'exception' => $e]);
            throw new DomainException('Database error while fetching leads by agency.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching leads by agency', ['agency_id' => $agencyId, 'exception' => $e]);
            throw new DomainException('Unexpected error while fetching leads by agency.');
        }
    }

    /**
     * Get leads assigned to a specific user.
     *
     * @param int $userId
     * @param int $perPage
     * @return LengthAwarePaginator
     * @throws DomainException
     */
    public function getLeadsByAssignedUser(int $userId, int $perPage = 10): LengthAwarePaginator
    {
        try {
            return $this->leadRepository->getLeadsByAssignedUser($userId, $perPage);
        } catch (QueryException $e) {
            Log::error('Database error fetching leads by assigned user', ['user_id' => $userId, 'exception' => $e]);
            throw new DomainException('Database error while fetching leads by assigned user.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching leads by assigned user', ['user_id' => $userId, 'exception' => $e]);
            throw new DomainException('Unexpected error while fetching leads by assigned user.');
        }
    }

    /**
     * Get leads by status.
     *
     * @param string $status
     * @param int $perPage
     * @return LengthAwarePaginator
     * @throws DomainException
     */
    public function getLeadsByStatus(string $status, int $perPage = 10): LengthAwarePaginator
    {
        try {
            return $this->leadRepository->getLeadsByStatus($status, $perPage);
        } catch (QueryException $e) {
            Log::error('Database error fetching leads by status', ['status' => $status, 'exception' => $e]);
            throw new DomainException('Database error while fetching leads by status.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching leads by status', ['status' => $status, 'exception' => $e]);
            throw new DomainException('Unexpected error while fetching leads by status.');
        }
    }

    /**
     * Get leads by priority.
     *
     * @param int $priorityId
     * @param int $perPage
     * @return LengthAwarePaginator
     * @throws DomainException
     */
    public function getLeadsByPriority(int $priorityId, int $perPage = 10): LengthAwarePaginator
    {
        try {
            return $this->leadRepository->getLeadsByPriority($priorityId, $perPage);
        } catch (QueryException $e) {
            Log::error('Database error fetching leads by priority', ['priority_id' => $priorityId, 'exception' => $e]);
            throw new DomainException('Database error while fetching leads by priority.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching leads by priority', ['priority_id' => $priorityId, 'exception' => $e]);
            throw new DomainException('Unexpected error while fetching leads by priority.');
        }
    }

    /**
     * Get a simple list of leads (ID and Name).
     *
     * @return Collection|null
     * @throws DomainException
     */
    public function getLeadList(): ?Collection
    {
        try {
            return $this->leadRepository->getLeadList();
        } catch (QueryException $e) {
            Log::error('Database error fetching lead list', ['exception' => $e]);
            throw new DomainException('Database error while fetching lead list.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching lead list', ['exception' => $e]);
            throw new DomainException('Unexpected error while fetching lead list.');
        }
    }

    /**
     * Get leads with multiple filters.
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     * @throws DomainException
     */
    public function getLeadsWithFilters(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        try {
            return $this->leadRepository->getLeadsWithFilters($filters, $perPage);
        } catch (QueryException $e) {
            Log::error('Database error fetching leads with filters', ['filters' => $filters, 'exception' => $e]);
            throw new DomainException('Database error while fetching leads with filters.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching leads with filters', ['filters' => $filters, 'exception' => $e]);
            throw new DomainException('Unexpected error while fetching leads with filters.');
        }
    }

    /**
     * Get lead count statistics for a priority.
     *
     * @param int $priorityId
     * @param array $filters
     * @return array
     * @throws DomainException
     */
    public function getLeadCountStatsForPriority(int $priorityId, array $filters): array
    {
        try {
            return $this->leadRepository->getLeadCountStatsForPriority($priorityId, $filters);
        } catch (QueryException $e) {
            Log::error('Database error fetching lead count stats for priority', ['priority_id' => $priorityId, 'exception' => $e]);
            throw new DomainException('Database error while fetching lead count stats for priority.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching lead count stats for priority', ['priority_id' => $priorityId, 'exception' => $e]);
            throw new DomainException('Unexpected error while fetching lead count stats for priority.');
        }
    }

    // ============================================================================
    // WRITE OPERATIONS
    // ============================================================================

    /**
     * Create a new lead.
     *
     * @param array $data
     * @return Lead
     * @throws DomainException
     */
    public function createLead(array $data): Lead
    {
        try {
            return DB::transaction(function () use ($data) {
                if (empty($data['name'])) {
                    throw new DomainException('Lead name is required.');
                }

                // Extract current_assign_user before passing to repository to avoid double-assignment
                $currentAssignUser = $data['current_assign_user'] ?? null;
                unset($data['current_assign_user']);

                $lead = $this->leadRepository->createLead($data);

                // Assign the lead user if provided - this will trigger LeadAssignedEvent
                // because lead.current_assign_user transitions from null → userId
                if (!empty($currentAssignUser)) {
                    $this->assignLeadToUser($lead->id, $currentAssignUser);
                }

                return $lead;
            });
        } catch (DomainException $e) {
            throw $e;
        } catch (QueryException $e) {
            Log::error('Database error creating lead', ['data' => $data, 'exception' => $e]);
            throw new DomainException('Database error while creating lead.');
        } catch (Exception $e) {
            Log::error('Unexpected error creating lead', ['data' => $data, 'exception' => $e]);
            throw new DomainException('Unexpected error while creating lead.');
        }
    }

    /**
     * Update an existing lead.
     *
     * @param int $id
     * @param array $data
     * @return bool
     * @throws DomainException
     */
    public function updateLead(int $id, array $data): bool
    {
        try {
            return $this->leadRepository->updateLead($id, $data);
        } catch (QueryException $e) {
            Log::error('Database error updating lead', ['id' => $id, 'data' => $data, 'exception' => $e]);
            throw new DomainException('Database error while updating lead.');
        } catch (Exception $e) {
            Log::error('Unexpected error updating lead', ['id' => $id, 'data' => $data, 'exception' => $e]);
            throw new DomainException('Unexpected error while updating lead.');
        }
    }

    /**
     * Update lead activity fields (comment, call_status_id, and optional reminder).
     *
     * @param int $id
     * @param array<string, mixed> $data
     * @return array{lead: Lead, history: LeadAssignHistory}
     * @throws DomainException
     */
    public function updateLeadActivity(int $id, array $data): array
    {
        try {
            unset($data['lead_id']);

            $allowed = array_intersect_key($data, array_flip([
                'comment',
                'call_status_id',
                'reminder',
                'reminder_at',
                'reminder_before',
                'reminder_before_unit',
            ]));

            return $this->leadRepository->updateLeadActivity($id, $allowed);
        } catch (DomainException $e) {
            throw $e;
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            throw $e;
        } catch (QueryException $e) {
            Log::error('Database error updating lead activity', ['id' => $id, 'data' => $data, 'exception' => $e]);
            throw new DomainException('Database error while updating lead activity.');
        } catch (Exception $e) {
            Log::error('Unexpected error updating lead activity', ['id' => $id, 'data' => $data, 'exception' => $e]);
            throw new DomainException('Unexpected error while updating lead activity.');
        }
    }

    /**
     * Assign a lead to a user.
     *
     * @param int $leadId
     * @param int $userId
     * @return bool
     * @throws DomainException
     */
    public function assignLeadToUser(int $leadId, int $userId): bool
    {
        try {
            // Fetch current assigned user before update
            $lead = $this->leadRepository->getLeadById($leadId);
            $currentAssignedUser = $lead ? $lead->current_assign_user : null;

            $result = $this->leadRepository->assignLeadToUser($leadId, $userId);

            // Fire event only if assignment successful and user changed
            if ($result && $currentAssignedUser != $userId) {
                event(new LeadAssignedEvent($leadId, $userId));
            }

            return $result;
        } catch (QueryException $e) {
            Log::error('Database error assigning lead to user', ['lead_id' => $leadId, 'user_id' => $userId, 'exception' => $e]);
            throw new DomainException('Database error while assigning lead to user.');
        } catch (Exception $e) {
            Log::error('Unexpected error assigning lead to user', ['lead_id' => $leadId, 'user_id' => $userId, 'exception' => $e]);
            throw new DomainException('Unexpected error while assigning lead to user.');
        }
    }

    /**
     * Update lead priority.
     *
     * @param int $leadId
     * @param int $priorityId
     * @return bool
     * @throws DomainException
     */
    public function updateLeadPriority(int $leadId, int $priorityId): bool
    {
        try {
            return $this->leadRepository->updateLeadPriority($leadId, $priorityId);
        } catch (QueryException $e) {
            Log::error('Database error updating lead priority', ['lead_id' => $leadId, 'priority_id' => $priorityId, 'exception' => $e]);
            throw new DomainException('Database error while updating lead priority.');
        } catch (Exception $e) {
            Log::error('Unexpected error updating lead priority', ['lead_id' => $leadId, 'priority_id' => $priorityId, 'exception' => $e]);
            throw new DomainException('Unexpected error while updating lead priority.');
        }
    }

    /**
     * Update lead status.
     *
     * @param int $leadId
     * @param string $status
     * @return bool
     * @throws DomainException
     */
    public function updateLeadStatus(int $leadId, string $status): bool
    {
        try {
            return $this->leadRepository->updateLeadStatus($leadId, $status);
        } catch (QueryException $e) {
            Log::error('Database error updating lead status', ['lead_id' => $leadId, 'status' => $status, 'exception' => $e]);
            throw new DomainException('Database error while updating lead status.');
        } catch (Exception $e) {
            Log::error('Unexpected error updating lead status', ['lead_id' => $leadId, 'status' => $status, 'exception' => $e]);
            throw new DomainException('Unexpected error while updating lead status.');
        }
    }

    /**
     * Add call status to a lead.
     *
     * @param int $leadId
     * @param int $callStatusId
     * @return bool
     * @throws DomainException
     */
    public function addCallStatus(int $leadId, int $callStatusId): bool
    {
        try {
            // Use DB transaction with row-level locking to prevent race condition
            return DB::transaction(function () use ($leadId, $callStatusId) {
                // Get previous call status before update
                $lead = $this->leadRepository->getLeadById($leadId);
                $previousCallStatusId = $lead ? $lead->call_status : null;
                $previousLeadStatus = $lead ? $lead->lead_status : null;

                // Restrict update if last call status was updated within 10 minutes
                // Use lockForUpdate to prevent concurrent modifications
                $lastHistory = \App\Models\LeadAssignHistory::where('lead_id', $leadId)
                    ->orderByDesc('last_call_status_date_time')
                    ->lockForUpdate()
                    ->first();
                if ($lastHistory && $lastHistory->last_call_status_date_time) {
                    $lastUpdate = $lastHistory->last_call_status_date_time;
                    if (now()->diffInMinutes($lastUpdate) < 10) {
                        throw new DomainException('Call status can only be updated once every 10 minutes.');
                    }
                }

                // If status is unchanged, return true without update or event
                if ($previousCallStatusId == $callStatusId) {
                    return true;
                }

                $updatedByUserId = auth()->id();
                $result = $this->leadRepository->addCallStatus($leadId, $callStatusId);

                // Fire event only if update was successful and we have an authenticated user
                // Note: If updatedByUserId is null, the event still fires but listeners should handle gracefully
                if ($result && $updatedByUserId) {
                    event(new \App\Events\LeadCallStatusAddedEvent(
                        $leadId,
                        $callStatusId,
                        $previousCallStatusId,
                        $updatedByUserId,
                        now()
                    ));
                } elseif ($result && !$updatedByUserId) {
                    Log::warning('Call status updated without authenticated user', [
                        'lead_id' => $leadId,
                        'call_status_id' => $callStatusId,
                        'note' => 'Event not fired due to missing authentication context'
                    ]);
                }

                if ($result) {
                    $this->dispatchLeadStatusChangedEvent($leadId, $previousLeadStatus);
                }

                return $result;
            });
        } catch (DomainException $e) {
            // Re-throw DomainException as-is to preserve the original message
            throw $e;
        } catch (QueryException $e) {
            Log::error('Database error adding call status', ['lead_id' => $leadId, 'call_status_id' => $callStatusId, 'exception' => $e]);
            throw new DomainException('Database error while adding call status.');
        } catch (Exception $e) {
            Log::error('Unexpected error adding call status', ['lead_id' => $leadId, 'call_status_id' => $callStatusId, 'exception' => $e]);
            throw new DomainException('Unexpected error while adding call status.');
        }
    }

    /**
     * Remove call status from a lead.
     *
     * @param int $leadId
     * @param int $callStatusId
     * @return bool
     * @throws DomainException
     */
    public function removeCallStatus(int $leadId, int $callStatusId): bool
    {
        try {
            return $this->leadRepository->removeCallStatus($leadId, $callStatusId);
        } catch (QueryException $e) {
            Log::error('Database error removing call status', ['lead_id' => $leadId, 'call_status_id' => $callStatusId, 'exception' => $e]);
            throw new DomainException('Database error while removing call status.');
        } catch (Exception $e) {
            Log::error('Unexpected error removing call status', ['lead_id' => $leadId, 'call_status_id' => $callStatusId, 'exception' => $e]);
            throw new DomainException('Unexpected error while removing call status.');
        }
    }

    /**
     * Delete a lead (soft delete).
     *
     * @param int $id
     * @return bool
     * @throws DomainException
     */
    public function deleteLead(int $id): bool
    {
        try {
            return $this->leadRepository->deleteLead($id);
        } catch (QueryException $e) {
            Log::error('Database error deleting lead', ['id' => $id, 'exception' => $e]);
            throw new DomainException('Database error while deleting lead.');
        } catch (Exception $e) {
            Log::error('Unexpected error deleting lead', ['id' => $id, 'exception' => $e]);
            throw new DomainException('Unexpected error while deleting lead.');
        }
    }

    /**
     * Permanently delete a lead.
     *
     * @param int $id
     * @return bool
     * @throws DomainException
     */
    public function forceDeleteLead(int $id): bool
    {
        try {
            return $this->leadRepository->forceDeleteLead($id);
        } catch (QueryException $e) {
            Log::error('Database error force deleting lead', ['id' => $id, 'exception' => $e]);
            throw new DomainException('Database error while force deleting lead.');
        } catch (Exception $e) {
            Log::error('Unexpected error force deleting lead', ['id' => $id, 'exception' => $e]);
            throw new DomainException('Unexpected error while force deleting lead.');
        }
    }

    /**
     * Get all pending leads.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     * @throws DomainException
     */
    public function getPendingLeads(int $perPage = 10, array $filters = []): LengthAwarePaginator
    {
        try {
            return $this->leadRepository->getPendingLeads($perPage, $filters);
        } catch (QueryException $e) {
            Log::error('Database error fetching pending leads', ['exception' => $e]);
            throw new DomainException('Database error while fetching pending leads.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching pending leads', ['exception' => $e]);
            throw new DomainException('Unexpected error while fetching pending leads.');
        }
    }

    /**
     * Get lead assignment history by lead ID.
     *
     * @param int $leadId
     * @param int $perPage
     * @return LengthAwarePaginator
     * @throws DomainException
     */
    public function getLeadHistory(int $leadId, int $perPage = 10): LengthAwarePaginator
    {
        try {
            // Verify the lead exists
            $lead = $this->leadRepository->getLeadById($leadId);
            if (!$lead) {
                throw new DomainException('Lead not found');
            }

            // Get the lead assign history
            return $this->leadRepository->getLeadHistory($leadId, $perPage);
        } catch (QueryException $e) {
            Log::error('Database error fetching lead history', ['lead_id' => $leadId, 'exception' => $e]);
            throw new DomainException('Database error while fetching lead history.');
        } catch (DomainException $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error('Unexpected error fetching lead history', ['lead_id' => $leadId, 'exception' => $e]);
            throw new DomainException('Unexpected error while fetching lead history.');
        }
    }

    /**
     * Add mobile numbers to a lead.
     *
     * @param int $leadId
     * @param array $mobileNumbers
     * @return bool
     * @throws DomainException
     */
    public function addMobileNumbers(int $leadId, array $mobileNumbers): bool
    {
        try {
            // Verify the lead exists
            $lead = $this->leadRepository->getLeadById($leadId);
            if (!$lead) {
                throw new DomainException('Lead not found');
            }

            // Delete existing mobile numbers for this lead
            LeadMobileNumber::where('lead_id', $leadId)->delete();

            // Add new mobile numbers
            $isFirst = true;
            foreach ($mobileNumbers as $number) {
                LeadMobileNumber::create([
                    'lead_id' => $leadId,
                    'mobile_number' => $number,
                    'is_primary' => $isFirst,
                    'is_verified' => false,
                ]);
                $isFirst = false;
            }

            return true;
        } catch (QueryException $e) {
            Log::error('Database error adding mobile numbers', ['lead_id' => $leadId, 'exception' => $e]);
            throw new DomainException('Database error while adding mobile numbers.');
        } catch (DomainException $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error('Unexpected error adding mobile numbers', ['lead_id' => $leadId, 'exception' => $e]);
            throw new DomainException('Unexpected error while adding mobile numbers.');
        }
    }

    /**
     * Get mobile numbers for a lead.
     *
     * @param int $leadId
     * @return Collection
     * @throws DomainException
     */
    public function getMobileNumbers(int $leadId): Collection
    {
        try {
            return LeadMobileNumber::where('lead_id', $leadId)
                ->orderBy('is_primary', 'desc')
                ->orderBy('created_at', 'asc')
                ->get()
                ->pluck('mobile_number');
        } catch (QueryException $e) {
            Log::error('Database error fetching mobile numbers', ['lead_id' => $leadId, 'exception' => $e]);
            throw new DomainException('Database error while fetching mobile numbers.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching mobile numbers', ['lead_id' => $leadId, 'exception' => $e]);
            throw new DomainException('Unexpected error while fetching mobile numbers.');
        }
    }

    /**
     * Get the latest two follow-up leads.
     *
     * @return Collection
     * @throws DomainException
     */
    public function getLatestTwoFollowUpLeads(array $filters = [])
    {
        try {
            return $this->leadRepository->getLatestTwoFollowUpLeads($filters);
        } catch (QueryException $e) {
            Log::error('Database error fetching latest two follow-up leads', ['exception' => $e]);
            throw new DomainException('Database error while fetching latest follow-up leads.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching latest two follow-up leads', ['exception' => $e]);
            throw new DomainException('Unexpected error while fetching latest follow-up leads.');
        }
    }

    /**
     * Get the latest two meeting-scheduled leads.
     *
     * @return Collection
     * @throws DomainException
     */

    public function getLatestTwoMeetingScheduledLeads(array $filters = [])
    {
        try {
            return $this->leadRepository->getLatestTwoMeetingScheduledLeads($filters);
        } catch (QueryException $e) {
            Log::error('Database error fetching latest two  meeting scheduled leads', ['exception' => $e]);
            throw new DomainException('Database error while fetching latest meeting scheduled leads.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching latest two meeting scheduled leads', ['exception' => $e]);
            throw new DomainException('Unexpected error while fetching latest meeting scheduled leads.');
        }
    }

    /**
     * Get the latest two meeting-done leads.
     *
     * @return Collection
     * @throws DomainException
     */
    public function getLatestTwoMeetingDoneLeads(): Collection
    {
        try {
            return $this->leadRepository->getLatestTwoMeetingDoneLeads();
        } catch (QueryException $e) {
            Log::error('Database error fetching latest two meeting done leads', ['exception' => $e]);
            throw new DomainException('Database error while fetching latest meeting done leads.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching latest two meeting done leads', ['exception' => $e]);
            throw new DomainException('Unexpected error while fetching latest meeting done leads.');
        }
    }

    /**
     * Update lead workflow fields (lead_status, call_status, priority_id) and dispatch notifications.
     *
     * @param int $leadId
     * @param array $fields
     * @return bool
     * @throws DomainException
     */
    public function updateLeadWorkflowFields(int $leadId, array $fields): bool
    {
        $allowed = array_intersect_key($fields, array_flip(['lead_status', 'call_status', 'priority_id']));

        if (empty($allowed)) {
            return false;
        }

        try {
            $lead = $this->leadRepository->getLeadById($leadId);

            if (!$lead) {
                throw new DomainException('Lead not found.');
            }

            $previousLeadStatus = $lead->lead_status;
            $result = $this->leadRepository->updateLead($leadId, $allowed);

            if ($result) {
                $this->dispatchLeadStatusChangedEvent($leadId, $previousLeadStatus);
            }

            return $result;
        } catch (DomainException $e) {
            throw $e;
        } catch (QueryException $e) {
            Log::error('Database error updating lead workflow fields', ['lead_id' => $leadId, 'fields' => $fields, 'exception' => $e]);
            throw new DomainException('Database error while updating lead workflow fields.');
        } catch (Exception $e) {
            Log::error('Unexpected error updating lead workflow fields', ['lead_id' => $leadId, 'fields' => $fields, 'exception' => $e]);
            throw new DomainException('Unexpected error while updating lead workflow fields.');
        }
    }

    /**
     * Apply workflow updates when a meeting is scheduled for a lead.
     */
    public function applyMeetingScheduleWorkflow(int $leadId): bool
    {
        $updateData = [];

        $meetingScheduleStatus = Status::where('name', 'Meeting Schedule')->first();
        $meetingScheduleCallStatus = CallStatus::where('name', 'Meeting Schedule')->first();
        $mediumPriority = Priority::where('name', 'Medium')->first();

        if ($meetingScheduleStatus) {
            $updateData['lead_status'] = $meetingScheduleStatus->id;
        }

        if ($meetingScheduleCallStatus) {
            $updateData['call_status'] = $meetingScheduleCallStatus->id;
        }

        if ($mediumPriority) {
            $updateData['priority_id'] = $mediumPriority->id;
        }

        if (empty($updateData)) {
            Log::warning('No matching statuses or priority found for meeting schedule workflow', ['lead_id' => $leadId]);
            return false;
        }

        return $this->updateLeadWorkflowFields($leadId, $updateData);
    }

    /**
     * Apply workflow updates when a meeting is marked complete.
     */
    public function applyMeetingCompleteWorkflow(int $leadId): bool
    {
        $updateData = [];

        $meetingDoneStatus = Status::where('name', 'Meeting Done')->first();
        $meetingDoneCallStatus = CallStatus::where('name', 'Meeting Done')->first();
        $mediumPriority = Priority::where('name', 'Medium')->first();

        if ($meetingDoneStatus) {
            $updateData['lead_status'] = $meetingDoneStatus->id;
        }

        if ($meetingDoneCallStatus) {
            $updateData['call_status'] = $meetingDoneCallStatus->id;
        }

        if ($mediumPriority) {
            $updateData['priority_id'] = $mediumPriority->id;
        }

        if (empty($updateData)) {
            Log::warning('No matching statuses or priority found for meeting complete workflow', ['lead_id' => $leadId]);
            return false;
        }

        return $this->updateLeadWorkflowFields($leadId, $updateData);
    }

    /**
     * Get the latest two leads.
     *
     * @param array $filters
     * @return \Illuminate\Support\Collection
     */
    public function getLatestTwoLeads(array $filters = [])
    {
        return $this->leadRepository->getLatestTwoLeads($filters);
    }

    /**
     * Fire lead status changed notification when lead_status column changes.
     */
    protected function dispatchLeadStatusChangedEvent(int $leadId, ?int $previousLeadStatus): void
    {
        $lead = $this->leadRepository->getLeadById($leadId);

        if (!$lead || !$lead->lead_status || $lead->lead_status == $previousLeadStatus) {
            return;
        }

        event(new LeadStatusChangedEvent($leadId, (int) $lead->lead_status));
    }

    /**
     * Get lead performance data for a specific assigned user.
     *
     * @param int $userId
     * @return Collection
     * @throws DomainException
     */
    public function getUserLeadPerformance(int $userId, array $filters = []): Collection
    {
        try {
            return $this->leadRepository->getUserLeadPerformance($userId, $filters);
        } catch (QueryException $e) {
            Log::error('Database error fetching user lead performance', ['user_id' => $userId, 'exception' => $e]);
            throw new DomainException('Database error while fetching user lead performance.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching user lead performance', ['user_id' => $userId, 'exception' => $e]);
            throw new DomainException('Unexpected error while fetching user lead performance.');
        }
    }

    /**
     * Get assign-history comments for a lead in pages of 9.
     *
     * @param int $leadId
     * @param int $perPage
     * @return LengthAwarePaginator
     * @throws DomainException
     */
    public function getAssignHistoryByLeadId(int $leadId, int $perPage = 9): LengthAwarePaginator
    {
        try {
            return $this->leadRepository->getAssignHistoryByLeadId($leadId, $perPage);
        } catch (QueryException $e) {
            Log::error('Database error fetching lead assign history', ['lead_id' => $leadId, 'exception' => $e]);
            throw new DomainException('Database error while fetching lead assign history.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching lead assign history', ['lead_id' => $leadId, 'exception' => $e]);
            throw new DomainException('Unexpected error while fetching lead assign history.');
        }
    }
}

