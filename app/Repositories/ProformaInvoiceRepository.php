<?php

/**
 * ProformaInvoiceRepository
 *
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-12
 */

namespace App\Repositories;

use App\Contracts\Repositories\ProformaInvoiceRepositoryInterface;
use App\Models\ProformaInvoice;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProformaInvoiceRepository implements ProformaInvoiceRepositoryInterface
{
    /**
     * @var ProformaInvoice
     */
    protected ProformaInvoice $model;

    /**
     * ProformaInvoiceRepository constructor.
     *
     * @param ProformaInvoice $model
     */
    public function __construct(ProformaInvoice $model)
    {
        $this->model = $model;
    }

    /**
     * Paginate proforma invoices with filters.
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
     * Find a proforma invoice by ID for detail.
     *
     * @param int $id
     * @return ProformaInvoice|null
     */
    public function find(int $id): ?ProformaInvoice
    {
        return $this->model->findForDetail($id);
    }

    /**
     * Get latest proforma invoice matching number prefix.
     *
     * @param string $like
     * @return ProformaInvoice|null
     */
    public function latestByNumberPrefix(string $like): ?ProformaInvoice
    {
        return $this->model->latestByNumberPrefix($like);
    }

    /**
     * Create a new proforma invoice.
     *
     * @param array $data
     * @return ProformaInvoice
     */
    public function create(array $data): ProformaInvoice
    {
        return $this->model->storeInvoice($data);
    }

    /**
     * Create items for a proforma invoice.
     *
     * @param ProformaInvoice $invoice
     * @param array $items
     * @return void
     */
    public function createItems(ProformaInvoice $invoice, array $items): void
    {
        $invoice->storeItems((int) $invoice->id, $items);
    }

    /**
     * Update a proforma invoice by ID.
     *
     * @param int $id
     * @param array $data
     * @return ProformaInvoice|null
     */
    public function update(int $id, array $data): ?ProformaInvoice
    {
        return $this->model->updateInvoice($id, $data);
    }

    /**
     * Synchronize items for a proforma invoice.
     *
     * @param ProformaInvoice $invoice
     * @param array $items
     * @return void
     */
    public function syncItems(ProformaInvoice $invoice, array $items): void
    {
        $invoice->syncItems((int) $invoice->id, $items);
    }

    /**
     * Delete a proforma invoice by ID.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        return $this->model->deleteInvoice($id);
    }
}
