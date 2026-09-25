<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeadAssignHistory extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'lead_assign_histories';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'lead_id',
        'assign_user_id',
        'current_user_id',
        'priority_id',
        'lead_status_id',
        'call_status_id',
        'last_call_status_date_time',
        'lead_comment',
        'reminder',
        'reminder_at',
        'reminder_before',
        'reminder_before_unit',
        'meeting_date',
        'meeting_time',
        'status',
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
        'meeting_date' => 'date',
        'last_call_status_date_time' => 'datetime',
        'reminder' => 'boolean',
        'reminder_at' => 'datetime',
        'reminder_before' => 'integer',
    ];

    /**
     * Get the lead that this history record belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function lead()
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }

    /**
     * Get the user who the lead was assigned to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assign_user_id');
    }

    /**
     * Get the user who made the assignment.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function currentUser()
    {
        return $this->belongsTo(User::class, 'current_user_id');
    }

    /**
     * Get the priority of the lead at the time of assignment.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function priority()
    {
        return $this->belongsTo(Priority::class, 'priority_id');
    }

    /**
     * Get the status of the lead at the time of assignment.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function status()
    {
        return $this->belongsTo(Status::class, 'lead_status_id');
    }

    /**
     * Get the call status of the lead at the time of assignment.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function callStatus()
    {
        return $this->belongsTo(CallStatus::class, 'call_status_id');
    }
}

