<?php

namespace App\Contracts\Repositories;

use App\Models\BrandImport;

interface BrandImportRepositoryInterface
{
    public function create(array $data): BrandImport;

    public function update(BrandImport $import, array $data): BrandImport;
}
