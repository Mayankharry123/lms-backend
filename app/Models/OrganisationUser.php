<?php
/**
 * OrganisationUser
 * 
 * @author Achal Sharma
 * @package App\Models
 * @version 1.0.1
 */
namespace App\Models;

use App\Traits\HasTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrganisationUser extends Model
{
    use HasFactory, HasTimestamps, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'organisation_user';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'organisation_id',
        'user_id',
    ];

    /**
     * Get the organisation for this assignment.
     */
    public function organisation(): BelongsTo
    {
        return $this->belongsTo(Organisation::class);
    }

    /**
     * Get the user for this assignment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
