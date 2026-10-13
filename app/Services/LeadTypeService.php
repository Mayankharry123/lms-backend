<?php

/**
 * Lead Type Service
 * -----------------------------------------
 * Handles business logic related to lead types, including creation and validation.
 * Interacts with the LeadType repository to perform data operations while managing
 * exceptions and logging for better maintainability and error tracking.
 *
 * @package App\Services
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-05-05
 */

namespace App\Services;

use App\Contracts\Repositories\LeadTypeRepositoryInterface;
use Illuminate\Support\Facades\Log;
use DomainException;
use Illuminate\Database\QueryException;
use Exception;


class LeadTypeService
{
    protected $leadTypeRepository;

    /**
     * Inject the LeadType repository interface
     */
    public function __construct(LeadTypeRepositoryInterface $leadTypeRepository)
    {
        $this->leadTypeRepository = $leadTypeRepository;
    }

    /**
     * Get all lead types.
     */
    public function getAllLeadTypes(int $perPage = 10, ?string $searchTerm = null)
    {
        return $this->leadTypeRepository->getAllLeadTypes($perPage, $searchTerm);
    }

    /**
     * List all lead types.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function listLeadTypes()
    {
        return $this->leadTypeRepository->listLeadTypes();
    }

    /**
     * Get a lead type by ID.
     *
     * @param int $id
     * @return \App\Models\LeadType
     */
    public function getLeadTypeById(int $id)
    {
        return $this->leadTypeRepository->getLeadTypeById($id);
    }

    /**
     * Create a new lead type.
     */
    public function createLeadType(array $data)
    {
        try {
            return $this->leadTypeRepository->createLeadType($data);
        } catch (QueryException $e) {
            Log::error('Database error creating lead type: ' . $e->getMessage());
            throw new DomainException('Database error while creating lead type.');
        } catch (Exception $e) {
            Log::error('Unexpected error creating lead type: ' . $e->getMessage());
            throw new DomainException('Unexpected error while creating lead type.');
        }
    }
}