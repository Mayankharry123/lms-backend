<?php

namespace App\Repositories;

use App\Contracts\Repositories\CountryRepositoryInterface;
use App\Models\Country;

class EloquentCountryRepository implements CountryRepositoryInterface 
{
    protected $model;

    public function __construct(Country $model)
    {
        $this->model = $model;
    }

    public function getAll()
    {
        return $this->model->getLatest();
    }

    public function getPaginated(int $perPage = 15, ?string $search = null)
    {
        return $this->model->getPaginatedWithStates($perPage, $search);
    }

    public function findById(int $id)
    {
        return $this->model->getByIdWithStates($id);
    }

    public function create(array $data)
    {
        // Only 'name' field is fillable in the model
        return $this->model->create($data);
    }

    public function update(int $id, array $data)
    {
        $country = $this->model->findOrFail($id);
        $country->update($data);
        return $country;
    }

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

