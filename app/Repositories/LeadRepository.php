<?php

namespace App\Repositories;

use App\Contracts\Repositories\LeadRepositoryInterface;
use App\Models\Lead;
use App\Models\LeadAssignHistory;
use App\Models\LeadMobileNumber;
use App\Models\Meeting;
use App\Models\Priority;
use App\Models\Status;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use DomainException;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class LeadRepository implements LeadRepositoryInterface
{
    /**
     * @var Lead
     */
    protected Lead $model;

    /**
     * Create a new LeadRepository instance.
     *
     * @param Lead $lead
     */
    public function __construct(Lead $lead)
    {
        $this->model = $lead;
    }

    // ============================================================================
    // READ OPERATIONS
    // ============================================================================

    /**
     * Fetch paginated list of leads with relationships.
     * Filtered by user access: Super Admin sees all, others see only their leads.
     *
     * @param int $perPage
     * @param string|null $searchTerm
     * @return LengthAwarePaginator
     */
    public function getAllLeads(int $perPage = 10, ?string $searchTerm = null): LengthAwarePaginator
    {
        return $this->model->getAllLeads($perPage, $searchTerm);
    }

    /**
     * Fetch a single lead by its primary ID.
     *
     * @param int $id
     * @return Lead|null
     */
    public function getLeadById(int $id): ?Lead
    {
        return $this->model->getLeadById($id);
    }

    /**
     * Fetch leads by brand ID.
     *
     * @param int $brandId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getLeadsByBrandId(int $brandId, int $perPage = 10): LengthAwarePaginator
    {
        return $this->model->getLeadsByBrandId($brandId, $perPage);
    }

    /**
     * Fetch leads by agency ID.
     *
     * @param int $agencyId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getLeadsByAgencyId(int $agencyId, int $perPage = 10): LengthAwarePaginator
    {
        return $this->model->getLeadsByAgencyId($agencyId, $perPage);
    }

    /**
     * Fetch leads assigned to a specific user.
     *
     * @param int $userId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getLeadsByAssignedUser(int $userId, int $perPage = 10): LengthAwarePaginator
    {
        return $this->model->getLeadsByAssignedUser($userId, $perPage);
    }

    /**
     * Fetch leads by status.
     *
     * @param string $status
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getLeadsByStatus(string $status, int $perPage = 10): LengthAwarePaginator
    {
        return $this->model->getLeadsByStatus($status, $perPage);
    }

    /**
     * Fetch leads by priority ID.
     *
     * @param int $priorityId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getLeadsByPriority(int $priorityId, int $perPage = 10): LengthAwarePaginator
    {
        return $this->model->getLeadsByPriority($priorityId, $perPage);
    }

    /**
     * Get a simple list of leads (ID and Name).
     *
     * @return Collection|null
     */
    public function getLeadList(): ?Collection
    {
        return $this->model->getLeadList();
    }

    /**
     * Get lead assignment history by lead ID.
     *
     * @param int $leadId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getLeadHistory(int $leadId, int $perPage = 10): LengthAwarePaginator
    {
        return $this->model->getLeadHistory($leadId, $perPage);
    }

    /**
     * Fetch leads by multiple criteria.
     *
     * @param array<string, mixed> $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getLeadsWithFilters(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return $this->model->getLeadsWithFilters($filters, $perPage);
    }

    // ============================================================================
    // WRITE OPERATIONS
    // ============================================================================

    /**
     * Create a new lead record.
     * If call_status_id is provided, automatically sets call_status and lead_status.
     *
     * @param array<string, mixed> $data
     * @return Lead
     */
    public function createLead(array $data): Lead
    {
        try {
            // Generate UUID if not provided
            if (!isset($data['uuid'])) {
                $data['uuid'] = (string) Str::uuid();
            }
            
            // Generate slug from name if not provided
            if (!isset($data['slug'])) {
                $data['slug'] = Str::slug($data['name'] ?? 'lead-' . time());
            }
            
            // Set created_by to the currently authenticated user if not provided
            if (!isset($data['created_by'])) {
                $currentUser = Auth::user();
                if ($currentUser) {
                    $data['created_by'] = $currentUser->id;
                }
            }
            
            // Extract mobile numbers before creating the lead
            $mobileNumbers = $data['mobile_number'] ?? [];
            
            // Ensure mobile_number is an array
            if (is_string($mobileNumbers)) {
                $mobileNumbers = [$mobileNumbers];
            }
            
            // Remove mobile_number from data as it will be stored separately
            unset($data['mobile_number']);
            
            // Remove current_assign_user to prevent double-assignment during creation.
            // The assignment should be handled explicitly via assignLeadToUser() in the service layer
            // to ensure the LeadAssignedEvent is fired (detecting a change from null → userId).
            unset($data['current_assign_user']);
            
            // Handle call_status_id: convert to call_status and lead_status
            if (isset($data['call_status_id']) && !empty($data['call_status_id'])) {
                $callStatusId = $data['call_status_id'];
                $data['call_status'] = $callStatusId;
                
                // Find the Status that contains this callStatusId
                $statusRecord = Status::findForCallStatus((int) $callStatusId);
                
                // If matching status found, set lead_status
                if ($statusRecord) {
                    $data['lead_status'] = $statusRecord->id;
                }
                
                // Find and set priority based on call_status_id
                $priorityRecord = Priority::findForCallStatus((int) $callStatusId);
                
                // If matching priority found, set priority_id
                if ($priorityRecord) {
                    $data['priority_id'] = $priorityRecord->id;
                }
                
                // Remove call_status_id from data as it's not a column
                unset($data['call_status_id']);
            }
            
            // Remove null values to avoid validation errors
            $data = array_filter($data, function ($value) {
                return $value !== null && $value !== '';
            });
            
            // Create the lead
            $lead = $this->model->create($data);
            
            // Add mobile numbers if provided
            if (!empty($mobileNumbers)) {
                $isFirst = true;
                foreach ($mobileNumbers as $number) {
                    LeadMobileNumber::create([
                        'lead_id' => $lead->id,
                        'mobile_number' => $number,
                        'is_primary' => $isFirst,
                        'is_verified' => false,
                    ]);
                    $isFirst = false;
                }
            }

            if ($this->shouldRecordCreatedLeadHistory($lead)) {
                $this->saveLeadHistory($lead, !empty($lead->call_status));
            }
            
            return $lead;
        } catch (DomainException $e) {
            throw $e;
        } catch (QueryException $e) {
            Log::error('Database error creating lead', ['data' => $data, 'exception' => $e->getMessage()]);
            throw new DomainException('Database error while creating lead.');
        } catch (Exception $e) {
            Log::error('Unexpected error creating lead', ['data' => $data, 'exception' => $e->getMessage()]);
            throw new DomainException('Unexpected error while creating lead.');
        }
    }

    /**
     * Update an existing lead by ID.
     *
     * @param int $id
     * @param array<string, mixed> $data
     * @return bool
     */
    public function updateLead(int $id, array $data): bool
    {
        try {
            $lead = $this->model->findOrFail($id);
            
            // If name is being updated, also update the slug
            if (isset($data['name'])) {
                $data['slug'] = Str::slug($data['name']);
            }
            
            // Handle brand_id and agency_id updates
            // If brand_id is being set, clear agency_id
            if (isset($data['brand_id']) && !empty($data['brand_id'])) {
                $data['agency_id'] = null;
            }
            
            // If agency_id is being set, clear brand_id
            if (isset($data['agency_id']) && !empty($data['agency_id'])) {
                $data['brand_id'] = null;
            }
            
            // Extract mobile numbers before updating
            $mobileNumbers = null;
            if (isset($data['mobile_number'])) {
                $mobileNumbers = $data['mobile_number'];
                if (is_string($mobileNumbers)) {
                    $mobileNumbers = [$mobileNumbers];
                }
                // Remove from data as it will be updated separately
                unset($data['mobile_number']);
            }
            
            // Handle call_status_id: convert to call_status, lead_status, and priority_id
            if (isset($data['call_status_id']) && !empty($data['call_status_id'])) {
                $callStatusId = $data['call_status_id'];
                $data['call_status'] = $callStatusId;
                
                // Find the Status that contains this callStatusId
                $statusRecord = Status::findForCallStatus((int) $callStatusId);
                
                // If matching status found, set lead_status
                if ($statusRecord) {
                    $data['lead_status'] = $statusRecord->id;
                }
                
                // Find and set priority based on call_status_id
                $priorityRecord = Priority::findForCallStatus((int) $callStatusId);
                
                // If matching priority found, set priority_id
                if ($priorityRecord) {
                    $data['priority_id'] = $priorityRecord->id;
                }
                
                // Remove call_status_id from data as it's not a column
                unset($data['call_status_id']);
            }
            
            $shouldRecordHistory = $this->hasStatusOrCommentChange($lead, $data);
            $callStatusChanged = array_key_exists('call_status', $data)
                && $this->historyValuesDiffer($data['call_status'] ?? null, $lead->call_status);

            $result = $lead->update($data);

            if ($shouldRecordHistory && $result) {
                $lead->refresh();
                $this->saveLeadHistory($lead, $callStatusChanged);
            }
            
            // Update mobile numbers if provided
            if ($mobileNumbers !== null && !empty($mobileNumbers)) {
                LeadMobileNumber::deleteForLead($id);
                $isFirst = true;
                foreach ($mobileNumbers as $number) {
                    LeadMobileNumber::create([
                        'lead_id' => $id,
                        'mobile_number' => $number,
                        'is_primary' => $isFirst,
                        'is_verified' => false,
                    ]);
                    $isFirst = false;
                }
            }
            
            return $result;
        } catch (QueryException $e) {
            Log::error('Database error updating lead', ['id' => $id, 'data' => $data, 'exception' => $e]);
            throw new DomainException('Database error while updating lead.');
        } catch (Exception $e) {
            Log::error('Unexpected error updating lead', ['id' => $id, 'data' => $data, 'exception' => $e]);
            throw new DomainException('Unexpected error while updating lead.');
        }
    }

    /**
     * Update only lead activity fields (comment and call_status) and record history.
     *
     * @param int $id
     * @param array<string, mixed> $data
     * @return array{lead: Lead, history: LeadAssignHistory}
     */
    public function updateLeadActivity(int $id, array $data): array
    {
        try {
            $lead = $this->model->findOrFail($id);

            $payload = [
                'comment' => $data['comment'] ?? $lead->comment,
            ];

            $callStatusChanged = false;
            if (array_key_exists('call_status_id', $data) && $data['call_status_id'] !== null && $data['call_status_id'] !== '') {
                $payload['call_status'] = $data['call_status_id'];
                $callStatusChanged = $this->historyValuesDiffer($payload['call_status'], $lead->call_status);

                $statusRecord = Status::findForCallStatus((int) $data['call_status_id']);
                if ($statusRecord) {
                    $payload['lead_status'] = $statusRecord->id;
                }
            }

            $lead->update($payload);
            $lead->refresh();

            $history = $this->saveLeadHistory($lead, $callStatusChanged, $this->reminderPayload($data), false);
            if (!$history) {
                throw new DomainException('Unable to save lead activity.');
            }

            $lead->load(['callStatusRelation', 'leadStatusRelation']);

            return [
                'lead' => $lead,
                'history' => $history,
            ];
        } catch (ModelNotFoundException $e) {
            throw $e;
        } catch (QueryException $e) {
            Log::error('Database error updating lead activity', ['id' => $id, 'data' => $data, 'exception' => $e]);
            throw new DomainException('Database error while updating lead activity.');
        } catch (DomainException $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error('Unexpected error updating lead activity', ['id' => $id, 'data' => $data, 'exception' => $e]);
            throw new DomainException('Unexpected error while updating lead activity.');
        }
    }

    /**
     * @param array<string, mixed> $data
     * @return array{reminder: bool, reminder_at: mixed, reminder_before: mixed, reminder_before_unit: mixed}
     */
    private function reminderPayload(array $data): array
    {
        $enabled = filter_var($data['reminder'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if (!$enabled) {
            return [
                'reminder' => false,
                'reminder_at' => null,
                'reminder_before' => null,
                'reminder_before_unit' => null,
            ];
        }

        return [
            'reminder' => true,
            'reminder_at' => $data['reminder_at'] ?? null,
            'reminder_before' => $data['reminder_before'] ?? null,
            'reminder_before_unit' => $data['reminder_before_unit'] ?? null,
        ];
    }

    /**
     * Assign a lead to a user.
     *
     * @param int $leadId
     * @param int $userId
     * @return bool
     */
    public function assignLeadToUser(int $leadId, int $userId): bool
    {
        try {
            $lead = $this->model->findOrFail($leadId);
            
            // Save history before update (captures old data)
            $this->saveLeadHistory($lead);
            
            $result = $lead->update(['current_assign_user' => $userId]);
            
            return $result;
        } catch (QueryException $e) {
            Log::error('Database error assigning lead', ['lead_id' => $leadId, 'user_id' => $userId, 'exception' => $e]);
            throw new DomainException('Database error while assigning lead.');
        } catch (Exception $e) {
            Log::error('Unexpected error assigning lead', ['lead_id' => $leadId, 'user_id' => $userId, 'exception' => $e]);
            throw new DomainException('Unexpected error while assigning lead.');
        }
    }

    /**
     * Update the priority of a lead.
     *
     * @param int $leadId
     * @param int $priorityId
     * @return bool
     */
    public function updateLeadPriority(int $leadId, int $priorityId): bool
    {
        try {
            $lead = $this->model->findOrFail($leadId);
            
            // Save history before update (captures old data)
            $this->saveLeadHistory($lead);
            
            $result = $lead->update(['priority_id' => $priorityId]);
            
            return $result;
        } catch (QueryException $e) {
            Log::error('Database error updating lead priority', ['lead_id' => $leadId, 'priority_id' => $priorityId, 'exception' => $e]);
            throw new DomainException('Database error while updating lead priority.');
        } catch (Exception $e) {
            Log::error('Unexpected error updating lead priority', ['lead_id' => $leadId, 'priority_id' => $priorityId, 'exception' => $e]);
            throw new DomainException('Unexpected error while updating lead priority.');
        }
    }

    /**
     * Update the status of a lead.
     *
     * @param int $leadId
     * @param string $status
     * @return bool
     */
    public function updateLeadStatus(int $leadId, string $status): bool
    {
        try {
            $lead = $this->model->findOrFail($leadId);
            
            // Save history before update (captures old data)
            $this->saveLeadHistory($lead);
            
            $result = $lead->update(['status' => $status]);
            
            return $result;
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
     */
    public function addCallStatus(int $leadId, int $callStatusId): bool
    {
        try {
            $lead = $this->model->findOrFail($leadId);
            
            // Get all statuses to find which one contains this callStatusId
            $statusRecord = Status::findForCallStatus($callStatusId);
            
            // Get priority based on call_status mapping
            $priorityId = Priority::findForCallStatus($callStatusId)?->id;
            
            Log::info('Adding call status', [
                'lead_id' => $leadId,
                'call_status_id' => $callStatusId,
                'status_record_found' => $statusRecord ? 'Yes' : 'No',
                'status_id' => $statusRecord?->id,
                'priority_id' => $priorityId,
            ]);
            
            // Prepare update data
            $updateData = [
                'call_status' => $callStatusId,
                'call_attempt' => $lead->call_attempt + 1, // Increment call attempt count
            ];
            
            // If matching status found, also update lead_status (the lead's status)
            if ($statusRecord) {
                $updateData['lead_status'] = $statusRecord->id;
            }
            
            // If priority found, update it
            if ($priorityId) {
                $updateData['priority_id'] = $priorityId;
            }
            
            // Save history before update (captures old data)
            $this->saveLeadHistory($lead);
            
            $result = $lead->update($updateData);
            
            return $result;
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
     */
    public function removeCallStatus(int $leadId, int $callStatusId): bool
    {
        try {
            $lead = $this->model->findOrFail($leadId);
            
            // Only remove if the current call_status matches the provided one
            if ($lead->call_status === $callStatusId) {
                // Save history before update (captures old data)
                $this->saveLeadHistory($lead);
                
                $result = $lead->update([
                    'call_status' => null,
                    'lead_status' => null,
                ]);
                
                return $result;
            }
            
            return true;
        } catch (QueryException $e) {
            Log::error('Database error removing call status', ['lead_id' => $leadId, 'call_status_id' => $callStatusId, 'exception' => $e]);
            throw new DomainException('Database error while removing call status.');
        } catch (Exception $e) {
            Log::error('Unexpected error removing call status', ['lead_id' => $leadId, 'call_status_id' => $callStatusId, 'exception' => $e]);
            throw new DomainException('Unexpected error while removing call status.');
        }
    }

    /**
     * Soft delete a lead by ID.
     *
     * @param int $id
     * @return bool
     */
    public function deleteLead(int $id): bool
    {
        try {
            $lead = $this->model->findOrFail($id);
            return $lead->delete();
        } catch (QueryException $e) {
            Log::error('Database error deleting lead', ['id' => $id, 'exception' => $e]);
            throw new DomainException('Database error while deleting lead.');
        } catch (Exception $e) {
            Log::error('Unexpected error deleting lead', ['id' => $id, 'exception' => $e]);
            throw new DomainException('Unexpected error while deleting lead.');
        }
    }

    /**
     * Permanently delete a lead by ID.
     *
     * @param int $id
     * @return bool
     */
    public function forceDeleteLead(int $id): bool
    {
        try {
            $lead = $this->model->findOrFail($id);
            return $lead->forceDelete();
        } catch (QueryException $e) {
            Log::error('Database error force deleting lead', ['id' => $id, 'exception' => $e]);
            throw new DomainException('Database error while force deleting lead.');
        } catch (Exception $e) {
            Log::error('Unexpected error force deleting lead', ['id' => $id, 'exception' => $e]);
            throw new DomainException('Unexpected error while force deleting lead.');
        }
    }

    /**
     * Fetch pending leads (leads with pending status).
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPendingLeads(int $perPage = 10, array $filters = []): LengthAwarePaginator
    {
        return $this->model->getPendingLeads($perPage, $filters);
    }

    /**
     * Fetch all leads assigned to a specific user with performance relations.
     *
     * @param int $userId
     * @return Collection
     */
    public function getUserLeadPerformance(int $userId, array $filters = []): Collection
    {
        return $this->model->getUserLeadPerformance($userId, $filters);
    }

    /**
     * Fetch assign-history comments for a lead in pages of 9.
     *
     * @param int $leadId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getAssignHistoryByLeadId(int $leadId, int $perPage = 9): LengthAwarePaginator
    {
        return $this->model->getAssignHistoryByLeadId($leadId, $perPage);
    }

    /**
     * Whether create should write a lead_assign_histories row.
     */
    private function shouldRecordCreatedLeadHistory(Lead $lead): bool
    {
        return $this->normalizeHistoryValue($lead->comment) !== ''
            || !empty($lead->call_status)
            || !empty($lead->lead_status)
            || $this->normalizeHistoryValue($lead->status) !== '1';
    }

    /**
     * Whether the update payload changes status (enum / call / lead) or comment.
     *
     * @param array<string, mixed> $data
     */
    private function hasStatusOrCommentChange(Lead $lead, array $data): bool
    {
        if (array_key_exists('comment', $data)
            && $this->historyValuesDiffer($data['comment'] ?? null, $lead->comment)
        ) {
            return true;
        }

        if (array_key_exists('status', $data)
            && $this->historyValuesDiffer($data['status'] ?? null, $lead->status)
        ) {
            return true;
        }

        if (array_key_exists('call_status', $data)
            && $this->historyValuesDiffer($data['call_status'] ?? null, $lead->call_status)
        ) {
            return true;
        }

        if (array_key_exists('lead_status', $data)
            && $this->historyValuesDiffer($data['lead_status'] ?? null, $lead->lead_status)
        ) {
            return true;
        }

        return false;
    }

    private function historyValuesDiffer(mixed $left, mixed $right): bool
    {
        return $this->normalizeHistoryValue($left) !== $this->normalizeHistoryValue($right);
    }

    private function normalizeHistoryValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        return trim((string) $value);
    }

    /**
     * Save lead update history to lead_assign_histories table.
     *
     * @param Lead $lead
     * @param bool $touchCallStatusTime When false (comment-only), do not reset the 1-hour call-status lock.
     * @param array<string, mixed> $reminder
     * @param bool $failSilently
     * @return LeadAssignHistory|null
     */
    private function saveLeadHistory(
        Lead $lead,
        bool $touchCallStatusTime = true,
        array $reminder = [],
        bool $failSilently = true
    ): ?LeadAssignHistory {
        try {
            // Get current authenticated user
            $currentUserId = Auth::check() ? Auth::id() : null;

            $meetingContext = Meeting::getLeadHistoryContext($lead->id);
            $allMeetings = $meetingContext['all'];
            
            Log::info('Lead history - All meetings for lead', [
                'lead_id' => $lead->id,
                'total_meetings' => $allMeetings->count(),
                'meetings' => $allMeetings->map(fn($m) => [
                    'id' => $m->id,
                    'status' => $m->status,
                    'deleted_at' => $m->deleted_at,
                    'meeting_date' => $m->meeting_date,
                    'meeting_time' => $m->meeting_time,
                ])->toArray(),
            ]);

            $meeting = $meetingContext['meeting'];
            
            $meetingDateTime = null;
            
            Log::info('Lead history - Filtered meeting query', [
                'lead_id' => $lead->id,
                'meeting_found' => $meeting ? 'Yes' : 'No',
                'meeting_data' => $meeting ? [
                    'id' => $meeting->id,
                    'lead_id' => $meeting->lead_id,
                    'meeting_date' => $meeting->meeting_date,
                    'meeting_time' => $meeting->meeting_time,
                    'status' => $meeting->status,
                    'deleted_at' => $meeting->deleted_at,
                ] : null,
            ]);
            
            if ($meeting && $meeting->meeting_date) {
                // Store only the meeting date
                $meetingDateTime = $meeting->meeting_date;
            }

            $reminderEnabled = filter_var($reminder['reminder'] ?? false, FILTER_VALIDATE_BOOLEAN);

            // Create history record
            $history = LeadAssignHistory::createHistory([
                'uuid' => Str::uuid(),
                'lead_id' => $lead->id,
                'assign_user_id' => $lead->current_assign_user ?? $currentUserId ?? $lead->created_by,
                'current_user_id' => $currentUserId,
                'priority_id' => $lead->priority_id,
                'lead_status_id' => $lead->lead_status,
                'call_status_id' => $lead->call_status,
                'last_call_status_date_time' => $touchCallStatusTime ? now() : null,
                'lead_comment' => $lead->comment,
                'reminder' => $reminderEnabled,
                'reminder_at' => $reminderEnabled ? ($reminder['reminder_at'] ?? null) : null,
                'reminder_before' => $reminderEnabled ? ($reminder['reminder_before'] ?? null) : null,
                'reminder_before_unit' => $reminderEnabled ? ($reminder['reminder_before_unit'] ?? null) : null,
                'meeting_date' => $meeting?->meeting_date,
                'meeting_time' => $meeting?->meeting_time,
                'status' => $lead->status,
            ]);
            
            Log::info('Lead assign history created', [
                'lead_id' => $lead->id,
                'meeting_date_time' => $meetingDateTime,
            ]);

            return $history;
        } catch (Exception $e) {
            Log::warning('Failed to record lead history', [
                'lead_id' => $lead->id,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if (!$failSilently) {
                throw $e;
            }

            return null;
        }
    }

    /**
     * Get the latest two leads.
     *
     * @param array $filters
     * @return Collection
     */
    public function getLatestTwoLeads(array $filters = [])
    {
        return $this->model->getLatestTwoLeads($filters);
    }

    /**
     * Get the latest two follow-up leads.
     *
     * @return Collection
     */

    public function getLatestTwoFollowUpLeads(array $filters = [])
    {
        return $this->model->getLatestTwoFollowUpLeads($filters);
    }

    /**
     * Get the latest two meeting scheduled leads.
     *
     * @return Collection
     */
    public function getLatestTwoMeetingScheduledLeads(array $filters = [])
    {
        return $this->model->getLatestTwoMeetingScheduledLeads($filters);
    }

    /**
     * Get the latest two meeting done leads.
     *
     * @return Collection
     */
    public function getLatestTwoMeetingDoneLeads()
    {
        return $this->model->getLatestTwoMeetingDoneLeads();
    }

    /**
     * Get lead count statistics for a given priority.
     *
     * @param int $priorityId
     * @param array $filters
     * @return array
     */
    public function getLeadCountStatsForPriority(int $priorityId, array $filters): array
    {
        return $this->model->getLeadCountStatsForPriority($priorityId, $filters);
    }
}
