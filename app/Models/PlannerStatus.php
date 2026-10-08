<?php

namespace App\Models;

class PlannerStatus extends BaseModel
{
    protected $table = 'planner_statuses';

    protected $fillable = [
        'uuid',
        'name',
        'slug',
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
    ];

    /**
     * Find the Plan Approved status by slug or name.
     */
    public static function findPlanApproved(): ?self
    {
        return static::where('slug', 'plan-approved')
            ->orWhere('name', 'Plan Approved')
            ->first();
    }
}
