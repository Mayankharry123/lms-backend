<?php

namespace App\Contracts\Repositories;

use App\Models\Industry;

interface IndustryRepositoryInterface 
{
    /**
     * Fetch all industries
     */
    public function getAllIndustries();

    /**
     * Fetch a single industry by its primary key (id)
     * @param int $id
     */
    public function getIndustryById($id);

    /**
     * Create a new industry
     * @param array $data Example: ['name' => 'New Industry']
     */
    public function createIndustry(array $data);

    /**
     * Update an existing industry
     * @param int $id
     * @param array $data
     */
    public function updateIndustry($id, array $data);

    /**
     * Delete an industry (soft delete)
     * @param int $id
     */
    public function deleteIndustry($id);

    /**
     * Find non-deleted industries by name (case-insensitive).
     *
     * @param array<int, string> $names
     * @return \Illuminate\Support\Collection
     */
    public function findByNames(array $names);
}
