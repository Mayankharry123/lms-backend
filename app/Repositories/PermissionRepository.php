<?php

/**
 * PermissionRepository
 * 
 * @author Achal Sharma
 * @package App\Repositories
 * @version 1.0.1
 */

namespace App\Repositories;

use App\Contracts\Repositories\PermissionRepositoryInterface;
use App\Models\Permission;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PermissionRepository implements PermissionRepositoryInterface
{
    /**
     * @var Permission
     */
    protected Permission $model;

    /**
     * PermissionRepository constructor.
     *
     * @param Permission $model
     */
    public function __construct(Permission $model)
    {
        $this->model = $model;
    }

    /**
     * Get all permissions with pagination.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function all(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->latest()->paginate($perPage);
    }   

    /**
     * Find a permission by ID.
     *
     * @param int $id
     * @return Permission|null
     */
    public function find(int $id): ?Permission
    {
        return $this->model->find($id);
    }

    /**
     * Find a permission by UUID.
     *
     * @param string $uuid
     * @return Permission|null
     */
    public function findByUuid(string $uuid): ?Permission
    {
        return $this->model->where('uuid', $uuid)->first();
    }

    /**
     * Find a permission by name.
     *
     * @param string $name
     * @return Permission|null
     */
    public function findByName(string $name): ?Permission
    {
        return $this->model->where('name', $name)->first();
    }

    /**
     * Find a permission by slug.
     *
     * @param string $slug
     * @return Permission|null
     */
    public function findBySlug(string $slug): ?Permission
    {
        return $this->model->where('slug', $slug)->first();
    }

    /**
     * Create a new permission.
     *
     * @param array $data
     * @return Permission
     */
    public function create(array $data): Permission
    {
        return $this->model->create($data);
    }

    /**
     * Update a permission by ID.
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $permission = $this->model->find($id);

        if (! $permission) {
            return false;
        }

        return (bool) $permission->update($data);
    }

    /**
     * Delete a permission by ID.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $permission = $this->model->find($id);

        if (! $permission) {
            return false;
        }

        return (bool) $permission->delete();
    }

    /**
     * Search permissions by criteria with pagination.
     *
     * @param array $criteria
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function search(array $criteria, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        // Global q parameter for quick search
        if (! empty($criteria['q'])) {
            $q = $criteria['q'];
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('display_name', 'like', "%{$q}%")
                    ->orWhere('slug', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            });
        }

        if (! empty($criteria['name'])) {
            $query->where('name', 'like', "%{$criteria['name']}%");
        }

        if (isset($criteria['status'])) {
            $query->where('status', $criteria['status']);
        }

        if (isset($criteria['is_parent'])) {
            $query->where('is_parent', $criteria['is_parent']);
        }

        // Order results ascending by id by default
        return $query->orderBy('id', 'asc')->paginate($perPage);
    }

    /**
     * Find a permission by ID with specified relationships.
     *
     * @param int $id
     * @param array $relations
     * @return Permission|null
     */
    public function findWithRelations(int $id, array $relations = []): ?Permission
    {
        return $this->model->with($relations)->find($id);
    }

    /**
     * Sync roles for a permission.
     *
     * @param int $permissionId
     * @param array $roleIds
     * @return bool
     */
    public function syncRoles(int $permissionId, array $roleIds): bool
    {
        $permission = $this->model->find($permissionId);

        if (! $permission) {
            return false;
        }

        try {
            $permission->roles()->sync($roleIds);
            return true;
        } catch (\Throwable $e) {
            // Optionally log the exception here
            return false;
        }
    }

    /**
     * Attach a role to a permission without detaching existing roles.
     *
     * @param int $permissionId
     * @param int $roleId
     * @return bool
     */
    public function attachRole(int $permissionId, int $roleId): bool
    {
        $permission = $this->model->find($permissionId);

        if (! $permission) {
            return false;
        }

        try {
            $role = \App\Models\Role::find($roleId);
            if (!$role) {
                return false;
            }
            $permission->roles()->syncWithoutDetaching([$roleId]);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Detach a role from a permission.
     *
     * @param int $permissionId
     * @param int $roleId
     * @return bool
     */
    public function detachRole(int $permissionId, int $roleId): bool
    {
        $permission = $this->model->find($permissionId);

        if (! $permission) {
            return false;
        }

        try {
            $permission->roles()->detach($roleId);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Get permissions by parent status.
     *
     * @param bool $isParent
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getByParentStatus(bool $isParent, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->where('is_parent', $isParent)
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Get active permissions.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getActive(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->active()
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Get all permissions in tree structure.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAllPermissionTree(): \Illuminate\Support\Collection
    {
        // Get all root permissions (where is_parent is NULL) with their children recursively
        return $this->model
            ->with($this->getRecursiveChildrenRelation())
            ->where('status', 1)
            ->whereNull('is_parent')
            ->orderBy('order', 'asc')
            ->get();
    }

    /**
     * Get recursive children relation query
     * This loads children -> grandchildren -> great-grandchildren, etc.
     */
    private function getRecursiveChildrenRelation()
    {
        return 'children.children.children.children.children';
    }
}