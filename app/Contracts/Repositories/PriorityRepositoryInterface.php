<?php
/**
 * Interface PriorityRepositoryInterface
 * 
 * @author Achal Sharma
 * @package App\Contracts\Repositories
 * @version 1.0.1
 */
namespace App\Contracts\Repositories;

use App\Models\Priority;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PriorityRepositoryInterface
{
    // ============================================================================
    // READ OPERATIONS
    // ============================================================================

    /**
     * Fetch paginated list of priorities with optional search.
     *
     * @param int $perPage The number of items per page.
     * @param string|null $searchTerm Optional search term to filter priorities.
     * @return LengthAwarePaginator
     */
    public function getAllPriorities(int $perPage = 10, ?string $searchTerm = null): LengthAwarePaginator;

    /**
     * Fetch a single priority by its primary ID.
     *
     * @param int $id The priority ID.
     * @return Priority|null
     */
    public function getPriorityById(int $id): ?Priority;

    /**
     * Fetch a single priority by its unique slug.
     *
     * @param string $slug The unique priority slug.
     * @return Priority|null
     */
    public function getPriorityBySlug(string $slug): ?Priority;

    /**
     * Fetch a single priority by its UUID.
     *
     * @param string $uuid The unique priority UUID.
     * @return Priority|null
     */
    public function getPriorityByUuid(string $uuid): ?Priority;

    /**
     * Get all active priorities.
     *
     * @return LengthAwarePaginator
     */
    public function getActivePriorities(int $perPage = 10): LengthAwarePaginator;

    // ============================================================================
    // WRITE OPERATIONS
    // ============================================================================

    /**
     * Create a new priority record.
     *
     * @param array<string, mixed> $data
     * @return Priority
     */
    public function createPriority(array $data): Priority;

    /**
     * Update an existing priority by ID.
     *
     * @param int $id The priority ID.
     * @param array<string, mixed> $data
     * @return Priority
     */
    public function updatePriority(int $id, array $data): Priority;

    /**
     * Soft delete a priority by ID.
     *
     * @param int $id The priority ID.
     * @return bool
     */
    public function deletePriority(int $id): bool;
}
