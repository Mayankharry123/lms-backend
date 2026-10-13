<?php
/**
 * Interface DepartmentRepositoryInterface
 * 
 * @author Achal Sharma
 * @package App\Contracts\Repositories
 * @version 1.0.1
 */
namespace App\Contracts\Repositories;

interface DepartmentRepositoryInterface 
{
    /**
     * Get all departments
     * 
     * @return array
     */
    public function getAllDepartments();
    /**
     * Get department by ID
     * 
     * @param int $id
     * @return array
     */
    public function getDepartmentById($id);
    /**
     * Create a new department
     * 
     * @param array $data
     * @return array
     */
    public function createDepartment(array $data);
    /**
     * Update a department
     * 
     * @param int $id
     * @param array $data
     * @return array
     */
    public function updateDepartment($id, array $data);
    /**
     * Delete a department
     * 
     * @param int $id
     * @return array
     */
    public function deleteDepartment($id);

    /**
     * Find a department by slug.
     *
     * @param string $slug
     * @return mixed
     */
    public function findBySlug(string $slug);
}

