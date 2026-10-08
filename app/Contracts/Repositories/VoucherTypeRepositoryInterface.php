<?php

namespace App\Contracts\Repositories;

use App\Models\VoucherType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface VoucherTypeRepositoryInterface
{
    /**
     * Get all active voucher types as a collection.
     */
    public function getActiveList(?string $search = null): Collection;

    /**
     * Get active voucher types with pagination.
     */
    public function getPaginated(int $perPage = 15, ?string $search = null): LengthAwarePaginator;

    /**
     * Find a voucher type by ID.
     */
    public function getById(int $id): ?VoucherType;

    /**
     * Find a voucher type by UUID.
     */
    public function getByUuid(string $uuid): ?VoucherType;

    /**
     * Find a voucher type by slug.
     */
    public function getBySlug(string $slug): ?VoucherType;
}
