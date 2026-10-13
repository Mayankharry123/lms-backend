<?php
/**
 * Interface PermissionRepositoryInterface
 * 
 * @author Achal Sharma
 * @package App\Contracts\Repositories
 * @version 1.0.1
 */
namespace App\Contracts\Repositories;

use App\Models\Permission;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PermissionRepositoryInterface
{
	/**
	 * Get all permissions with pagination
	 *
	 * @param int $perPage
	 * @return LengthAwarePaginator<Permission>
	 */
	public function all(int $perPage = 15): LengthAwarePaginator;
	
	/**
	 * Find a permission by ID
	 */
	public function find(int $id): ?Permission;

	/**
	 * Find a permission by UUID
	 */
	public function findByUuid(string $uuid): ?Permission;

	/**
	 * Find a permission by name
	 */
	public function findByName(string $name): ?Permission;

	/**
	 * Find a permission by slug
	 */
	public function findBySlug(string $slug): ?Permission;

	/**
	 * Create a new permission
	 */
	public function create(array $data): Permission;

	/**
	 * Update a permission by ID
	 */
	public function update(int $id, array $data): bool;

	/**
	 * Delete a permission by ID
	 */
	public function delete(int $id): bool;

	/**
	 * Search permissions by criteria with pagination
	 */
	public function search(array $criteria, int $perPage = 15): LengthAwarePaginator;

	/**
	 * Find a permission by ID with relationships
	 */
	public function findWithRelations(int $id, array $relations = []): ?Permission;

	/**
	 * Sync roles for a permission (replace existing)
	 *
	 * @param int $permissionId
	 * @param array $roleIds
	 * @return bool
	 */
	public function syncRoles(int $permissionId, array $roleIds): bool;

	/**
	 * Attach a role to a permission
	 */
	public function attachRole(int $permissionId, int $roleId): bool;

	/**
	 * Detach a role from a permission
	 */
	public function detachRole(int $permissionId, int $roleId): bool;

	/**
	 * Get permissions by parent status
	 *
	 * @param bool $isParent
	 * @param int $perPage
	 * @return LengthAwarePaginator<Permission>
	 */
	public function getByParentStatus(bool $isParent, int $perPage = 15): LengthAwarePaginator;

	/**
	 * Get active permissions
	 *
	 * @param int $perPage
	 * @return LengthAwarePaginator<Permission>
	 */
	public function getActive(int $perPage = 15): LengthAwarePaginator;

	/**
	 * Get all permissions as tree structure with id, display_name, and is_parent
	 *
	 * @return \Illuminate\Support\Collection
	 */
	public function getAllPermissionTree(): \Illuminate\Support\Collection;
}

