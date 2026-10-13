<?php

/**
 * PriorityService
 * 
 * @author Achal Sharma
 * @package App\Services
 * @version 1.0.1
 */

namespace App\Services;

use App\Contracts\Repositories\PriorityRepositoryInterface;
use App\Models\Priority;
use DomainException;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

class PriorityService
{
    /**
     * @var PriorityRepositoryInterface
     */
    protected PriorityRepositoryInterface $repository;

    /**
     * Create a new PriorityService instance.
     *
     * @param PriorityRepositoryInterface $repository
     */
    public function __construct(PriorityRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    // ============================================================================
    // READ OPERATIONS
    // ============================================================================

    /**
     * Get all priorities with pagination.
     *
     * @param int $perPage
     * @param string|null $searchTerm
     * @return LengthAwarePaginator
     * @throws DomainException
     */
    public function getAllPriorities(int $perPage = 10, ?string $searchTerm = null): LengthAwarePaginator
    {
        try {
            return $this->repository->getAllPriorities($perPage, $searchTerm);
        } catch (QueryException $e) {
            Log::error('Database error fetching priorities', ['exception' => $e]);
            throw new DomainException('Database error while fetching priorities.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching priorities', ['exception' => $e]);
            throw new DomainException('Unexpected error while fetching priorities.');
        }
    }

    /**
     * Get a priority by ID.
     *
     * @param int $id
     * @return Priority|null
     * @throws DomainException
     */
    public function getPriority(int $id): ?Priority
    {
        try {
            return $this->repository->getPriorityById($id);
        } catch (QueryException $e) {
            Log::error('Database error fetching priority', ['id' => $id, 'exception' => $e]);
            throw new DomainException('Database error while fetching priority.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching priority', ['id' => $id, 'exception' => $e]);
            throw new DomainException('Unexpected error while fetching priority.');
        }
    }

    /**
     * Get a priority by slug.
     *
     * @param string $slug
     * @return Priority|null
     * @throws DomainException
     */
    public function getPriorityBySlug(string $slug): ?Priority
    {
        try {
            return $this->repository->getPriorityBySlug($slug);
        } catch (QueryException $e) {
            Log::error('Database error fetching priority by slug', ['slug' => $slug, 'exception' => $e]);
            throw new DomainException('Database error while fetching priority by slug.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching priority by slug', ['slug' => $slug, 'exception' => $e]);
            throw new DomainException('Unexpected error while fetching priority by slug.');
        }
    }

    /**
     * Get a priority by UUID.
     *
     * @param string $uuid
     * @return Priority|null
     * @throws DomainException
     */
    public function getPriorityByUuid(string $uuid): ?Priority
    {
        try {
            return $this->repository->getPriorityByUuid($uuid);
        } catch (QueryException $e) {
            Log::error('Database error fetching priority by UUID', ['uuid' => $uuid, 'exception' => $e]);
            throw new DomainException('Database error while fetching priority by UUID.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching priority by UUID', ['uuid' => $uuid, 'exception' => $e]);
            throw new DomainException('Unexpected error while fetching priority by UUID.');
        }
    }

    /**
     * Get all active priorities.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     * @throws DomainException
     */
    public function getActivePriorities(int $perPage = 10): LengthAwarePaginator
    {
        try {
            return $this->repository->getActivePriorities($perPage);
        } catch (QueryException $e) {
            Log::error('Database error fetching active priorities', ['exception' => $e]);
            throw new DomainException('Database error while fetching active priorities.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching active priorities', ['exception' => $e]);
            throw new DomainException('Unexpected error while fetching active priorities.');
        }
    }

    // ============================================================================
    // WRITE OPERATIONS
    // ============================================================================

    /**
     * Create a new priority.
     *
     * @param array $data
     * @return Priority
     * @throws DomainException
     */
    public function createPriority(array $data): Priority
    {
        try {
            if (empty($data['name'])) {
                throw new DomainException('Priority name is required.');
            }

            if (empty($data['slug'])) {
                throw new DomainException('Priority slug is required.');
            }

            return $this->repository->createPriority($data);
        } catch (DomainException $e) {
            throw $e;
        } catch (QueryException $e) {
            Log::error('Database error creating priority', ['data' => $data, 'exception' => $e]);
            throw new DomainException('Database error while creating priority.');
        } catch (Exception $e) {
            Log::error('Unexpected error creating priority', ['data' => $data, 'exception' => $e]);
            throw new DomainException('Unexpected error while creating priority.');
        }
    }

    /**
     * Update an existing priority.
     *
     * @param int $id
     * @param array $data
     * @return Priority
     * @throws DomainException
     */
    public function updatePriority(int $id, array $data): Priority
    {
        try {
            return $this->repository->updatePriority($id, $data);
        } catch (QueryException $e) {
            Log::error('Database error updating priority', ['id' => $id, 'data' => $data, 'exception' => $e]);
            throw new DomainException('Database error while updating priority.');
        } catch (Exception $e) {
            Log::error('Unexpected error updating priority', ['id' => $id, 'data' => $data, 'exception' => $e]);
            throw new DomainException('Unexpected error while updating priority.');
        }
    }

    /**
     * Delete a priority (soft delete).
     *
     * @param int $id
     * @return bool
     * @throws DomainException
     */
    public function deletePriority(int $id): bool
    {
        try {
            return $this->repository->deletePriority($id);
        } catch (QueryException $e) {
            Log::error('Database error deleting priority', ['id' => $id, 'exception' => $e]);
            throw new DomainException('Database error while deleting priority.');
        } catch (Exception $e) {
            Log::error('Unexpected error deleting priority', ['id' => $id, 'exception' => $e]);
            throw new DomainException('Unexpected error while deleting priority.');
        }
    }
}
