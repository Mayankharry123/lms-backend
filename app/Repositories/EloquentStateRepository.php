<?php

namespace App\Repositories;

use App\Contracts\Repositories\StateRepositoryInterface;
use App\Models\State;

class EloquentStateRepository implements StateRepositoryInterface 
{
    protected $model;

    public function __construct(State $model)
    {
        $this->model = $model;
    }

    public function getAll()
    {
        return $this->model->getAllWithCountry();
    }

    public function getByCountry(int $countryId)
    {
        return $this->model->getByCountryWithCountry($countryId);
    }

    public function getPaginated(int $perPage = 15, ?string $search = null)
    {
        return $this->model->getPaginatedWithRelations($perPage, $search);
    }

    public function findById(int $id)
    {
        return $this->model->getByIdWithRelations($id);
    }

    public function create(array $data)
    {
        // Model mein 'name' aur 'country_id' fillable hain
        return $this->model->create($data);
    }

    public function update(int $id, array $data)
    {
        $state = $this->model->findOrFail($id);
        $state->update($data);
        return $state;
    }

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
