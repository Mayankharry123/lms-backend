<?php

/**
 * SalesPurchaseOrderRepository
 * -----------------------------------------
 * Implementation of SalesPurchaseOrderRepositoryInterface.
 *
 * @package App\Repositories
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-15
 */

namespace App\Repositories;

use App\Contracts\Repositories\SalesPurchaseOrderRepositoryInterface;
use App\Models\SalesPurchaseOrder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SalesPurchaseOrderRepository implements SalesPurchaseOrderRepositoryInterface
{
    /**
     * @var SalesPurchaseOrder
     */
    protected SalesPurchaseOrder $model;

    /**
     * SalesPurchaseOrderRepository constructor.
     *
     * @param SalesPurchaseOrder $model
     */
    public function __construct(SalesPurchaseOrder $model)
    {
        $this->model = $model;
    }

    /**
     * Paginate sales purchase orders with filters.
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
     * Find a sales purchase order by ID for detail.
     *
     * @param int $id
     * @return SalesPurchaseOrder|null
     */
    public function find(int $id): ?SalesPurchaseOrder
    {
        return $this->model->findForDetail($id);
    }

    /**
     * Get latest sales purchase order matching number prefix.
     *
     * @param string $like
     * @return SalesPurchaseOrder|null
     */
    public function latestByNumberPrefix(string $like): ?SalesPurchaseOrder
    {
        return $this->model->latestByNumberPrefix($like);
    }

    /**
     * Create a new sales purchase order header.
     *
     * @param array $data
     * @return SalesPurchaseOrder
     */
    public function create(array $data): SalesPurchaseOrder
    {
        return $this->model->storeOrder($data);
    }

    /**
     * Create items for a sales purchase order.
     *
     * @param SalesPurchaseOrder $order
     * @param array $items
     * @return void
     */
    public function createItems(SalesPurchaseOrder $order, array $items): void
    {
        $order->storeItems((int) $order->id, $items);
    }

    /**
     * Update a sales purchase order by ID.
     *
     * @param int $id
     * @param array $data
     * @return SalesPurchaseOrder|null
     */
    public function update(int $id, array $data): ?SalesPurchaseOrder
    {
        return $this->model->updateOrder($id, $data);
    }

    /**
     * Synchronize items for a sales purchase order.
     *
     * @param SalesPurchaseOrder $order
     * @param array $items
     * @return void
     */
    public function syncItems(SalesPurchaseOrder $order, array $items): void
    {
        $order->syncItems((int) $order->id, $items);
    }

    /**
     * Delete a sales purchase order by ID.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        return $this->model->deleteOrder($id);
    }
}
