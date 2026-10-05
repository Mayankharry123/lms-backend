<?php

/**
 * PurchaseOrderItem
 * -----------------------------------------
 * One purchase order line. The line amount is calculated before it is saved.
 *
 * @package App\Models
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-10-05
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    protected $table = 'purchase_order_items';

    protected $fillable = [
        'purchase_order_id',
        'description',
        'hsn_sac',
        'city',
        'qty',
        'rate',
        'amount',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'qty' => 'float',
        'rate' => 'float',
        'amount' => 'float',
    ];

    /**
     * The purchase order this line belongs to.
     */
    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }
}
