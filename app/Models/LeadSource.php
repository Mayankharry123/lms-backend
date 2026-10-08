<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeadSource extends Model
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     * Matches the table name defined in migration.
     *
     * @var string
     */
    protected $table = 'lead_source';
    
    protected $fillable = [
        'name',
        'slug',
        'description',
        'status',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    public function leads()
    {
        return $this->hasMany(Lead::class, 'source_id');
    }
}
