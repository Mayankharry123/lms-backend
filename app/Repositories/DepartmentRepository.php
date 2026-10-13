<?php

/**
 * DepartmentRepository
 * 
 * @author Achal Sharma
 * @package App\Repositories
 * @version 1.0.1
 */

namespace App\Repositories;

use App\Contracts\Repositories\DepartmentRepositoryInterface;
use App\Models\Department;

class DepartmentRepository implements DepartmentRepositoryInterface 
{
    /**
     * @var Department
     */
    protected $model;

    /**
     * DepartmentRepository constructor.
     *
     * @param Department $department
     */
    public function __construct(Department $department)
    {
        $this->model = $department;
    }

    /**
     * Get all departments with pagination and optional search filter.
     *
     * @param int $perPage
     * @param string|null $searchTerm
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getAllDepartments(int $perPage = 10, ?string $searchTerm = null)
    {
        $query = $this->model->query();
        if ($searchTerm) {
            $query->where('name', 'LIKE', "%{$searchTerm}%");
        }
        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Get a department by ID.
     *
     * @param int $id
     * @return Department
     */
    public function getDepartmentById($id) 
    {
        return $this->model->findOrFail($id);
    }

    /**
     * Create a new department.
     *
     * @param array $data
     * @return Department
     */
    public function createDepartment(array $data) 
    {
        return $this->model->create($data);
    }

    /**
     * Update a department by ID.
     *
     * @param int $id
     * @param array $data
     * @return Department
     */
    public function updateDepartment($id, array $data) 
    {
        $department = $this->model->findOrFail($id);
        $department->update($data);
        return $department;
    }

    /**
     * Delete a department by ID.
     *
     * @param int $id
     * @return bool|null
     */
    public function deleteDepartment($id) 
    {
        $department = $this->model->findOrFail($id);
        return $department->delete();
    }

    /**
     * NEW METHOD IMPLEMENTED
     * Added to support finding departments by slug
     *
     * @param string $slug The department slug to search for
     * @return \App\Models\Department|null
     */
    public function findBySlug(string $slug)
    {
        return $this->model->where('slug', $slug)->first();
    }
}
