<?php
/**
 * BaseModel
 * 
 * @author Achal Sharma
 * @package App\Models
 * @version 1.0.1
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes as EloquentSoftDeletes;
use App\Traits\HasTimestamps;
use App\Traits\HasUuid;

class BaseModel extends Model
{
    use EloquentSoftDeletes, HasTimestamps, HasUuid;

    /**
     * Never expose soft-delete timestamp in API payloads.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'deleted_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'created_at_formatted',
        'updated_at_formatted',
        'created_at_human',
        'updated_at_human',
    ];

    /**
     * Get the table name
     */
    public static function getTableName(): string
    {
        return (new static)->getTable();
    }

    /**
     * Exclude soft-deleted rows (explicit for list/filter/export queries).
     */
    public function scopeNotDeleted($query)
    {
        return $query->whereNull($this->getTable() . '.deleted_at');
    }

    /**
     * Scope for active records
     */
    public function scopeActive($query)
    {
        if (in_array('status', $this->getFillable())) {
            return $query->where('status', 'active');
        }
        return $query;
    }

    /**
     * Get formatted attributes for API response
     */
    public function getApiAttributes(): array
    {
        return array_merge(
            $this->toArray(),
            [
                'uuid' => $this->uuid ?? null,
            ]
        );
    }
}
