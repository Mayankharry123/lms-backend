<?php
/**
 * Interface LeadSubSourceRepositoryInterface
 * 
 * @author Achal Sharma
 * @package App\Contracts\Repositories
 * @version 1.0.1
 */
namespace App\Contracts\Repositories;

interface LeadSubSourceRepositoryInterface 
{
    /**
     * Get all lead sub-sources
     * 
     * @param array $filters
     * @return array
     */
    public function getAllLeadSubSources(array $filters = []);
    /**
     * Get lead sub-source by ID
     * 
     * @param int $id
     * @return array
     */
    public function getLeadSubSourceById($id);
    /**
     * Create a new lead sub-source
     * 
     * @param array $data
     * @return array
     */
    public function createLeadSubSource(array $data);
    /**
     * Update a lead sub-source
     * 
     * @param int $id
     * @param array $data
     * @return array
     */
    public function updateLeadSubSource($id, array $data);
    /**
     * Delete a lead sub-source
     * 
     * @param int $id
     * @return array
     */
    public function deleteLeadSubSource($id);
    /**
     * Find lead sub-source by slug
     * 
     * @param string $slug
     * @return array
     */
    public function findBySlug(string $slug);
    /**
     * Get lead sub-sources by source ID
     * 
     * @param int $sourceId
     * @return array
     */
    public function getLeadSubSourcesBySourceId($sourceId);
}
