<?php

namespace App\Services;

use App\Contracts\Repositories\RoleRepositoryInterface;
use App\Models\Role;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
class RoleService
{
	protected RoleRepositoryInterface $roleRepository;
	protected ResponseService $responseService;

	public function __construct(RoleRepositoryInterface $roleRepository, ResponseService $responseService)
	{
		$this->roleRepository = $roleRepository;
		$this->responseService = $responseService;
	}

	/**
	 * Get paginated roles
	 */
	public function list(array $criteria = [], int $perPage = 15): LengthAwarePaginator
	{
		return $this->roleRepository->search($criteria, $perPage);
	}

	/**
	 * Find role by id
	 */
	public function find(int $id): ?Role
	{
		return $this->roleRepository->find($id);
	}

	/**
	 * Find role by uuid
	 */
	public function findByUuid(string $uuid): ?Role
	{
		return $this->roleRepository->findByUuid($uuid);
	}

	/**
	 * Find role by name (replaces findBySlug since slug doesn't exist)
	 */
	public function findByName(string $name): ?Role
	{
		return $this->roleRepository->findByName($name);
	}

	/**
	 * Create a new role and optionally sync permissions
	 *
	 * @param array $data Role data (name, display_name, description, slug, status)
	 * @param array $permissions Array of permission IDs to assign to the role
	 * @return Role
	 * @throws ValidationException
	 */
	public function create(array $data, array $permissions = []): Role
	{
		$this->validateRoleData($data);
		$data['slug'] = $this->generateUniqueSlug((string) $data['name']);

		// Create the role
		$role = $this->roleRepository->create($data);

		// Sync permissions if provided
		if (! empty($permissions)) {
			// Ensure permission IDs are integers
			$permissionIds = array_map('intval', $permissions);
			$role->permissions()->sync($permissionIds);
		}

		return $role;
	}

	/**
	 * Update a role
	 *
	 * @param int $id Role ID
	 * @param array $data Role data to update (name, display_name, description, slug, status)
	 * @param array|null $permissions Array of permission IDs to sync (if null, permissions are not updated)
	 * @return bool
	 * @throws ValidationException
	 */
	public function update(int $id, array $data, ?array $permissions = null): bool
	{
		$this->validateRoleData($data, $id);

		$existingRole = $this->roleRepository->find($id);
		unset($data['slug']);

		if ($existingRole) {
			$nameChanged = array_key_exists('name', $data)
				&& (string) $data['name'] !== (string) $existingRole->name;
			$slugMissing = $existingRole->slug === null || $existingRole->slug === '';

			if ($nameChanged || $slugMissing) {
				$name = array_key_exists('name', $data) ? (string) $data['name'] : (string) $existingRole->name;
				$data['slug'] = $this->generateUniqueSlug($name, $id);
			}
		}

		// Update the role
		$updated = $this->roleRepository->update($id, $data);

		// Sync permissions if provided
		if ($updated && $permissions !== null) {
			$role = $this->roleRepository->find($id);
			if ($role) {
				// Ensure permission IDs are integers
				$permissionIds = array_map('intval', $permissions);
				$role->permissions()->sync($permissionIds);
			}
		}

		return $updated;
	}

	/**
	 * Delete a role
	 */
	public function delete(int $id): bool
	{
		return $this->roleRepository->delete($id);
	}

	/**
	 * Sync permissions for a role
	 */
	public function syncPermissions(int $roleId, array $permissionIds): bool
	{
		return $this->roleRepository->syncPermissions($roleId, $permissionIds);
	}

	/**
	 * Attach a permission to a role
	 */
	public function attachPermission(int $roleId, int $permissionId): bool
	{
		return $this->roleRepository->attachPermission($roleId, $permissionId);
	}

	/**
	 * Detach a permission from a role
	 */
	public function detachPermission(int $roleId, int $permissionId): bool
	{
		return $this->roleRepository->detachPermission($roleId, $permissionId);
	}

	/**
	 * Get users for a specific role
	 */
	public function getUsers(int $roleId, int $perPage = 10): LengthAwarePaginator
	{
		return $this->roleRepository->getUsers($roleId, $perPage);
	}

	/**
	 * Validate role data
	 *
	 * @param array $data
	 * @param int|null $ignoreId
	 * @throws ValidationException
	 */
	protected function validateRoleData(array $data, ?int $ignoreId = null): void
	{
		$rules = [
			'display_name' => 'nullable|string|max:255',
			'description' => 'nullable|string|max:1000',
		];

		if ($ignoreId === null || array_key_exists('name', $data)) {
			$rules['name'] = 'required|string|max:255|unique:roles,name' . ($ignoreId ? ",{$ignoreId}" : '');
		}

		$validator = Validator::make($data, $rules);

		if ($validator->fails()) {
			throw new ValidationException($validator);
		}
	}

	/**
	 * Build a unique lowercase slug from a role name.
	 * "Planner Admin" becomes "planner-admin". A taken slug becomes "planner-admin-1".
	 *
	 * @param string $name
	 * @param int|null $ignoreId Current role id to ignore during an update.
	 * @return string
	 */
	protected function generateUniqueSlug(string $name, ?int $ignoreId = null): string
	{
		$baseSlug = Str::slug($name);
		if ($baseSlug === '') {
			$baseSlug = 'role';
		}

		$slug = $baseSlug;
		$counter = 1;

		while ($this->slugIsTaken($slug, $ignoreId)) {
			$slug = $baseSlug . '-' . $counter;
			$counter++;
		}

		return $slug;
	}

	/**
	 * @param string $slug
	 * @param int|null $ignoreId
	 * @return bool
	 */
	protected function slugIsTaken(string $slug, ?int $ignoreId = null): bool
	{
		$existing = $this->roleRepository->findBySlug($slug);

		if (!$existing) {
			return false;
		}

		return $ignoreId === null || (int) $existing->id !== $ignoreId;
	}
}

