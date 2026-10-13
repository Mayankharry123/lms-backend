<?php
/**
 * Interface StatusRepositoryInterface
 * 
 * @author Achal Sharma
 * @package App\Contracts\Repositories
 * @version 1.0.1
 */
namespace App\Contracts\Repositories;

use App\Models\Status;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface StatusRepositoryInterface
{
    // ============================================================================
    // READ OPERATIONS
    // ============================================================================

    /**
     * Fetch paginated list of statuses with optional search.
     *
     * @param int $perPage The number of items per page.
     * @param string|null $searchTerm Optional search term to filter statuses.
     * @return LengthAwarePaginator
     */
    public function getAllStatuses(int $perPage = 10, ?string $searchTerm = null): LengthAwarePaginator;

    /**
     * Fetch a single status by its primary ID.
     *
     * @param int $id The status ID.
     * @return Status|null
     */
    public function getStatusById(int $id): ?Status;

    /**
     * Fetch a single status by its unique slug.
     *
     * @param string $slug The unique status slug.
     * @return Status|null
     */
    public function getStatusBySlug(string $slug): ?Status;

    /**
     * Fetch a single status by its UUID.
     *
     * @param string $uuid The unique status UUID.
     * @return Status|null
     */
    public function getStatusByUuid(string $uuid): ?Status;

    /**
     * Get all active statuses.
     *
     * @return LengthAwarePaginator
     */
    public function getActiveStatuses(int $perPage = 10): LengthAwarePaginator;

    // ============================================================================
    // WRITE OPERATIONS
    // ============================================================================

    /**
     * Create a new status record.
     *
     * @param array<string, mixed> $data
     * @return Status
     */
    public function createStatus(array $data): Status;

    /**
     * Update an existing status by ID.
     *
     * @param int $id The status ID.
     * @param array<string, mixed> $data
     * @return Status
     */
    public function updateStatus(int $id, array $data): Status;

    /**
     * Soft delete a status by ID.
     *
     * @param int $id The status ID.
     * @return bool
     */
    public function deleteStatus(int $id): bool;
}
