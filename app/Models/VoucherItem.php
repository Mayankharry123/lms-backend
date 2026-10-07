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
        'amount',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
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
}
