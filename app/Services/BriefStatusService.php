<?php

/**
 * BriefStatusService
 * 
 * @author Achal Sharma
 * @package App\Services
 * @version 1.0.1
 */

namespace App\Services;

use App\Contracts\Repositories\BriefStatusRepositoryInterface;
use App\Models\BriefStatus;
use DomainException;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BriefStatusService
{
    /**
     * @var BriefStatusRepositoryInterface
     */
    protected BriefStatusRepositoryInterface $repository;

    /**
     * Create a new BriefStatusService instance.
     *
     * @param BriefStatusRepositoryInterface $repository
     */
    public function __construct(BriefStatusRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    // ============================================================================
    // READ OPERATIONS
    // ============================================================================

    /**
     * Get all brief statuses with pagination.
     *
     * @param int $perPage
     * @param string|null $searchTerm
     * @return LengthAwarePaginator
     * @throws DomainException
     */
    public function getAllBriefStatuses(int $perPage = 10, ?string $searchTerm = null): LengthAwarePaginator
    {
        try {
            return $this->repository->getAllBriefStatuses($perPage, $searchTerm);
        } catch (QueryException $e) {
            Log::error('Database error fetching brief statuses', ['exception' => $e]);
            throw new DomainException('Database error while fetching brief statuses.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching brief statuses', ['exception' => $e]);
            throw new DomainException('Unexpected error while fetching brief statuses.');
        }
    }

    /**
     * Get a brief status by ID.
     *
     * @param int $id
     * @return BriefStatus|null
     * @throws DomainException
     */
    public function getBriefStatus(int $id): ?BriefStatus
    {
        try {
            return $this->repository->getBriefStatusById($id);
        } catch (QueryException $e) {
            Log::error('Database error fetching brief status', ['id' => $id, 'exception' => $e]);
            throw new DomainException('Database error while fetching brief status.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching brief status', ['id' => $id, 'exception' => $e]);
            throw new DomainException('Unexpected error while fetching brief status.');
        }
    }

    /**
     * Get a brief status by UUID.
     *
     * @param string $uuid
     * @return BriefStatus|null
     * @throws DomainException
     */
    public function getBriefStatusByUuid(string $uuid): ?BriefStatus
    {
        try {
            return $this->repository->getBriefStatusByUuid($uuid);
        } catch (QueryException $e) {
            Log::error('Database error fetching brief status by UUID', ['uuid' => $uuid, 'exception' => $e]);
            throw new DomainException('Database error while fetching brief status.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching brief status by UUID', ['uuid' => $uuid, 'exception' => $e]);
            throw new DomainException('Unexpected error while fetching brief status.');
        }
    }

    /**
     * Get a brief status by name.
     *
     * @param string $name
     * @return BriefStatus|null
     * @throws DomainException
     */
    public function getBriefStatusByName(string $name): ?BriefStatus
    {
        try {
            return $this->repository->getBriefStatusByName($name);
        } catch (QueryException $e) {
            Log::error('Database error fetching brief status by name', ['name' => $name, 'exception' => $e]);
            throw new DomainException('Database error while fetching brief status.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching brief status by name', ['name' => $name, 'exception' => $e]);
            throw new DomainException('Unexpected error while fetching brief status.');
        }
    }

    /**
     * Get a brief status by slug.
     *
     * @param string $slug
     * @return BriefStatus|null
     * @throws DomainException
     */
    public function getBriefStatusBySlug(string $slug): ?BriefStatus
    {
        try {
            return $this->repository->getBriefStatusBySlug($slug);
        } catch (QueryException $e) {
            Log::error('Database error fetching brief status by slug', ['slug' => $slug, 'exception' => $e]);
            throw new DomainException('Database error while fetching brief status.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching brief status by slug', ['slug' => $slug, 'exception' => $e]);
            throw new DomainException('Unexpected error while fetching brief status.');
        }
    }

    /**
     * Get all active brief statuses.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     * @throws DomainException
     */
    public function getActiveBriefStatuses(int $perPage = 10): LengthAwarePaginator
    {
        try {
            return $this->repository->getActiveBriefStatuses($perPage);
        } catch (QueryException $e) {
            Log::error('Database error fetching active brief statuses', ['exception' => $e]);
            throw new DomainException('Database error while fetching active brief statuses.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching active brief statuses', ['exception' => $e]);
            throw new DomainException('Unexpected error while fetching active brief statuses.');
        }
    }

    // ============================================================================
    // WRITE OPERATIONS
    // ============================================================================

    /**
     * Create a new brief status.
     *
     * @param array<string, mixed> $data
     * @return BriefStatus
     * @throws DomainException
     */
    public function createBriefStatus(array $data): BriefStatus
    {
        try {
            // Generate UUID if not provided
            if (!isset($data['uuid'])) {
                $data['uuid'] = Str::uuid();
            }

            // Set default status if not provided
            if (!isset($data['status'])) {
                $data['status'] = '1';
            }

            return $this->repository->createBriefStatus($data);
        } catch (QueryException $e) {
            Log::error('Database error creating brief status', ['data' => $data, 'exception' => $e]);
            throw new DomainException('Database error while creating brief status.');
        } catch (Exception $e) {
            Log::error('Unexpected error creating brief status', ['data' => $data, 'exception' => $e]);
            throw new DomainException('Unexpected error while creating brief status.');
        }
    }

    /**
     * Update an existing brief status.
     *
     * @param int $id
     * @param array<string, mixed> $data
     * @return BriefStatus|null
     * @throws DomainException
     */
    public function updateBriefStatus(int $id, array $data): ?BriefStatus
    {
        try {
            $briefStatus = $this->getBriefStatus($id);

            if (!$briefStatus) {
                return null;
            }

            return $this->repository->updateBriefStatus($id, $data);
        } catch (QueryException $e) {
            Log::error('Database error updating brief status', ['id' => $id, 'data' => $data, 'exception' => $e]);
            throw new DomainException('Database error while updating brief status.');
        } catch (Exception $e) {
            Log::error('Unexpected error updating brief status', ['id' => $id, 'data' => $data, 'exception' => $e]);
            throw new DomainException('Unexpected error while updating brief status.');
        }
    }

    /**
     * Delete a brief status.
     *
     * @param int $id
     * @return bool
     * @throws DomainException
     */
    public function deleteBriefStatus(int $id): bool
    {
        try {
            $briefStatus = $this->getBriefStatus($id);

            if (!$briefStatus) {
                return false;
            }

            return $this->repository->deleteBriefStatus($id);
        } catch (QueryException $e) {
            Log::error('Database error deleting brief status', ['id' => $id, 'exception' => $e]);
            throw new DomainException('Database error while deleting brief status.');
        } catch (Exception $e) {
            Log::error('Unexpected error deleting brief status', ['id' => $id, 'exception' => $e]);
            throw new DomainException('Unexpected error while deleting brief status.');
        }
    }

    /**
     * Get priorities filtered by brief status ID.
     *
     * @param int $briefStatusId
     * @return array
     * @throws DomainException
     */
    public function getPrioritiesByBriefStatusId(int $briefStatusId): array
    {
        try {
            return $this->repository->getPrioritiesByBriefStatusId($briefStatusId);
        } catch (QueryException $e) {
            Log::error('Database error fetching priorities by brief status', ['brief_status_id' => $briefStatusId, 'exception' => $e]);
            throw new DomainException('Database error while fetching priorities.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching priorities by brief status', ['brief_status_id' => $briefStatusId, 'exception' => $e]);
            throw new DomainException('Unexpected error while fetching priorities.');
        }
    }

    /**
     * Get brief statuses by priority ID.
     *
     * @param int $priorityId
     * @param int $perPage
     * @return LengthAwarePaginator
     * @throws DomainException
     */
    public function getBriefStatusesByPriorityId(int $priorityId, int $perPage = 10): LengthAwarePaginator
    {
        try {
            return $this->repository->getBriefStatusesByPriorityId($priorityId, $perPage);
        } catch (QueryException $e) {
            Log::error('Database error fetching brief statuses by priority', ['priority_id' => $priorityId, 'exception' => $e]);
            throw new DomainException('Database error while fetching brief statuses.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching brief statuses by priority', ['priority_id' => $priorityId, 'exception' => $e]);
            throw new DomainException('Unexpected error while fetching brief statuses.');
        }
    }
}

