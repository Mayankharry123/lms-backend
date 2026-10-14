<?php

/**
 * Lead Type Repository
 * -----------------------------------------
 * Implements the LeadTypeRepositoryInterface to provide data access methods for lead types.
 * This repository interacts with the LeadType Eloquent model to perform CRUD operations,
 * including pagination and search functionality for lead types.
 *
 * @package App\Repositories
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-05-05
 */

namespace App\Repositories;

use App\Contracts\Repositories\LeadTypeRepositoryInterface;
use App\Models\LeadType;

class LeadTypeRepository implements LeadTypeRepositoryInterface
{
    /**
     * @var LeadType
     */
    protected LeadType $model;

    /**
     * LeadTypeRepository constructor.
     *
     * @param LeadType $leadType
     */
    public function __construct(LeadType $leadType)
    {
        $this->model = $leadType;
    }

    /**
     * Get all lead types with pagination and optional search filter.
     *
     * @param int $perPage
     * @param string|null $searchTerm
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getAllLeadTypes(int $perPage = 10, ?string $searchTerm = null)
    {
        $query = $this->model->newQuery();

        if ($searchTerm) {
            $query->where('name', 'like', '%' . $searchTerm . '%');
        }

        return $query->orderBy('created_at', 'asc')->paginate($perPage);
    }

    /**
     * List all lead types (id, name) ordered by name.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function listLeadTypes()
    {
        return $this->model
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get();
    }

    /**
     * Get a lead type by ID.
     *
     * @param int $id
     * @return LeadType
     */
    public function getLeadTypeById(int $id)
    {
        return $this->model->findOrFail($id);
    }

    /**
     * Create a new lead type.
     *
     * @param array $data
     * @return LeadType
     */
    public function createLeadType(array $data)
    {
        return $this->model->create($data);
    }
}
