<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use DomainException;
use Exception;

class Brand extends Model
{
    use SoftDeletes;

    protected const DEFAULT_RELATIONSHIPS = [
        'agency',
        'agencies',
        'zone',
        'brandType',
        'industry',
        'country',
        'state',
        'city',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'brand_type_id',
        'industry_id',
        'country_id',
        'state_id',
        'city_id',
        'zone_id',
        'created_by',
        'website',
        'postal_code',
        'status',
        'contact_person_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
    
    /**
     * Get the brand type that owns the brand.
     *
     * @return BelongsTo
     */
    public function brandType(): BelongsTo
    {
        return $this->belongsTo(BrandType::class);
    }

    /**
     * Get the industry that owns the brand.
     *
     * @return BelongsTo
     */
    public function industry(): BelongsTo
    {
        return $this->belongsTo(Industry::class);
    }

    /**
     * Get the country that owns the brand.
     *
     * @return BelongsTo
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Get the state that owns the brand.
     *
     * @return BelongsTo
     */
    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    /**
     * Get the city that owns the brand.
     *
     * @return BelongsTo
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /**
     * Get the zone that owns the brand.
     *
     * @return BelongsTo
     */
    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    /**
     * Get the agency that owns the brand.
     *
     * @return BelongsTo
     */
    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    /**
     * Get the user who created the brand.
     *
     * @return BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the agencies that own this brand.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function agencies()
    {
        return $this->belongsToMany(Agency::class, 'brand_agency_relationships', 'brand_id', 'agency_id')
                    ->withTimestamps();
    }

    /**
     * Get all contact persons (leads) for this brand.
     *
     * @return HasMany
     */
    public function contactPersons(): HasMany
    {
        return $this->hasMany(Lead::class, 'brand_id');
    }

    /**
     * Get the count of contact persons for this brand.
     *
     * @return int
     */
    public function getContactPersonCount(): int
    {
        return $this->contactPersons()->count();
    }

    public function getAllBrands(int $perPage = 10, ?string $searchTerm = null): LengthAwarePaginator
    {
        $query = $this->newQuery()
            ->with(self::DEFAULT_RELATIONSHIPS)
            ->where('status', '1');

        if ($searchTerm) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('slug', 'LIKE', "%{$searchTerm}%");
            })
                ->orWhereHas('agencies', function ($agenciesQuery) use ($searchTerm) {
                    $agenciesQuery->where('name', 'LIKE', "%{$searchTerm}%");
                })
                ->orWhereHas('brandType', function ($brandTypeQuery) use ($searchTerm) {
                    $brandTypeQuery->where('name', 'LIKE', "%{$searchTerm}%");
                })
                ->orWhereHas('industry', function ($industryQuery) use ($searchTerm) {
                    $industryQuery->where('name', 'LIKE', "%{$searchTerm}%");
                })
                ->orWhereHas('city', function ($cityQuery) use ($searchTerm) {
                    $cityQuery->where('name', 'LIKE', "%{$searchTerm}%");
                })
                ->orWhereHas('zone', function ($zoneQuery) use ($searchTerm) {
                    $zoneQuery->where('name', 'LIKE', "%{$searchTerm}%");
                });
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->appends(request()->query());
    }

    public function nameExists(string $name): bool
    {
        $normalized = mb_strtolower(trim($name));
        if ($normalized === '') {
            return false;
        }

        return $this->newQuery()
            ->whereRaw('LOWER(name) = ?', [$normalized])
            ->whereNull('deleted_at')
            ->exists();
    }

    public function slugExistsWithTrashed(string $slug, int $exceptId): bool
    {
        return $this->withTrashed()
            ->where('slug', $slug)
            ->where('id', '!=', $exceptId)
            ->exists();
    }

    /** @param array<int, string> $names */
    public function findExistingNames(array $names): array
    {
        $normalized = $this->normalizeNameList($names);
        if ($normalized === []) {
            return [];
        }

        return $this->newQuery()
            ->whereNull('deleted_at')
            ->whereRaw(
                'LOWER(name) IN (' . implode(',', array_fill(0, count($normalized), '?')) . ')',
                $normalized
            )
            ->pluck('name')
            ->map(fn ($name) => mb_strtolower(trim((string) $name)))
            ->unique()
            ->values()
            ->all();
    }

    public function getBrandList(): Collection
    {
        return $this->newQuery()
            ->select('id', 'name')
            ->where('status', '1')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getBrandById(int $id): ?Brand
    {
        return $this->newQuery()->with(self::DEFAULT_RELATIONSHIPS)->find($id);
    }

    public function getBrandBySlug(string $slug): ?Brand
    {
        return $this->newQuery()->with(self::DEFAULT_RELATIONSHIPS)->where('slug', $slug)->first();
    }

    public function createBrand(array $data): Brand
    {
        try {
            $brand = $this->newQuery()->create($data);
            $slugBase = Str::slug($data['name'] ?? '');
            $finalSlug = $slugBase . '-' . $brand->id;

            $existingSlug = $this->withTrashed()
                ->where('slug', $finalSlug)
                ->where('id', '!=', $brand->id)
                ->first();

            if ($existingSlug) {
                $finalSlug = $slugBase . '-' . $brand->id . '-' . Str::random(4);
            }

            $brand->update(['slug' => $finalSlug]);

            return $brand;
        } catch (DomainException $e) {
            throw $e;
        } catch (QueryException $e) {
            Log::error('Database error creating brand', ['data' => $data, 'exception' => $e]);
            throw new DomainException('Database error while creating brand.');
        } catch (Exception $e) {
            Log::error('Unexpected error creating brand', ['data' => $data, 'exception' => $e]);
            throw new DomainException('Unexpected error while creating brand.');
        }
    }

    public function updateBrand(int $id, array $data): bool
    {
        $brand = $this->newQuery()->findOrFail($id);

        if (isset($data['slug'])) {
            $slugBase = $data['slug'];
            $finalSlug = $slugBase . '-' . $id;

            $existingSlug = $this->withTrashed()
                ->where('slug', $finalSlug)
                ->where('id', '!=', $id)
                ->first();

            if ($existingSlug) {
                $finalSlug = $slugBase . '-' . $id . '-' . Str::random(4);
            }

            $data['slug'] = $finalSlug;
        }

        return $brand->update($data);
    }

    public function deleteBrand(int $id): bool
    {
        return $this->newQuery()->findOrFail($id)->delete();
    }

    /** @param array<int, string> $names */
    public function findByNames(array $names): Collection
    {
        $normalized = $this->normalizeNameList($names);
        if ($normalized === []) {
            return $this->newCollection();
        }

        return $this->newQuery()
            ->whereNull('deleted_at')
            ->whereRaw(
                'LOWER(name) IN (' . implode(',', array_fill(0, count($normalized), '?')) . ')',
                $normalized
            )
            ->get(['id', 'name', 'slug']);
    }

    /** @param array<int, string> $slugs */
    public function findBySlugs(array $slugs): Collection
    {
        $slugs = array_values(array_filter(array_map(static fn ($slug) => trim((string) $slug), $slugs)));
        if ($slugs === []) {
            return $this->newCollection();
        }

        return $this->newQuery()->whereIn('slug', $slugs)->get(['id', 'name', 'slug']);
    }

    /** @param array<int, array<string, mixed>> $rows */
    public function insertBatch(array $rows): void
    {
        if ($rows !== []) {
            $this->newQuery()->insert($rows);
        }
    }

    /** @param array<int, array<string, mixed>> $rows */
    public function updateBatch(array $rows): void
    {
        foreach ($rows as $row) {
            if (empty($row['id'])) {
                continue;
            }

            $id = (int) $row['id'];
            unset($row['id']);
            if ($row !== []) {
                $this->newQuery()->where('id', $id)->update($row);
            }
        }
    }

    /** @param array<int, int> $brandIds */
    public function attachAgencyToBrands(array $brandIds, int $agencyId): void
    {
        $brandIds = array_values(array_unique(array_filter($brandIds)));
        if ($brandIds === []) {
            return;
        }

        $now = now();
        $rows = [];
        foreach ($brandIds as $brandId) {
            $rows[] = [
                'brand_id' => (int) $brandId,
                'agency_id' => $agencyId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('brand_agency_relationships')->insert($rows);
    }

    /** @param array<int, string> $names
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