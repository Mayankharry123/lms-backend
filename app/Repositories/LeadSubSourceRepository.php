<?php

/**
 * LeadSubSourceRepository
 * 
 * @author Achal Sharma
 * @package App\Repositories
 * @version 1.0.1
 */

namespace App\Repositories;

use App\Contracts\Repositories\LeadSubSourceRepositoryInterface;
use App\Models\LeadSubSource;

class LeadSubSourceRepository implements LeadSubSourceRepositoryInterface 
{
    /**
     * @var LeadSubSource
     */
    protected $model;

    /**
     * LeadSubSourceRepository constructor.
     *
     * @param LeadSubSource $leadSubSource
     */
    public function __construct(LeadSubSource $leadSubSource)
    {
        $this->model = $leadSubSource;
    }

    /**
     * Get all lead sub-sources with filters and pagination.
     *
     * @param array $filters
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getAllLeadSubSources(array $filters = [], int $perPage = 10)
    {
        $query = $this->model->with('leadSource'); 

        // --- Filter logic ---

        // 1. Legacy filter: Filter by specific lead source ID
        if (!empty($filters['lead_source_id'])) {
            $query->where('lead_source_id', $filters['lead_source_id']);
        }

        // 2. NEW filter: For general search functionality
        // We're assuming the search will be performed on the 'name' column
        if (!empty($filters['search'])) {
            $searchTerm = $filters['search'];
            $query->where('name', 'LIKE', "%{$searchTerm}%"); // Replace 'name' with your actual column name if different
        }
        
        // --- End Filter logic ---


        // Apply pagination
        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Get a lead sub-source by ID.
     *
     * @param int $id
     * @return LeadSubSource
     */
    public function getLeadSubSourceById($id) 
    {
        // Also load the parent 'leadSource' relationship
        return $this->model->with('leadSource')->findOrFail($id);
    }

    /**
     * Create a new lead sub-source.
     *
     * @param array $data
     * @return LeadSubSource
     */
    public function createLeadSubSource(array $data) 
    {
        return $this->model->create($data);
    }

    /**
     * Update a lead sub-source by ID.
     *
     * @param int $id
     * @param array $data
     * @return LeadSubSource
     */
    public function updateLeadSubSource($id, array $data) 
    {
        $leadSubSource = $this->model->findOrFail($id);
        $leadSubSource->update($data);
        return $leadSubSource;
    }

    /**
     * Delete a lead sub-source by ID.
     *
     * @param int $id
     * @return bool|null
     */
    public function deleteLeadSubSource($id) 
    {
        $leadSubSource = $this->model->findOrFail($id);
        return $leadSubSource->delete();
    }

    /**
     * Find a lead sub-source by slug.
     *
     * @param string $slug
     * @return LeadSubSource|null
     */
    public function findBySlug(string $slug)
    {
        return $this->model->where('slug', $slug)->first();
    }

    /**
     * Get lead sub-sources by parent lead source ID.
     *
     * @param int $sourceId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getLeadSubSourcesBySourceId($sourceId)
    {
        return $this->model->where('lead_source_id', $sourceId)->get();
    }
}
