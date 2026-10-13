<?php

/**
 * AgencyTypeRepository
 * 
 * @author Achal Sharma
 * @package App\Repositories
 * @version 1.0.1
 */

namespace App\Repositories;

use App\Contracts\Repositories\AgencyTypeRepositoryInterface;
use App\Models\AgencyType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AgencyTypeRepository implements AgencyTypeRepositoryInterface
{
    /**
     * @var AgencyType
     */
    protected AgencyType $model;

    /**
     * Create a new AgencyTypeRepository instance.
     *
     * @param AgencyType $agencyType
     */
    public function __construct(AgencyType $agencyType)
    {
        $this->model = $agencyType;
    }

    // ============================================================================
    // READ OPERATIONS
    // ============================================================================

    /**
     * Fetch paginated list of agency types with optional search.
     *
     * @param int $perPage
     * @param string|null $searchTerm
     * @return LengthAwarePaginator
     */
    public function getAllAgencyTypes(int $perPage = 10, ?string $searchTerm = null): LengthAwarePaginator
    {
        $query = $this->model->where('status', '1');

        // Apply search filter if search term is provided
        if ($searchTerm) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('slug', 'LIKE', "%{$searchTerm}%");
            });
        }

        return $query
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->appends(request()->query());
    }

    /**
     * Fetch a single agency type by its primary ID.
     *
     * @param int $id
     * @return AgencyType|null
     */
    public function getAgencyTypeById(int $id): ?AgencyType
    {
        return $this->model->find($id);
    }

    /**
     * Fetch a single agency type by its unique slug.
     *
     * @param string $slug
     * @return AgencyType|null
     */
    public function getAgencyTypeBySlug(string $slug): ?AgencyType
    {
        return $this->model->where('slug', $slug)->first();
    }

    // ============================================================================
    // WRITE OPERATIONS
    // ============================================================================

    /**
     * Create a new agency type record.
     *
     * @param array<string, mixed> $data
     * @return AgencyType
     */
    public function createAgencyType(array $data): AgencyType
    {
        return $this->model->create($data);
    }

    /**
     * Update an existing agency type by ID.
     *
     * @param int $id
     * @param array<string, mixed> $data
     * @return AgencyType
     */
    public function updateAgencyType(int $id, array $data): AgencyType
    {
        $agencyType = $this->model->findOrFail($id);
        $agencyType->update($data);
        return $agencyType;
    }

    /**
     * Soft delete an agency type by ID.
     *
     * @param int $id
     * @return bool
     */
    public function deleteAgencyType(int $id): bool
    {
        $agencyType = $this->model->findOrFail($id);
        return $agencyType->delete();
    }
}