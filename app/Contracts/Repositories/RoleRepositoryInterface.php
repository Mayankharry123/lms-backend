<?php

namespace App\Contracts\Repositories;

use App\Models\Role;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface RoleRepositoryInterface
{
	/**
	 * Get all roles with pagination
	 *
	 * @param int $perPage
	 * @return LengthAwarePaginator<Role>
	 */
	public function all(int $perPage = 15): LengthAwarePaginator;
	
	/**
	 * Find a role by ID
	 */
	public function find(int $id): ?Role;

	/**
	 * Find a role by UUID
	 */
	public function findByUuid(string $uuid): ?Role;

	/**
	 * Find a role by name
	 */
	public function findByName(string $name): ?Role;

	/**
	 * Find a role by slug, including soft-deleted rows so uniqueness matches the database index.
	 *
	 * @param string $slug
	 * @return Role|null
	 */
	public function findBySlug(string $slug): ?Role;

	/**
	 * Create a new role
	 */
	public function create(array $data): Role;

	/**
	 * Update a role by ID
	 */
	public function update(int $id, array $data): bool;

	/**
	 * Delete a role by ID
	 */
	public function delete(int $id): bool;

	/**
	 * Search roles by criteria with pagination
	 */
	public function search(array $criteria, int $perPage = 15): LengthAwarePaginator;

	/**
	 * Find a role by ID with relationships
	 */
	public function findWithRelations(int $id, array $relations = []): ?Role;

	/**
	 * Sync permissions for a role (replace existing)
	 *
	 * @param int $roleId
	 * @param array $permissionIds
	 * @return bool
	 */
	public function syncPermissions(int $roleId, array $permissionIds): bool;

	/**
	 * Attach a permission to a role
	 */
	public function attachPermission(int $roleId, int $permissionId): bool;

	/**
	 * Detach a permission from a role
	 */
	public function detachPermission(int $roleId, int $permissionId): bool;

	/**
	 * Get users for a specific role
	 */
	public function getUsers(int $roleId, int $perPage = 15): LengthAwarePaginator;
}

