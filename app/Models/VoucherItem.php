<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoucherItem extends BaseModel
{
    protected $table = 'voucher_items';

    protected $fillable = [
        'uuid',
        'voucher_id',
        'date',
        'particular',
        'purpose',
        'mode',
        'payment_mode_type_id',
        'amount',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'payment_mode_type_id' => 'integer',
        'date' => 'date',
        'amount' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the voucher that owns this item.
     */
    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class, 'voucher_id');
    }

    /**
     * Get the payment mode type for this item.
     */
    public function paymentModeType(): BelongsTo
    {
        return $this->belongsTo(PaymentModeType::class, 'payment_mode_type_id');
    }
}
