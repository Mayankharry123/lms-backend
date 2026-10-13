<?php

/**
 * EloquentCountryRepository
 * 
 * @author Achal Sharma
 * @package App\Repositories
 * @version 1.0.1
 */

namespace App\Repositories;

use App\Contracts\Repositories\CountryRepositoryInterface;
use App\Models\Country;

class EloquentCountryRepository implements CountryRepositoryInterface 
{
    /**
     * @var Country
     */
    protected $model;

    /**
     * EloquentCountryRepository constructor.
     *
     * @param Country $model
     */
    public function __construct(Country $model)
    {
        $this->model = $model;
    }

    /**
     * Get all countries ordered by latest.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAll()
    {
        return $this->model->getLatest();
    }

    /**
     * Get paginated countries with states relation and optional search.
     *
     * @param int $perPage
     * @param string|null $search
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getPaginated(int $perPage = 15, ?string $search = null)
    {
        return $this->model->getPaginatedWithStates($perPage, $search);
    }

    /**
     * Find a country by ID with states relation.
     *
     * @param int $id
     * @return Country|null
     */
    public function findById(int $id)
    {
        return $this->model->getByIdWithStates($id);
    }

    /**
     * Create a new country.
     *
     * @param array $data
     * @return Country
     */
    public function create(array $data)
    {
        // Only 'name' field is fillable in the model
        return $this->model->create($data);
    }

    /**
     * Update a country by ID.
     *
     * @param int $id
     * @param array $data
     * @return Country
     */
    public function update(int $id, array $data)
    {
        $country = $this->model->findOrFail($id);
        $country->update($data);
        return $country;
    }

    /**
     * Delete a country by ID.
     *
     * @param int $id
     * @return bool|null
     */
    public function delete(int $id)
    {
        $country = $this->model->findOrFail($id);
        // This will be a HARD delete since model doesn't use SoftDeletes
        return $country->delete(); 
    }

    /**
     * Find countries by name (case-insensitive).
     *
     * @param array<int, string> $names
     * @return \Illuminate\Support\Collection
     */
    public function findByNames(array $names)
    {
        return $this->model->findByNames($names);
    }
}

