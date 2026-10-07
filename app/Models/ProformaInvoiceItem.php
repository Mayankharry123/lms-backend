<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProformaInvoiceItem extends Model
{
    use SoftDeletes;

    protected $table = 'proforma_invoice_items';

    protected $fillable = [
        'proforma_invoice_id',
        'order_name',
        'hsn_sac',
        'city',
        'slot',
        'rate',
        'amount',
        'status',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'proforma_invoice_id' => 'integer',
        'slot' => 'float',
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
     * The proforma invoice this line belongs to.
     */
    public function proformaInvoice(): BelongsTo
    {
        return $this->belongsTo(ProformaInvoice::class, 'proforma_invoice_id');
    }
}
