<?php

/**
 * SalesPurchaseOrderRepositoryInterface
 * -----------------------------------------
 * Contract for SalesPurchaseOrder data access.
 *
 * @package App\Contracts\Repositories
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-15
 */

namespace App\Contracts\Repositories;

use App\Models\SalesPurchaseOrder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface SalesPurchaseOrderRepositoryInterface
{
    /**
     * Paginate sales purchase orders for list API.
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator;

    /**
     * Find one sales purchase order with relations.
     */
    public function find(int $id): ?SalesPurchaseOrder;

    /**
     * Latest sales purchase order matching a number prefix.
     */
    public function latestByNumberPrefix(string $like): ?SalesPurchaseOrder;

    /**
     * Create the sales purchase order header.
     */
    public function create(array $data): SalesPurchaseOrder;

    /**
     * Insert line items.
     *
     * @param array<int, array<string, mixed>> $items
     */
    public function createItems(SalesPurchaseOrder $order, array $items): void;

    /**
     * Update the sales purchase order.
     */
    public function update(int $id, array $data): ?SalesPurchaseOrder;

    /**
     * Sync line items for update.
     *
     * @param array<int, array<string, mixed>> $items
     */
    public function syncItems(SalesPurchaseOrder $order, array $items): void;

    /**
     * Soft delete the sales purchase order.
     */
    public function delete(int $id): bool;
}
