<?php
/**
 * Interface BrandImportRepositoryInterface
 * 
 * @author Achal Sharma
 * @package App\Contracts\Repositories
 * @version 1.0.1
 */
namespace App\Contracts\Repositories;

use App\Models\BrandImport;

interface BrandImportRepositoryInterface
{
    /**
     * Create a new brand import
     * 
     * @param array $data
     * @return BrandImport
     */
    public function create(array $data): BrandImport;

    /**
     * Update a brand import
     * 
     * @param BrandImport $import
     * @param array $data
     * @return BrandImport
     */
    public function update(BrandImport $import, array $data): BrandImport;
}
