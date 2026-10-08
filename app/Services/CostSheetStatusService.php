<?php

/**
 * CostSheetStatus Service
 * -----------------------------------------
 * Handles business logic for cost sheet status operations, including validation and data transformation.
 *
 * @package App\Services
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-05
 */

namespace App\Services;

use App\Contracts\Repositories\CostSheetStatusRepositoryInterface;
use App\Models\CostSheetStatus;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class CostSheetStatusService
{
    protected CostSheetStatusRepositoryInterface $costSheetStatusRepository;

    /**
     * Inject the cost sheet status repository.
     */
    public function __construct(CostSheetStatusRepositoryInterface $costSheetStatusRepository)
    {
        $this->costSheetStatusRepository = $costSheetStatusRepository;
    }

    /**
     * Get paginated cost sheet statuses.
     *
     * @throws Throwable
     */
    public function list(array $criteria = [], int $perPage = 15): LengthAwarePaginator
    {
        try {
            return $this->costSheetStatusRepository->search($criteria, $perPage);
        } catch (Throwable $e) {
            Log::error('Error fetching cost sheet statuses', ['criteria' => $criteria, 'exception' => $e]);
            throw $e;
        }
    }

    /**
     * Find cost sheet status by ID.
     *
     * @throws Throwable
     */
    public function find(int $id): ?CostSheetStatus
    {
        try {
            return $this->costSheetStatusRepository->find($id);
        } catch (Throwable $e) {
            Log::error('Error fetching cost sheet status by ID', ['id' => $id, 'exception' => $e]);
            throw $e;
        }
    }

    /**
     * Find cost sheet status by UUID.
     *
     * @throws Throwable
     */
    public function findByUuid(string $uuid): ?CostSheetStatus
    {
        try {
            return $this->costSheetStatusRepository->findByUuid($uuid);
        } catch (Throwable $e) {
            Log::error('Error fetching cost sheet status by UUID', ['uuid' => $uuid, 'exception' => $e]);
            throw $e;
        }
    }

    /**
     * Find cost sheet status by name.
     *
     * @throws Throwable
     */
    public function findByName(string $name): ?CostSheetStatus
    {
        try {
            return $this->costSheetStatusRepository->findByName($name);
        } catch (Throwable $e) {
            Log::error('Error fetching cost sheet status by name', ['name' => $name, 'exception' => $e]);
            throw $e;
        }
    }

    /**
     * Find cost sheet status by slug.
     *
     * @throws Throwable
     */
    public function findBySlug(string $slug): ?CostSheetStatus
    {
        try {
            return $this->costSheetStatusRepository->findBySlug($slug);
        } catch (Throwable $e) {
            Log::error('Error fetching cost sheet status by slug', ['slug' => $slug, 'exception' => $e]);
            throw $e;
        }
    }

    /**
     * Create a new cost sheet status.
     *
     * @throws ValidationException
     * @throws Throwable
     */
    public function create(array $data): CostSheetStatus
    {
        try {
            $this->validateCostSheetStatusData($data);

            if (!isset($data['uuid'])) {
                $data['uuid'] = (string) Str::uuid();
            }

            if (!isset($data['slug']) || $data['slug'] === '') {
                $data['slug'] = Str::slug($data['name']);
            }

            if (!isset($data['status'])) {
                $data['status'] = '1';
            }

            return $this->costSheetStatusRepository->create($data);
        } catch (Throwable $e) {
            if (!$e instanceof ValidationException) {
                Log::error('Error creating cost sheet status', ['data' => $data, 'exception' => $e]);
            }
            throw $e;
        }
    }

    /**
     * Update a cost sheet status.
     *
     * @throws ValidationException
     * @throws Throwable
     */
    public function update(int $id, array $data): bool
    {
        try {
            $costSheetStatus = $this->costSheetStatusRepository->find($id);

            if (!$costSheetStatus) {
                throw new Exception('Cost Sheet Status not found');
            }

            $this->validateCostSheetStatusData($data, $id);

            if (isset($data['name']) && !isset($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            return $this->costSheetStatusRepository->update($id, $data);
        } catch (Throwable $e) {
            if (!$e instanceof ValidationException) {
                Log::error('Error updating cost sheet status', ['id' => $id, 'data' => $data, 'exception' => $e]);
            }
            throw $e;
        }
    }

    /**
     * Soft delete a cost sheet status (status 15).
     *
     * @throws Throwable
     */
    public function delete(int $id): bool
    {
        try {
            $costSheetStatus = $this->costSheetStatusRepository->find($id);

            if (!$costSheetStatus) {
                throw new Exception('Cost Sheet Status not found');
            }

            return $this->costSheetStatusRepository->delete($id);
        } catch (Throwable $e) {
            Log::error('Error deleting cost sheet status', ['id' => $id, 'exception' => $e]);
            throw $e;
        }
    }

    /**
     * Validate cost sheet status create and update data.
     *
     * @throws ValidationException
     */
    protected function validateCostSheetStatusData(array $data, ?int $id = null): void
    {
        $rules = $id
            ? [
                'name' => 'sometimes|required|string|max:255|unique:cost_sheet_statuses,name,' . $id,
                'slug' => 'sometimes|nullable|string|max:255|unique:cost_sheet_statuses,slug,' . $id,
                'status' => 'sometimes|nullable|in:1,2,15',
            ]
            : [
                'name' => 'required|string|max:255|unique:cost_sheet_statuses,name',
                'slug' => 'nullable|string|max:255|unique:cost_sheet_statuses,slug',
                'status' => 'nullable|in:1,2,15',
            ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
}
