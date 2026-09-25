<?php

namespace App\Repositories;

use App\Contracts\Repositories\IndustryRepositoryInterface; 
use App\Models\Industry;                              

class IndustryRepository implements IndustryRepositoryInterface 
{
    /**
     * @var Industry
     */
    protected $model;

    /**
     * Constructor Injection
     */
    public function __construct(Industry $industry)
    {
        $this->model = $industry;
    }

    public function getAllIndustries(int $perPage = 10, ?string $searchTerm = null)
    {
        $query = $this->model->query(); 

        
        if ($searchTerm) {
            $query->where('name', 'LIKE', "%{$searchTerm}%");
        }
        return $query->orderBy('created_at', 'desc')->paginate($perPage); 
    }   

    public function getIndustryById($id) 
    {
        return $this->model->findOrFail($id);
    }
    /**
     * Create industry
     */
    public function createIndustry(array $data)
    {
        // Only allow name, slug, status
        $filtered = [
            'name' => $data['name'] ?? null,
            'slug' => $data['slug'] ?? null,
            'status' => $data['status'] ?? 1,
        ];
        return $this->model->create($filtered);
    }

    /**
     * Industry should update
     */
    public function updateIndustry($id, array $data)
    {
        $industry = $this->model->findOrFail($id);
        $filtered = [
            'name' => $data['name'] ?? $industry->name,
            'slug' => $data['slug'] ?? $industry->slug,
            'status' => $data['status'] ?? $industry->status,
        ];
        $industry->update($filtered);
        return $industry;
    }

    public function deleteIndustry($id)
    {
        $industry = $this->model->findOrFail($id);
        return $industry->delete();
    }

    /**
     * Find non-deleted industries by name (case-insensitive).
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
            ->whereNull('deleted_at')
            ->whereRaw(
                'LOWER(name) IN (' . implode(',', array_fill(0, count($normalized), '?')) . ')',
                $normalized
            )
            ->get(['id', 'name']);
    }
}
