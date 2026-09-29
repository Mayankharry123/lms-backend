<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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

    public function getAllWithLocation(): \Illuminate\Database\Eloquent\Collection
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

    public function getByStateWithLocation(int $stateId): \Illuminate\Database\Eloquent\Collection
    {
        return $this->newQuery()->where('state_id', $stateId)
            ->with(['country', 'state'])
            ->latest()
            ->get();
    }

    public function getByCountryWithLocation(int $countryId): \Illuminate\Database\Eloquent\Collection
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
