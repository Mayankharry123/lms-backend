<?php

namespace App\Repositories;

use App\Contracts\Repositories\BrandRepositoryInterface;
use App\Models\Brand;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class BrandRepository implements BrandRepositoryInterface
{
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
        return $this->model->getAllBrands($perPage, $searchTerm);
    }

    /**
     * Check whether a non-deleted brand already exists with the given name.
     *
     * @param string $name
     * @return bool
     */
    public function nameExists(string $name): bool
    {
        return $this->model->nameExists($name);
    }

    public function slugExistsWithTrashed(string $slug, int $exceptId): bool
    {
        return $this->model->slugExistsWithTrashed($slug, $exceptId);
    }

    /**
     * Return existing non-deleted brand names (lowercased) for the given list.
     *
     * @param array<int, string> $names
     * @return array<int, string>
     */
    public function findExistingNames(array $names): array
    {
        return $this->model->findExistingNames($names);
    }

    /**
     * Get a simple list of brands (ID and Name).
     *
     * @return Collection|null
     */
    public function getBrandList(): ?Collection
    {
        return $this->model->getBrandList();
    }

    /**
     * Fetch a single brand by its primary ID.
     *
     * @param int $id
     * @return Brand|null
     */
    public function getBrandById(int $id): ?Brand
    {
        return $this->model->getBrandById($id);
    }

    /**
     * Fetch a single brand by its unique slug.
     *
     * @param string $slug
     * @return Brand|null
     */
    public function getBrandBySlug(string $slug): ?Brand
    {
        return $this->model->getBrandBySlug($slug);
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
        return $this->model->createBrand($data);
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
        return $this->model->updateBrand($id, $data);
    }

    /**
     * Soft delete a brand by ID.
     *
     * @param int $id
     * @return bool
     */
    public function deleteBrand(int $id): bool
    {
        return $this->model->deleteBrand($id);
    }

    /**
     * Return existing non-deleted brands for the given names.
     *
     * @param array<int, string> $names
     * @return Collection
     */
    public function findByNames(array $names): Collection
    {
        return $this->model->findByNames($names);
    }

    /**
     * Return brands matching the given slugs.
     *
     * @param array<int, string> $slugs
     * @return Collection
     */
    public function findBySlugs(array $slugs): Collection
    {
        return $this->model->findBySlugs($slugs);
    }

    /**
     * Insert multiple brand rows in a single query.
     *
     * @param array<int, array<string, mixed>> $rows
     * @return void
     */
    public function insertBatch(array $rows): void
    {
        $this->model->insertBatch($rows);
    }

    /**
     * Update multiple brand rows by primary key.
     *
     * @param array<int, array<string, mixed>> $rows
     * @return void
     */
    public function updateBatch(array $rows): void
    {
        $this->model->updateBatch($rows);
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
        $this->model->attachAgencyToBrands($brandIds, $agencyId);
    }
}