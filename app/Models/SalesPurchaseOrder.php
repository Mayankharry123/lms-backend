<?php

/**
 * SalesPurchaseOrder
 * -----------------------------------------
 * Sales Purchase Order header model.
 * Includes brief_id, planner_id, assign_to, created_by, finance_status_id,
 * line items (order details), and PO file upload.
 *
 * @package App\Models
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-15
 */

namespace App\Models;

use App\Traits\HandlesFileUploads;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SalesPurchaseOrder extends BaseModel
{
    use HandlesFileUploads;

    public const PO_SERIES = 'PO/Client';

    protected $table = 'sales_purchase_orders';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }

            if (empty($model->po_number)) {
                $model->po_number = static::generatePoNumber($model->po_date ? Carbon::parse($model->po_date) : null);
            }
        });
    }

    protected $fillable = [
        'uuid',
        'po_number',
        'po_date',
        'brief_id',
        'planner_id',
        'brand_id',
        'assign_to',
        'created_by',
        'finance_status_id',
        'company_name',
        'client_name',
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
        'campaign',
        'period',
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
        'po_file',
        'status',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'po_date' => 'date',
        'brief_id' => 'integer',
        'planner_id' => 'integer',
        'brand_id' => 'integer',
        'assign_to' => 'integer',
        'created_by' => 'integer',
        'finance_status_id' => 'integer',
        'subtotal' => 'float',
        'sgst_rate' => 'float',
        'sgst_amount' => 'float',
        'cgst_rate' => 'float',
        'cgst_amount' => 'float',
        'igst_rate' => 'float',
        'igst_amount' => 'float',
        'tax_amount' => 'float',
        'total_amount' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Accessors to append to array/json form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'po_file_url',
        'created_at_formatted',
        'updated_at_formatted',
        'created_at_human',
        'updated_at_human',
    ];

    /**
     * Relations for list API.
     *
     * @var array<int, string>
     */
    public const LIST_RELATIONS = [
        'brief:id,name',
        'planner:id,created_by',
        'planner.creator:id,name',
        'brand:id,name,slug',
        'creator:id,name,email',
        'assignedTo:id,name,email',
        'financeStatus:id,name',
        'items',
    ];

    /**
     * Brief associated with this sales purchase order.
     */
    public function brief(): BelongsTo
    {
        return $this->belongsTo(Brief::class, 'brief_id');
    }

    /**
     * Planner associated with this sales purchase order.
     */
    public function planner(): BelongsTo
    {
        return $this->belongsTo(Planner::class, 'planner_id');
    }

    /**
     * Brand associated with this sales purchase order.
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    /**
     * User to whom the sales purchase order is assigned.
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assign_to');
    }

    /**
     * User who created the sales purchase order.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Finance status of this sales purchase order.
     */
    public function financeStatus(): BelongsTo
    {
        return $this->belongsTo(FinanceStatus::class, 'finance_status_id');
    }

    /**
     * Line items on this sales purchase order.
     */
    public function items(): HasMany
    {
        return $this->hasMany(SalesPurchaseOrderItem::class, 'sales_purchase_order_id');
    }

    /**
     * Get the full URL for the uploaded PO file.
     */
    public function getPoFileUrlAttribute(): ?string
    {
        if (empty($this->po_file)) {
            return null;
        }

        return $this->getFileUrl($this->po_file);
    }


    /**
     * Generate the next PO number format: PO/Client/{YY-YY}/{0001}
     * Example: PO/Client/26-27/0008
     */
    public static function generatePoNumber(?\DateTimeInterface $date = null): string
    {
        $fy = static::financialYearSuffix($date);
        $prefix = self::PO_SERIES . '/' . $fy . '/';

        $latest = static::withTrashed()
            ->where('po_number', 'like', "{$prefix}%")
            ->orderByDesc('id')
            ->first();

        $sequence = 1;
        if ($latest && preg_match('/\/(\d+)$/', (string) $latest->po_number, $matches)) {
            $sequence = ((int) $matches[1]) + 1;
        }

        return sprintf('%s%04d', $prefix, $sequence);
    }

    /**
     * Indian financial year suffix, e.g. 26-27.
     */
    public static function financialYearSuffix(?\DateTimeInterface $date = null): string
    {
        $dt = $date ? Carbon::instance($date) : Carbon::now('Asia/Kolkata');
        $year = (int) $dt->format('y');
        $month = (int) $dt->format('n');

        if ($month < 4) {
            $start = $year - 1;
            $end = $year;
        } else {
            $start = $year;
            $end = $year + 1;
        }

        return sprintf('%02d-%02d', $start % 100, $end % 100);
    }

    /**
     * Paginate sales purchase orders for list API.
     */
    public function paginateForList(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = $this->newQuery()->with(self::LIST_RELATIONS);

        if (!empty($filters['search'])) {
            $search = '%' . trim((string) $filters['search']) . '%';
            $query->where(function (Builder $q) use ($search) {
                $q->where('po_number', 'like', $search)
                  ->orWhere('company_name', 'like', $search)
                  ->orWhere('client_name', 'like', $search)
                  ->orWhere('gst_number', 'like', $search);
            });
        }

        if (!empty($filters['brief_id'])) {
            $query->where('brief_id', (int) $filters['brief_id']);
        }

        if (!empty($filters['planner_id'])) {
            $query->where('planner_id', (int) $filters['planner_id']);
        }

        if (!empty($filters['brand_id'])) {
            $query->where('brand_id', (int) $filters['brand_id']);
        }

        if (!empty($filters['assign_to'])) {
            $query->where('assign_to', (int) $filters['assign_to']);
        }

        if (!empty($filters['created_by'])) {
            $query->where('created_by', (int) $filters['created_by']);
        }

        if (!empty($filters['finance_status_id'])) {
            $query->where('finance_status_id', (int) $filters['finance_status_id']);
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
     * Find one sales purchase order with relations for detail view.
     */
    public function findForDetail(int $id): ?self
    {
        return $this->newQuery()
            ->with([
                'brief',
                'planner.creator',
                'brand',
                'assignedTo',
                'creator',
                'financeStatus',
                'items',
            ])
            ->find($id);
    }

    /**
     * Latest sales purchase order whose number matches prefix.
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
     * Insert the sales purchase order header.
     */
    public function storeOrder(array $data): self
    {
        if (empty($data['uuid'])) {
            $data['uuid'] = (string) Str::uuid();
        }

        if (empty($data['po_number'])) {
            $poDate = !empty($data['po_date']) ? Carbon::parse($data['po_date']) : null;
            $data['po_number'] = static::generatePoNumber($poDate);
        }

        return $this->create($data);
    }

    /**
     * Update the sales purchase order header.
     */
    public function updateOrder(int $id, array $data): ?self
    {
        $order = $this->newQuery()->find($id);
        if (!$order) {
            return null;
        }

        $order->update($data);
        return $order->fresh([
            'brief',
            'planner.creator',
            'brand',
            'assignedTo',
            'creator',
            'financeStatus',
            'items',
        ]);
    }

    /**
     * Soft delete sales purchase order.
     */
    public function deleteOrder(int $id): bool
    {
        $order = $this->newQuery()->find($id);
        if (!$order) {
            return false;
        }

        $order->items()->delete();
        return (bool) $order->delete();
    }

    /**
     * Insert line items for a sales purchase order.
     *
     * @param array<int, array<string, mixed>> $items
     */
    public function storeItems(int $salesPurchaseOrderId, array $items): void
    {
        $order = ($this->getKey() == $salesPurchaseOrderId) ? $this : $this->newQuery()->find($salesPurchaseOrderId);
        if (!$order) {
            return;
        }

        foreach ($items as $item) {
            $order->items()->create($item);
        }
    }

    /**
     * Sync line items for update.
     *
     * @param array<int, array<string, mixed>> $items
     */
    public function syncItems(int $salesPurchaseOrderId, array $items): void
    {
        $order = ($this->getKey() == $salesPurchaseOrderId) ? $this : $this->newQuery()->find($salesPurchaseOrderId);
        if (!$order) {
            return;
        }

        $order->items()->forceDelete();

        foreach ($items as $item) {
            $order->items()->create($item);
        }
    }
}
