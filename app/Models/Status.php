<?php

namespace App\Models;

class Status extends BaseModel
{
    protected $table = 'statuses';

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
        foreach (self::all() as $status) {
            $callStatuses = is_string($status->call_status)
                ? json_decode($status->call_status, true)
                : $status->call_status;

            if (is_array($callStatuses) && in_array($callStatusId, $callStatuses)) {
                return $status;
            }
        }

        return null;
    }

    /**
     * Get the call statuses associated with this status.
     */
    public function callStatuses()
    {
        return $this->belongsToMany(
            CallStatus::class,
            'statuses',
            'id',
            'id'
        )->whereRaw('FIND_IN_SET(call_statuses.id, statuses.call_status)');
    }
}
