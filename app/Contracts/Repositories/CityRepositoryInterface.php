<?php

namespace App\Contracts\Repositories;

interface CityRepositoryInterface 
{
    /**
     * Get all cities (for dropdowns)
     */
    public function getAll();

    /**
     * Get paginated list (for table view)
     */
    public function getPaginated(int $perPage = 10);

    /**
     * Get all cities for a specific state
     */
    public function getByState(int $stateId);

    /**
     * Get all cities for a specific country
     */
    public function getByCountry(int $countryId);

    /**
     * Find a city by ID
     */
    public function findById(int $id);

    /**
     * Create a new city
     */
    public function create(array $data);

    /**
     * Update a city
     */
    public function update(int $id, array $data);

    /**
     * Delete a city (This will be a HARD delete)
     */
    public function delete(int $id);

    /**
     * Find cities by name (case-insensitive).
     *
     * @param array<int, string> $names
     * @return \Illuminate\Support\Collection
     */
    public function findByNames(array $names);
}
