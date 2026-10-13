<?php

/**
 * BrandTypeService
 * 
 * @author Achal Sharma
 * @package App\Services
 * @version 1.0.1
 */

namespace App\Services;

use App\Contracts\Repositories\BrandTypeRepositoryInterface;
use Illuminate\Support\Collection;
use App\Models\BrandType;
use Illuminate\Support\Str;
use App\Exceptions\ResourceInUseException; // We will create this
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Services\ResponseService;

class BrandTypeService
{
    protected $repository;

    /**
     * Inject the Repository Interface.
     *
     * @param BrandTypeRepositoryInterface $repository
     */
    public function __construct(BrandTypeRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get all active brand types.
     */
    public function getAll(int $perPage = 10, ?string $searchTerm = null): LengthAwarePaginator // <-- Parameters aur return type update karein
    {
        // Parameters ko repository method mein pass karein
        return $this->repository->getAllActive($perPage, $searchTerm);
    }

    /**
     * Find a brand type by ID.
     */
    public function findById(int $id): BrandType
    {
        return $this->repository->findById($id);
    }

    /**
     * Create a new brand type.
     */
    public function create(array $data): BrandType
    {
        // --- Business Logic ---
        $data['slug'] = Str::slug($data['name']);
        // --- End Business Logic ---

        return $this->repository->create($data);
    }

    /**
     * Update a brand type.
     */
    public function update(int $id, array $data): BrandType
    {
        // --- Business Logic ---
        if (isset($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }
        // --- End Business Logic ---

        return $this->repository->update($id, $data);
    }

    /**
     * Delete a brand type.
     */
    public function delete(int $id): bool
    {
        // --- Business Logic (Safety Check) ---
        $brandCount = $this->repository->getBrandsCount($id);

        if ($brandCount > 0) {
            // Throw a custom exception that the controller can catch
            throw new \Exception(
                'Cannot delete. This brand type is in use by one or more brands.', 
                ResponseService::HTTP_FORBIDDEN // Using the code from your service
            );
        }
        // --- End Business Logic ---
        
        return $this->repository->delete($id);
    }
}
