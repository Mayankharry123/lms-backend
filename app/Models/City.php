<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;

class City extends Model
{
    protected $table = 'cities';
    protected $fillable = ['name', 'country_id', 'state_id'];
    
    public $timestamps = true;
    
    // Relationship with country
    public function country()
    {
        return $this->belongsTo(Country::class);
    }
    
    // Relationship with state
    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function getAllWithLocation(): Collection
    {
        return $this->newQuery()->with(['country', 'state'])->latest()->get();
    }

    public function getPaginatedWithLocation(int $perPage = 15, ?string $search = null)
    {
        $query = $this->newQuery()->with(['country', 'state']);

        if ($search) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        return $query->latest()->paginate($perPage);
    }

    public function getByStateWithLocation(int $stateId): Collection
    {
        return $this->newQuery()->where('state_id', $stateId)
            ->with(['country', 'state'])
            ->latest()
            ->get();
    }

    public function getByCountryWithLocation(int $countryId): Collection
    {
        return $this->newQuery()->where('country_id', $countryId)
            ->with(['country', 'state'])
            ->latest()
            ->get();
    }

    public function getByIdWithLocation(int $id): self
    {
        return $this->newQuery()->with(['country', 'state'])->findOrFail($id);
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
            ->get(['id', 'name', 'state_id', 'country_id']);
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
