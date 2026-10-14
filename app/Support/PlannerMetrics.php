<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

class PlannerMetrics
{
    /**
     * SQL expression: days from planner assignment (created_at) to first plan submission.
     */
    public static function assignmentToSubmissionDaysSql(): string
    {
        return 'DATEDIFF(
            COALESCE(
                (
                    SELECT MIN(ph.created_at)
                    FROM planner_histories ph
                    WHERE ph.planner_id = planners.id
                      AND ph.deleted_at IS NULL
                      AND ph.submitted_plan IS NOT NULL
                      AND JSON_LENGTH(ph.submitted_plan) > 0
                ),
                planners.updated_at
            ),
            planners.created_at
        )';
    }

    /**
     * Limit to planners that have at least one submitted plan file.
     *
     * @param Builder $query
     */
    public static function applySubmittedPlansScope(Builder $query): Builder
    {
        return $query
            ->whereNotNull('planners.submitted_plan')
            ->whereRaw('JSON_LENGTH(planners.submitted_plan) > 0');
    }
}
