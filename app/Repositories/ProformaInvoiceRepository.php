<?php

namespace App\Repositories;

use App\Contracts\Repositories\ProformaInvoiceRepositoryInterface;
use App\Models\ProformaInvoice;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProformaInvoiceRepository implements ProformaInvoiceRepositoryInterface
{
    protected ProformaInvoice $model;

    public function __construct(ProformaInvoice $model)
    {
        $this->model = $model;
    }

    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->model->paginateForList($perPage, $filters);
    }

    public function find(int $id): ?ProformaInvoice
    {
        return $this->model->findForDetail($id);
    }

    public function latestByNumberPrefix(string $like): ?ProformaInvoice
    {
        return $this->model->latestByNumberPrefix($like);
    }

    public function create(array $data): ProformaInvoice
    {
        return $this->model->storeInvoice($data);
    }

    public function createItems(ProformaInvoice $invoice, array $items): void
    {
        $invoice->storeItems((int) $invoice->id, $items);
    }

    public function update(int $id, array $data): ?ProformaInvoice
    {
        return $this->model->updateInvoice($id, $data);
    }

    public function syncItems(ProformaInvoice $invoice, array $items): void
    {
        $invoice->syncItems((int) $invoice->id, $items);
    }

    public function delete(int $id): bool
    {
        return $this->model->deleteInvoice($id);
    }
}
