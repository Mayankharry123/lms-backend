<?php
/**
 * PermissionController
 * 
 * @author Achal Sharma
 * @package App\Http\Controllers
 * @version 1.0.1
 */
namespace App\Http\Controllers;

use App\Models\Permission;
use App\Services\PermissionService;
use App\Services\ResponseService;
use App\Http\Resources\PermissionResource;
use App\Traits\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Throwable;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class PermissionController extends Controller
{
    use ValidatesRequests;

    /**
     * @var PermissionService
     */
    protected PermissionService $permissionService;

    /**
     * @var ResponseService
     */
    protected ResponseService $responseService;

    /**
     * Create a new PermissionController instance.
     *
     * @param PermissionService $permissionService
     * @param ResponseService $responseService
     */
    public function __construct(PermissionService $permissionService, ResponseService $responseService)
    {
        $this->permissionService = $permissionService;
        $this->responseService = $responseService;
    }

    /**
     * Display a listing of the permissions.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = (int) $request->get('per_page', 15);
            $criteria = array_filter([
                'q' => $request->input('search'),
                'name' => $request->input('name'),
                'status' => $request->input('status'),
                'is_parent' => $request->input('is_parent'),
            ], fn($value) => $value !== null);

            $permissions = $this->permissionService->list($criteria, $perPage);

            // Apply resource collection to paginated results
            $resource = PermissionResource::collection($permissions);

            return $this->responseService->paginated($resource, 'Permissions retrieved successfully');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Get list of permissions with only id and display_name
     *
     * @return JsonResponse
     */
    public function list(): JsonResponse
    {
        try {
            $permissions = $this->permissionService->list([], 10000);
            $data = $permissions->items() ? collect($permissions->items())->map(function ($permission) {
                return [
                    'id' => $permission->id,
                    'display_name' => $permission->display_name,
                ];
            }) : collect([]);
            return $this->responseService->success($data, 'Permissions list retrieved successfully');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Store a newly created permission in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $this->normalizePermissionRequest($request);

            $rules = [
                'name' => 'required|string|max:255',
                'display_name' => 'required|string|max:255',
                'description' => 'required|string|max:1000',
                'slug' => 'nullable|string|max:255|unique:permissions,slug',
                'url' => 'nullable|string|max:255',
                'icon_file' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp,svg|max:5120',
                'icon_text' => 'nullable|string|max:255',
                'is_parent' => 'nullable|integer|min:1',
                'status' => 'nullable|in:1,2,15',
                'order' => 'nullable|numeric|min:0',
            ];
            $validatedData = $this->validate($request, $rules);

            // Handle icon file upload if present
            if ($request->hasFile('icon_file')) {
                $iconUrl = $this->permissionService->uploadIcon($request->file('icon_file'));
                $validatedData['icon_file'] = $iconUrl;
            }

            $permission = $this->permissionService->create($validatedData);

            return $this->responseService->created(new PermissionResource($permission), 'Permission created successfully');
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'Validation failed');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Display the specified permission.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show($id): JsonResponse
    {
        try {
            $permission = $this->permissionService->find($id);

            if (! $permission) {
                return $this->responseService->notFound('Permission not found');
            }

            return $this->responseService->success(new PermissionResource($permission), 'Permission retrieved successfully');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Update the specified permission in storage.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $id = (int) $id;
            $this->normalizePermissionRequest($request);

            $rules = [
                'name' => 'sometimes|required|string|max:255',
                'display_name' => 'sometimes|required|string|max:255',
                'description' => 'sometimes|required|string|max:1000',
                'slug' => [
                    'sometimes',
                    'nullable',
                    'string',
                    'max:255',
                    Rule::unique('permissions', 'slug')->ignore($id),
                ],
                'url' => 'sometimes|nullable|string|max:255',
                'icon_file' => 'sometimes|nullable|image|mimes:jpg,jpeg,png,gif,webp,svg|max:5120',
                'icon_text' => 'sometimes|nullable|string|max:255',
                'is_parent' => 'sometimes|nullable|integer|min:1',
                'status' => 'sometimes|nullable|in:1,2,15',
                'order' => 'sometimes|nullable|numeric|min:0',
            ];

            $validatedData = collect($this->validate($request, $rules))
                ->except(['_method'])
                ->all();

            // Get existing permission to check for old icon file
            $existingPermission = $this->permissionService->find($id);
            if (!$existingPermission) {
                return $this->responseService->notFound('Permission not found');
            }

            // Handle icon file upload if present
            if ($request->hasFile('icon_file')) {
                // Delete old icon file if exists
                if ($existingPermission->icon_file) {
                    $this->permissionService->deleteIcon($existingPermission->icon_file);
                }
                
                // Upload new icon file
                $iconUrl = $this->permissionService->uploadIcon($request->file('icon_file'));
                $validatedData['icon_file'] = $iconUrl;
            }

            $updated = $this->permissionService->update($id, $validatedData);

            if (! $updated) {
                return $this->responseService->notFound('Permission not found');
            }

            $permission = $this->permissionService->find($id);
            return $this->responseService->updated(new PermissionResource($permission), 'Permission updated successfully');
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'Validation failed');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Remove the specified permission from storage.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        try {
            // Get permission before deletion to check for icon file
            $permission = $this->permissionService->find($id);
            
            if (! $permission) {
                return $this->responseService->notFound('Permission not found');
            }

            // Delete icon file if exists
            if ($permission->icon_file) {
                $this->permissionService->deleteIcon($permission->icon_file);
            }

            $deleted = $this->permissionService->delete($id);

            if (! $deleted) {
                return $this->responseService->notFound('Permission not found');
            }

            return $this->responseService->deleted('Permission deleted successfully');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Get permissions by UUID.
     *
     * @param string $uuid
     * @return JsonResponse
     */
    public function showByUuid(string $uuid): JsonResponse
    {
        try {
            $permission = $this->permissionService->findByUuid($uuid);

            if (! $permission) {
                return $this->responseService->notFound('Permission not found');
            }

            return $this->responseService->success(new PermissionResource($permission), 'Permission retrieved successfully');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Get active permissions.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function active(Request $request): JsonResponse
    {
        try {
            $perPage = (int) $request->get('per_page', 10);
            $permissions = $this->permissionService->getActive($perPage);

            if ($permissions instanceof \Illuminate\Pagination\LengthAwarePaginator || $permissions instanceof \Illuminate\Pagination\Paginator) {
                $permissions->getCollection()->transform(function ($permission) {
                    return new PermissionResource($permission);
                });
            } elseif ($permissions instanceof \Illuminate\Support\Collection) {
                $permissions->transform(function ($permission) {
                    return new PermissionResource($permission);
                });
            } else {
                $permissions = collect($permissions)->map(function ($permission) {
                    return new PermissionResource($permission);
                });
            }

            return $this->responseService->paginated($permissions, 'Active permissions retrieved successfully');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Get permissions by parent status.
     *
     * @param Request $request
     * @param bool $isParent
     * @return JsonResponse
     */
    public function byParentStatus(Request $request, bool $isParent): JsonResponse
    {
        try {
            $perPage = (int) $request->get('per_page', 10);
            $permissions = $this->permissionService->getByParentStatus($isParent, $perPage);

            if ($permissions instanceof \Illuminate\Pagination\LengthAwarePaginator || $permissions instanceof \Illuminate\Pagination\Paginator) {
                $permissions->getCollection()->transform(function ($permission) {
                    return new PermissionResource($permission);
                });
            } elseif ($permissions instanceof \Illuminate\Support\Collection) {
                $permissions->transform(function ($permission) {
                    return new PermissionResource($permission);
                });
            } else {
                $permissions = collect($permissions)->map(function ($permission) {
                    return new PermissionResource($permission);
                });
            }

            $message = $isParent 
                ? 'Parent permissions retrieved successfully' 
                : 'Child permissions retrieved successfully';

            return $this->responseService->paginated($permissions, $message);
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Get parent permissions.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function parents(Request $request): JsonResponse
    {
        return $this->byParentStatus($request, true);
    }

    /**
     * Get child permissions.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function children(Request $request): JsonResponse
    {
        return $this->byParentStatus($request, false);
    }

    /**
     * Sync roles for a permission.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function syncRoles(Request $request, $id): JsonResponse
    {
        try {
            $rules = [
                'role_ids' => 'required|array',
                'role_ids.*' => 'required|integer|exists:roles,id',
            ];

            $validatedData = $this->validate($request, $rules);

            $synced = $this->permissionService->syncRoles($id, $validatedData['role_ids']);

            if (! $synced) {
                return $this->responseService->notFound('Permission not found');
            }

            $permission = $this->permissionService->find($id);
            return $this->responseService->success(
                new PermissionResource($permission->load('roles')), 
                'Roles synced successfully'
            );
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'Validation failed');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Attach a role to a permission.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function attachRole(Request $request, $id): JsonResponse
    {
        try {
            $rules = [
                'role_id' => 'required|integer|exists:roles,id',
            ];

            $validatedData = $this->validate($request, $rules);

            $attached = $this->permissionService->attachRole($id, $validatedData['role_id']);

            if (! $attached) {
                return $this->responseService->notFound('Permission or role not found');
            }

            $permission = $this->permissionService->find($id);
            return $this->responseService->success(
                new PermissionResource($permission->load('roles')), 
                'Role attached successfully'
            );
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'Validation failed');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Detach a role from a permission.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function detachRole(Request $request, $id): JsonResponse
    {
        try {
            $rules = [
                'role_id' => 'required|integer|exists:roles,id',
            ];

            $validatedData = $this->validate($request, $rules);

            $detached = $this->permissionService->detachRole($id, $validatedData['role_id']);

            if (! $detached) {
                return $this->responseService->notFound('Permission not found');
            }

            $permission = $this->permissionService->find($id);
            return $this->responseService->success(
                new PermissionResource($permission->load('roles')), 
                'Role detached successfully'
            );
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'Validation failed');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Get all permissions as tree with id, display_name, and is_parent.
     *
     * @return JsonResponse
     */
    public function allPermissionTree(): JsonResponse
    {
        try {
            $permissions = $this->permissionService->getAllPermissionTree();
            $resource = PermissionResource::collection($permissions);

            return $this->responseService->success($resource, 'Permission tree retrieved successfully');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Get sidebar permissions in hierarchical format
     * Returns parent -> children structure for sidebar navigation
     * Filters permissions based on logged-in user's roles and assigned permissions
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function sidebar(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return $this->responseService->error('Unauthorized', statusCode: 401);
            }
            
            $permissions = $this->permissionService->getSidebarPermissionsByUser($user);
            return $this->responseService->success($permissions, 'Sidebar permissions retrieved successfully');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Normalize empty string values in request input to null for optional permission fields.
     *
     * @param Request $request
     * @return void
     */
    protected function normalizePermissionRequest(Request $request): void
    {
        $input = $request->all();

        foreach (['url', 'icon_text', 'description', 'slug'] as $field) {
            if (array_key_exists($field, $input) && $input[$field] === '') {
                $input[$field] = null;
            }
        }

        if (array_key_exists('is_parent', $input)) {
            $isParent = $input['is_parent'];
            if ($isParent === '' || $isParent === '0' || $isParent === 0 || $isParent === null) {
                $input['is_parent'] = null;
            }
        }

        if (array_key_exists('_method', $input)) {
            unset($input['_method']);
        }

        $request->replace($input);
    }
}