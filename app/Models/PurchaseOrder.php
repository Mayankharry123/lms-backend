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
use Illuminate\Database\Eloquent\Builder;

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
        'sgst_rate',
        'sgst_amount',
        'cgst_rate',
        'cgst_amount',
        'igst_rate',
        'igst_amount',
        'tax_amount',
        'total_amount',
        'amount_in_words',
        'pdf_path',
        'created_by',
        'assign_to',
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
        'sgst_rate' => 'float',
        'sgst_amount' => 'float',
        'cgst_rate' => 'float',
        'cgst_amount' => 'float',
        'igst_rate' => 'float',
        'igst_amount' => 'float',
        'tax_amount' => 'float',
        'total_amount' => 'float',
        'created_by' => 'integer',
        'assign_to' => 'integer',
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
        'creator:id,name,email',
        'assignedTo:id,name,email',
    ];

    /**
     * Finance record this purchase order was raised from.
     */
    public function financeRecord()
    {
        return $this->belongsTo(FinanceRecord::class, 'finance_record_id');
    }

    /**
     * User who created this purchase order.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * User to whom this purchase order is assigned.
     */
    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assign_to');
    }

    /**
     * Paginate purchase orders for the list API.
     */
    public function paginateForList(int $perPage = 15, array $filters = [], ?User $user = null): LengthAwarePaginator
    {
        return $this->accessiblePurchaseOrdersQuery($filters, $user)
            ->with(self::LIST_RELATIONS)
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * Find one purchase order for the detail API.
     */
    public function findForDetail(int $id, ?User $user = null): ?self
    {
        return $this->accessiblePurchaseOrdersQuery([], $user)
            ->with(self::LIST_RELATIONS)
            ->find($id);
    }

    /**
     * Restrict purchase orders to finance records visible to the current user.
     *
     * @param array<string, mixed> $filters
     */
    private function accessiblePurchaseOrdersQuery(array $filters, ?User $user): Builder
    {
        $query = $this->newQuery();

        if (!empty($filters['created_by'])) {
            $query->where('purchase_orders.created_by', (int) $filters['created_by']);
        }

        if (!empty($filters['assign_to'])) {
            $assignToId = (int) $filters['assign_to'];
            $query->where(function (Builder $q) use ($assignToId) {
                $q->where('purchase_orders.assign_to', $assignToId)
                  ->orWhereHas('financeRecord', function (Builder $fq) use ($assignToId) {
                      $fq->where('finance_records.assign_to', $assignToId);
                  });
            });
        }

        return $query
            ->whereHas('financeRecord', function (Builder $financeQuery) use ($filters, $user) {
                $financeRecord = new FinanceRecord();

                $financeQuery->accessibleToUser($user)
                    ->whereNull('finance_records.deleted_at');
                $financeRecord->applyOrganisationValidation($financeQuery, $user, $filters);
                $financeRecord->applyDepartmentFilter($financeQuery, $filters);

                $userIds = $this->normalizeIds($filters['user_ids'] ?? $filters['user_id'] ?? []);
                if ($userIds !== []) {
                    $financeQuery->where(function (Builder $userQuery) use ($userIds) {
                        $userQuery->whereIn('finance_records.assign_to', $userIds)
                            ->orWhereIn('finance_records.assign_by', $userIds)
                            ->orWhereHas('planner', function (Builder $plannerQuery) use ($userIds) {
                                $plannerQuery->whereIn('created_by', $userIds);
                            })
                            ->orWhereHas('brief', function (Builder $briefQuery) use ($userIds) {
                                $briefQuery->whereIn('created_by', $userIds)
                                    ->orWhereIn('assign_user_id', $userIds);
                            });
                    });
                }

                if (!empty($filters['assign_by'])) {
                    $financeQuery->where(
                        'finance_records.assign_by',
                        (int) $filters['assign_by']
                    );
                }
            });
    }

    /**
     * @return array<int>
     */
    private function normalizeIds($values): array
    {
        if (!is_array($values)) {
            $values = explode(',', (string) $values);
        }

        $ids = [];
        foreach ($values as $value) {
            if (!is_scalar($value) || !is_numeric($value)) {
                continue;
            }

            $id = (int) $value;
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
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
