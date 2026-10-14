<?php
/**
 * LoginLog
 * 
 * @author Achal Sharma
 * @package App\Models
 * @version 1.0.1
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
// Remove HasFactory if you're not using model factories
// use Illuminate\Database\Eloquent\Factories\HasFactory; 

class LoginLog extends Model
{
    use SoftDeletes; // Remove HasFactory trait if not using model factories

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'login_logs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'login_data',
        'login_time',
        'logout_time',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'login_data' => 'array',
        'login_time' => 'datetime',
        'logout_time' => 'datetime',
    ];

    /**
     * Get the user that owns the login log.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}