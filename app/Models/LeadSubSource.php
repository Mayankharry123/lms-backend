<?php
/**
 * LeadSubSource
 * 
 * @author Achal Sharma
 * @package App\Models
 * @version 1.0.1
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeadSubSource extends Model
{
    use SoftDeletes;

    protected $table = 'lead_sub_source';

    protected $fillable = [
        'lead_source_id',
        'name',
        'slug',
        'description',
        'status',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the parent lead source for this sub-source.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function leadSource()
    {
        return $this->belongsTo(LeadSource::class, 'lead_source_id');
    }
}
