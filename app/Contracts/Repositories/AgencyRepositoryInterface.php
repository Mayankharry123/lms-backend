<?php
/**
 * Interface AgencyRepositoryInterface
 * 
 * @author Achal Sharma
 * @package App\Contracts\Repositories
 * @version 1.0.1
 */
namespace App\Contracts\Repositories;

interface AgencyRepositoryInterface 
{
    /**
     * Get all agencies with pagination and optional search.
     *
     * @param int $perPage
     * @param string|null $search
     * @return LengthAwarePaginator
     */
    public function getAllAgency(int $perPage = 10, ?string $search = null);

    /**
     * Get agency by ID.
     *
     * @param int $id
     * @return Agency|null
     */
    public function getAgencyById(int $id);

    /**
     * Get agency by slug.
     *
     * @param string $slug
     * @return Agency|null
     */
    public function getAgencyBySlug(string $slug);

    /**
     * Create a new agency.
     *
     * @param array $data
     * @return Agency
     */
    public function createAgency(array $data);

    /**
     * Update an existing agency.
     *
     * @param int $id
     * @param array $data
     * @return Agency|null
     */
    public function updateAgency(int $id, array $data);

    /**
     * Delete an existing agency by ID.
     *
     * @param int $id
     * @return bool
     */
    public function deleteAgency(int $id);
}