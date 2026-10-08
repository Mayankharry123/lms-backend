<?php

namespace App\Support;

use App\Models\Priority;
use App\Models\User;
use App\Support\UserAccessScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardFilters
{
    /**
     * Parse dashboard filter params from the request.
     *
     * @return array{
     *     date_from: ?string,
     *     date_to: ?string,
     *     organisation_ids: array<int>,
     *     department_ids: array<int>,
     *     user_ids: array<int>,
     *     assign_to: ?int,
     *     assign_by: ?int,
     *     priority: ?string
     * }
     */
    public static function fromRequest(Request $request): array
    {
        $organisationIds = self::normalizeIds(
            $request->input('organisation_ids', $request->input('organisation_id', []))
        );
        $departmentIds = self::normalizeIds(
            $request->input('department_ids', $request->input('department_id', []))
        );
        $userIds = self::normalizeIds(
            $request->input('user_ids', $request->input('user_id', []))
        );

        $priority = $request->query('priority');
        if (is_string($priority) && strtolower(trim($priority)) === 'all') {
            $priority = null;
        }

        $filters = [
            'date_from' => $request->query('date_from', $request->query('from_date')) ?: null,
            'date_to' => $request->query('date_to', $request->query('to_date')) ?: null,
            'organisation_ids' => $organisationIds,
            'department_ids' => $departmentIds,
            'user_ids' => $userIds,
            'assign_to' => self::normalizeIds($request->input('assign_to', []))[0] ?? null,
            'assign_by' => self::normalizeIds($request->input('assign_by', []))[0] ?? null,
            'priority' => $priority ?: null,
            'operation_status_id' => $request->input('operation_status_id') ? (int) $request->input('operation_status_id') : null,
            'finance_status_id' => $request->input('finance_status_id') ? (int) $request->input('finance_status_id') : null,
            'brief_id' => $request->input('brief_id') ? (int) $request->input('brief_id') : null,
            'planner_id' => $request->input('planner_id') ? (int) $request->input('planner_id') : null,
            'voucher_type_id' => $request->input('voucher_type_id') ? (int) $request->input('voucher_type_id') : null,
            'brand_id' => $request->input('brand_id') ? (int) $request->input('brand_id') : null,
            'month' => $request->input('month') ?: null,
            'created_by' => $request->input('created_by') ? (int) $request->input('created_by') : null,
            'status' => $request->input('status') !== null && $request->input('status') !== '' ? (string) $request->input('status') : null,
        ];

        $user = auth()->user();
        if ($user) {
            $filters = UserAccessScope::resolveOrganisationFilter($user, $filters);
        }

        return $filters;
    }

    /**
     * @return array<int>
     */
    private static function normalizeIds($values): array
    {
        if (!is_array($values)) {
            $values = explode(',', (string) $values);
        }

        $ids = [];
        foreach ($values as $value) {
            if (!is_scalar($value) || !is_numeric($value)) {
                continue;
            }

            $id = (int) $value;
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }

    public static function applyDateFilter(
        Builder $query,
        array $filters,
        string $column = 'created_at'
    ): Builder {
        if (!empty($filters['date_from'])) {
            $query->whereDate($column, '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate($column, '<=', $filters['date_to']);
        }

        return $query;
    }

    /**
     * @param array<int, string> $columns
     */
    public static function applyOrganisationUserFilter(
        Builder $query,
        array $filters,
        array $columns,
        ?string $table = null
    ): Builder {
        if (empty($filters['organisation_ids'])) {
            return $query->whereRaw('0 = 1');
        }

        $userIds = self::getOrganisationUserIds($filters['organisation_ids']);
        if (empty($userIds)) {
            return $query->whereRaw('0 = 1');
        }

        $query->where(function (Builder $builder) use ($columns, $userIds, $table) {
            foreach ($columns as $index => $column) {
                $qualifiedColumn = $table ? "{$table}.{$column}" : $column;

                if ($index === 0) {
                    $builder->whereIn($qualifiedColumn, $userIds);
                    continue;
                }

                $builder->orWhereIn($qualifiedColumn, $userIds);
            }
        });

        $user = auth()->user();
        if ($user) {
            $ancestorIds = UserAccessScope::getAncestorIds($user);
            if (!empty($ancestorIds)) {
                $createdByCol = $table ? "{$table}.{$columns[0]}" : $columns[0];
                $assignedToCol = isset($columns[1]) ? ($table ? "{$table}.{$columns[1]}" : $columns[1]) : null;

                $query->where(function ($q) use ($ancestorIds, $user, $createdByCol, $assignedToCol) {
                    $q->whereNotIn($createdByCol, $ancestorIds);
                    if ($assignedToCol) {
                        $descendantIds = UserAccessScope::getStrictDescendantIds($user);
                        $q->orWhereIn($assignedToCol, $descendantIds);
                    }
                });
            }
        }

        return $query;
    }

    public static function applyLeadPriorityFilter(
        Builder $query,
        array $filters,
        ?string $table = null
    ): Builder {
        if (empty($filters['priority'])) {
            return $query;
        }

        $priorityName = trim((string) $filters['priority']);
        $priorityId = Priority::query()->where('name', $priorityName)->value('id');

        if (!$priorityId) {
            return $query->whereRaw('0 = 1');
        }

        $column = $table ? "{$table}.priority_id" : 'priority_id';

        return $query->where($column, $priorityId);
    }

    public static function applyLeadDashboardFilters(
        Builder $query,
        array $filters,
        ?string $table = 'leads'
    ): Builder {
        self::applyDateFilter($query, $filters, "{$table}.created_at");
        self::applyLeadOrganisationFilter(
            $query,
            $filters,
            ['created_by', 'current_assign_user'],
            $table
        );
        self::applyLeadPriorityFilter($query, $filters, $table);

        return $query;
    }

    public static function applyPendingLeadDashboardFilters(
        Builder $query,
        array $filters,
        ?string $table = 'leads'
    ): Builder {
        // Pending leads should be filtered by when they were last updated (assigned), not just created
        self::applyDateFilter($query, $filters, "{$table}.updated_at");
        self::applyLeadOrganisationFilter(
            $query,
            $filters,
            ['created_by', 'current_assign_user'],
            $table
        );
        self::applyLeadPriorityFilter($query, $filters, $table);

        return $query;
    }

    public static function applyLeadOrganisationFilter(
        Builder $query,
        array $filters,
        array $columns,
        ?string $table = null
    ): Builder {
        if (empty($filters['organisation_ids'])) {
            return $query->whereRaw('0 = 1');
        }

        $query->whereIn("{$table}.organisation_id", $filters['organisation_ids']);

        $user = auth()->user();
        if ($user) {
            $ancestorIds = UserAccessScope::getAncestorIds($user);
            if (!empty($ancestorIds)) {
                $createdByCol = $table ? "{$table}.{$columns[0]}" : $columns[0];
                $assignedToCol = isset($columns[1]) ? ($table ? "{$table}.{$columns[1]}" : $columns[1]) : null;

                $query->where(function ($q) use ($ancestorIds, $user, $createdByCol, $assignedToCol) {
                    $q->whereNotIn($createdByCol, $ancestorIds);
                    if ($assignedToCol) {
                        $descendantIds = UserAccessScope::getStrictDescendantIds($user);
                        $q->orWhereIn($assignedToCol, $descendantIds);
                    }
                });
            }
        }

        return $query;
    }

    public static function applyBriefDashboardFilters(
        Builder $query,
        array $filters,
        ?string $table = 'briefs'
    ): Builder {
        self::applyDateFilter($query, $filters, "{$table}.created_at");
        self::applyBriefOrganisationFilter(
            $query,
            $filters,
            ['created_by', 'assign_user_id'],
            $table
        );
        self::applyLeadPriorityFilter($query, $filters, $table);

        return $query;
    }

    public static function applyBriefOrganisationFilter(
        Builder $query,
        array $filters,
        array $columns,
        ?string $table = null
    ): Builder {
        if (empty($filters['organisation_ids'])) {
            return $query->whereRaw('0 = 1');
        }

        $query->whereHas('contactPerson', function ($q) use ($filters) {
            $q->whereIn('organisation_id', $filters['organisation_ids']);
        });

        $user = auth()->user();
        if ($user) {
            $ancestorIds = UserAccessScope::getAncestorIds($user);
            if (!empty($ancestorIds)) {
                $createdByCol = $table ? "{$table}.{$columns[0]}" : $columns[0];
                $assignedToCol = isset($columns[1]) ? ($table ? "{$table}.{$columns[1]}" : $columns[1]) : null;

                $query->where(function ($q) use ($ancestorIds, $user, $createdByCol, $assignedToCol) {
                    $q->whereNotIn($createdByCol, $ancestorIds);
                    if ($assignedToCol) {
                        $descendantIds = UserAccessScope::getStrictDescendantIds($user);
                        $q->orWhereIn($assignedToCol, $descendantIds);
                    }
                });
            }
        }

        return $query;
    }

    public static function applyMissCampaignDashboardFilters(
        Builder $query,
        array $filters,
        ?string $table = 'miss_campaigns'
    ): Builder {
        self::applyDateFilter($query, $filters, "{$table}.created_at");
        self::applyMissCampaignOrganisationFilter($query, $filters, $table);

        return $query;
    }

    public static function applyMissCampaignOrganisationFilter(
        Builder $query,
        array $filters,
        ?string $table = 'miss_campaigns'
    ): Builder {
        if (empty($filters['organisation_ids'])) {
            return $query->whereRaw('0 = 1');
        }

        $query->whereHas('lead', function ($q) use ($filters) {
            $q->whereIn('organisation_id', $filters['organisation_ids']);
        });

        $user = auth()->user();
        if ($user) {
            $ancestorIds = UserAccessScope::getAncestorIds($user);
            if (!empty($ancestorIds)) {
                $query->where(function ($q) use ($ancestorIds, $user, $table) {
                    $descendantIds = UserAccessScope::getStrictDescendantIds($user);
                    $q->whereNotIn("{$table}.assign_by", $ancestorIds)
                      ->orWhereIn("{$table}.assign_to", $descendantIds);
                });
            }
        }

        return $query;
    }

    public static function applyMeetingDashboardFilters(
        Builder $query,
        array $filters,
        ?string $table = 'meetings'
    ): Builder {
        self::applyDateFilter($query, $filters, "{$table}.meeting_start_date");

        if (empty($filters['organisation_ids'])) {
            return $query->whereRaw('0 = 1');
        }

        $userIds = self::getOrganisationUserIds($filters['organisation_ids']);
        if (empty($userIds)) {
            return $query->whereRaw('0 = 1');
        }

        return $query->whereHas('lead', function (Builder $leadQuery) use ($userIds) {
            $leadQuery->where(function (Builder $builder) use ($userIds) {
                $builder->whereIn('created_by', $userIds)
                    ->orWhereIn('current_assign_user', $userIds);
            });
        });
    }

    public static function applyUserOrganisationFilter(Builder $query, array $filters): Builder
    {
        if (empty($filters['organisation_ids'])) {
            return $query;
        }

        $userIds = self::getOrganisationUserIds($filters['organisation_ids']);
        if (empty($userIds)) {
            return $query->whereRaw('0 = 1');
        }

        return $query->whereIn('id', $userIds);
    }

    /**
     * @param array<int> $organisationIds
     * @return array<int>
     */
    public static function getOrganisationUserIds(array $organisationIds): array
    {
        if (empty($organisationIds)) {
            return [];
        }

        $pivotIds = DB::table('organisation_user')
            ->whereIn('organisation_id', $organisationIds)
            ->pluck('user_id');

        $legacyIds = User::query()
            ->whereIn('organisation_id', $organisationIds)
            ->whereNull('deleted_at')
            ->pluck('id');

        return $pivotIds
            ->merge($legacyIds)
            ->unique()
            ->values()
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
