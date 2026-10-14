<?php

/**
 * LeadSubSourceService
 * 
 * @author Achal Sharma
 * @package App\Services
 * @version 1.0.1
 */

/*
 * LeadSubSourceService
 * -----------------------------------------
 * This service class provides methods to manage lead sub-sources,
 * including listing, creating, updating, and deleting sub-source records.
 *
 * @package App\Services
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-05-06
 */

namespace App\Services;

use App\Contracts\Repositories\LeadSubSourceRepositoryInterface;
use App\Models\LeadSource;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;
use InvalidArgumentException;

class LeadSubSourceService
{
    protected $leadSubSourceRepository;
    /**
     * LeadSubSourceService constructor.
     *
     * @param LeadSubSourceRepositoryInterface $leadSubSourceRepository
     */
    public function __construct(LeadSubSourceRepositoryInterface $leadSubSourceRepository)
    {
        $this->leadSubSourceRepository = $leadSubSourceRepository;
    }

    /**
     * Fetch all lead sub-sources with optional filters AND pagination
     */
    public function getAllLeadSubSources(array $filters = [], int $perPage = 10,?string $searchTerm = null ) // <-- perPage added
    {
        try {
            return $this->leadSubSourceRepository->getAllLeadSubSources($filters, $perPage,$searchTerm); // <-- perPage passed
        } catch (QueryException $e) {
            throw new Exception('Database query failed while fetching lead sub-sources: ' . $e->getMessage());
        } catch (Exception $e) {
            throw new Exception('An unexpected error occurred while fetching lead sub-sources: ' . $e->getMessage());
        }
    }

    /**
     * ID of the default "direct" lead source (slug: direct).
     *
     * @throws InvalidArgumentException when no matching lead source exists
     * @throws Exception when the lookup fails due to the database layer
     */
    public function getDirectLeadSourceId(): int
    {
        try {
            $source = LeadSource::query()->where('slug', 'direct')->first();
        } catch (QueryException $e) {
            throw new Exception('Database error while resolving default lead source: ' . $e->getMessage(), 0, $e);
        }

        if ($source === null) {
            throw new InvalidArgumentException('Default lead source "direct" is not configured. Ensure lead sources are seeded.');
        }

        return (int) $source->id;
    }

    /**
     * Create a new lead sub-source
     */
    public function createNewLeadSubSource(array $data)
    {
        try {
            $slug = $this->createUniqueSlug($data['name']);
            $data['slug'] = $slug;
            $data['status'] = $data['status'] ?? '1';

            $subSource = $this->leadSubSourceRepository->createLeadSubSource($data);

            return $subSource->load('leadSource');
        } catch (QueryException $e) {
            throw new Exception('Failed to create lead sub-source: ' . $e->getMessage());
        } catch (Exception $e) {
            throw new Exception('An unexpected error occurred while creating lead sub-source: ' . $e->getMessage());
        }
    }

    /**
     * Fetch a single lead sub-source by ID
     */
    public function getLeadSubSource($id)
    {
        try {
            return $this->leadSubSourceRepository->getLeadSubSourceById($id);
        } catch (ModelNotFoundException $e) {
            throw new Exception('Lead sub-source not found for the given ID.');
        } catch (Exception $e) {
            throw new Exception('An unexpected error occurred while fetching the lead sub-source: ' . $e->getMessage());
        }
    }

    /**
     * Update an existing lead sub-source
     */
    public function updateLeadSubSource($id, array $data)
    {
        try {
            if (isset($data['name'])) {
                $leadSubSource = $this->leadSubSourceRepository->getLeadSubSourceById($id);

                if ($leadSubSource->name !== $data['name']) {
                    $data['slug'] = $this->createUniqueSlug($data['name'], $id);
                }
            }

            $updated = $this->leadSubSourceRepository->updateLeadSubSource($id, $data);

            return $updated->load('leadSource');
        } catch (ModelNotFoundException $e) {
            throw new Exception('Lead sub-source not found for update.');
        } catch (QueryException $e) {
            throw new Exception('Failed to update lead sub-source: ' . $e->getMessage());
        } catch (Exception $e) {
            throw new Exception('An unexpected error occurred while updating lead sub-source: ' . $e->getMessage());
        }
    }

    /**
     * Delete a lead sub-source
     */
    public function deleteLeadSubSource($id)
    {
        try {
            return $this->leadSubSourceRepository->deleteLeadSubSource($id);
        } catch (ModelNotFoundException $e) {
            throw new Exception('Lead sub-source not found for deletion.');
        } catch (QueryException $e) {
            throw new Exception('Failed to delete lead sub-source: ' . $e->getMessage());
        } catch (Exception $e) {
            throw new Exception('An unexpected error occurred while deleting lead sub-source: ' . $e->getMessage());
        }
    }

    /**
     * Get lead sub-sources by source ID
     */
    public function getLeadSubSourcesBySourceId($sourceId)
    {
        try {
            return $this->leadSubSourceRepository->getLeadSubSourcesBySourceId($sourceId);
        } catch (Exception $e) {
            throw new Exception('An unexpected error occurred while fetching lead sub-sources by source ID: ' . $e->getMessage());
        }
    }

    /**
     * Generate a unique slug for the lead sub-source name
     */
    private function createUniqueSlug(string $name, $excludeId = null): string
    {
        try {
            $slug = Str::slug($name);
            $originalSlug = $slug;
            $count = 1;

            $existing = $this->leadSubSourceRepository->findBySlug($slug);

            while ($existing && $existing->id != $excludeId) {
                $slug = $originalSlug . '-' . $count++;
                $existing = $this->leadSubSourceRepository->findBySlug($slug);
            }

            return $slug;
        } catch (QueryException $e) {
            throw new Exception('Error generating unique slug: ' . $e->getMessage());
        } catch (Exception $e) {
            throw new Exception('Unexpected error while generating slug: ' . $e->getMessage());
        }
    }
}
