<?php

/**
 * CityService
 * 
 * @author Achal Sharma
 * @package App\Services
 * @version 1.0.1
 */

namespace App\Services;

use App\Contracts\Repositories\CityRepositoryInterface;

class CityService
{
    /**
     * @var CityRepositoryInterface
     */
    protected $cityRepo;

    /**
     * CityService constructor.
     *
     * @param CityRepositoryInterface $cityRepo
     */
    public function __construct(CityRepositoryInterface $cityRepo)
    {
        $this->cityRepo = $cityRepo;
    }

    /**
     * Get all cities.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllCities()
    {
        return $this->cityRepo->getAll();
    }

    /**
     * Get paginated cities with optional search filter.
     *
     * @param int $perPage
     * @param string|null $search
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getPaginatedCities(int $perPage = 15, ?string $search = null)
    {
        return $this->cityRepo->getPaginated($perPage, $search);
    }

    /**
     * Get cities by state ID.
     *
     * @param int $stateId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getCitiesByState(int $stateId)
    {
        return $this->cityRepo->getByState($stateId);
    }

    /**
     * Get cities by country ID.
     *
     * @param int $countryId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getCitiesByCountry(int $countryId)
    {
        return $this->cityRepo->getByCountry($countryId);
    }

    /**
     * Get a city by ID.
     *
     * @param int $id
     * @return \App\Models\City|null
     */
    public function getCityById(int $id)
    {
        return $this->cityRepo->findById($id);
    }

    /**
     * Create a new city.
     *
     * @param array $data
     * @return \App\Models\City
     */
    public function createCity(array $data)
    {
        // Direct create without additional business logic
        return $this->cityRepo->create($data);
    }

    /**
     * Update a city by ID.
     *
     * @param int $id
     * @param array $data
     * @return \App\Models\City
     */
    public function updateCity(int $id, array $data)
    {
        // Direct update without additional business logic
        return $this->cityRepo->update($id, $data);
    }

    /**
     * Delete a city by ID.
     *
     * @param int $id
     * @return bool|null
     */
    public function deleteCity(int $id)
    {
        return $this->cityRepo->delete($id);
    }
}
