<?php

/**
 * RoleRepository
 * 
 * @author Achal Sharma
 * @package App\Repositories
 * @version 1.0.1
 */

namespace App\Repositories;

use App\Contracts\Repositories\RoleRepositoryInterface;
use App\Models\Role;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class RoleRepository implements RoleRepositoryInterface
{
    /**
     * @var Role
     */
    protected Role $model;

    /**
     * RoleRepository constructor.
     *
     * @param Role $model
     */
    public function __construct(Role $model)
    {
        $this->model = $model;
    }

    /**
     * Get all roles with pagination.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function all(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->latest()->paginate($perPage);
    }   

    /**
     * Find a role by ID.
     *
     * @param int $id
     * @return Role|null
     */
    public function find(int $id): ?Role
    {
        return $this->model->find($id);
    }

    /**
     * Find a role by UUID.
     *
     * @param string $uuid
     * @return Role|null
     */
    public function findByUuid(string $uuid): ?Role
    {
        return $this->model->where('uuid', $uuid)->first();
    }

    /**
     * Find a role by name.
     *
     * @param string $name
     * @return Role|null
     */
    public function findByName(string $name): ?Role
    {
        return $this->model->where('name', $name)->first();
    }

    /**
     * Find a role by slug, including soft-deleted rows.
     *
     * @param string $slug
     * @return Role|null
     */
    public function findBySlug(string $slug): ?Role
    {
        return $this->model->withTrashed()->where('slug', $slug)->first();
    }

    /**
     * Create a new role.
     *
     * @param array $data
     * @return Role
     */
    public function create(array $data): Role
    {
        return $this->model->create($data);
    }

    /**
     * Update a role by ID.
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $role = $this->model->find($id);

        if (! $role) {
            return false;
        }

        return (bool) $role->update($data);
    }

    /**
     * Delete a role by ID.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $role = $this->model->find($id);

        if (! $role) {
            return false;
        }

        return (bool) $role->delete();
    }

    /**
     * Search roles by criteria with pagination.
     *
     * @param array $criteria
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function search(array $criteria, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->with('permissions')->newQuery();

        // Global q parameter for quick search
        if (! empty($criteria['q'])) {
            $q = $criteria['q'];
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('display_name', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            });
        }

        if (! empty($criteria['name'])) {
            $query->where('name', 'like', "%{$criteria['name']}%");
        }

        // Order results ascending by id by default
        return $query->orderBy('id', 'asc')->paginate($perPage);
    }

    /**
     * Find a role by ID with specified relationships.
     *
     * @param int $id
     * @param array $relations
     * @return Role|null
     */
    public function findWithRelations(int $id, array $relations = []): ?Role
    {
        return $this->model->with($relations)->find($id);
    }

    /**
     * Sync permissions for a role.
     *
     * @param int $roleId
     * @param array $permissionIds
     * @return bool
     */
    public function syncPermissions(int $roleId, array $permissionIds): bool
    {
        $role = $this->model->find($roleId);

        if (! $role) {
            return false;
        }

        try {
            // Laratrust provides syncPermissions on the Role model
            $role->syncPermissions($permissionIds);
            return true;
        } catch (\Throwable $e) {
            // Optionally log the exception here
            return false;
        }
    }

    /**
     * Attach a permission to a role.
     *
     * @param int $roleId
     * @param int $permissionId
     * @return bool
     */
    public function attachPermission(int $roleId, int $permissionId): bool
    {
        $role = $this->model->find($roleId);

        if (! $role) {
            return false;
        }

        try {
            $permission = \App\Models\Permission::find($permissionId);
            if (!$permission) {
                return false;
            }
            $role->givePermission($permission);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Detach a permission from a role.
     *
     * @param int $roleId
     * @param int $permissionId
     * @return bool
     */
    public function detachPermission(int $roleId, int $permissionId): bool
    {
        $role = $this->model->find($roleId);

        if (! $role) {
            return false;
        }

        try {
            $permission = \App\Models\Permission::find($permissionId);
            if (!$permission) {
                return false;
            }
            $role->removePermission($permission);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Get users for a specific role
     */
    public function getUsers(int $roleId, int $perPage = 15): LengthAwarePaginator
    {
        $role = $this->model->find($roleId);

        if (! $role) {
            return new LengthAwarePaginator([], 0, $perPage, 1, [
                'path' => '',
                'pageName' => 'page'
            ]);
        }

        return $role->users()->paginate($perPage);
    }
}