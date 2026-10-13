<?php

/**
 * MissCampaign Repository Interface
 * -----------------------------------------
 * Defines the contract for miss campaign data access operations and repository methods.
 *
 * @package App\Contracts\Repositories
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-04-08
 */

namespace App\Contracts\Repositories;

interface MissCampaignRepositoryInterface
{
    /**
     * Get all miss campaigns
     * 
     * @param int $perPage
     * @param string|null $searchTerm
     * @return array
     */
    public function getAllMissCampaigns(int $perPage = 10, ?string $searchTerm = null);
    /**
     * Count miss campaigns for export
     * 
     * @param string|null $searchTerm
     * @return int
     */
    public function countMissCampaignsForExport(?string $searchTerm = null): int;
    /**
     * Export miss campaign data in chunks
     * 
     * @param string|null $searchTerm
     * @param int $chunkSize
     * @param callable $callback
     * @return void
     */
    public function eachMissCampaignExportChunk(?string $searchTerm, int $chunkSize, callable $callback): void;
    /**
     * Get miss campaign list
     * 
     * @return array
     */
    public function getMissCampaignList();
    /**
     * Get miss campaign by ID
     * 
     * @param int $id
     * @return array
     */
    public function getMissCampaignById(int $id);
    /**
     * Get miss campaign by slug
     * 
     * @param string $slug
     * @return array
     */
    public function getMissCampaignBySlug(string $slug);
    /**
     * Create a new miss campaign
     * 
     * @param array $data
     * @return array
     */
    public function createMissCampaign(array $data);
    /**
     * Update a miss campaign
     * 
     * @param int $id
     * @param array $data
     * @return array
     */
    public function updateMissCampaign(int $id, array $data);
    /**
     * Delete a miss campaign
     * 
     * @param int $id
     * @return array
     */
    public function deleteMissCampaign(int $id);
    /**
     * Update status of a miss campaign
     * 
     * @param int $id
     * @param string $status
     * @return bool
     */
    public function updateStatus(int $id, string $status): bool;
    /**
     * Assign user to a miss campaign
     * 
     * @param int $id
     * @param int $userId
     * @param int $assignBy
     * @return bool
     */
    public function assignUser(int $id, int $userId, int $assignBy): bool;
}
