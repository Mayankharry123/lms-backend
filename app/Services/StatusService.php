<?php

/**
 * StatusService
 * 
 * @author Achal Sharma
 * @package App\Services
 * @version 1.0.1
 */

namespace App\Services;

use App\Contracts\Repositories\StatusRepositoryInterface;
use App\Models\Status;
use DomainException;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

class StatusService
{
    /**
     * @var StatusRepositoryInterface
     */
    protected StatusRepositoryInterface $repository;

    /**
     * Create a new StatusService instance.
     *
     * @param StatusRepositoryInterface $repository
     */
    public function __construct(StatusRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    // ============================================================================
    // READ OPERATIONS
    // ============================================================================

    /**
     * Get all statuses with pagination.
     *
     * @param int $perPage
     * @param string|null $searchTerm
     * @return LengthAwarePaginator
     * @throws DomainException
     */
    public function getAllStatuses(int $perPage = 10, ?string $searchTerm = null): LengthAwarePaginator
    {
        try {
            return $this->repository->getAllStatuses($perPage, $searchTerm);
        } catch (QueryException $e) {
            Log::error('Database error fetching statuses', ['exception' => $e]);
            throw new DomainException('Database error while fetching statuses.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching statuses', ['exception' => $e]);
            throw new DomainException('Unexpected error while fetching statuses.');
        }
    }

    /**
     * Get a status by ID.
     *
     * @param int $id
     * @return Status|null
     * @throws DomainException
     */
    public function getStatus(int $id): ?Status
    {
        try {
            return $this->repository->getStatusById($id);
        } catch (QueryException $e) {
            Log::error('Database error fetching status', ['id' => $id, 'exception' => $e]);
            throw new DomainException('Database error while fetching status.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching status', ['id' => $id, 'exception' => $e]);
            throw new DomainException('Unexpected error while fetching status.');
        }
    }

    /**
     * Get a status by slug.
     *
     * @param string $slug
     * @return Status|null
     * @throws DomainException
     */
    public function getStatusBySlug(string $slug): ?Status
    {
        try {
            return $this->repository->getStatusBySlug($slug);
        } catch (QueryException $e) {
            Log::error('Database error fetching status by slug', ['slug' => $slug, 'exception' => $e]);
            throw new DomainException('Database error while fetching status by slug.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching status by slug', ['slug' => $slug, 'exception' => $e]);
            throw new DomainException('Unexpected error while fetching status by slug.');
        }
    }

    /**
     * Get a status by UUID.
     *
     * @param string $uuid
     * @return Status|null
     * @throws DomainException
     */
    public function getStatusByUuid(string $uuid): ?Status
    {
        try {
            return $this->repository->getStatusByUuid($uuid);
        } catch (QueryException $e) {
            Log::error('Database error fetching status by UUID', ['uuid' => $uuid, 'exception' => $e]);
            throw new DomainException('Database error while fetching status by UUID.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching status by UUID', ['uuid' => $uuid, 'exception' => $e]);
            throw new DomainException('Unexpected error while fetching status by UUID.');
        }
    }

    /**
     * Get all active statuses.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     * @throws DomainException
     */
    public function getActiveStatuses(int $perPage = 10): LengthAwarePaginator
    {
        try {
            return $this->repository->getActiveStatuses($perPage);
        } catch (QueryException $e) {
            Log::error('Database error fetching active statuses', ['exception' => $e]);
            throw new DomainException('Database error while fetching active statuses.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching active statuses', ['exception' => $e]);
            throw new DomainException('Unexpected error while fetching active statuses.');
        }
    }

    // ============================================================================
    // WRITE OPERATIONS
    // ============================================================================

    /**
     * Create a new status.
     *
     * @param array $data
     * @return Status
     * @throws DomainException
     */
    public function createStatus(array $data): Status
    {
        try {
            if (empty($data['name'])) {
                throw new DomainException('Status name is required.');
            }

            if (empty($data['slug'])) {
                throw new DomainException('Status slug is required.');
            }

            return $this->repository->createStatus($data);
        } catch (DomainException $e) {
            throw $e;
        } catch (QueryException $e) {
            Log::error('Database error creating status', ['data' => $data, 'exception' => $e]);
            throw new DomainException('Database error while creating status.');
        } catch (Exception $e) {
            Log::error('Unexpected error creating status', ['data' => $data, 'exception' => $e]);
            throw new DomainException('Unexpected error while creating status.');
        }
    }

    /**
     * Update an existing status.
     *
     * @param int $id
     * @param array $data
     * @return Status
     * @throws DomainException
     */
    public function updateStatus(int $id, array $data): Status
    {
        try {
            return $this->repository->updateStatus($id, $data);
        } catch (QueryException $e) {
            Log::error('Database error updating status', ['id' => $id, 'data' => $data, 'exception' => $e]);
            throw new DomainException('Database error while updating status.');
        } catch (Exception $e) {
            Log::error('Unexpected error updating status', ['id' => $id, 'data' => $data, 'exception' => $e]);
            throw new DomainException('Unexpected error while updating status.');
        }
    }

    /**
     * Delete a status (soft delete).
     *
     * @param int $id
     * @return bool
     * @throws DomainException
     */
    public function deleteStatus(int $id): bool
    {
        try {
            return $this->repository->deleteStatus($id);
        } catch (QueryException $e) {
            Log::error('Database error deleting status', ['id' => $id, 'exception' => $e]);
            throw new DomainException('Database error while deleting status.');
        } catch (Exception $e) {
            Log::error('Unexpected error deleting status', ['id' => $id, 'exception' => $e]);
            throw new DomainException('Unexpected error while deleting status.');
        }
    }
}
