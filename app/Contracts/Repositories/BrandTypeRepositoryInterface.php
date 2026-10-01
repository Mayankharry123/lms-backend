<?php

namespace App\Contracts\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Models\BrandType;

interface BrandTypeRepositoryInterface 
{
    /**
     * Get all active brand types with pagination and optional search.
     *
     * @param int $perPage
     * @param string|null $searchTerm
     * @return LengthAwarePaginator
     */
    public function getAllActive(int $perPage = 10, ?string $searchTerm = null): LengthAwarePaginator;

    /**
     * Get a brand type by its ID.
     *
     * @param int $id
     * @return BrandType
     */
    public function findById(int $id): BrandType;

    /**
     * Create a new brand type.
     *
     * @param array $data
     * @return BrandType
     */
    public function create(array $data): BrandType;

    /**
     * Update a brand type.
     *
     * @param int $id
     * @param array $data
     * @return BrandType
     */
    public function update(int $id, array $data): BrandType;

    /**
     * Soft delete a brand type.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool;

    /**
     * Get the count of brands associated with a brand type.
     *
     * @param int $id
     * @return int
     */
    public function getBrandsCount(int $id): int;

    /**
     * Find non-deleted brand types by name (case-insensitive).
     *
     * @param array<int, string> $names
     * @return Collection
     */
    public function findByNames(array $names): Collection;
}
