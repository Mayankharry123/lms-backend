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
        // Sabhi states ko unke country ke saath load karein
        return $this->model->with('country')->latest()->get();
    }

    public function getByCountry(int $countryId)
    {
        // Sirf us country ke states layein
        return $this->model->where('country_id', $countryId)
                           ->with('country')
                           ->latest()
                           ->get();
    }

    public function getPaginated(int $perPage = 15, ?string $search = null)
    {
        $query = $this->model->with(['country', 'cities']);
        
        // Apply search filter if provided
        if ($search) {
            $query->where('name', 'like', '%' . $search . '%');
        }
        
        return $query->latest()->paginate($perPage);
    }

    public function findById(int $id)
    {
        // Country aur cities ke saath load karein
        return $this->model->with(['country', 'cities'])->findOrFail($id);
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
            ->get(['id', 'name', 'country_id']);
    }
}
