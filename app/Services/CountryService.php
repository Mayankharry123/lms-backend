<?php

/**
 * CountryService
 * 
 * @author Achal Sharma
 * @package App\Services
 * @version 1.0.1
 */

namespace App\Services;

use App\Contracts\Repositories\CountryRepositoryInterface;

class CountryService
{
    /**
     * @var CountryRepositoryInterface
     */
    protected $countries;

    /**
     * CountryService constructor.
     *
     * @param CountryRepositoryInterface $countries
     */
    public function __construct(CountryRepositoryInterface $countries)
    {
        $this->countries = $countries;
    }

    /**
     * Get all country records.
     *
     * @return mixed
     */
    public function getAllCountries()
    {
        return $this->countries->getAll();
    }

    /**
     * Get paginated country records.
     *
     * @param int $perPage
     * @param string|null $search
     * @return mixed
     */
    public function getPaginatedCountries(int $perPage = 15, ?string $search = null)
    {
        return $this->countries->getPaginated($perPage, $search);
    }

    /**
     * Get country by ID.
     *
     * @param int $id
     * @return mixed
     */
    public function getCountryById(int $id)
    {
        return $this->countries->findById($id);
    }

    /**
     * Create a new country record.
     *
     * @param array $data
     * @return mixed
     */
    public function createCountry(array $data)
    {
        return $this->countries->create($data);
    }

    /**
     * Update an existing country record by ID.
     *
     * @param int $id
     * @param array $data
     * @return mixed
     */
    public function updateCountry(int $id, array $data)
    {
        return $this->countries->update($id, $data);
    }

    /**
     * Delete a country record by ID.
     *
     * @param int $id
     * @return mixed
     */
    public function deleteCountry(int $id)
    {
        return $this->countries->delete($id);
    }
}


