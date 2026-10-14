<?php

/**
 * AgencyRepository
 * 
 * @author Achal Sharma
 * @package App\Repositories
 * @version 1.0.1
 */

namespace App\Repositories;

use App\Contracts\Repositories\AgencyRepositoryInterface;
use App\Models\Agency;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class AgencyRepository implements AgencyRepositoryInterface
{
    /**
     * @var Agency
     */
    protected Agency $model;

    /**
     * Create a new AgencyTypeRepository instance.
     *
     * @param Agency $agencyType
     */
    public function __construct(Agency $agency)
    {
        $this->model = $agency;
    }


    /**
     * Fetch paginated list of agency types with optional search.
     *
     * @param int $perPage
     * @param string|null $searchTerm
     * @param int|null $isParent Filter by parent agency group (is_parent)
     * @return LengthAwarePaginator
     */
    public function getAllAgency(int $perPage = 10, ?string $searchTerm = null, ?int $isParent = null): LengthAwarePaginator
    {
        $query = $this->model->with(['agencyType', 'brand', 'parentAgency'])->where('status', '1');

        // Apply search filter if search term is provided
        if ($searchTerm) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'LIKE', "%{$searchTerm}%")
                  ->orWhereHas('agencyType', function ($agencyTypeQuery) use ($searchTerm) {
                      $agencyTypeQuery->where('name', 'LIKE', "%{$searchTerm}%");
                  });
            });
        }

        // Apply parent agency group filter if provided
        if ($isParent !== null) {
            $query->where('is_parent', $isParent);
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
     * @return Agency|null
     */
    public function getAgencyById(int $id)
    {
        return $this->model->with(['agencyType', 'brand', 'parentAgency','childs','childs.agencyType','childs.brand'])->find($id);
    }

    /**
     * Fetch a single agency type by its unique slug.
     *
     * @param string $slug
     * @return Agency|null
     */
    public function getAgencyBySlug(string $slug): ?Agency
    {
        return $this->model->where('slug', $slug)->first();
    }

    /**
     * Create a new agency  record.
     *
     * @param array<string, mixed> $data
     * @return Agency
     */
    public function createAgency(array $data): Agency
    {
        return $this->model->create($data);
    }

    /**
     * Update an existing agency record.
     *
     * @param int $id
     * @param array<string, mixed> $data
     * @return Agency
     */
    public function updateAgency(int $id, array $data): Agency
    {
        $agency = $this->model->findOrFail($id);

        // Update slug only when name is provided
        if (isset($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $agency->update($data);

        return $agency->fresh([
            'agencyType',
            'brand',
            'parentAgency',
            'childs',
            'childs.agencyType',
            'childs.brand',
        ]);
    }

    /**
     * Soft delete an agency by ID.
     *
     * @param int $id
     * @return bool
     */
    public function deleteAgency(int $id): bool
    {
        $agency = $this->model->findOrFail($id);
        return $agency->delete();
    }
}