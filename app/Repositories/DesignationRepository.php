<?php

/**
 * DesignationRepository
 * 
 * @author Achal Sharma
 * @package App\Repositories
 * @version 1.0.1
 */

namespace App\Repositories;

use App\Contracts\Repositories\DesignationRepositoryInterface;
use App\Models\Designation;

class DesignationRepository implements DesignationRepositoryInterface 
{
    /**
     * @var Designation
     */
    protected $model;

    /**
     * DesignationRepository constructor.
     *
     * @param Designation $designation
     */
    public function __construct(Designation $designation)
    {
        $this->model = $designation;
    }

    /**
     * Get all designations with pagination and optional search filter.
     *
     * @param int $perPage
     * @param string|null $searchTerm
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getAllDesignations(int $perPage = 10, ?string $searchTerm = null) 
    {
        $query = $this->model->query(); // Designation::query()

        if ($searchTerm) {
            $query->where('title', 'like', '%' . $searchTerm . '%');
        }
        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Get a designation by ID.
     *
     * @param int $id
     * @return Designation
     */
    public function getDesignationById($id) 
    {
        return $this->model->findOrFail($id);
    }

    /**
     * Create a new designation.
     *
     * @param array $data
     * @return Designation
     */
    public function createDesignation(array $data) 
    {
        return $this->model->create($data);
    }

    /**
     * Check if a slug already exists for another designation.
     *
     * @param string $slug
     * @param int|null $excludeId
     * @return bool
     */
    public function slugExists(string $slug, $excludeId = null): bool
    {
        $query = $this->model->where('slug', $slug)->whereNull('deleted_at');
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        return $query->exists();
    }

    /**
     * Find a designation by slug.
     *
     * @param string $slug
     * @return Designation|null
     */
    public function findBySlug(string $slug)
    {
        return $this->model->where('slug', $slug)->first();
    }

    /**
     * Get the underlying model instance.
     *
     * @return Designation
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Update a designation by ID.
     *
     * @param int $id
     * @param array $data
     * @return Designation
     */
    public function updateDesignation($id, array $data) 
    {
        $designation = $this->model->findOrFail($id);
        $designation->update($data);
        return $designation;
    }

    /**
     * Delete a designation by ID.
     *
     * @param int $id
     * @return bool|null
     */
    public function deleteDesignation($id) 
    {
        $designation = $this->model->findOrFail($id);
        return $designation->delete();
    }
}
