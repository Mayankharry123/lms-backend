<?php

namespace App\Services;

use App\Contracts\Repositories\BriefAssignHistoryRepositoryInterface;
use App\Models\BriefAssignHistory;
use DomainException;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

class BriefAssignHistoryService
{
    /**
     * @var BriefAssignHistoryRepositoryInterface
     */
    protected BriefAssignHistoryRepositoryInterface $repository;

    /**
     * Create a new BriefAssignHistoryService instance.
     *
     * @param BriefAssignHistoryRepositoryInterface $repository
     */
    public function __construct(BriefAssignHistoryRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    // ============================================================================
    // READ OPERATIONS
    // ============================================================================

    /**
     * Get all brief assign histories with pagination.
     *
     * @param int $perPage
     * @param string|null $searchTerm
     * @return LengthAwarePaginator
     * @throws DomainException
     */
    public function getAllBriefAssignHistories(int $perPage = 10, ?string $searchTerm = null): LengthAwarePaginator
    {
        try {
            return $this->repository->getAllBriefAssignHistories($perPage, $searchTerm);
        } catch (QueryException $e) {
            Log::error('Database error fetching brief assign histories', ['exception' => $e]);
            throw new DomainException('Database error while fetching brief assign histories.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching brief assign histories', ['exception' => $e]);
            throw new DomainException('Unexpected error while fetching brief assign histories.');
        }
    }

    /**
     * Get a brief assign history by ID.
     *
     * @param int $id
     * @return BriefAssignHistory|null
     * @throws DomainException
     */
    public function getBriefAssignHistory(int $id): ?BriefAssignHistory
    {
        try {
            return $this->repository->getBriefAssignHistoryById($id);
        } catch (QueryException $e) {
            Log::error('Database error fetching brief assign history', ['id' => $id, 'exception' => $e]);
            throw new DomainException('Database error while fetching brief assign history.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching brief assign history', ['id' => $id, 'exception' => $e]);
            throw new DomainException('Unexpected error while fetching brief assign history.');
        }
    }

    /**
     * Get a brief assign history by UUID.
     *
     * @param string $uuid
     * @return BriefAssignHistory|null
     * @throws DomainException
     */
    public function getBriefAssignHistoryByUuid(string $uuid): ?BriefAssignHistory
    {
        try {
            return $this->repository->getBriefAssignHistoryByUuid($uuid);
        } catch (QueryException $e) {
            Log::error('Database error fetching brief assign history by UUID', ['uuid' => $uuid, 'exception' => $e]);
            throw new DomainException('Database error while fetching brief assign history.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching brief assign history by UUID', ['uuid' => $uuid, 'exception' => $e]);
            throw new DomainException('Unexpected error while fetching brief assign history.');
        }
    }

    /**
     * Get all assign histories for a specific brief.
     *
     * @param int $briefId
     * @param int $perPage
     * @param \App\Models\User|null $user
     * @return LengthAwarePaginator
     * @throws DomainException
     */
    public function getBriefAssignHistoriesByBriefId(int $briefId, int $perPage = 10, ?\App\Models\User $user = null): LengthAwarePaginator
    {
        try {
            return $this->repository->getBriefAssignHistoriesByBriefId($briefId, $perPage, $user);
        } catch (QueryException $e) {
            Log::error('Database error fetching assign histories by brief', ['brief_id' => $briefId, 'exception' => $e]);
            throw new DomainException('Database error while fetching assign histories.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching assign histories by brief', ['brief_id' => $briefId, 'exception' => $e]);
            throw new DomainException('Unexpected error while fetching assign histories.');
        }
    }

    /**
     * Get all assign histories assigned by a specific user.
     *
     * @param int $userId
     * @param int $perPage
     * @return LengthAwarePaginator
     * @throws DomainException
     */
    public function getBriefAssignHistoriesByAssignBy(int $userId, int $perPage = 10): LengthAwarePaginator
    {
        try {
            return $this->repository->getBriefAssignHistoriesByAssignBy($userId, $perPage);
        } catch (QueryException $e) {
            Log::error('Database error fetching assign histories by assign_by user', ['user_id' => $userId, 'exception' => $e]);
            throw new DomainException('Database error while fetching assign histories.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching assign histories by assign_by user', ['user_id' => $userId, 'exception' => $e]);
            throw new DomainException('Unexpected error while fetching assign histories.');
        }
    }

    /**
     * Get all assign histories assigned to a specific user.
     *
     * @param int $userId
     * @param int $perPage
     * @return LengthAwarePaginator
     * @throws DomainException
     */
    public function getBriefAssignHistoriesByAssignTo(int $userId, int $perPage = 10): LengthAwarePaginator
    {
        try {
            return $this->repository->getBriefAssignHistoriesByAssignTo($userId, $perPage);
        } catch (QueryException $e) {
            Log::error('Database error fetching assign histories by assign_to user', ['user_id' => $userId, 'exception' => $e]);
            throw new DomainException('Database error while fetching assign histories.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching assign histories by assign_to user', ['user_id' => $userId, 'exception' => $e]);
            throw new DomainException('Unexpected error while fetching assign histories.');
        }
    }

    // ============================================================================
    // CREATE OPERATIONS
    // ============================================================================

    /**
     * Create a new brief assign history.
     *
     * @param array $data
     * @return BriefAssignHistory
     * @throws DomainException
     */
    public function createBriefAssignHistory(array $data): BriefAssignHistory
    {
        try {
            $briefAssignHistory = BriefAssignHistory::create($data);
            return $briefAssignHistory->load(['brief', 'assignedBy', 'assignedTo', 'briefStatus']);
        } catch (QueryException $e) {
            Log::error('Database error creating brief assign history', ['data' => $data, 'exception' => $e]);
            throw new DomainException('Database error while creating brief assign history.');
        } catch (Exception $e) {
            Log::error('Unexpected error creating brief assign history', ['data' => $data, 'exception' => $e]);
            throw new DomainException('Unexpected error while creating brief assign history.');
        }
    }

    // ============================================================================
    // UPDATE OPERATIONS
    // ============================================================================

    /**
     * Update a brief assign history.
     *
     * @param int $id
     * @param array $data
     * @return BriefAssignHistory|null
     * @throws DomainException
     */
    public function updateBriefAssignHistory(int $id, array $data): ?BriefAssignHistory
    {
        try {
            $briefAssignHistory = BriefAssignHistory::find($id);
            if (!$briefAssignHistory) {
                return null;
            }
            $briefAssignHistory->update($data);
            return $briefAssignHistory->load(['brief', 'assignedBy', 'assignedTo', 'briefStatus']);
        } catch (QueryException $e) {
            Log::error('Database error updating brief assign history', ['id' => $id, 'data' => $data, 'exception' => $e]);
            throw new DomainException('Database error while updating brief assign history.');
        } catch (Exception $e) {
            Log::error('Unexpected error updating brief assign history', ['id' => $id, 'data' => $data, 'exception' => $e]);
            throw new DomainException('Unexpected error while updating brief assign history.');
        }
    }

    // ============================================================================
    // DELETE OPERATIONS
    // ============================================================================

    /**
     * Delete a brief assign history (soft delete).
     *
     * @param int $id
     * @return bool
     * @throws DomainException
     */
    public function deleteBriefAssignHistory(int $id): bool
    {
        try {
            $briefAssignHistory = BriefAssignHistory::find($id);
            if (!$briefAssignHistory) {
                return false;
            }
            return (bool) $briefAssignHistory->delete();
        } catch (QueryException $e) {
            Log::error('Database error deleting brief assign history', ['id' => $id, 'exception' => $e]);
            throw new DomainException('Database error while deleting brief assign history.');
        } catch (Exception $e) {
            Log::error('Unexpected error deleting brief assign history', ['id' => $id, 'exception' => $e]);
            throw new DomainException('Unexpected error while deleting brief assign history.');
        }
    }

    /**
     * Permanently delete a brief assign history.
     *
     * @param int $id
     * @return bool
     * @throws DomainException
     */
    public function forceDeleteBriefAssignHistory(int $id): bool
    {
        try {
            $briefAssignHistory = BriefAssignHistory::withTrashed()->find($id);
            if (!$briefAssignHistory) {
                return false;
            }
            return (bool) $briefAssignHistory->forceDelete();
        } catch (QueryException $e) {
            Log::error('Database error force deleting brief assign history', ['id' => $id, 'exception' => $e]);
            throw new DomainException('Database error while force deleting brief assign history.');
        } catch (Exception $e) {
            Log::error('Unexpected error force deleting brief assign history', ['id' => $id, 'exception' => $e]);
            throw new DomainException('Unexpected error while force deleting brief assign history.');
        }
    }

    /**
     * Restore a soft deleted brief assign history.
     *
     * @param int $id
     * @return BriefAssignHistory|null
     * @throws DomainException
     */
    public function restoreBriefAssignHistory(int $id): ?BriefAssignHistory
    {
        try {
            $briefAssignHistory = BriefAssignHistory::onlyTrashed()->find($id);
            if (!$briefAssignHistory) {
                return null;
            }
            $briefAssignHistory->restore();
            return $briefAssignHistory->load(['brief', 'assignedBy', 'assignedTo', 'briefStatus']);
        } catch (QueryException $e) {
            Log::error('Database error restoring brief assign history', ['id' => $id, 'exception' => $e]);
            throw new DomainException('Database error while restoring brief assign history.');
        } catch (Exception $e) {
            Log::error('Unexpected error restoring brief assign history', ['id' => $id, 'exception' => $e]);
            throw new DomainException('Unexpected error while restoring brief assign history.');
        }
    }
}
