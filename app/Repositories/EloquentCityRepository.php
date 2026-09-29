<?php

namespace App\Repositories;

use App\Contracts\Repositories\CityRepositoryInterface;
use App\Models\City; // City model implementation

class EloquentCityRepository implements CityRepositoryInterface 
{
    protected $model;

    public function __construct(City $model)
    {
        $this->model = $model;
    }

    public function getAll()
    {
        return $this->model->getAllWithLocation();
    }

    public function getPaginated(int $perPage = 15, ?string $search = null)
    {
        return $this->model->getPaginatedWithLocation($perPage, $search);
    }

    public function getByState(int $stateId)
    {
        return $this->model->getByStateWithLocation($stateId);
    }

    public function getByCountry(int $countryId)
    {
        return $this->model->getByCountryWithLocation($countryId);
    }

    public function findById(int $id)
    {
        return $this->model->getByIdWithLocation($id);
    }

    public function create(array $data)
    {
        // Model has 'name', 'country_id', 'state_id' as fillable fields
        return $this->model->create($data);
    }

    public function update(int $id, array $data)
    {
        $city = $this->model->findOrFail($id);
        $city->update($data);
        return $city;
    }

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
