<?php

namespace App\Repositories;

use App\Contracts\Repositories\VoucherRepositoryInterface;
use App\Models\Voucher;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class VoucherRepository implements VoucherRepositoryInterface
{
    protected Voucher $model;

    public function __construct(Voucher $model)
    {
        $this->model = $model;
    }

    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->model->paginateForList($perPage, $filters);
    }

    public function findById(int $id): ?Voucher
    {
        return $this->model->findForDetail($id);
    }

    public function create(array $data): Voucher
    {
        return $this->model->storeVoucher($data);
    }

    public function update(int $id, array $data): ?Voucher
    {
        return $this->model->updateVoucher($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->model->deleteVoucher($id);
    }

    public function createItems(Voucher $voucher, array $items): void
    {
        $voucher->storeItems($items);
    }

    public function syncItems(Voucher $voucher, array $items): void
    {
        $voucher->syncItems($items);
    }

    public function getLatestByPrefix(string $prefix): ?Voucher
    {
        return $this->model->latestByNumberPrefix($prefix);
    }
}
