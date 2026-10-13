<?php

/**
 * PermissionService
 * 
 * @author Achal Sharma
 * @package App\Services
 * @version 1.0.1
 */

namespace App\Services;

use App\Contracts\Repositories\PermissionRepositoryInterface;
use App\Models\Permission;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Exception;

class PermissionService
{
	protected PermissionRepositoryInterface $permissionRepository;
	protected ResponseService $responseService;
    /**
     * PermissionService constructor.
     *
     * @param PermissionRepositoryInterface $permissionRepository
     * @param ResponseService $responseService
     */
    public function __construct(PermissionRepositoryInterface $permissionRepository, ResponseService $responseService)
	{
		$this->permissionRepository = $permissionRepository;
		$this->responseService = $responseService;
	}

	/**
	 * Get paginated permissions
	 */
	public function list(array $criteria = [], int $perPage = 15): LengthAwarePaginator
	{
		return $this->permissionRepository->search($criteria, $perPage);
	}

	/**
	 * Find permission by id
	 */
	public function find(int $id): ?Permission
	{
		return $this->permissionRepository->find($id);
	}

	/**
	 * Find permission by uuid
	 */
	public function findByUuid(string $uuid): ?Permission
	{
		return $this->permissionRepository->findByUuid($uuid);
	}

	/**
	 * Find permission by name
	 */
	public function findByName(string $name): ?Permission
	{
		return $this->permissionRepository->findByName($name);
	}

	/**
	 * Find permission by slug
	 */
	public function findBySlug(string $slug): ?Permission
	{
		return $this->permissionRepository->findBySlug($slug);
	}

	/**
	 * Create a new permission and optionally sync roles
	 *
	 * @param array $data Permission data (name, display_name, description, slug, url, icon_file, icon_text, is_parent, status)
	 * @param array $roles Array of role IDs to assign to the permission
	 * @return Permission
	 * @throws ValidationException
	 */
	public function create(array $data, array $roles = []): Permission
	{
		$this->validatePermissionData($data);

		// Auto-generate UUID if not provided
		if (!isset($data['uuid'])) {
			$data['uuid'] = (string) Str::uuid();
		}

		// Auto-generate slug if not provided
		if (!isset($data['slug']) && isset($data['display_name'])) {
			$baseSlug = Str::slug($data['display_name'] ?? $data['name']);
			$slug = $baseSlug;
			$counter = 1;
			
			// Ensure slug is unique
			while ($this->permissionRepository->findBySlug($slug)) {
				$slug = $baseSlug . '-' . $counter;
				$counter++;
			}
			$data['slug'] = $slug;
		} elseif (!isset($data['slug']) && isset($data['name'])) {
			$baseSlug = Str::slug($data['name']);
			$slug = $baseSlug;
			$counter = 1;
			
			// Ensure slug is unique
			while ($this->permissionRepository->findBySlug($slug)) {
				$slug = $baseSlug . '-' . $counter;
				$counter++;
			}
			$data['slug'] = $slug;
		}

		// Set default status if not provided
		if (!isset($data['status'])) {
			$data['status'] = '1';
		}

		// // Set default is_parent if not provided
		// if (!isset($data['is_parent'])) {
		// 	$data['is_parent'] = false;
		// }

		// Create the permission
		$permission = $this->permissionRepository->create($data);

		// Sync roles if provided
		if (!empty($roles)) {
			// Ensure role IDs are integers
			$roleIds = array_map('intval', $roles);
			$permission->roles()->sync($roleIds);
		}

		return $permission;
	}

	/**
	 * Update a permission
	 *
	 * @param int $id Permission ID
	 * @param array $data Permission data to update (name, display_name, description, slug, url, icon_file, icon_text, is_parent, status)
	 * @param array|null $roles Array of role IDs to sync (if null, roles are not updated)
	 * @return bool
	 * @throws ValidationException
	 */
	public function update(int $id, array $data, ?array $roles = null): bool
	{
		// Normalize is_parent - handle both null and empty string
		if (array_key_exists('is_parent', $data)) {
			$isParent = $data['is_parent'];
			
			// Convert empty string, "0", 0 to null
			if ($isParent === '' || $isParent === '0' || $isParent === 0) {
				$data['is_parent'] = null;
			} elseif ($isParent !== null) {
				// Convert to integer for non-null values
				$data['is_parent'] = intval($isParent);
			}
		}

		$existingPermission = $this->permissionRepository->find($id);

		// Auto-generate slug only when name/display_name changed and slug not provided
		if (
			!isset($data['slug'])
			&& $existingPermission
			&& (isset($data['display_name']) || isset($data['name']))
		) {
			$displayNameChanged = isset($data['display_name'])
				&& $data['display_name'] !== $existingPermission->display_name;
			$nameChanged = isset($data['name'])
				&& $data['name'] !== $existingPermission->name;

			if ($displayNameChanged || $nameChanged) {
				$baseSlug = Str::slug($data['display_name'] ?? $data['name']);
				$slug = $baseSlug;
				$counter = 1;
				
				while ($this->permissionRepository->findBySlug($slug)) {
					$existingBySlug = $this->permissionRepository->findBySlug($slug);
					if ($existingBySlug && $existingBySlug->id == $id) {
						break;
					}
					$slug = $baseSlug . '-' . $counter;
					$counter++;
				}
				$data['slug'] = $slug;
			}
		}

		$this->validatePermissionData($data, $id);

		// Update the permission
		$updated = $this->permissionRepository->update($id, $data);

		// Sync roles if provided
		if ($updated && $roles !== null) {
			$permission = $this->permissionRepository->find($id);
			if ($permission) {
				$roleIds = array_map('intval', $roles);
				$permission->roles()->sync($roleIds);
			}
		}

		return $updated;
	}


	/**
	 * Delete a permission
	 */
	public function delete(int $id): bool
	{
		return $this->permissionRepository->delete($id);
	}

	/**
	 * Sync roles for a permission
	 */
	public function syncRoles(int $permissionId, array $roleIds): bool
	{
		return $this->permissionRepository->syncRoles($permissionId, $roleIds);
	}

	/**
	 * Attach a role to a permission
	 */
	public function attachRole(int $permissionId, int $roleId): bool
	{
		return $this->permissionRepository->attachRole($permissionId, $roleId);
	}

	/**
	 * Detach a role from a permission
	 */
	public function detachRole(int $permissionId, int $roleId): bool
	{
		return $this->permissionRepository->detachRole($permissionId, $roleId);
	}

	/**
	 * Get permissions by parent status
	 */
	public function getByParentStatus(bool $isParent, int $perPage = 15): LengthAwarePaginator
	{
		return $this->permissionRepository->getByParentStatus($isParent, $perPage);
	}

	/**
	 * Get active permissions
	 */
	public function getActive(int $perPage = 15): LengthAwarePaginator
	{
		return $this->permissionRepository->getActive($perPage);
	}

	/**
	 * Get all permissions as tree structure with id, display_name, and is_parent
	 *
	 * @return \Illuminate\Support\Collection
	 */
	public function getAllPermissionTree(): \Illuminate\Support\Collection
	{
		return $this->permissionRepository->getAllPermissionTree();
	}

	/**
	 * Get sidebar permissions in hierarchical format (parent -> children)
	 * Returns only the necessary fields for sidebar display
	 *
	 * @return \Illuminate\Support\Collection
	 */
	public function getSidebarPermissions(): \Illuminate\Support\Collection
	{
		$permissions = $this->getAllPermissionTree();
		
		return $permissions->map(function ($permission) {
			// Recursively format permission with children
			$format = function ($perm) use (&$format) {
				$formatted = [
					'id' => $perm->id,
					'name' => $perm->name,
					'display_name' => $perm->display_name,
					'slug' => $perm->slug,
					'url' => $perm->url,
					'icon_text' => $perm->icon_text,
					'icon_file' => $perm->icon_file,
					'is_parent' => $perm->is_parent,
					'order' => $perm->order,
				];

				// Add children recursively if they exist
				if ($perm->children && $perm->children->isNotEmpty()) {
					$formatted['children'] = $perm->children->map(function ($child) use ($format) {
						return $format($child);
					})->values()->toArray();
				} else {
					$formatted['children'] = [];
				}

				return $formatted;
			};

			return $format($permission);
		});
	}


	/**
	 * Upload icon file for permission
	 *
	 * @param UploadedFile $file
	 * @return string|null
	 */
	public function uploadIcon(UploadedFile $file): ?string
	{
		try {
			$model = new Permission();
			// Use HandlesFileUploads trait method
			$uploadResult = $model->uploadImage($file, 'permissions/icons', [
				'disk' => 'public',
				'prefix' => 'icon_',
			]);
			
			// Return the URL to store in database
			return $uploadResult['url'] ?? $uploadResult['path'] ?? null;
		} catch (Exception $e) {
			Log::error('Error uploading permission icon', ['exception' => $e]);
			throw new Exception('Error uploading icon file: ' . $e->getMessage());
		}
	}

	/**
	 * Delete icon file
	 *
	 * @param string $filePath
	 * @return bool
	 */
	public function deleteIcon(string $filePath): bool
	{
		try {
			$model = new Permission();
			return $model->deleteFile($filePath, 'public');
		} catch (Exception $e) {
			Log::error('Error deleting permission icon', ['file_path' => $filePath, 'exception' => $e]);
			return false;
		}
	}

	/**
	 * Validate permission data
	 *
	 * @param array $data
	 * @param int|null $ignoreId
	 * @throws ValidationException
	 */
	protected function validatePermissionData(array $data, ?int $ignoreId = null): void
	{
		$allRules = [
			'name' => 'required|string|max:255',
			'display_name' => 'nullable|string|max:255',
			'description' => 'nullable|string|max:1000',
			'slug' => 'nullable|string|max:255|unique:permissions,slug' . ($ignoreId ? ",{$ignoreId}" : ''),
			'url' => 'nullable|string|max:255',
			'icon_file' => 'nullable|string|max:255',
			'icon_text' => 'nullable|string|max:255',
			'is_parent' => [
				'nullable',
				'integer',
				'min:1',
				function ($attribute, $value, $fail) use ($ignoreId) {
					if ($value !== null) {
						$exists = Permission::where('id', $value)->exists();
						if (!$exists) {
							$fail('The selected parent permission does not exist.');
						}
						if ($ignoreId && $value == $ignoreId) {
							$fail('A permission cannot be its own parent.');
						}
					}
				}
			],
			'status' => 'nullable|in:1,2,15',
			'order' => 'nullable|numeric|min:0',
			'uuid' => 'nullable|uuid|unique:permissions,uuid' . ($ignoreId ? ",{$ignoreId}" : ''),
		];

		$rules = $ignoreId !== null
			? array_intersect_key($allRules, $data)
			: $allRules;

		$validator = Validator::make($data, $rules);

		if ($validator->fails()) {
			throw new ValidationException($validator);
		}
	}

	/**
	 * Get sidebar permissions filtered by user's roles and direct permissions
	 * Returns only menu items the user has access to
	 *
	 * @param \App\Models\User $user
	 * @return \Illuminate\Support\Collection
	 */
	public function getSidebarPermissionsByUser(\App\Models\User $user): \Illuminate\Support\Collection
	{
		// Get all sidebar permissions in tree structure
		$allPermissions = $this->getSidebarPermissions();

		// Get user's permission IDs (both direct and through roles)
		$userPermissionIds = $this->getUserAccessiblePermissionIds($user);

		// Filter to show ONLY permissions the user has (strictly)
		return $allPermissions->filter(function ($permission) use ($userPermissionIds) {
			return $this->hasUserPermission($permission, $userPermissionIds);
		})->map(function ($permission) use ($userPermissionIds) {
			return $this->filterToUserPermissionsOnly($permission, $userPermissionIds);
		})->values();
	}

	/**
	 * Get sidebar permissions with detailed role information for debugging
	 * Shows which permissions come from which roles
	 *
	 * @param \App\Models\User $user
	 * @return array
	 */
	public function getSidebarPermissionsByUserWithRoleInfo(\App\Models\User $user): array
	{
		$allPermissions = $this->getSidebarPermissions();

		// Get direct user permissions
		$directPermissionIds = $user->permissions()
			->where('permissions.status', '1')
			->pluck('permissions.id')
			->toArray();

		// Get permissions grouped by role
		$rolePermissions = [];
		foreach ($user->roles as $role) {
			$role->load('permissions');
			$permissionIds = $role->permissions
				->where('status', '1')
				->pluck('id')
				->toArray();
			
			if (!empty($permissionIds)) {
				$rolePermissions[$role->name] = $permissionIds;
			}
		}

		// Build combined accessible permissions
		$userPermissionIds = collect($directPermissionIds)
			->merge(collect($rolePermissions)->flatten())
			->unique();

		// Filter to show only selected permissions
		$filteredPermissions = $allPermissions->filter(function ($permission) use ($userPermissionIds) {
			return $this->hasUserPermission($permission, $userPermissionIds);
		})->map(function ($permission) use ($userPermissionIds) {
			return $this->filterToUserPermissionsOnly($permission, $userPermissionIds);
		})->values();

		return [
			'permissions' => $filteredPermissions,
			'direct_permissions' => $directPermissionIds,
			'role_permissions' => $rolePermissions,
			'total_accessible' => $userPermissionIds->count(),
		];
	}

	/**
	 * Get all permission IDs accessible to a user (through roles and direct permissions)
	 *
	 * @param \App\Models\User $user
	 * @return \Illuminate\Support\Collection
	 */
	private function getUserAccessiblePermissionIds(\App\Models\User $user): \Illuminate\Support\Collection
	{
		// Get direct user permissions
		$directPermissionIds = $user->permissions()
			->where('permissions.status', '1') // Only active permissions
			->pluck('permissions.id');

		// Get permissions through roles - improved method
		$rolePermissionIds = collect();
		
		foreach ($user->roles as $role) {
			// Get all permissions for this role
			$role->load('permissions');
			$permissions = $role->permissions
				->where('status', '1') // Only active permissions
				->pluck('id');
			
			$rolePermissionIds = $rolePermissionIds->merge($permissions);
		}

		// Merge and get unique IDs
		return $directPermissionIds->merge($rolePermissionIds)->unique();
	}

	/**
	 * Recursively filter permission tree by accessible permission IDs
	 * Keeps parent permissions if they have any accessible children
	 *
	 * @param array $permission
	 * @param \Illuminate\Support\Collection $accessibleIds
	 * @return bool
	 */
	/**
	 * Check if permission or any of its children belong to user
	 * Used for filtering at root level
	 *
	 * @param array $permission
	 * @param \Illuminate\Support\Collection $accessibleIds
	 * @return bool
	 */
	private function hasUserPermission(array $permission, \Illuminate\Support\Collection $accessibleIds): bool
	{
		// Check if this permission itself is accessible
		if ($accessibleIds->contains($permission['id'])) {
			return true;
		}

		// Check if any children are accessible
		if (!empty($permission['children'])) {
			foreach ($permission['children'] as $child) {
				if ($this->hasUserPermission($child, $accessibleIds)) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Filter permission tree to show ONLY permissions user has access to
	 * Removes all unselected permissions and parents without direct access
	 *
	 * @param array $permission
	 * @param \Illuminate\Support\Collection $accessibleIds
	 * @return array
	 */
	private function filterToUserPermissionsOnly(array $permission, \Illuminate\Support\Collection $accessibleIds): array
	{
		// Filter children first - only keep children user has access to
		if (!empty($permission['children'])) {
			$filteredChildren = [];
			foreach ($permission['children'] as $child) {
				// Recursively filter and keep only if child or its descendants are accessible
				if ($accessibleIds->contains($child['id'])) {
					// If child is accessible, include it with its filtered children
					$filteredChildren[] = $this->filterToUserPermissionsOnly($child, $accessibleIds);
				} elseif (!empty($child['children'])) {
					// If child itself isn't accessible but has children, recursively filter children
					$filteredChild = $this->filterToUserPermissionsOnly($child, $accessibleIds);
					if (!empty($filteredChild['children'])) {
						$filteredChildren[] = $filteredChild;
					}
				}
			}
			$permission['children'] = $filteredChildren;
		}

		return $permission;
	}
}
