<?php

/**
 * EloquentStateRepository
 * 
 * @author Achal Sharma
 * @package App\Repositories
 * @version 1.0.1
 */

namespace App\Repositories;

use App\Contracts\Repositories\StateRepositoryInterface;
use App\Models\State;

class EloquentStateRepository implements StateRepositoryInterface 
{
    /**
     * @var State
     */
    protected $model;

    /**
     * EloquentStateRepository constructor.
     *
     * @param State $model
     */
    public function __construct(State $model)
    {
        $this->model = $model;
    }

    /**
     * Get all states with country relation.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAll()
    {
        return $this->model->getAllWithCountry();
    }

    /**
     * Get states by country ID with country relation.
     *
     * @param int $countryId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByCountry(int $countryId)
    {
        return $this->model->getByCountryWithCountry($countryId);
    }

    /**
     * Get paginated states with relations and optional search.
     *
     * @param int $perPage
     * @param string|null $search
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getPaginated(int $perPage = 15, ?string $search = null)
    {
        return $this->model->getPaginatedWithRelations($perPage, $search);
    }

    /**
     * Find a state by ID with relations.
     *
     * @param int $id
     * @return State|null
     */
    public function findById(int $id)
    {
        return $this->model->getByIdWithRelations($id);
    }

    /**
     * Create a new state.
     *
     * @param array $data
     * @return State
     */
    public function create(array $data)
    {
        // Model mein 'name' aur 'country_id' fillable hain
        return $this->model->create($data);
    }

    /**
     * Update a state by ID.
     *
     * @param int $id
     * @param array $data
     * @return State
     */
    public function update(int $id, array $data)
    {
        $state = $this->model->findOrFail($id);
        $state->update($data);
        return $state;
    }

    /**
     * Delete a state by ID.
     *
     * @param int $id
     * @return bool|null
     */
    public function delete(int $id)
    {
        $state = $this->model->findOrFail($id);
        // Model mein SoftDeletes nahi hai, isliye yeh HARD delete hoga
        return $state->delete(); 
    }

    /**
     * Find states by name (case-insensitive).
     *
     * @param array<int, string> $names
     * @return \Illuminate\Support\Collection
     */
    public function findByNames(array $names)
    {
        return $this->model->findByNames($names);
    }
}
