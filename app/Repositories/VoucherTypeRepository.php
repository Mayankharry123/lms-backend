<?php

namespace App\Repositories;

use App\Contracts\Repositories\VoucherTypeRepositoryInterface;
use App\Models\VoucherType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class VoucherTypeRepository implements VoucherTypeRepositoryInterface
{
    protected VoucherType $model;

    public function __construct(VoucherType $model)
    {
        $this->model = $model;
    }

    public function getActiveList(?string $search = null): Collection
    {
        return $this->model->getActiveList($search);
    }

    public function getPaginated(int $perPage = 15, ?string $search = null): LengthAwarePaginator
    {
        return $this->model->paginateActive($perPage, $search);
    }

    public function getById(int $id): ?VoucherType
    {
        return $this->model->findById($id);
    }

    public function getByUuid(string $uuid): ?VoucherType
    {
        return $this->model->findByUuid($uuid);
    }

    public function getBySlug(string $slug): ?VoucherType
    {
        return $this->model->findBySlug($slug);
    }
}
