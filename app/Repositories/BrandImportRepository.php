<?php

/**
 * BrandImportRepository
 * 
 * @author Achal Sharma
 * @package App\Repositories
 * @version 1.0.1
 */

namespace App\Repositories;

use App\Contracts\Repositories\BrandImportRepositoryInterface;
use App\Models\BrandImport;

class BrandImportRepository implements BrandImportRepositoryInterface
{
    /**
     * Create a new brand import record.
     *
     * @param array $data
     * @return BrandImport
     */
    public function create(array $data): BrandImport
    {
        return BrandImport::create($data);
    }

    /**
     * Update an existing brand import record.
     *
     * @param BrandImport $import
     * @param array $data
     * @return BrandImport
     */
    public function update(BrandImport $import, array $data): BrandImport
    {
        $import->update($data);
        $import->refresh();

        return $import;
    }
}
