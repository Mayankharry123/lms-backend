<?php
/**
 * DepartmentController
 * 
 * @author Achal Sharma
 * @package App\Http\Controllers
 * @version 1.0.1
 */
namespace App\Http\Controllers;

use App\Services\DepartmentService;
use App\Services\ResponseService;
use App\Http\Resources\DepartmentResource;
use App\Traits\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;
use DomainException;
use Exception;
use Throwable;

class DepartmentController extends Controller
{
    use ValidatesRequests;

    /**
     * @var DepartmentService
     */
    protected $departmentService;

    /**
     * @var ResponseService
     */
    protected $responseService;

    /**
     * Create a new DepartmentController instance.
     *
     * @param DepartmentService $departmentService
     * @param ResponseService $responseService
     */
    public function __construct(DepartmentService $departmentService, ResponseService $responseService)
    {
        $this->departmentService = $departmentService;
        $this->responseService = $responseService;
    }

    /**
     * Get all departments
     */
    public function index(Request $request) // <-- Request object added
    {
        try {
            // Get per_page parameter from request, default to 10
            $perPage = $request->get('per_page', 10);
            $searchTerm = $request->get('search', null);

            // Pass perPage to the Service layer
            $departments = $this->departmentService->getAllDepartments((int) $perPage,$searchTerm);
            
            // Check if any records exist (handling paginator object)
            if ($departments->isEmpty() && !($departments instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator && $departments->total() > 0)) {
                return $this->responseService->success([], 'No departments found.');
            }

            // Use standard Resource Collection wrapping (which preserves pagination meta)
            return $this->responseService->paginated(
                DepartmentResource::collection($departments),
                'Departments fetched successfully.'
            );
        } catch (DomainException $e) {
            return $this->responseService->error($e->getMessage(), null, 500, 'DOMAIN_ERROR');
        } catch (QueryException $e) {
            return $this->responseService->error('Database error: ' . $e->getMessage(), null, 500, 'DB_ERROR');
        } catch (Exception $e) {
            return $this->responseService->serverError('Unexpected error while fetching departments.', $e->getMessage());
        }
    }

    /**
     * Get list of departments with only id and name (e.g., /api/v1/departments/list)
     */
    public function list(): JsonResponse
    {
        try {
            $departments = $this->departmentService->getAllDepartments(perPage: 10000);
            $data = $departments->items() ? collect($departments->items())->map(function ($department) {
                return [
                    'id' => $department->id,
                    'name' => $department->name,
                ];
            }) : collect([]);
            return $this->responseService->success($data, 'Departments list retrieved');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Create a new department
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $rules = [
                'name' => 'required|string|max:255|unique:departments,name',
                'description' => 'nullable|string',
                'status' => 'nullable|in:1,2,15',
            ];

            $validatedData = $this->validate($request, $rules);

            // Always create new department (unique name ensures no duplicates)
            $department = $this->departmentService->createNewDepartment($validatedData);

            return $this->responseService->created(
                new DepartmentResource($department),
                'New department created successfully.'
            );

        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'Validation failed');
        } catch (DomainException $e) {
            return $this->responseService->error($e->getMessage(), null, 400, 'DOMAIN_ERROR');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Get a single department
     */
    public function show($id)
    {
        try {
            $department = $this->departmentService->getDepartment($id);
            return $this->responseService->success(new DepartmentResource($department), 'Department fetched successfully.');
        } catch (DomainException $e) {
            return $this->responseService->notFound($e->getMessage());
        } catch (QueryException $e) {
            return $this->responseService->error('Database error: ' . $e->getMessage(), null, 500, 'DB_ERROR');
        } catch (Exception $e) {
            return $this->responseService->serverError('Unexpected error while fetching department.', $e->getMessage());
        }
    }

    /**
     * Update an existing department
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $rules = [
                'name' => 'sometimes|required|string|max:255|unique:departments,name,' . $id,
                'description' => 'nullable|string',
                'status' => 'sometimes|required|in:1,2,15',
            ];

            $validatedData = $this->validate($request, $rules);

            $department = $this->departmentService->updateDepartment($id, $validatedData);
            return $this->responseService->updated(new DepartmentResource($department), 'Department updated successfully.');
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'Validation failed');
        } catch (DomainException $e) {
            return $this->responseService->notFound($e->getMessage());
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Delete a department
     */
    public function destroy($id)
    {
        try {
            $this->departmentService->deleteDepartment($id);
            return $this->responseService->deleted('Department deleted successfully.');
        } catch (DomainException $e) {
            return $this->responseService->notFound($e->getMessage());
        } catch (QueryException $e) {
            return $this->responseService->error('Database error: ' . $e->getMessage(), null, 500, 'DB_ERROR');
        } catch (Exception $e) {
            return $this->responseService->serverError('Unexpected error while deleting department.', $e->getMessage());
        }
    }
}
