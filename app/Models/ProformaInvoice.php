<?php

/**
 * ProformaInvoice
 *
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-12
 */

namespace App\Models;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class ProformaInvoice extends BaseModel
{
    protected $table = 'proforma_invoices';

    protected $fillable = [
        'uuid',
        'pi_number',
        'invoice_date',
        'brand_id',
        'brief_id',
        'planner_id',
        'brand_name',
        'client_name',
        'gst_no',
        'state_code',
        'address',
        'po_no',
        'po_date',
        'period',
        'campaign',
        'kind_attn',
        'subtotal',
        'sgst_rate',
        'sgst_amount',
        'cgst_rate',
        'cgst_amount',
        'igst_rate',
        'igst_amount',
        'total_tax',
        'total_amount',
        'amount_in_words',
        'pi_path',
        'created_by',
        'assign_to',
        'status',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'brand_id' => 'integer',
        'brief_id' => 'integer',
        'planner_id' => 'integer',
        'created_by' => 'integer',
        'assign_to' => 'integer',
        'subtotal' => 'float',
        'sgst_rate' => 'float',
        'sgst_amount' => 'float',
        'cgst_rate' => 'float',
        'cgst_amount' => 'float',
        'igst_rate' => 'float',
        'igst_amount' => 'float',
        'total_tax' => 'float',
        'total_amount' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public const LIST_RELATIONS = [
        'brand:id,name,slug',
        'brief:id,name',
        'planner:id,created_by',
        'planner.creator:id,name',
        'creator:id,name,email',
        'assignedTo:id,name,email',
        'items',
    ];

    /**
     * Brand for this proforma invoice.
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    /**
     * Creator of the proforma invoice.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * User to whom the proforma invoice is assigned.
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assign_to');
    }

    /**
     * Brief for this proforma invoice.
     */
    public function brief(): BelongsTo
    {
        return $this->belongsTo(Brief::class, 'brief_id');
    }

    /**
     * Planner for this proforma invoice.
     */
    public function planner(): BelongsTo
    {
        return $this->belongsTo(Planner::class, 'planner_id');
    }

    /**
     * Line items on this proforma invoice.
     */
    public function items(): HasMany
    {
        return $this->hasMany(ProformaInvoiceItem::class, 'proforma_invoice_id');
    }

    /**
     * Paginate proforma invoices for list API.
     */
    public function paginateForList(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = $this->newQuery()->with(self::LIST_RELATIONS);

        if (!empty($filters['search'])) {
            $search = '%' . trim((string) $filters['search']) . '%';
            $query->where(function (Builder $q) use ($search) {
                $q->where('pi_number', 'like', $search)
                  ->orWhere('brand_name', 'like', $search)
                  ->orWhere('gst_no', 'like', $search);
            });
        }

        if (!empty($filters['brand_id'])) {
            $query->where('brand_id', (int) $filters['brand_id']);
        }

        if (!empty($filters['brief_id'])) {
            $query->where('brief_id', (int) $filters['brief_id']);
        }

        if (!empty($filters['planner_id'])) {
            $query->where('planner_id', (int) $filters['planner_id']);
        }

        if (!empty($filters['assign_to'])) {
            $query->where('assign_to', (int) $filters['assign_to']);
        }

        if (!empty($filters['created_by'])) {
            $query->where('created_by', (int) $filters['created_by']);
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', (string) $filters['status']);
        } else {
            $query->where('status', '!=', '15');
        }

        if (!empty($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * Find one proforma invoice with relations.
     */
    public function findForDetail(int $id): ?self
    {
        return $this->newQuery()
            ->with(['brand', 'creator', 'assignedTo', 'brief', 'planner.creator', 'items'])
            ->find($id);
    }

    /**
     * Latest proforma invoice whose number matches prefix.
     */
    public function latestByNumberPrefix(string $like): ?self
    {
        return $this->newQuery()
            ->withTrashed()
            ->where('pi_number', 'like', $like)
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Insert the proforma invoice header.
     */
    public function storeInvoice(array $data): self
    {
        return $this->create($data);
    }

    /**
     * Update the proforma invoice header.
     */
    public function updateInvoice(int $id, array $data): ?self
    {
        $invoice = $this->newQuery()->find($id);
        if (!$invoice) {
            return null;
        }

        $invoice->update($data);
        return $invoice->fresh(['brand', 'creator', 'assignedTo', 'brief', 'planner.creator', 'items']);
    }

    /**
     * Soft delete proforma invoice.
     */
    public function deleteInvoice(int $id): bool
    {
        $invoice = $this->newQuery()->find($id);
        if (!$invoice) {
            return false;
        }

        $invoice->items()->delete();
        return (bool) $invoice->delete();
    }

    /**
     * Insert line items.
     *
     * @param array<int, array<string, mixed>> $items
     */
    public function storeItems(int $proformaInvoiceId, array $items): void
    {
        foreach ($items as $item) {
            $this->items()->create(array_merge($item, [
                'proforma_invoice_id' => $proformaInvoiceId,
            ]));
        }
    }

    /**
     * Sync line items for update.
     *
     * @param array<int, array<string, mixed>> $items
     */
    public function syncItems(int $proformaInvoiceId, array $items): void
    {
        $invoice = $this->newQuery()->find($proformaInvoiceId);
        if (!$invoice) {
            return;
        }

        $invoice->items()->forceDelete();

        foreach ($items as $item) {
            $invoice->items()->create(array_merge($item, [
                'proforma_invoice_id' => $proformaInvoiceId,
            ]));
        }
    }
}
