<?php

/**
 * StateService
 * 
 * @author Achal Sharma
 * @package App\Services
 * @version 1.0.1
 */

namespace App\Services;

use App\Contracts\Repositories\StateRepositoryInterface;

class StateService
{
    /**
     * @var StateRepositoryInterface
     */
    protected $stateRepo;

    /**
     * StateService constructor.
     *
     * @param StateRepositoryInterface $stateRepo
     */
    public function __construct(StateRepositoryInterface $stateRepo)
    {
        $this->stateRepo = $stateRepo;
    }

    /**
     * Get all state records.
     *
     * @return mixed
     */
    public function getAllStates()
    {
        return $this->stateRepo->getAll();
    }

    /**
     * Get states belonging to a specific country.
     *
     * @param int $countryId
     * @return mixed
     */
    public function getStatesByCountry(int $countryId)
    {
        return $this->stateRepo->getByCountry($countryId);
    }

    /**
     * Get paginated state records.
     *
     * @param int $perPage
     * @param string|null $search
     * @return mixed
     */
    public function getPaginatedStates(int $perPage = 15, ?string $search = null)
    {
        return $this->stateRepo->getPaginated($perPage, $search);
    }

    /**
     * Get state by ID.
     *
     * @param int $id
     * @return mixed
     */
    public function getStateById(int $id)
    {
        return $this->stateRepo->findById($id);
    }

    /**
     * Create a new state record.
     *
     * @param array $data
     * @return mixed
     */
    public function createState(array $data)
    {
        // No business logic needed, direct create operation
        return $this->stateRepo->create($data);
    }

    /**
     * Update an existing state record by ID.
     *
     * @param int $id
     * @param array $data
     * @return mixed
     */
    public function updateState(int $id, array $data)
    {
        // No business logic needed, direct update operation
        return $this->stateRepo->update($id, $data);
    }

    /**
     * Delete a state record by ID.
     *
     * @param int $id
     * @return mixed
     */
    public function deleteState(int $id)
    {
        return $this->stateRepo->delete($id);
    }
}
