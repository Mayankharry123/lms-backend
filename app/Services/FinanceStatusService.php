<?php

/**
 * FinanceStatus Service
 * -----------------------------------------
 * Handles business logic for finance status operations, including validation and data transformation.
 *
 * @package App\Services
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-04
 */

namespace App\Services;

use App\Contracts\Repositories\FinanceStatusRepositoryInterface;
use App\Models\FinanceStatus;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FinanceStatusService
{
    protected FinanceStatusRepositoryInterface $financeStatusRepository;

    /**
     * Inject the finance status repository.
     */
    public function __construct(FinanceStatusRepositoryInterface $financeStatusRepository)
    {
        $this->financeStatusRepository = $financeStatusRepository;
    }

    /**
     * Get paginated finance statuses
     *
     * @throws Throwable
     */
    public function list(array $criteria = [], int $perPage = 15): LengthAwarePaginator
    {
        try {
            return $this->financeStatusRepository->search($criteria, $perPage);
        } catch (Throwable $e) {
            Log::error('Error fetching finance statuses', ['criteria' => $criteria, 'exception' => $e]);
            throw $e;
        }
    }

    /**
     * Find finance status by id
     *
     * @throws Throwable
     */
    public function find(int $id): ?FinanceStatus
    {
        try {
            return $this->financeStatusRepository->find($id);
        } catch (Throwable $e) {
            Log::error('Error fetching finance status by ID', ['id' => $id, 'exception' => $e]);
            throw $e;
        }
    }

    /**
     * Find finance status by uuid
     *
     * @throws Throwable
     */
    public function findByUuid(string $uuid): ?FinanceStatus
    {
        try {
            return $this->financeStatusRepository->findByUuid($uuid);
        } catch (Throwable $e) {
            Log::error('Error fetching finance status by UUID', ['uuid' => $uuid, 'exception' => $e]);
            throw $e;
        }
    }

    /**
     * Find finance status by name
     *
     * @throws Throwable
     */
    public function findByName(string $name): ?FinanceStatus
    {
        try {
            return $this->financeStatusRepository->findByName($name);
        } catch (Throwable $e) {
            Log::error('Error fetching finance status by name', ['name' => $name, 'exception' => $e]);
            throw $e;
        }
    }

    /**
     * Find finance status by slug
     *
     * @throws Throwable
     */
    public function findBySlug(string $slug): ?FinanceStatus
    {
        try {
            return $this->financeStatusRepository->findBySlug($slug);
        } catch (Throwable $e) {
            Log::error('Error fetching finance status by slug', ['slug' => $slug, 'exception' => $e]);
            throw $e;
        }
    }

    /**
     * Create a new finance status
     *
     * @throws ValidationException
     * @throws Throwable
     */
    public function create(array $data): FinanceStatus
    {
        try {
            $this->validateFinanceStatusData($data);

            if (!isset($data['uuid'])) {
                $data['uuid'] = (string) Str::uuid();
            }

            if (!isset($data['slug']) || $data['slug'] === '') {
                $data['slug'] = Str::slug($data['name']);
            }

            if (!isset($data['status'])) {
                $data['status'] = '1';
            }

            return $this->financeStatusRepository->create($data);
        } catch (Throwable $e) {
            if (!$e instanceof ValidationException) {
                Log::error('Error creating finance status', ['data' => $data, 'exception' => $e]);
            }
            throw $e;
        }
    }

    /**
     * Update a finance status
     *
     * @throws ValidationException
     * @throws Throwable
     */
    public function update(int $id, array $data): bool
    {
        try {
            $financeStatus = $this->financeStatusRepository->find($id);

            if (!$financeStatus) {
                throw new Exception('Finance Status not found');
            }

            $this->validateFinanceStatusData($data, $id);

            if (isset($data['name']) && !isset($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            return $this->financeStatusRepository->update($id, $data);
        } catch (Throwable $e) {
            if (!$e instanceof ValidationException) {
                Log::error('Error updating finance status', ['id' => $id, 'data' => $data, 'exception' => $e]);
            }
            throw $e;
        }
    }

    /**
     * Soft delete a finance status (status 15)
     *
     * @throws Throwable
     */
    public function delete(int $id): bool
    {
        try {
            $financeStatus = $this->financeStatusRepository->find($id);

            if (!$financeStatus) {
                throw new Exception('Finance Status not found');
            }

            return $this->financeStatusRepository->delete($id);
        } catch (Throwable $e) {
            Log::error('Error deleting finance status', ['id' => $id, 'exception' => $e]);
            throw $e;
        }
    }

    /**
     * Validate finance status create and update data
     *
     * @throws ValidationException
     */
    protected function validateFinanceStatusData(array $data, ?int $id = null): void
    {
        $rules = $id
            ? [
                'name' => 'sometimes|required|string|max:255|unique:finance_statuses,name,' . $id,
                'slug' => 'sometimes|nullable|string|max:255|unique:finance_statuses,slug,' . $id,
                'status' => 'sometimes|nullable|in:1,2,15',
            ]
            : [
                'name' => 'required|string|max:255|unique:finance_statuses,name',
                'slug' => 'nullable|string|max:255|unique:finance_statuses,slug',
                'status' => 'nullable|in:1,2,15',
            ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
}
