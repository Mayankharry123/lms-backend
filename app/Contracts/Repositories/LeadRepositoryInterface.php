<?php

namespace App\Contracts\Repositories;

use App\Models\Lead;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface LeadRepositoryInterface
{
    /**
     * Fetch paginated list of leads with relationships.
     *
     * @param int $perPage The number of items per page.
     * @param string|null $searchTerm Optional search term to filter leads.
     * @return LengthAwarePaginator
     */
    public function getAllLeads(int $perPage = 10, ?string $searchTerm = null): LengthAwarePaginator;

    /**
     * Fetch a single lead by its primary ID.
     *
     * @param int $id The lead ID.
     * @return Lead|null
     */
    public function getLeadById(int $id): ?Lead;

    /**
     * Fetch leads by brand ID.
     *
     * @param int $brandId The brand ID.
     * @param int $perPage The number of items per page.
     * @return LengthAwarePaginator
     */
    public function getLeadsByBrandId(int $brandId, int $perPage = 10): LengthAwarePaginator;

    /**
     * Fetch leads by agency ID.
     *
     * @param int $agencyId The agency ID.
     * @param int $perPage The number of items per page.
     * @return LengthAwarePaginator
     */
    public function getLeadsByAgencyId(int $agencyId, int $perPage = 10): LengthAwarePaginator;

    /**
     * Fetch leads assigned to a specific user.
     *
     * @param int $userId The user ID.
     * @param int $perPage The number of items per page.
     * @return LengthAwarePaginator
     */
    public function getLeadsByAssignedUser(int $userId, int $perPage = 10): LengthAwarePaginator;

    /**
     * Fetch leads by status.
     *
     * @param string $status The status value.
     * @param int $perPage The number of items per page.
     * @return LengthAwarePaginator
     */
    public function getLeadsByStatus(string $status, int $perPage = 10): LengthAwarePaginator;

    /**
     * Fetch leads by priority ID.
     *
     * @param int $priorityId The priority ID.
     * @param int $perPage The number of items per page.
     * @return LengthAwarePaginator
     */
    public function getLeadsByPriority(int $priorityId, int $perPage = 10): LengthAwarePaginator;

    /**
     * Get a simple list of leads (ID and Name).
     *
     * @return Collection|null
     */
    public function getLeadList(): ?Collection;

    /**
     * Fetch leads by multiple criteria.
     *
     * @param array<string, mixed> $filters
     * @param int $perPage The number of items per page.
     * @return LengthAwarePaginator
     */
    public function getLeadsWithFilters(array $filters, int $perPage = 10): LengthAwarePaginator;

    /**
     * Fetch pending leads (leads with pending status).
     *
     * @param int $perPage The number of items per page.
     * @return LengthAwarePaginator
     */
    public function getPendingLeads(int $perPage = 10, array $filters = []): LengthAwarePaginator;

    /**
     * Fetch all leads assigned to a specific user with performance relations.
     *
     * @param int $userId The user ID.
     * @return Collection
     */
    public function getUserLeadPerformance(int $userId, array $filters = []): Collection;

    /**
     * Fetch assign-history comments for a lead in pages of 9.
     *
     * @param int $leadId The lead ID.
     * @param int $perPage Number of history rows per page.
     * @return LengthAwarePaginator
     */
    public function getAssignHistoryByLeadId(int $leadId, int $perPage = 9): LengthAwarePaginator;

    // ============================================================================
    // WRITE OPERATIONS
    // ============================================================================

    /**
     * Create a new lead record.
     *
     * @param array<string, mixed> $data
     * @return Lead
     */
    public function createLead(array $data): Lead;

    /**
     * Update an existing lead by ID.
     *
     * @param int $id The lead ID.
     * @param array<string, mixed> $data
     * @return bool
     */
    public function updateLead(int $id, array $data): bool;

    /**
     * Update lead activity fields only (comment and/or call_status).
     *
     * @param int $id The lead ID.
     * @param array<string, mixed> $data
     * @return array{lead: Lead, history: \App\Models\LeadAssignHistory}
     */
    public function updateLeadActivity(int $id, array $data): array;

    /**
     * Assign a lead to a user.
     *
     * @param int $leadId The lead ID.
     * @param int $userId The user ID.
     * @return bool
     */
    public function assignLeadToUser(int $leadId, int $userId): bool;

    /**
     * Update the priority of a lead.
     *
     * @param int $leadId The lead ID.
     * @param int $priorityId The priority ID.
     * @return bool
     */
    public function updateLeadPriority(int $leadId, int $priorityId): bool;

    /**
     * Update the status of a lead.
     *
     * @param int $leadId The lead ID.
     * @param string $status The status value.
     * @return bool
     */
    public function updateLeadStatus(int $leadId, string $status): bool;

    /**
     * Add call status to a lead.
     *
     * @param int $leadId The lead ID.
     * @param int $callStatusId The call status ID.
     * @return bool
     */
    public function addCallStatus(int $leadId, int $callStatusId): bool;

    /**
     * Remove call status from a lead.
     *
     * @param int $leadId The lead ID.
     * @param int $callStatusId The call status ID.
     * @return bool
     */
    public function removeCallStatus(int $leadId, int $callStatusId): bool;

    /**
     * Soft delete a lead by ID.
     *
     * @param int $id The lead ID.
     * @return bool
     */
    public function deleteLead(int $id): bool;

    /**
     * Permanently delete a lead by ID.
     *
     * @param int $id The lead ID.
     * @return bool
     */
    public function forceDeleteLead(int $id): bool;

    /**
     * Get the latest two follow-up leads.
     *
     * @return Collection
     */
    public function getLatestTwoFollowUpLeads(array $filters = []);

    /**
     * Get the latest two meeting-scheduled leads.
     *
     * @return Collection
     */    
    public function getLatestTwoMeetingScheduledLeads(array $filters = []);

    /**
     * Get the latest two meeting-done leads.
     *
     * @return Collection
     */
    public function getLatestTwoMeetingDoneLeads();

    /**
     * Get lead count statistics for a given priority.
     *
     * @param int $priorityId
     * @param array $filters
     * @return array
     */
    public function getLeadCountStatsForPriority(int $priorityId, array $filters): array;
}
