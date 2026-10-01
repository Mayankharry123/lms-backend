<?php

namespace App\Contracts\Repositories;

interface StateRepositoryInterface 
{
    /**
     * Get all states (for dropdowns)
     */
    public function getAll();

    /**
     * Get all states for a specific country
     */
    public function getByCountry(int $countryId);

    /**
     * Get paginated list (for table view)
     */
    public function getPaginated(int $perPage = 10);

    /**
     * Find a state by ID
     */
    public function findById(int $id);

    /**
     * Create a new state
     */
    public function create(array $data);

    /**
     * Update a state
     */
    public function update(int $id, array $data);

    /**
     * Delete a state (This will be a HARD delete)
     */
    public function delete(int $id);

    /**
     * Find states by name (case-insensitive).
     *
     * @param array<int, string> $names
     * @return \Illuminate\Support\Collection
     */
    public function findByNames(array $names);
}
