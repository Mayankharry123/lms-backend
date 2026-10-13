<?php

/**
 * CallStatusService
 * 
 * @author Achal Sharma
 * @package App\Services
 * @version 1.0.1
 */

namespace App\Services;

use App\Contracts\Repositories\CallStatusRepositoryInterface;
use App\Models\CallStatus;
use DomainException;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

class CallStatusService
{
    /**
     * @var CallStatusRepositoryInterface
     */
    protected CallStatusRepositoryInterface $repository;

    /**
     * Create a new CallStatusService instance.
     *
     * @param CallStatusRepositoryInterface $repository
     */
    public function __construct(CallStatusRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    // ============================================================================
    // READ OPERATIONS
    // ============================================================================

    /**
     * Get all call statuses with pagination.
     *
     * @param int $perPage
     * @param string|null $searchTerm
     * @return LengthAwarePaginator
     * @throws DomainException
     */
    public function getAllCallStatuses(int $perPage = 10, ?string $searchTerm = null): LengthAwarePaginator
    {
        try {
            return $this->repository->getAllCallStatuses($perPage, $searchTerm);
        } catch (QueryException $e) {
            Log::error('Database error fetching call statuses', ['exception' => $e]);
            throw new DomainException('Database error while fetching call statuses.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching call statuses', ['exception' => $e]);
            throw new DomainException('Unexpected error while fetching call statuses.');
        }
    }

    /**
     * Get a call status by ID.
     *
     * @param int $id
     * @return CallStatus|null
     * @throws DomainException
     */
    public function getCallStatus(int $id): ?CallStatus
    {
        try {
            return $this->repository->getCallStatusById($id);
        } catch (QueryException $e) {
            Log::error('Database error fetching call status', ['id' => $id, 'exception' => $e]);
            throw new DomainException('Database error while fetching call status.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching call status', ['id' => $id, 'exception' => $e]);
            throw new DomainException('Unexpected error while fetching call status.');
        }
    }

    /**
     * Get a call status by slug.
     *
     * @param string $slug
     * @return CallStatus|null
     * @throws DomainException
     */
    public function getCallStatusBySlug(string $slug): ?CallStatus
    {
        try {
            return $this->repository->getCallStatusBySlug($slug);
        } catch (QueryException $e) {
            Log::error('Database error fetching call status by slug', ['slug' => $slug, 'exception' => $e]);
            throw new DomainException('Database error while fetching call status by slug.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching call status by slug', ['slug' => $slug, 'exception' => $e]);
            throw new DomainException('Unexpected error while fetching call status by slug.');
        }
    }

    /**
     * Get a call status by UUID.
     *
     * @param string $uuid
     * @return CallStatus|null
     * @throws DomainException
     */
    public function getCallStatusByUuid(string $uuid): ?CallStatus
    {
        try {
            return $this->repository->getCallStatusByUuid($uuid);
        } catch (QueryException $e) {
            Log::error('Database error fetching call status by UUID', ['uuid' => $uuid, 'exception' => $e]);
            throw new DomainException('Database error while fetching call status by UUID.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching call status by UUID', ['uuid' => $uuid, 'exception' => $e]);
            throw new DomainException('Unexpected error while fetching call status by UUID.');
        }
    }

    /**
     * Get all active call statuses.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     * @throws DomainException
     */
    public function getActiveCallStatuses(int $perPage = 10): LengthAwarePaginator
    {
        try {
            return $this->repository->getActiveCallStatuses($perPage);
        } catch (QueryException $e) {
            Log::error('Database error fetching active call statuses', ['exception' => $e]);
            throw new DomainException('Database error while fetching active call statuses.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching active call statuses', ['exception' => $e]);
            throw new DomainException('Unexpected error while fetching active call statuses.');
        }
    }

    // ============================================================================
    // WRITE OPERATIONS
    // ============================================================================

    /**
     * Create a new call status.
     *
     * @param array $data
     * @return CallStatus
     * @throws DomainException
     */
    public function createCallStatus(array $data): CallStatus
    {
        try {
            if (empty($data['name'])) {
                throw new DomainException('Call status name is required.');
            }

            if (empty($data['slug'])) {
                throw new DomainException('Call status slug is required.');
            }

            return $this->repository->createCallStatus($data);
        } catch (DomainException $e) {
            throw $e;
        } catch (QueryException $e) {
            Log::error('Database error creating call status', ['data' => $data, 'exception' => $e]);
            throw new DomainException('Database error while creating call status.');
        } catch (Exception $e) {
            Log::error('Unexpected error creating call status', ['data' => $data, 'exception' => $e]);
            throw new DomainException('Unexpected error while creating call status.');
        }
    }

    /**
     * Update an existing call status.
     *
     * @param int $id
     * @param array $data
     * @return CallStatus
     * @throws DomainException
     */
    public function updateCallStatus(int $id, array $data): CallStatus
    {
        try {
            return $this->repository->updateCallStatus($id, $data);
        } catch (QueryException $e) {
            Log::error('Database error updating call status', ['id' => $id, 'data' => $data, 'exception' => $e]);
            throw new DomainException('Database error while updating call status.');
        } catch (Exception $e) {
            Log::error('Unexpected error updating call status', ['id' => $id, 'data' => $data, 'exception' => $e]);
            throw new DomainException('Unexpected error while updating call status.');
        }
    }

    /**
     * Delete a call status (soft delete).
     *
     * @param int $id
     * @return bool
     * @throws DomainException
     */
    public function deleteCallStatus(int $id): bool
    {
        try {
            return $this->repository->deleteCallStatus($id);
        } catch (QueryException $e) {
            Log::error('Database error deleting call status', ['id' => $id, 'exception' => $e]);
            throw new DomainException('Database error while deleting call status.');
        } catch (Exception $e) {
            Log::error('Unexpected error deleting call status', ['id' => $id, 'exception' => $e]);
            throw new DomainException('Unexpected error while deleting call status.');
        }
    }
}
