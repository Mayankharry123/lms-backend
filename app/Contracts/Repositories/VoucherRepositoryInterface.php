<?php

namespace App\Contracts\Repositories;

use App\Models\Voucher;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface VoucherRepositoryInterface
{
    /**
     * Get paginated vouchers with filters.
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator;

    /**
     * Find a single voucher by ID.
     */
    public function findById(int $id): ?Voucher;

    /**
     * Create a new voucher header record.
     */
    public function create(array $data): Voucher;

    /**
     * Update an existing voucher header record.
     */
    public function update(int $id, array $data): ?Voucher;

    /**
     * Soft delete a voucher by ID.
     */
    public function delete(int $id): bool;

    /**
     * Create items for a voucher.
     */
    public function createItems(Voucher $voucher, array $items): void;

    /**
     * Sync items for a voucher.
     */
    public function syncItems(Voucher $voucher, array $items): void;

    /**
     * Find the latest voucher matching a voucher number prefix.
     */
    public function getLatestByPrefix(string $prefix): ?Voucher;
}
