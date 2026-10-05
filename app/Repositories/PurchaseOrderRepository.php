<?php

/**
 * PurchaseOrder Repository
 * -----------------------------------------
 * Reads the existing publisher database and writes purchase orders.
 * Totals are calculated in the service before these methods are called.
 *
 * @package App\Repositories
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-05
 */

namespace App\Repositories;

use App\Contracts\Repositories\PurchaseOrderRepositoryInterface;
use App\Models\PurchaseOrder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PurchaseOrderRepository implements PurchaseOrderRepositoryInterface
{
    protected PurchaseOrder $model;

    /**
     * Inject the purchase order model.
     */
    public function __construct(PurchaseOrder $model)
    {
        $this->model = $model;
    }

    /**
     * Load an active publisher and bank row by publisher id.
     *
     * @return array<string, mixed>|null
     */
    public function findPublisherWithBank(int $publisherId): ?array
    {
        return $this->model->findPublisherWithBank($publisherId);
    }

    /**
     * Latest purchase order number for a financial-year prefix.
     */
    public function latestByNumberPrefix(string $like): ?PurchaseOrder
    {
        return $this->model->latestByNumberPrefix($like);
    }

    /**
     * Insert the purchase order header.
     */
    public function create(array $data): PurchaseOrder
    {
        return $this->model->storeOrder($data);
    }

    /**
     * Insert the line items for a purchase order.
     *
     * @param array<int, array<string, mixed>> $items
     */
    public function createItems(PurchaseOrder $purchaseOrder, array $items): void
    {
        $purchaseOrder->storeItems((int) $purchaseOrder->id, $items);
    }

    /**
     * Paginate purchase orders for the list API.
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->paginateForList($perPage);
    }

    /**
     * Find one purchase order for the detail API.
     */
    public function find(int $id): ?PurchaseOrder
    {
        return $this->model->findForDetail($id);
    }
}
