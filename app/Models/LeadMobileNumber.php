<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadMobileNumber extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'lead_mobile_numbers';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'lead_id',
        'mobile_number',
        'is_primary',
        'is_verified',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_primary' => 'boolean',
        'is_verified' => 'boolean',
    ];

    /**
     * Get the lead that owns this mobile number.
     */
    public function lead()
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }

    public static function deleteForLead(int $leadId): void
    {
        self::where('lead_id', $leadId)->delete();
    }
}
