<?php

/**
 * PurchaseOrder Repository Interface
 * -----------------------------------------
 * Database operations for purchase orders and the existing publisher record.
 *
 * @package App\Contracts\Repositories
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-05
 */

namespace App\Contracts\Repositories;

use App\Models\PurchaseOrder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PurchaseOrderRepositoryInterface
{
    /**
     * Load an active publisher and bank row by publisher id.
     *
     * @return array<string, mixed>|null
     */
    public function findPublisherWithBank(int $publisherId): ?array;

    /**
     * Latest purchase order number for a financial-year prefix.
     */
    public function latestByNumberPrefix(string $like): ?PurchaseOrder;

    /**
     * Insert the purchase order header.
     */
    public function create(array $data): PurchaseOrder;

    /**
     * Insert the line items for a purchase order.
     *
     * @param array<int, array<string, mixed>> $items
     */
    public function createItems(PurchaseOrder $purchaseOrder, array $items): void;

    /**
     * Paginate purchase orders for the list API.
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    /**
     * Find one purchase order for the detail API.
     */
    public function find(int $id): ?PurchaseOrder;
}
