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
        // Load all cities with their country and state relationships
        return $this->model->with(['country', 'state'])->latest()->get();
    }

    public function getPaginated(int $perPage = 15, ?string $search = null)
    {
        $query = $this->model->with(['country', 'state']);
        
        // Apply search filter if provided
        if ($search) {
            $query->where('name', 'like', '%' . $search . '%');
        }
        
        return $query->latest()->paginate($perPage);
    }

    public function getByState(int $stateId)
    {
        return $this->model->where('state_id', $stateId)
                           ->with(['country', 'state'])
                           ->latest()
                           ->get();
    }

    public function getByCountry(int $countryId)
    {
        return $this->model->where('country_id', $countryId)
                           ->with(['country', 'state'])
                           ->latest()
                           ->get();
    }

    public function findById(int $id)
    {
        // Load with country and state relationships
        return $this->model->with(['country', 'state'])->findOrFail($id);
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
        $normalized = [];
        foreach ($names as $name) {
            $value = mb_strtolower(trim((string) $name));
            if ($value !== '') {
                $normalized[$value] = $value;
            }
        }
        $normalized = array_values($normalized);

        if ($normalized === []) {
            return $this->model->newCollection();
        }

        return $this->model
            ->whereRaw(
                'LOWER(name) IN (' . implode(',', array_fill(0, count($normalized), '?')) . ')',
                $normalized
            )
            ->get(['id', 'name', 'state_id', 'country_id']);
    }
}
