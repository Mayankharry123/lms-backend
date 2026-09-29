<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class State extends Model
{
    protected $table = 'states';
    protected $fillable = ['name', 'country_id'];
    
    public $timestamps = true;
    
    // Relationship with country
    public function country()
    {
        return $this->belongsTo(Country::class);
    }
    
    // Relationship with cities
    public function cities()
    {
        return $this->hasMany(City::class);
    }

    public function getAllWithCountry(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->newQuery()->with('country')->latest()->get();
    }

    public function getByCountryWithCountry(int $countryId): \Illuminate\Database\Eloquent\Collection
    {
        return $this->newQuery()->where('country_id', $countryId)
            ->with('country')
            ->latest()
            ->get();
    }

    public function getPaginatedWithRelations(int $perPage = 15, ?string $search = null)
    {
        $query = $this->newQuery()->with(['country', 'cities']);

        if ($search) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        return $query->latest()->paginate($perPage);
    }

    public function getByIdWithRelations(int $id): self
    {
        return $this->newQuery()->with(['country', 'cities'])->findOrFail($id);
    }

    /**
     * @param array<int, string> $names
     * @return \Illuminate\Database\Eloquent\Collection<int, static>
     */
    public function findByNames(array $names): \Illuminate\Database\Eloquent\Collection
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
            ->get(['id', 'name', 'country_id']);
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
