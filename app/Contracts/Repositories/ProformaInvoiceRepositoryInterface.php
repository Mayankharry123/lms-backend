<?php

namespace App\Contracts\Repositories;

use App\Models\ProformaInvoice;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ProformaInvoiceRepositoryInterface
{
    /**
     * Paginate proforma invoices for list API.
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator;

    /**
     * Find one proforma invoice with relations.
     */
    public function find(int $id): ?ProformaInvoice;

    /**
     * Latest proforma invoice matching a number prefix.
     */
    public function latestByNumberPrefix(string $like): ?ProformaInvoice;

    /**
     * Create the proforma invoice header.
     */
    public function create(array $data): ProformaInvoice;

    /**
     * Insert line items.
     *
     * @param array<int, array<string, mixed>> $items
     */
    public function createItems(ProformaInvoice $invoice, array $items): void;

    /**
     * Update the proforma invoice.
     */
    public function update(int $id, array $data): ?ProformaInvoice;

    /**
     * Sync line items for update.
     *
     * @param array<int, array<string, mixed>> $items
     */
    public function syncItems(ProformaInvoice $invoice, array $items): void;

    /**
     * Soft delete the proforma invoice.
     */
    public function delete(int $id): bool;
}
