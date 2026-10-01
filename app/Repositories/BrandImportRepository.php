<?php

namespace App\Repositories;

use App\Contracts\Repositories\BrandImportRepositoryInterface;
use App\Models\BrandImport;

class BrandImportRepository implements BrandImportRepositoryInterface
{
    public function create(array $data): BrandImport
    {
        return BrandImport::create($data);
    }

    public function update(BrandImport $import, array $data): BrandImport
    {
        $import->update($data);
        $import->refresh();

        return $import;
    }
}
