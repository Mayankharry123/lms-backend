<?php
/**
 * BrandAgencyRelationship
 * 
 * @author Achal Sharma
 * @package App\Models
 * @version 1.0.1
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BrandAgencyRelationship extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     * Defines the database table name for this model
     *
     * @var string
     */
    protected $table = 'brand_agency_relationships';

    /**
     * The attributes that are mass assignable.
     * Only these fields can be mass assigned
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'agency_id',
        'brand_id',
    ];

    // Note: If you need to use this model directly, you can define
    // belongsTo relationships with Brand and Agency models here.

    /**
     * Get the Brand model for this relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    /**
     * Get the Agency model for this relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function agency()
    {
        // Use Agency model for the 'agency' table relationship
        return $this->belongsTo(Agency::class, 'agency_id'); 
    }
}