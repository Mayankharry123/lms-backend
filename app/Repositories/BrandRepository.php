<?php

namespace App\Repositories;

use App\Contracts\Repositories\BrandRepositoryInterface;
use App\Models\Brand;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use DomainException;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BrandRepository implements BrandRepositoryInterface
{
    /**
     * Default relationships to eager load.
     *
     * @var array<string>
     */
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
     * @var Brand
     */
    protected Brand $model;

    /**
     * Create a new BrandRepository instance.
     *
     * @param Brand $brand
     */
    public function __construct(Brand $brand)
    {
        $this->model = $brand;
    }

    // ============================================================================
    // READ OPERATIONS
    // ============================================================================

    /**
     * Fetch paginated list of brands with relationships.
     *
     * @param int $perPage
     * @param string|null $searchTerm
     * @return LengthAwarePaginator
     */
    public function getAllBrands(int $perPage = 10, ?string $searchTerm = null): LengthAwarePaginator
    {
        $query = $this->model
            ->with(self::DEFAULT_RELATIONSHIPS)
            ->where('status', '1');

        // Apply search filter if search term is provided
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

        return $query
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->appends(request()->query());
    }

    /**
     * Check whether a non-deleted brand already exists with the given name.
     *
     * @param string $name
     * @return bool
     */
    public function nameExists(string $name): bool
    {
        $normalized = mb_strtolower(trim($name));
        if ($normalized === '') {
            return false;
        }

        return $this->model
            ->whereRaw('LOWER(name) = ?', [$normalized])
            ->whereNull('deleted_at')
            ->exists();
    }

    /**
     * Return existing non-deleted brand names (lowercased) for the given list.
     *
     * @param array<int, string> $names
     * @return array<int, string>
     */
    public function findExistingNames(array $names): array
    {
        $normalized = $this->normalizeNameList($names);
        if ($normalized === []) {
            return [];
        }

        return $this->model
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

    /**
     * Get a simple list of brands (ID and Name).
     *
     * @return Collection|null
     */
    public function getBrandList(): ?Collection
    {
        return $this->model
            ->select('id', 'name')        // Select only id and name
            ->where('status', '1')        // Match 'active' status from getAllBrands
            ->orderBy('created_at', 'desc')      // Order by created_at in descending order
            ->get();
    }

    /**
     * Fetch a single brand by its primary ID.
     *
     * @param int $id
     * @return Brand|null
     */
    public function getBrandById(int $id): ?Brand
    {
        return $this->model
            ->with(self::DEFAULT_RELATIONSHIPS)
            ->find($id);
    }

    /**
     * Fetch a single brand by its unique slug.
     *
     * @param string $slug
     * @return Brand|null
     */
    public function getBrandBySlug(string $slug): ?Brand
    {
        return $this->model
            ->with(self::DEFAULT_RELATIONSHIPS)
            ->where('slug', $slug)
            ->first();
    }

    // ============================================================================
    // WRITE OPERATIONS
    // ============================================================================

    /**
     * Create a new brand record.
     *
     * @param array<string, mixed> $data
     * @return Brand
     */
    public function createBrand(array $data): Brand
    {
        try {
            // Create the brand with the temporary slug
            $brand = $this->model->create($data);
            
            // Now update with the final unique slug using the brand ID
            $slugBase = \Illuminate\Support\Str::slug($data['name'] ?? '');
            $finalSlug = $slugBase . '-' . $brand->id;
            
            // Check if this final slug already exists (accounting for soft deletes)
            $existingSlug = $this->model->withTrashed()
                ->where('slug', $finalSlug)
                ->where('id', '!=', $brand->id)
                ->first();
            
            if ($existingSlug) {
                // If it exists, append a random string
                $finalSlug = $slugBase . '-' . $brand->id . '-' . \Illuminate\Support\Str::random(4);
            }
            
            // Update with the final slug
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

    /**
     * Update an existing brand by ID.
     *
     * @param int $id
     * @param array<string, mixed> $data
     * @return bool
     */
    public function updateBrand(int $id, array $data): bool
    {
        $brand = $this->model->findOrFail($id);
        
        // If slug is being updated, ensure it's unique
        if (isset($data['slug'])) {
            $slugBase = $data['slug'];
            $finalSlug = $slugBase . '-' . $id;
            
            // Check if this final slug already exists (accounting for soft deletes)
            $existingSlug = $this->model->withTrashed()
                ->where('slug', $finalSlug)
                ->where('id', '!=', $id)
                ->first();
            
            if ($existingSlug) {
                // If it exists, append a random string
                $finalSlug = $slugBase . '-' . $id . '-' . \Illuminate\Support\Str::random(4);
            }
            
            $data['slug'] = $finalSlug;
        }
        
        return $brand->update($data);
    }

    /**
     * Soft delete a brand by ID.
     *
     * @param int $id
     * @return bool
     */
    public function deleteBrand(int $id): bool
    {
        $brand = $this->model->findOrFail($id);
        return $brand->delete();
    }

    /**
     * Return existing non-deleted brands for the given names.
     *
     * @param array<int, string> $names
     * @return Collection
     */
    public function findByNames(array $names): Collection
    {
        $normalized = $this->normalizeNameList($names);
        if ($normalized === []) {
            return $this->model->newCollection();
        }

        return $this->model
            ->whereNull('deleted_at')
            ->whereRaw(
                'LOWER(name) IN (' . implode(',', array_fill(0, count($normalized), '?')) . ')',
                $normalized
            )
            ->get(['id', 'name', 'slug']);
    }

    /**
     * Return brands matching the given slugs.
     *
     * @param array<int, string> $slugs
     * @return Collection
     */
    public function findBySlugs(array $slugs): Collection
    {
        $slugs = array_values(array_filter(array_map(static fn ($slug) => trim((string) $slug), $slugs)));
        if ($slugs === []) {
            return $this->model->newCollection();
        }

        return $this->model
            ->whereIn('slug', $slugs)
            ->get(['id', 'name', 'slug']);
    }

    /**
     * Insert multiple brand rows in a single query.
     *
     * @param array<int, array<string, mixed>> $rows
     * @return void
     */
    public function insertBatch(array $rows): void
    {
        if ($rows === []) {
            return;
        }

        $this->model->newQuery()->insert($rows);
    }

    /**
     * Update multiple brand rows by primary key.
     *
     * @param array<int, array<string, mixed>> $rows
     * @return void
     */
    public function updateBatch(array $rows): void
    {
        foreach ($rows as $row) {
            if (empty($row['id'])) {
                continue;
            }

            $id = (int) $row['id'];
            unset($row['id']);
            if ($row === []) {
                continue;
            }

            $this->model->newQuery()->where('id', $id)->update($row);
        }
    }

    /**
     * Attach an agency to many brands.
     *
     * @param array<int, int> $brandIds
     * @param int $agencyId
     * @return void
     */
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
}