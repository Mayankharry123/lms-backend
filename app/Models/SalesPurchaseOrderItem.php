<?php

/**
 * SalesPurchaseOrderItem
 * -----------------------------------------
 * Line item for a sales purchase order.
 *
 * @package App\Models
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-15
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesPurchaseOrderItem extends Model
{
    use SoftDeletes;

    protected $table = 'sales_purchase_order_items';

    protected $fillable = [
        'sales_purchase_order_id',
        'order_name',
        'description',
        'hsn_sac',
        'city',
        'slot',
        'qty',
        'rate',
        'amount',
        'status',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'sales_purchase_order_id' => 'integer',
        'slot' => 'float',
        'qty' => 'float',
        'rate' => 'float',
        'amount' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * @var array<int, string>
     */
    protected $hidden = [
        'deleted_at',
    ];

    /**
     * The sales purchase order this item belongs to.
     */
    public function salesPurchaseOrder(): BelongsTo
    {
        return $this->belongsTo(SalesPurchaseOrder::class, 'sales_purchase_order_id');
    }
}
