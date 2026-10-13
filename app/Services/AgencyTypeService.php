<?php

/**
 * AgencyTypeService
 * 
 * @author Achal Sharma
 * @package App\Services
 * @version 1.0.1
 */

namespace App\Services;

use App\Contracts\Repositories\AgencyTypeRepositoryInterface;
use App\Models\AgencyType;
use DomainException;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

class AgencyTypeService
{
    /**
     * @var AgencyTypeRepositoryInterface
     */
    protected AgencyTypeRepositoryInterface $repository;

    /**
     * Create a new AgencyTypeService instance.
     *
     * @param AgencyTypeRepositoryInterface $repository
     */
    public function __construct(AgencyTypeRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    // ============================================================================
    // READ OPERATIONS
    // ============================================================================

    /**
     * Get all agency types with pagination.
     *
     * @param int $perPage
     * @param string|null $searchTerm
     * @return LengthAwarePaginator
     * @throws DomainException
     */
    public function getAllAgencyTypes(int $perPage = 10, ?string $searchTerm = null): LengthAwarePaginator
    {
        try {
            return $this->repository->getAllAgencyTypes($perPage, $searchTerm);
        } catch (QueryException $e) {
            Log::error('Database error fetching agency types', ['exception' => $e]);
            throw new DomainException('Database error while fetching agency types.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching agency types', ['exception' => $e]);
            throw new DomainException('Unexpected error while fetching agency types.');
        }
    }

    /**
     * Get an agency type by ID.
     *
     * @param int $id
     * @return AgencyType|null
     * @throws DomainException
     */
    public function getAgencyType(int $id): ?AgencyType
    {
        try {
            return $this->repository->getAgencyTypeById($id);
        } catch (QueryException $e) {
            Log::error('Database error fetching agency type', ['id' => $id, 'exception' => $e]);
            throw new DomainException('Database error while fetching agency type.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching agency type', ['id' => $id, 'exception' => $e]);
            throw new DomainException('Unexpected error while fetching agency type.');
        }
    }

    /**
     * Get an agency type by slug.
     *
     * @param string $slug
     * @return AgencyType|null
     * @throws DomainException
     */
    public function getAgencyTypeBySlug(string $slug): ?AgencyType
    {
        try {
            return $this->repository->getAgencyTypeBySlug($slug);
        } catch (QueryException $e) {
            Log::error('Database error fetching agency type by slug', ['slug' => $slug, 'exception' => $e]);
            throw new DomainException('Database error while fetching agency type by slug.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching agency type by slug', ['slug' => $slug, 'exception' => $e]);
            throw new DomainException('Unexpected error while fetching agency type by slug.');
        }
    }

    // ============================================================================
    // WRITE OPERATIONS
    // ============================================================================

    /**
     * Create a new agency type.
     *
     * @param array $data
     * @return AgencyType
     * @throws DomainException
     */
    public function createAgencyType(array $data): AgencyType
    {
        try {
            if (empty($data['name'])) {
                throw new DomainException('Agency type name is required.');
            }

            return $this->repository->createAgencyType($data);
        } catch (DomainException $e) {
            throw $e;
        } catch (QueryException $e) {
            Log::error('Database error creating agency type', ['data' => $data, 'exception' => $e]);
            throw new DomainException('Database error while creating agency type.');
        } catch (Exception $e) {
            Log::error('Unexpected error creating agency type', ['data' => $data, 'exception' => $e]);
            throw new DomainException('Unexpected error while creating agency type.');
        }
    }

    /**
     * Update an existing agency type.
     *
     * @param int $id
     * @param array $data
     * @return AgencyType
     * @throws DomainException
     */
    public function updateAgencyType(int $id, array $data): AgencyType
    {
        try {
            return $this->repository->updateAgencyType($id, $data);
        } catch (QueryException $e) {
            Log::error('Database error updating agency type', ['id' => $id, 'data' => $data, 'exception' => $e]);
            throw new DomainException('Database error while updating agency type.');
        } catch (Exception $e) {
            Log::error('Unexpected error updating agency type', ['id' => $id, 'data' => $data, 'exception' => $e]);
            throw new DomainException('Unexpected error while updating agency type.');
        }
    }

    /**
     * Delete an agency type (soft delete).
     *
     * @param int $id
     * @return bool
     * @throws DomainException
     */
    public function deleteAgencyType(int $id): bool
    {
        try {
            return $this->repository->deleteAgencyType($id);
        } catch (QueryException $e) {
            Log::error('Database error deleting agency type', ['id' => $id, 'exception' => $e]);
            throw new DomainException('Database error while deleting agency type.');
        } catch (Exception $e) {
            Log::error('Unexpected error deleting agency type', ['id' => $id, 'exception' => $e]);
            throw new DomainException('Unexpected error while deleting agency type.');
        }
    }
}