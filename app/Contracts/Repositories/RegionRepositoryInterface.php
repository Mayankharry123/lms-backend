<?php
/**
 * Interface RegionRepositoryInterface
 * 
 * @author Achal Sharma
 * @package App\Contracts\Repositories
 * @version 1.0.1
 */
namespace App\Contracts\Repositories;

use Illuminate\Support\Collection;
use App\Models\Region;

interface RegionRepositoryInterface 
{
    /**
     * Get all active regions.
     *
     * @return Collection
     */
    public function getAllActive(): Collection;

    /**
     * Get a region by its ID.
     *
     * @param int $id
     * @return Region
     */
    public function findById(int $id): Region;

    /**
     * Create a new region.
     *
     * @param array $data
     * @return Region
     */
    public function create(array $data): Region;

    /**
     * Update a region.
     *
     * @param int $id
     * @param array $data
     * @return Region
     */
    public function update(int $id, array $data): Region;

    /**
     * Delete a region.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool;

    /**
     * Get the count of brands associated with a region.
     *
     * @param int $id
     * @return int
     */
    public function getBrandsCount(int $id): int;
}
