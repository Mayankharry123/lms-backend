<?php
/**
 * Interface AgencyTypeRepositoryInterface
 * 
 * @author Achal Sharma
 * @package App\Contracts\Repositories
 * @version 1.0.1
 */
namespace App\Contracts\Repositories;

use App\Models\AgencyType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AgencyTypeRepositoryInterface
{
    // ============================================================================
    // READ OPERATIONS
    // ============================================================================

    /**
     * Fetch paginated list of agency types with optional search.
     *
     * @param int $perPage The number of items per page.
     * @param string|null $searchTerm Optional search term to filter agency types.
     * @return LengthAwarePaginator
     */
    public function getAllAgencyTypes(int $perPage = 10, ?string $searchTerm = null): LengthAwarePaginator;

    /**
     * Fetch a single agency type by its primary ID.
     *
     * @param int $id The agency type ID.
     * @return AgencyType|null
     */
    public function getAgencyTypeById(int $id): ?AgencyType;

    /**
     * Fetch a single agency type by its unique slug.
     *
     * @param string $slug The unique agency type slug.
     * @return AgencyType|null
     */
    public function getAgencyTypeBySlug(string $slug): ?AgencyType;

    // ============================================================================
    // WRITE OPERATIONS
    // ============================================================================

    /**
     * Create a new agency type record.
     *
     * @param array<string, mixed> $data
     * @return AgencyType
     */
    public function createAgencyType(array $data): AgencyType;

    /**
     * Update an existing agency type by ID.
     *
     * @param int $id The agency type ID.
     * @param array<string, mixed> $data
     * @return AgencyType
     */
    public function updateAgencyType(int $id, array $data): AgencyType;

    /**
     * Soft delete an agency type by ID.
     *
     * @param int $id The agency type ID.
     * @return bool
     */
    public function deleteAgencyType(int $id): bool;
}