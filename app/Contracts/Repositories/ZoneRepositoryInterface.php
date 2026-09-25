<?php

namespace App\Contracts\Repositories;

use App\Models\Zone;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ZoneRepositoryInterface
{
    /**
     * Get all paginated zones with optional search.
     * @param int $perPage
     * @param string|null $searchTerm
     * @return LengthAwarePaginator
     */
    public function allPaginated(int $perPage = 10, ?string $searchTerm = null): LengthAwarePaginator;

    /**
     * Get all active zones as a list.
     * @return Collection
     */
    public function getActiveList(): Collection;

    /**
     * Find a zone by its ID.
     * @param int $id
     * @return Zone
     */
    public function findById(int $id): Zone;

    /**
     * Create a new zone.
     * @param array $data
     * @return Zone
     */
    public function create(array $data): Zone;

    /**
     * Update an existing zone.
     * @param Zone $zone
     * @param array $data
     * @return bool
     */
    public function update(Zone $zone, array $data): bool;

    /**
     * Delete a zone.
     * @param Zone $zone
     * @return bool
     */
    public function delete(Zone $zone): bool;

    /**
     * Find non-deleted zones by name (case-insensitive).
     *
     * @param array<int, string> $names
     * @return Collection
     */
    public function findByNames(array $names): Collection;
}