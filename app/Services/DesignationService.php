<?php

/**
 * DesignationService
 * 
 * @author Achal Sharma
 * @package App\Services
 * @version 1.0.1
 */

namespace App\Services;

use App\Contracts\Repositories\DesignationRepositoryInterface;
use Illuminate\Support\Str;
use DomainException;
use Illuminate\Database\QueryException;
use Exception;

class DesignationService
{
    /**
     * @var DesignationRepositoryInterface
     */
    protected $designationRepository;

    /**
     * DesignationService constructor.
     *
     * @param DesignationRepositoryInterface $designationRepository
     */
    public function __construct(DesignationRepositoryInterface $designationRepository)
    {
        $this->designationRepository = $designationRepository;
    }

    /**
     * Get all designations with pagination and optional search filter.
     *
     * @param int $perPage
     * @param string|null $searchTerm
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     * @throws DomainException
     */
    public function getAllDesignations(int $perPage = 10, ?string $searchTerm = null)
    {
        try {
            return $this->designationRepository->getAllDesignations($perPage, $searchTerm);
        } catch (QueryException $e) {
            throw new DomainException('Database error while fetching designations: ' . $e->getMessage());
        } catch (Exception $e) {
            throw new DomainException('Unexpected error while fetching designations: ' . $e->getMessage());
        }
    }

    /**
     * Create a new designation.
     *
     * @param array $data
     * @return \App\Models\Designation
     * @throws DomainException
     */
    public function createNewDesignation(array $data)
    {
        try {
            if (isset($data['designation_name']) && !isset($data['title'])) {
                $data['title'] = $data['designation_name'];
                unset($data['designation_name']);
            }

            if (!empty($data['title'])) {
                $baseSlug = Str::slug($data['title']);
                $slug = $baseSlug;
                $attempt = 0;

                // Generate unique slug that doesn't exist (including soft-deleted records)
                while ($this->slugExistsIncludingDeleted($slug) && $attempt < 50) {
                    $attempt++;
                    $slug = $baseSlug . '-' . $attempt;
                }

                if ($this->slugExistsIncludingDeleted($slug)) {
                    throw new DomainException('Unable to generate a unique slug for the designation.');
                }

                $data['slug'] = $slug;
            }

            if (!isset($data['status'])) {
                $data['status'] = '1';
            }

            return $this->designationRepository->createDesignation($data);
        } catch (QueryException $e) {
            throw new DomainException('Database error while creating designation: ' . $e->getMessage());
        } catch (DomainException $e) {
            throw $e;
        } catch (Exception $e) {
            throw new DomainException('Unexpected error while creating designation: ' . $e->getMessage());
        }
    }

    /**
     * Check if slug exists including soft-deleted designations.
     *
     * @param string $slug
     * @return bool
     */
    private function slugExistsIncludingDeleted($slug)
    {
        // Check all records including soft-deleted ones to avoid database constraint violation
        return \App\Models\Designation::withTrashed()->where('slug', $slug)->exists();
    }

    /**
     * Get a designation by ID.
     *
     * @param int $id
     * @return \App\Models\Designation
     * @throws DomainException
     */
    public function getDesignation($id)
    {
        try {
            return $this->designationRepository->getDesignationById($id);
        } catch (QueryException $e) {
            throw new DomainException('Database error while fetching designation: ' . $e->getMessage());
        } catch (Exception $e) {
            throw new DomainException('Designation not found or unexpected error occurred.');
        }
    }

    /**
     * Update a designation by ID.
     *
     * @param int $id
     * @param array $data
     * @return \App\Models\Designation
     * @throws DomainException
     */
    public function updateDesignation($id, array $data)
    {
        try {
            if (isset($data['designation_name']) && !isset($data['title'])) {
                $data['title'] = $data['designation_name'];
                unset($data['designation_name']);
            }

            if (!empty($data['title'])) {
                $baseSlug = Str::slug($data['title']);
                $slug = $baseSlug;
                $attempt = 0;

                // Generate unique slug that doesn't exist (including soft-deleted records), except for current ID
                while ($this->slugExistsIncludingDeletedExcept($slug, $id) && $attempt < 50) {
                    $attempt++;
                    $slug = $baseSlug . '-' . $attempt;
                }

                if ($this->slugExistsIncludingDeletedExcept($slug, $id)) {
                    throw new DomainException('Unable to generate a unique slug for the designation.');
                }

                $data['slug'] = $slug;
            }

            return $this->designationRepository->updateDesignation($id, $data);
        } catch (QueryException $e) {
            throw new DomainException('Database error while updating designation: ' . $e->getMessage());
        } catch (DomainException $e) {
            throw $e;
        } catch (Exception $e) {
            throw new DomainException('Unexpected error while updating designation: ' . $e->getMessage());
        }
    }

    /**
     * Check if slug exists including soft-deleted designations except given ID.
     *
     * @param string $slug
     * @param int $excludeId
     * @return bool
     */
    private function slugExistsIncludingDeletedExcept($slug, $excludeId)
    {
        // Check all records including soft-deleted ones (except current ID) to avoid database constraint violation
        return \App\Models\Designation::withTrashed()->where('slug', $slug)->where('id', '!=', $excludeId)->exists();
    }

    /**
     * Delete a designation by ID.
     *
     * @param int $id
     * @return bool|null
     * @throws DomainException
     */
    public function deleteDesignation($id)
    {
        try {
            return $this->designationRepository->deleteDesignation($id);
        } catch (QueryException $e) {
            throw new DomainException('Database error while deleting designation: ' . $e->getMessage());
        } catch (Exception $e) {
            throw new DomainException('Unexpected error while deleting designation: ' . $e->getMessage());
        }
    }
}
