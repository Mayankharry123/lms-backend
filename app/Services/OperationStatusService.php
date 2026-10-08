<?php

/**
 * OperationStatus Service
 * -----------------------------------------
 * Handles business logic for operation status operations, including validation and data transformation.
 *
 * @package App\Services
 * @version 1.0.0
 * @since 2026-10-03
 */

namespace App\Services;

use App\Contracts\Repositories\OperationStatusRepositoryInterface;
use App\Models\OperationStatus;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class OperationStatusService
{
    protected OperationStatusRepositoryInterface $operationStatusRepository;
    protected ResponseService $responseService;

    public function __construct(
        OperationStatusRepositoryInterface $operationStatusRepository,
        ResponseService $responseService
    ) {
        $this->operationStatusRepository = $operationStatusRepository;
        $this->responseService = $responseService;
    }

    /**
     * Get paginated operation statuses
     *
     * @throws Throwable
     */
    public function list(array $criteria = [], int $perPage = 15): LengthAwarePaginator
    {
        try {
            return $this->operationStatusRepository->search($criteria, $perPage);
        } catch (Throwable $e) {
            Log::error('Error fetching operation statuses', ['criteria' => $criteria, 'exception' => $e]);
            throw $e;
        }
    }

    /**
     * Find operation status by id
     *
     * @throws Throwable
     */
    public function find(int $id): ?OperationStatus
    {
        try {
            return $this->operationStatusRepository->find($id);
        } catch (Throwable $e) {
            Log::error('Error fetching operation status by ID', ['id' => $id, 'exception' => $e]);
            throw $e;
        }
    }

    /**
     * Find operation status by uuid
     *
     * @throws Throwable
     */
    public function findByUuid(string $uuid): ?OperationStatus
    {
        try {
            return $this->operationStatusRepository->findByUuid($uuid);
        } catch (Throwable $e) {
            Log::error('Error fetching operation status by UUID', ['uuid' => $uuid, 'exception' => $e]);
            throw $e;
        }
    }

    /**
     * Find operation status by name
     *
     * @throws Throwable
     */
    public function findByName(string $name): ?OperationStatus
    {
        try {
            return $this->operationStatusRepository->findByName($name);
        } catch (Throwable $e) {
            Log::error('Error fetching operation status by name', ['name' => $name, 'exception' => $e]);
            throw $e;
        }
    }

    /**
     * Find operation status by slug
     *
     * @throws Throwable
     */
    public function findBySlug(string $slug): ?OperationStatus
    {
        try {
            return $this->operationStatusRepository->findBySlug($slug);
        } catch (Throwable $e) {
            Log::error('Error fetching operation status by slug', ['slug' => $slug, 'exception' => $e]);
            throw $e;
        }
    }

    /**
     * Create a new operation status
     *
     * @throws ValidationException
     * @throws Throwable
     */
    public function create(array $data): OperationStatus
    {
        try {
            $this->validateOperationStatusData($data);

            if (!isset($data['uuid'])) {
                $data['uuid'] = (string) Str::uuid();
            }

            if (!isset($data['slug']) || $data['slug'] === '') {
                $data['slug'] = Str::slug($data['name']);
            }

            if (!isset($data['status'])) {
                $data['status'] = '1';
            }

            return $this->operationStatusRepository->create($data);
        } catch (Throwable $e) {
            if (!$e instanceof ValidationException) {
                Log::error('Error creating operation status', ['data' => $data, 'exception' => $e]);
            }
            throw $e;
        }
    }

    /**
     * Update an operation status
     *
     * @throws ValidationException
     * @throws Throwable
     */
    public function update(int $id, array $data): bool
    {
        try {
            $operationStatus = $this->operationStatusRepository->find($id);

            if (!$operationStatus) {
                throw new Exception('Operation Status not found');
            }

            $this->validateOperationStatusData($data, $id);

            if (isset($data['name']) && !isset($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            return $this->operationStatusRepository->update($id, $data);
        } catch (Throwable $e) {
            if (!$e instanceof ValidationException) {
                Log::error('Error updating operation status', ['id' => $id, 'data' => $data, 'exception' => $e]);
            }
            throw $e;
        }
    }

    /**
     * Soft delete an operation status (status 15)
     *
     * @throws Throwable
     */
    public function delete(int $id): bool
    {
        try {
            $operationStatus = $this->operationStatusRepository->find($id);

            if (!$operationStatus) {
                throw new Exception('Operation Status not found');
            }

            return $this->operationStatusRepository->delete($id);
        } catch (Throwable $e) {
            Log::error('Error deleting operation status', ['id' => $id, 'exception' => $e]);
            throw $e;
        }
    }

    /**
     * @throws ValidationException
     */
    protected function validateOperationStatusData(array $data, ?int $id = null): void
    {
        $rules = $id
            ? [
                'name' => 'sometimes|required|string|max:255|unique:operation_statuses,name,' . $id,
                'slug' => 'sometimes|nullable|string|max:255|unique:operation_statuses,slug,' . $id,
                'status' => 'sometimes|nullable|in:1,2,15',
            ]
            : [
                'name' => 'required|string|max:255|unique:operation_statuses,name',
                'slug' => 'nullable|string|max:255|unique:operation_statuses,slug',
                'status' => 'nullable|in:1,2,15',
            ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
}
