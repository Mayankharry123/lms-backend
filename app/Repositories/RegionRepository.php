<?php

/**
 * RegionRepository
 * 
 * @author Achal Sharma
 * @package App\Repositories
 * @version 1.0.1
 */

namespace App\Repositories;

use App\Contracts\Repositories\RegionRepositoryInterface;
use App\Models\Region;
use Illuminate\Support\Collection;

class RegionRepository implements RegionRepositoryInterface
{
    /**
     * @var Region
     */
    protected $model;

    /**
     * RegionRepository constructor.
     *
     * @param Region $model
     */
    public function __construct(Region $model)
    {
        $this->model = $model;
    }

    /**
     * Get all active regions (where flag = 1)
     */
    public function getAllActive(): Collection
    {
        return $this->model
            ->where('flag', 1) 
            ->get();
    }

    /**
     * Get a region by its ID.
     */
    public function findById(int $id): Region
    {
        return $this->model->findOrFail($id);
    }

    /**
     * Create a new region.
     */
    public function create(array $data): Region
    {
        return $this->model->create($data);
    }

    /**
     * Update a region.
     */
    public function update(int $id, array $data): Region
    {
        $region = $this->findById($id);
        $region->update($data);
        return $region;
    }

    /**
     * Delete a region.
     * Note: Your 'regions' table does not use SoftDeletes.
     * This will be a permanent deletion.
     */
    public function delete(int $id): bool
    {
        return $this->findById($id)->delete();
    }

    /**
     * Get the count of associated brands.
     */
    public function getBrandsCount(int $id): int
    {
        return $this->findById($id)->brands()->count();
    }
}
