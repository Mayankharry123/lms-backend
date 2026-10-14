<?php

/**
 * VoucherTypeRepository
 *
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-12
 */

namespace App\Repositories;

use App\Contracts\Repositories\VoucherTypeRepositoryInterface;
use App\Models\VoucherType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class VoucherTypeRepository implements VoucherTypeRepositoryInterface
{
    /**
     * @var VoucherType
     */
    protected VoucherType $model;

    /**
     * VoucherTypeRepository constructor.
     *
     * @param VoucherType $model
     */
    public function __construct(VoucherType $model)
    {
        $this->model = $model;
    }

    /**
     * Get active voucher types as collection.
     *
     * @param string|null $search
     * @return Collection
     */
    public function getActiveList(?string $search = null): Collection
    {
        return $this->model->getActiveList($search);
    }

    /**
     * Get paginated active voucher types.
     *
     * @param int $perPage
     * @param string|null $search
     * @return LengthAwarePaginator
     */
    public function getPaginated(int $perPage = 15, ?string $search = null): LengthAwarePaginator
    {
        return $this->model->paginateActive($perPage, $search);
    }

    /**
     * Get voucher type by ID.
     *
     * @param int $id
     * @return VoucherType|null
     */
    public function getById(int $id): ?VoucherType
    {
        return $this->model->findById($id);
    }

    /**
     * Get voucher type by UUID.
     *
     * @param string $uuid
     * @return VoucherType|null
     */
    public function getByUuid(string $uuid): ?VoucherType
    {
        return $this->model->findByUuid($uuid);
    }

    /**
     * Get voucher type by slug.
     *
     * @param string $slug
     * @return VoucherType|null
     */
    public function getBySlug(string $slug): ?VoucherType
    {
        return $this->model->findBySlug($slug);
    }
}
