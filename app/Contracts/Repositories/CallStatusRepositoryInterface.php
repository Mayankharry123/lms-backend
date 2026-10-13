<?php
/**
 * Interface CallStatusRepositoryInterface
 * 
 * @author Achal Sharma
 * @package App\Contracts\Repositories
 * @version 1.0.1
 */
namespace App\Contracts\Repositories;

use App\Models\CallStatus;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CallStatusRepositoryInterface
{
    // ============================================================================
    // READ OPERATIONS
    // ============================================================================

    /**
     * Fetch paginated list of call statuses with optional search.
     *
     * @param int $perPage The number of items per page.
     * @param string|null $searchTerm Optional search term to filter call statuses.
     * @return LengthAwarePaginator
     */
    public function getAllCallStatuses(int $perPage = 10, ?string $searchTerm = null): LengthAwarePaginator;

    /**
     * Fetch a single call status by its primary ID.
     *
     * @param int $id The call status ID.
     * @return CallStatus|null
     */
    public function getCallStatusById(int $id): ?CallStatus;

    /**
     * Fetch a single call status by its unique slug.
     *
     * @param string $slug The unique call status slug.
     * @return CallStatus|null
     */
    public function getCallStatusBySlug(string $slug): ?CallStatus;

    /**
     * Fetch a single call status by its UUID.
     *
     * @param string $uuid The unique call status UUID.
     * @return CallStatus|null
     */
    public function getCallStatusByUuid(string $uuid): ?CallStatus;

    /**
     * Get all active call statuses.
     *
     * @return LengthAwarePaginator
     */
    public function getActiveCallStatuses(int $perPage = 10): LengthAwarePaginator;

    // ============================================================================
    // WRITE OPERATIONS
    // ============================================================================

    /**
     * Create a new call status record.
     *
     * @param array<string, mixed> $data
     * @return CallStatus
     */
    public function createCallStatus(array $data): CallStatus;

    /**
     * Update an existing call status by ID.
     *
     * @param int $id The call status ID.
     * @param array<string, mixed> $data
     * @return CallStatus
     */
    public function updateCallStatus(int $id, array $data): CallStatus;

    /**
     * Soft delete a call status by ID.
     *
     * @param int $id The call status ID.
     * @return bool
     */
    public function deleteCallStatus(int $id): bool;
}
