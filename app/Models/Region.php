<?php
/**
 * Region
 * 
 * @author Achal Sharma
 * @package App\Models
 * @version 1.0.1
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'regions';

    /**
     * The attributes that are mass assignable.
     * Keep minimal to avoid schema mismatches.
     *
     * @var array
     */
    protected $fillable = [
        'name',
    ];

    /**
     * Get all of the brands for the Region.
     */
    public function brands()
    {
        return $this->hasMany(Brand::class);
    }
}
