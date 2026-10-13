<?php

/**
 * Lead Source Repository Interface
 * -----------------------------------------
 * Defines the contract for lead source data access operations.
 * Part of the repository layer to abstract database interactions
 * and support clean architecture principles.
 *
 * @package App\Contracts\Repositories
 * @author Achal Sharma 
 * @version 1.0.1
 * @since 2026-05-05
 */
namespace App\Contracts\Repositories;

interface LeadSourceRepositoryInterface 
{
    /**
     * Get all lead sources
     * 
     * @return array
     */
    public function getAllLeadSources();
    /**
     * Get lead source by ID
     * 
     * @param int $id
     * @return array
     */
    public function getLeadSourceById($id);
    /**
     * Create a new lead source
     * 
     * @param array $data
     * @return array
     */
    public function createLeadSource(array $data);
    /**
     * Update a lead source
     * 
     * @param int $id
     * @param array $data
     * @return array
     */
    public function updateLeadSource($id, array $data);
    /**
     * Delete a lead source
     * 
     * @param int $id
     * @return array
     */
    public function deleteLeadSource($id);

    /**
     * Find a lead source by slug.
     *
     * @param string $slug
     * @return mixed
     */
    public function findBySlug(string $slug);
}
