<?php
/**
 * Interface DesignationRepositoryInterface
 * 
 * @author Achal Sharma
 * @package App\Contracts\Repositories
 * @version 1.0.1
 */
namespace App\Contracts\Repositories;

interface DesignationRepositoryInterface 
{
    /**
     * Get all designations
     * 
     * @return array
     */
    public function getAllDesignations();
    /**
     * Get designation by ID
     * 
     * @param int $id
     * @return array
     */
    public function getDesignationById($id);
    /**
     * Create a new designation
     * 
     * @param array $data
     * @return array
     */
    public function createDesignation(array $data);
    /**
     * Update a designation
     * 
     * @param int $id
     * @param array $data
     * @return array
     */
    public function updateDesignation($id, array $data);
    /**
     * Delete a designation
     * 
     * @param int $id
     * @return array
     */
    public function deleteDesignation($id);

    /**
     * Check if a slug already exists for another designation.
     *
     * @param string $slug
     * @param int|null $excludeId
     * @return bool
     */
    public function slugExists(string $slug, $excludeId = null): bool;

    /**
     * Find a designation by slug.
     *
     * @param string $slug
     * @return mixed
     */
    public function findBySlug(string $slug);
}
