<?php

/**
 * VoucherRepository
 *
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-12
 */

namespace App\Repositories;

use App\Contracts\Repositories\VoucherRepositoryInterface;
use App\Models\Voucher;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class VoucherRepository implements VoucherRepositoryInterface
{
    /**
     * @var Voucher
     */
    protected Voucher $model;

    /**
     * VoucherRepository constructor.
     *
     * @param Voucher $model
     */
    public function __construct(Voucher $model)
    {
        $this->model = $model;
    }

    /**
     * Paginate vouchers with filters.
     *
     * @param int $perPage
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->model->paginateForList($perPage, $filters);
    }

    /**
     * Find a voucher by ID for detail.
     *
     * @param int $id
     * @return Voucher|null
     */
    public function findById(int $id): ?Voucher
    {
        return $this->model->findForDetail($id);
    }

    /**
     * Create a new voucher.
     *
     * @param array $data
     * @return Voucher
     */
    public function create(array $data): Voucher
    {
        return $this->model->storeVoucher($data);
    }

    /**
     * Update a voucher by ID.
     *
     * @param int $id
     * @param array $data
     * @return Voucher|null
     */
    public function update(int $id, array $data): ?Voucher
    {
        return $this->model->updateVoucher($id, $data);
    }

    /**
     * Delete a voucher by ID.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        return $this->model->deleteVoucher($id);
    }

    /**
     * Create items for a voucher.
     *
     * @param Voucher $voucher
     * @param array $items
     * @return void
     */
    public function createItems(Voucher $voucher, array $items): void
    {
        $voucher->storeItems($items);
    }

    /**
     * Synchronize items for a voucher.
     *
     * @param Voucher $voucher
     * @param array $items
     * @return void
     */
    public function syncItems(Voucher $voucher, array $items): void
    {
        $voucher->syncItems($items);
    }

    /**
     * Get latest voucher by number prefix.
     *
     * @param string $prefix
     * @return Voucher|null
     */
    public function getLatestByPrefix(string $prefix): ?Voucher
    {
        return $this->model->latestByNumberPrefix($prefix);
    }
}
