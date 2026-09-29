<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class BriefAssignHistory extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'brief_assign_histories';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'brief_id',
        'assign_by_id',
        'assign_to_id',
        'brief_status_id',
        'brief_status_time',
        'submission_date',
        'comment',
        'reminder',
        'reminder_at',
        'reminder_before',
        'reminder_before_unit',
        'attachment',
        'status',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => '2', // Inactive by default
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'brief_status_time' => 'datetime',
        'submission_date' => 'datetime',
        'reminder' => 'boolean',
        'reminder_at' => 'datetime',
        'reminder_before' => 'integer',
        'attachment' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'deleted_at',
    ];

    /**
     * Get the brief that this history belongs to.
     */
    public function brief()
    {
        return $this->belongsTo(Brief::class, 'brief_id');
    }

    /**
     * Get the user who assigned this brief.
     */
    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assign_by_id');
    }

    /**
     * Get the user to whom the brief was assigned.
     */
    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assign_to_id');
    }

    /**
     * Get the brief status.
     */
    public function briefStatus()
    {
        return $this->belongsTo(BriefStatus::class, 'brief_status_id');
    }

    /**
     * Scope to get only active records.
     */
    public function scopeActive($query)
    {
        return $query->where('status', '1');
    }

    /**
     * Scope to get only inactive records.
     */
    public function scopeInactive($query)
    {
        return $query->where('status', '2');
    }

    /**
     * Check if the record is active.
     */
    public function isActive(): bool
    {
        return $this->status === '1';
    }

    /**
     * Check if the record is inactive.
     */
    public function isInactive(): bool
    {
        return $this->status === '2';
    }
}

