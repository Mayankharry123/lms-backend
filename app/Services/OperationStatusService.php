<?php

/**
 * OperationStatus Service
 * -----------------------------------------
 * Handles business logic for operation status operations, including validation and data transformation.
 *
 * @package App\Services
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-03
 */

namespace App\Services;

use App\Contracts\Repositories\OperationStatusRepositoryInterface;
use App\Models\OperationStatus;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

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
     */
    public function list(array $criteria = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->operationStatusRepository->search($criteria, $perPage);
    }

    /**
     * Find operation status by id
     */
    public function find(int $id): ?OperationStatus
    {
        return $this->operationStatusRepository->find($id);
    }

    /**
     * Find operation status by uuid
     */
    public function findByUuid(string $uuid): ?OperationStatus
    {
        return $this->operationStatusRepository->findByUuid($uuid);
    }

    /**
     * Find operation status by name
     */
    public function findByName(string $name): ?OperationStatus
    {
        return $this->operationStatusRepository->findByName($name);
    }

    /**
     * Find operation status by slug
     */
    public function findBySlug(string $slug): ?OperationStatus
    {
        return $this->operationStatusRepository->findBySlug($slug);
    }

    /**
     * Create a new operation status
     *
     * @throws ValidationException
     */
    public function create(array $data): OperationStatus
    {
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
    }

    /**
     * Update an operation status
     *
     * @throws ValidationException
     */
    public function update(int $id, array $data): bool
    {
        $operationStatus = $this->operationStatusRepository->find($id);

        if (!$operationStatus) {
            throw new Exception('Operation Status not found');
        }

        $this->validateOperationStatusData($data, $id);

        if (isset($data['name']) && !isset($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        return $this->operationStatusRepository->update($id, $data);
    }

    /**
     * Soft delete an operation status (status 15)
     */
    public function delete(int $id): bool
    {
        $operationStatus = $this->operationStatusRepository->find($id);

        if (!$operationStatus) {
            throw new Exception('Operation Status not found');
        }

        return $this->operationStatusRepository->delete($id);
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
