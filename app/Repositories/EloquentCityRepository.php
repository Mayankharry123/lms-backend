<?php

/**
 * EloquentCityRepository
 * 
 * @author Achal Sharma
 * @package App\Repositories
 * @version 1.0.1
 */

namespace App\Repositories;

use App\Contracts\Repositories\CityRepositoryInterface;
use App\Models\City; // City model implementation

class EloquentCityRepository implements CityRepositoryInterface 
{
    /**
     * @var City
     */
    protected $model;

    /**
     * EloquentCityRepository constructor.
     *
     * @param City $model
     */
    public function __construct(City $model)
    {
        $this->model = $model;
    }

    /**
     * Get all cities with location relations.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAll()
    {
        return $this->model->getAllWithLocation();
    }

    /**
     * Get paginated cities with location relations and optional search.
     *
     * @param int $perPage
     * @param string|null $search
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getPaginated(int $perPage = 15, ?string $search = null)
    {
        return $this->model->getPaginatedWithLocation($perPage, $search);
    }

    /**
     * Get cities by state ID.
     *
     * @param int $stateId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByState(int $stateId)
    {
        return $this->model->getByStateWithLocation($stateId);
    }

    /**
     * Get cities by country ID.
     *
     * @param int $countryId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByCountry(int $countryId)
    {
        return $this->model->getByCountryWithLocation($countryId);
    }

    /**
     * Find a city by ID with location relations.
     *
     * @param int $id
     * @return City|null
     */
    public function findById(int $id)
    {
        return $this->model->getByIdWithLocation($id);
    }

    /**
     * Create a new city.
     *
     * @param array $data
     * @return City
     */
    public function create(array $data)
    {
        // Model has 'name', 'country_id', 'state_id' as fillable fields
        return $this->model->create($data);
    }

    /**
     * Update a city by ID.
     *
     * @param int $id
     * @param array $data
     * @return City
     */
    public function update(int $id, array $data)
    {
        $city = $this->model->findOrFail($id);
        $city->update($data);
        return $city;
    }

    /**
     * Delete a city by ID.
     *
     * @param int $id
     * @return bool|null
     */
    public function delete(int $id)
    {
        $city = $this->model->findOrFail($id);
        // Model doesn't use SoftDeletes, so this will be a HARD delete
        return $city->delete(); 
    }

    /**
     * Find cities by name (case-insensitive).
     *
     * @param array<int, string> $names
     * @return \Illuminate\Support\Collection
     */
    public function findByNames(array $names)
    {
        return $this->model->findByNames($names);
    }
}
