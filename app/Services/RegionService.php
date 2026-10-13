<?php

/**
 * RegionService
 * 
 * @author Achal Sharma
 * @package App\Services
 * @version 1.0.1
 */

namespace App\Services;

use App\Contracts\Repositories\RegionRepositoryInterface;
use Illuminate\Support\Collection;
use App\Models\Region;
use App\Services\ResponseService; // For the error code

class RegionService
{
    protected $repository;
    /**
     * RegionService constructor.
     *
     * @param RegionRepositoryInterface $repository
     */
    public function __construct(RegionRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get all active regions.
     */
    public function getAll(): Collection
    {
        return $this->repository->getAllActive();
    }

    /**
     * Find a region by ID.
     */
    public function findById(int $id): Region
    {
        return $this->repository->findById($id);
    }

    /**
     * Create a new region.
     */
    public function create(array $data): Region
    {
        // Set default for flag if not provided
        if (!isset($data['flag'])) {
            $data['flag'] = 1;
        }
        
        return $this->repository->create($data);
    }

    /**
     * Update a region.
     */
    public function update(int $id, array $data): Region
    {
        return $this->repository->update($id, $data);
    }

    /**
     * Delete a region.
     */
    public function delete(int $id): bool
    {
        // --- Business Logic (Safety Check) ---
        $brandCount = $this->repository->getBrandsCount($id);

        if ($brandCount > 0) {
            // Throw a custom exception that the controller can catch
            throw new \Exception(
                'Cannot delete. This region is in use by one or more brands.', 
                ResponseService::HTTP_FORBIDDEN // Using the code from your service
            );
        }
        // --- End Business Logic ---
        
        return $this->repository->delete($id);
    }
}
