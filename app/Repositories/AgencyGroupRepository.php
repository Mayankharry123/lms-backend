<?php

/**
 * AgencyGroupRepository
 * 
 * @author Achal Sharma
 * @package App\Repositories
 * @version 1.0.1
 */

namespace App\Repositories;

use App\Contracts\Repositories\AgencyGroupRepositoryInterface;
use App\Models\Agency;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class AgencyGroupRepository implements AgencyGroupRepositoryInterface
{
    /**
     * @var Agency
     */
    protected Agency $model;

    /**
     * AgencyGroupRepository constructor.
     *
     * @param Agency $agency
     */
    public function __construct(Agency $agency)
    {
        $this->model = $agency;
    }

    /**
     * Parent agencies (agency groups) have no parent reference.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function baseQuery()
    {
        return $this->model
            ->with(['agencyType', 'brand', 'childs'])
            ->whereNull('is_parent')
            ->where('status', '1');
    }

    /**
     * Get all agency groups with pagination and optional search filter.
     *
     * @param int $perPage
     * @param string|null $searchTerm
     * @return LengthAwarePaginator
     */
    public function getAllGroups(int $perPage = 10, ?string $searchTerm = null): LengthAwarePaginator
    {
        $query = $this->baseQuery();

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
     * Get an agency group by ID.
     *
     * @param int $id
     * @return Agency|null
     */
    public function getGroupById(int $id): ?Agency
    {
        return $this->baseQuery()->find($id);
    }

    /**
     * Create a new agency group.
     *
     * @param array $data
     * @return Agency
     */
    public function createGroup(array $data): Agency
    {
        $data['is_parent'] = null;

        if (!empty($data['name']) && empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        return $this->model->create($data);
    }

    /**
     * Update an agency group by ID.
     *
     * @param int $id
     * @param array $data
     * @return Agency
     */
    public function updateGroup(int $id, array $data): Agency
    {
        $group = $this->baseQuery()->findOrFail($id);

        if (isset($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        // Groups remain top-level parents
        $data['is_parent'] = null;

        $group->update($data);

        return $group->fresh(['agencyType', 'brand', 'childs']);
    }

    /**
     * Delete an agency group by ID.
     *
     * @param int $id
     * @return bool
     */
    public function deleteGroup(int $id): bool
    {
        $group = $this->baseQuery()->findOrFail($id);

        return $group->delete();
    }
}
