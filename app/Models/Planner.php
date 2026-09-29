<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HandlesFileUploads;

class Planner extends BaseModel
{
    use HasFactory, SoftDeletes, HandlesFileUploads;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'planners';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'brief_id',
        'created_by',
        'planner_status_id',
        'status',
        'submitted_plan',
        'backup_plan',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'submitted_plan' => 'array', // Cast JSON to array
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relationship: A planner belongs to a brief.
     */
    public function brief()
    {
        return $this->belongsTo(Brief::class, 'brief_id');
    }

    /**
     * Relationship: A planner is created by a user.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relationship: A planner has a planner status.
     */
    public function plannerStatus()
    {
        return $this->belongsTo(PlannerStatus::class, 'planner_status_id');
    }

    /**
     * Scope: Get only active planners.
     */
    public function scopeActive($query)
    {
        return $query->where('status', '1');
    }

    /**
     * Scope: Get only deactivated planners.
     */
    public function scopeDeactivated($query)
    {
        return $query->where('status', '2');
    }

    /**
     * Scope: Get planners by brief ID.
     */
    public function scopeByBrief($query, $briefId)
    {
        return $query->where('brief_id', $briefId);
    }

    /**
     * Scope: Get planners created by a specific user.
     */
    public function scopeCreatedBy($query, $userId)
    {
        return $query->where('created_by', $userId);
    }

    /**
     * Scope: Filter planners accessible to the given user.
     */
    public function scopeAccessibleToUser($query, $user = null)
    {
        $user = $user ?? auth()->user();

        // If no user is authenticated, return empty query
        if (!$user) {
            return $query->whereRaw('0 = 1');
        }

        // Super Admin with organisation assignment may view all records (dashboard uses org filters).
        if (\App\Support\UserAccessScope::hasGlobalRecordAccess($user)) {
            return $query;
        }

        \App\Support\UserAccessScope::applyVisibleUserFilter(
            $query,
            $user,
            ['created_by'],
            $this->getTable()
        );

        return $query;
    }

    /**
     * Check if the planner has submitted plans.
     */
    public function hasSubmittedPlans(): bool
    {
        return !empty($this->submitted_plan) && is_array($this->submitted_plan) && count($this->submitted_plan) > 0;
    }

    /**
     * Check if the planner has a backup plan.
     */
    public function hasBackupPlan(): bool
    {
        return !empty($this->backup_plan);
    }

    /**
     * Get the count of submitted plan files.
     */
    public function getSubmittedPlanCount(): int
    {
        if ($this->hasSubmittedPlans()) {
            return count($this->submitted_plan);
        }
        return 0;
    }

    /** @param array<int, int> $briefIds
     * @return array{assigned_plans: int, average_assignment_days: float}
     */
    public static function getSummaryForBriefIds(array $briefIds): array
    {
        if ($briefIds === []) {
            return ['assigned_plans' => 0, 'average_assignment_days' => 0.0];
        }

        $plannerQuery = self::query()
            ->whereNull('planners.deleted_at')
            ->whereIn('planners.brief_id', $briefIds);

        $submittedQuery = \App\Support\PlannerMetrics::applySubmittedPlansScope(clone $plannerQuery);
        $averageAssignmentDays = $submittedQuery
            ->selectRaw(
                'AVG(' . \App\Support\PlannerMetrics::assignmentToSubmissionDaysSql() . ') as avg_days'
            )
            ->value('avg_days');

        return [
            'assigned_plans' => self::query()
                ->whereNull('deleted_at')
                ->whereIn('brief_id', $briefIds)
                ->count(),
            'average_assignment_days' => $averageAssignmentDays ? (float) $averageAssignmentDays : 0.0,
        ];
    }

    /**
     * Add a submitted plan file.
     */
    public function addSubmittedPlan($filePath): void
    {
        if (!is_array($this->submitted_plan)) {
            $this->submitted_plan = [];
        }

        if (count($this->submitted_plan) < 2) {
            $this->submitted_plan[] = $filePath;
            $this->save();
        }
    }

    /**
     * Remove a submitted plan file by index.
     */
    public function removeSubmittedPlan($index): void
    {
        if (is_array($this->submitted_plan) && isset($this->submitted_plan[$index])) {
            unset($this->submitted_plan[$index]);
            $this->submitted_plan = array_values($this->submitted_plan); // Re-index array
            $this->save();
        }
    }
}
