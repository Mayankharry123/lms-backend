<?php
/**
 * Interface AgencyGroupRepositoryInterface
 * 
 * @author Achal Sharma
 * @package App\Contracts\Repositories
 * @version 1.0.1
 */
namespace App\Contracts\Repositories;

use App\Models\Agency;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AgencyGroupRepositoryInterface
{
    /**
     * Get all agency groups with pagination and optional search filter.
     *
     * @param int $perPage
     * @param string|null $searchTerm
     * @return LengthAwarePaginator
     */
    public function getAllGroups(int $perPage = 10, ?string $searchTerm = null): LengthAwarePaginator;

    /**
     * Get an agency group by ID.
     *
     * @param int $id
     * @return Agency|null
     */
    public function getGroupById(int $id): ?Agency;

    /**
     * Create a new agency group.
     *
     * @param array $data
     * @return Agency
     */
    public function createGroup(array $data): Agency;

    /**
     * Update an agency group by ID.
     *
     * @param int $id
     * @param array $data
     * @return Agency
     */
    public function updateGroup(int $id, array $data): Agency;

    /**
     * Delete an agency group by ID.
     *
     * @param int $id
     * @return bool
     */
    public function deleteGroup(int $id): bool;
}
