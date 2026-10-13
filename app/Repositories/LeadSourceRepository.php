<?php

/**
 * LeadSourceRepository
 * 
 * @author Achal Sharma
 * @package App\Repositories
 * @version 1.0.1
 */

namespace App\Repositories;

use App\Contracts\Repositories\LeadSourceRepositoryInterface;
use App\Models\LeadSource;

class LeadSourceRepository implements LeadSourceRepositoryInterface 
{
    /**
     * @var LeadSource
     */
    protected $model;

    /**
     * LeadSourceRepository constructor.
     *
     * @param LeadSource $leadSource
     */
    public function __construct(LeadSource $leadSource)
    {
        $this->model = $leadSource;
    }

    /**
     * Get all lead sources with pagination and optional search filter.
     *
     * @param int $perPage
     * @param string|null $searchTerm
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getAllLeadSources(int $perPage = 10, ?string $searchTerm = null)
    {
        $query = $this->model->query();
        if ($searchTerm) {
            $query->where('name', 'LIKE', "%{$searchTerm}%");
        }
        return $query->orderBy('created_at', 'asc')->paginate($perPage);
    }

    /**
     * Get a lead source by ID.
     *
     * @param int $id
     * @return LeadSource
     */
    public function getLeadSourceById($id) 
    {
        return $this->model->findOrFail($id);
    }

    /**
     * Create a new lead source.
     *
     * @param array $data
     * @return LeadSource
     */
    public function createLeadSource(array $data) 
    {
        return $this->model->create($data);
    }

    /**
     * Update a lead source by ID.
     *
     * @param int $id
     * @param array $data
     * @return LeadSource
     */
    public function updateLeadSource($id, array $data) 
    {
        $leadSource = $this->model->findOrFail($id);
        $leadSource->update($data);
        return $leadSource;
    }

    /**
     * Delete a lead source by ID.
     *
     * @param int $id
     * @return bool|null
     */
    public function deleteLeadSource($id) 
    {
        $leadSource = $this->model->findOrFail($id);
        return $leadSource->delete();
    }

    /**
     * Find a lead source by slug.
     *
     * @param string $slug
     * @return LeadSource|null
     */
    public function findBySlug(string $slug)
    {
        return $this->model->where('slug', $slug)->first();
    }
}
