<?php

/**
 * IndustryService
 * 
 * @author Achal Sharma
 * @package App\Services
 * @version 1.0.1
 */

namespace App\Services;

use App\Contracts\Repositories\IndustryRepositoryInterface;
use Illuminate\Support\Facades\Log;
use DomainException;
use Illuminate\Database\QueryException;
use Exception;

class IndustryService
{
    protected $industryRepository;

    /**
     * Inject the Industry repository interface
     */
    public function __construct(IndustryRepositoryInterface $industryRepository)
    {
        $this->industryRepository = $industryRepository;
    }

    /**
     * Get all industries
     */
    public function getAllIndustries(int $perPage = 10, ?string $searchTerm = null)
    {
        try {
            return $this->industryRepository->getAllIndustries($perPage, $searchTerm); 
        } catch (QueryException $e) {
            Log::error('Database error fetching industries: ' . $e->getMessage());
            throw new DomainException('Database error while fetching industries.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching industries: ' . $e->getMessage());
            throw new DomainException('Unexpected error while fetching industries.');
        }
    }

    /**
     * Create a new industry
     */
    public function createNewIndustry(array $data)
    {
        try {
            if (empty($data['name'])) {
                throw new DomainException('Industry name is required.');
            }

            return $this->industryRepository->createIndustry($data);
        } catch (QueryException $e) {
            Log::error('Database error creating industry: ' . $e->getMessage());
            throw new DomainException('Database error while creating industry.');
        } catch (DomainException $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error('Unexpected error creating industry: ' . $e->getMessage());
            throw new DomainException('Unexpected error while creating industry.');
        }
    }

    /**
     * Get a single industry by ID
     */
    public function getIndustry($id)
    {
        try {
            $industry = $this->industryRepository->getIndustryById($id);
            if (!$industry) {
                throw new DomainException('Industry not found.');
            }
            return $industry;
        } catch (QueryException $e) {
            Log::error('Database error fetching industry: ' . $e->getMessage());
            throw new DomainException('Database error while fetching industry.');
        } catch (Exception $e) {
            Log::error('Unexpected error fetching industry: ' . $e->getMessage());
            throw new DomainException('Unexpected error while fetching industry.');
        }
    }

    /**
     * Update an existing industry
     */
    public function updateIndustry($id, array $data)
    {
        try {
            $industry = $this->industryRepository->getIndustryById($id);
            if (!$industry) {
                throw new DomainException('Industry not found.');
            }

            return $this->industryRepository->updateIndustry($id, $data);
        } catch (QueryException $e) {
            Log::error('Database error updating industry: ' . $e->getMessage());
            throw new DomainException('Database error while updating industry.');
        } catch (DomainException $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error('Unexpected error updating industry: ' . $e->getMessage());
            throw new DomainException('Unexpected error while updating industry.');
        }
    }

    /**
     * Delete an industry
     */
    public function deleteIndustry($id)
    {
        try {
            $industry = $this->industryRepository->getIndustryById($id);
            if (!$industry) {
                throw new DomainException('Industry not found.');
            }

            return $this->industryRepository->deleteIndustry($id);
        } catch (QueryException $e) {
            Log::error('Database error deleting industry: ' . $e->getMessage());
            throw new DomainException('Database error while deleting industry.');
        } catch (Exception $e) {
            Log::error('Unexpected error deleting industry: ' . $e->getMessage());
            throw new DomainException('Unexpected error while deleting industry.');
        }
    }
}
