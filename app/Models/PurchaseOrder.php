<?php

/**
 * PurchaseOrder
 * -----------------------------------------
 * Purchase order header. Totals are calculated in the service and stored here.
 *
 * @package App\Models
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-05
 */

namespace App\Models;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PurchaseOrder extends BaseModel
{
    protected $table = 'purchase_orders';

    protected $fillable = [
        'uuid',
        'po_number',
        'publisher_id',
        'publisher_address_id',
        'finance_record_id',
        'publisher_name',
        'company_name',
        'primary_email',
        'gst_number',
        'pan_number',
        'account_holder_name',
        'account_number',
        'ifsc_code',
        'bank_name',
        'address',
        'city',
        'state',
        'country',
        'pincode',
        'subtotal',
        'tax_amount',
        'total_amount',
        'amount_in_words',
        'status',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'publisher_id' => 'integer',
        'publisher_address_id' => 'integer',
        'finance_record_id' => 'integer',
        'subtotal' => 'float',
        'tax_amount' => 'float',
        'total_amount' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relations for the purchase order list and detail.
     *
     * @var array<int, string>
     */
    public const LIST_RELATIONS = [
        'financeRecord.brief.costSheetStatus',
        'financeRecord.planner.creator',
        'financeRecord.financeStatus',
        'financeRecord.assignedBy',
        'financeRecord.assignedTo',
    ];

    /**
     * Finance record this purchase order was raised from.
     */
    public function financeRecord()
    {
        return $this->belongsTo(FinanceRecord::class, 'finance_record_id');
    }

    /**
     * Paginate purchase orders for the list API.
     */
    public function paginateForList(int $perPage = 15): LengthAwarePaginator
    {
        return $this->newQuery()
            ->with(self::LIST_RELATIONS)
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * Find one purchase order for the detail API.
     */
    public function findForDetail(int $id): ?self
    {
        return $this->newQuery()->with(self::LIST_RELATIONS)->find($id);
    }

    /**
     * Line items on this purchase order.
     */
    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class, 'purchase_order_id');
    }

    /**
     * Load an active publisher and bank row from the DGPlay database.
     *
     * @return array<string, mixed>|null
     */
    public function findPublisherWithBank(int $publisherId): ?array
    {
        return (new Publisher())->findActiveWithBank($publisherId);
    }

    /**
     * Latest purchase order whose number matches a financial-year prefix.
     */
    public function latestByNumberPrefix(string $like): ?self
    {
        return $this->newQuery()
            ->withTrashed()
            ->where('po_number', 'like', $like)
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Insert the purchase order header.
     */
    public function storeOrder(array $data): self
    {
        return $this->create($data);
    }

    /**
     * Insert the line items for a purchase order.
     *
     * @param array<int, array<string, mixed>> $items
     */
    public function storeItems(int $purchaseOrderId, array $items): void
    {
        foreach ($items as $item) {
            $this->items()->create(array_merge($item, [
                'purchase_order_id' => $purchaseOrderId,
            ]));
        }
    }
}
