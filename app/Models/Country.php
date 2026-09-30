<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;

class Country extends Model
{
    protected $table = 'countries';
    protected $fillable = ['name'];
    
    public $timestamps = true;
    
    // Relationship with states
    public function states()
    {
        return $this->hasMany(State::class);
    }

    public function getLatest(): Collection
    {
        return $this->newQuery()->latest()->get();
    }

    public function getPaginatedWithStates(int $perPage = 15, ?string $search = null)
    {
        $query = $this->newQuery()->with('states');

        if ($search) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        return $query->latest()->paginate($perPage);
    }

    public function getByIdWithStates(int $id): self
    {
        return $this->newQuery()->with('states')->findOrFail($id);
    }

    /**
     * @param array<int, string> $names
    * @return Collection<int, static>
     */
    public function findByNames(array $names): Collection
    {
        $normalized = $this->normalizeNameList($names);
        if ($normalized === []) {
            return $this->newCollection();
        }

        return $this->newQuery()
            ->whereRaw(
                'LOWER(name) IN (' . implode(',', array_fill(0, count($normalized), '?')) . ')',
                $normalized
            )
            ->get(['id', 'name']);
    }

    /**
     * @param array<int, string> $names
     * @return array<int, string>
     */
    private function normalizeNameList(array $names): array
    {
        $normalized = [];
        foreach ($names as $name) {
            $value = mb_strtolower(trim((string) $name));
            if ($value !== '') {
                $normalized[$value] = $value;
            }
        }

        return array_values($normalized);
    }
}