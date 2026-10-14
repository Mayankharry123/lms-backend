<?php

/**
 * LeadSourceService
 * 
 * @author Achal Sharma
 * @package App\Services
 * @version 1.0.1
 */

namespace App\Services;

use App\Contracts\Repositories\LeadSourceRepositoryInterface;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Exception;

class LeadSourceService
{
    protected $leadSourceRepository;
    /**
     * LeadSourceService constructor.
     *
     * @param LeadSourceRepositoryInterface $leadSourceRepository
     */
    public function __construct(LeadSourceRepositoryInterface $leadSourceRepository)
    {
        $this->leadSourceRepository = $leadSourceRepository;
    }

    /**
     * Fetch all lead sources with pagination
     */
    public function getAllLeadSources(int $perPage = 10, ?string $searchTerm = null)
    {
        try {
            return $this->leadSourceRepository->getAllLeadSources($perPage, $searchTerm); 
        } catch (QueryException $e) {
            throw new Exception('Database query failed while fetching lead sources: ' . $e->getMessage());
        } catch (Exception $e) {
            throw new Exception('An unexpected error occurred while fetching lead sources: ' . $e->getMessage());
        }
    }

    /**
     * Create a new lead source
     */
    public function createNewLeadSource(array $data)
    {
        try {
            $slug = $this->createUniqueSlug($data['name']);
            $data['slug'] = $slug;
            $data['status'] = $data['status'] ?? '1';

            return $this->leadSourceRepository->createLeadSource($data);
        } catch (QueryException $e) {
            throw new Exception('Failed to create lead source: ' . $e->getMessage());
        } catch (Exception $e) {
            throw new Exception('An unexpected error occurred while creating lead source: ' . $e->getMessage());
        }
    }

    /**
     * Fetch a single lead source by ID
     */
    public function getLeadSource($id)
    {
        try {
            return $this->leadSourceRepository->getLeadSourceById($id);
        } catch (ModelNotFoundException $e) {
            throw new Exception('Lead source not found for the given ID.');
        } catch (Exception $e) {
            throw new Exception('An unexpected error occurred while fetching the lead source: ' . $e->getMessage());
        }
    }

    /**
     * Update an existing lead source
     */
    public function updateLeadSource($id, array $data)
    {
        try {
            if (isset($data['name'])) {
                $leadSource = $this->leadSourceRepository->getLeadSourceById($id);

                if ($leadSource->name !== $data['name']) {
                    $data['slug'] = $this->createUniqueSlug($data['name'], $id);
                }
            }

            return $this->leadSourceRepository->updateLeadSource($id, $data);
        } catch (ModelNotFoundException $e) {
            throw new Exception('Lead source not found for update.');
        } catch (QueryException $e) {
            throw new Exception('Failed to update lead source: ' . $e->getMessage());
        } catch (Exception $e) {
            throw new Exception('An unexpected error occurred while updating lead source: ' . $e->getMessage());
        }
    }

    /**
     * Delete a lead source
     */
    public function deleteLeadSource($id)
    {
        try {
            return $this->leadSourceRepository->deleteLeadSource($id);
        } catch (ModelNotFoundException $e) {
            throw new Exception('Lead source not found for deletion.');
        } catch (QueryException $e) {
            throw new Exception('Failed to delete lead source: ' . $e->getMessage());
        } catch (Exception $e) {
            throw new Exception('An unexpected error occurred while deleting lead source: ' . $e->getMessage());
        }
    }

    /**
     * Generate a unique slug for the lead source name
     */
    private function createUniqueSlug(string $name, $excludeId = null): string
    {
        try {
            $slug = Str::slug($name);
            $originalSlug = $slug;
            $count = 1;

            $existing = $this->leadSourceRepository->findBySlug($slug);

            while ($existing && $existing->id != $excludeId) {
                $slug = $originalSlug . '-' . $count++;
                $existing = $this->leadSourceRepository->findBySlug($slug);
            }

            return $slug;
        } catch (QueryException $e) {
            throw new Exception('Error generating unique slug: ' . $e->getMessage());
        } catch (Exception $e) {
            throw new Exception('Unexpected error while generating slug: ' . $e->getMessage());
        }
    }
}
