<?php

namespace App\Contracts\Repositories;

interface CountryRepositoryInterface 
{
    /**
     * Get all countries (for dropdowns)
     */
    public function getAll();

    /**
     * Get paginated list (for table view)
     */
    public function getPaginated(int $perPage = 10);

    /**
     * Find a country by ID
     */
    public function findById(int $id);

    /**
     * Create a new country
     */
    public function create(array $data);

    /**
     * Update a country
     */
    public function update(int $id, array $data);

    /**
     * Delete a country (This will be a HARD delete)
     */
    public function delete(int $id);

    /**
     * Find countries by name (case-insensitive).
     *
     * @param array<int, string> $names
     * @return \Illuminate\Support\Collection
     */
    public function findByNames(array $names);
}

