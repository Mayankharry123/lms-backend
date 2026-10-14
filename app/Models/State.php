<?php
/**
 * State
 * 
 * @author Achal Sharma
 * @package App\Models
 * @version 1.0.1
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;

class State extends Model
{
    protected $table = 'states';
    protected $fillable = ['name', 'country_id'];
    
    public $timestamps = true;
    
    /**
     * Relationship with country.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function country()
    {
        return $this->belongsTo(Country::class);
    }
    
    /**
     * Relationship with cities.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function cities()
    {
        return $this->hasMany(City::class);
    }

    /**
     * Get all states with their country relation.
     *
     * @return Collection
     */
    public function getAllWithCountry(): Collection
    {
        return $this->newQuery()->with('country')->latest()->get();
    }

    /**
     * Get states for a given country ID with country relation.
     *
     * @param int $countryId
     * @return Collection
     */
    public function getByCountryWithCountry(int $countryId): Collection
    {
        return $this->newQuery()->where('country_id', $countryId)
            ->with('country')
            ->latest()
            ->get();
    }

    /**
     * Get paginated states with country and cities relations.
     *
     * @param int $perPage
     * @param string|null $search
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getPaginatedWithRelations(int $perPage = 15, ?string $search = null)
    {
        $query = $this->newQuery()->with(['country', 'cities']);

        if ($search) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * Get state by ID with country and cities relations.
     *
     * @param int $id
     * @return self
     */
    public function getByIdWithRelations(int $id): self
    {
        return $this->newQuery()->with(['country', 'cities'])->findOrFail($id);
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
