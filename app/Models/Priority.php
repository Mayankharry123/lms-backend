<?php

namespace App\Models;

class Priority extends BaseModel
{
    protected $table = 'priorities';

    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'call_status',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'call_status' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public static function findForCallStatus(int $callStatusId): ?self
    {
        foreach (self::all() as $priority) {
            $callStatuses = is_string($priority->call_status)
                ? json_decode($priority->call_status, true)
                : $priority->call_status;

            if (is_array($callStatuses) && in_array($callStatusId, $callStatuses)) {
                return $priority;
            }
        }

        return null;
    }

    /**
     * Get the call statuses associated with this priority.
     */
    public function callStatuses()
    {
        return $this->belongsToMany(
            CallStatus::class,
            'priorities',
            'id',
            'id'
        )->whereRaw('FIND_IN_SET(call_statuses.id, priorities.call_status)');
    }
}
