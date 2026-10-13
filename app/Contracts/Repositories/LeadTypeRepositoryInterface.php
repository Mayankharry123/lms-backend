<?php

namespace App\Contracts\Repositories;

/**
 * Lead Type Repository Interface
 * -----------------------------------------
 * Defines the contract for lead type data access operations.
 * Part of the repository layer to abstract database interactions
 * and support clean architecture principles.
 *
 * @package App\Contracts\Repositories
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-05-05
 */

interface LeadTypeRepositoryInterface
{
    /**
     * Get all lead types
     * 
     * @param int $perPage
     * @param string|null $searchTerm
     * @return array
     */
    public function getAllLeadTypes(int $perPage = 10, ?string $searchTerm = null);
    /**
     * List lead types
     * 
     * @return array
     */
    public function listLeadTypes();
    /**
     * Get lead type by ID
     * 
     * @param int $id
     * @return array
     */
    public function getLeadTypeById(int $id);
    /**
     * Create a new lead type
     * 
     * @param array $data
     * @return array
     */
    public function createLeadType(array $data);
}