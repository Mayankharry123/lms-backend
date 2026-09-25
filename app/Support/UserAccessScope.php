<?php

namespace App\Support;

use App\Models\User;

class UserAccessScope
{
    public static function isSuperAdmin(?User $user): bool
    {
        return $user !== null && $user->hasRole('Super Admin');
    }

    /**
     * Organisation IDs assigned to the user (pivot + legacy organisation_id).
     *
     * @return array<int>
     */
    public static function getAccessibleOrganisationIds(User $user): array
    {
        $pivotIds = $user->organisations()
            ->pluck('organisations.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $legacyIds = $user->organisation_id ? [(int) $user->organisation_id] : [];

        return array_values(array_unique(array_filter(array_merge($pivotIds, $legacyIds))));
    }

    /**
     * Global organisation access is never implicit.
     * Users must be assigned to organisation(s) to scope dashboard data.
     */
    public static function canAccessAllOrganisations(User $user): bool
    {
        return false;
    }

    /**
     * Super Admin with organisation assignment bypasses per-user visibility on records.
     * Super Admin without assignment is scoped to self + team (same as other users).
     */
    public static function hasGlobalRecordAccess(User $user): bool
    {
        return self::isSuperAdmin($user) && !empty(self::getAccessibleOrganisationIds($user));
    }

    /**
     * Primary / process organisation for multi-org users.
     */
    public static function getProcessOrganisationId(User $user): ?int
    {
        if ($user->organisation_id) {
            return (int) $user->organisation_id;
        }

        $accessible = self::getAccessibleOrganisationIds($user);

        return $accessible[0] ?? null;
    }

    /**
     * Resolve dashboard organisation filter for the authenticated user.
     * Users without assigned organisation(s) see only their own/team data.
     * Assigned users are limited to those organisation(s).
     *
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public static function resolveOrganisationFilter(User $user, array $filters): array
    {
        $accessible = self::getAccessibleOrganisationIds($user);
        $requested = array_values(array_filter(array_map('intval', $filters['organisation_ids'] ?? [])));

        if (empty($accessible)) {
            $filters['organisation_ids'] = [];

            return $filters;
        }

        if (!empty($requested)) {
            $filters['organisation_ids'] = empty($accessible)
                ? []
                : array_values(array_intersect($requested, $accessible));
        } else {
            $filters['organisation_ids'] = $accessible;
        }

        return $filters;
    }

    /**
     * Default org scope: all organisations assigned to the user.
     *
     * @param array<int> $accessible
     * @return array<int>
     */
    public static function defaultOrganisationScope(User $user, array $accessible): array
    {
        return array_values($accessible);
    }

    /**
     * User IDs whose leads/pre-leads/briefs the current user can see:
     * self + all descendant child users. Empty for Super Admin (no restriction).
     *
     * @return array<int>
     */
    public static function getVisibleUserIds(User $user): array
    {
        if (self::hasGlobalRecordAccess($user)) {
            return [];
        }

        $ids = [(int) $user->id];
        self::collectDescendantIds($user, $ids);

        return array_values(array_unique($ids));
    }

    /**
     * Get strict hierarchy descendants (self + children), completely ignoring 
     * Super Admin or Role bypasses. Used for strict Parent/Child validation.
     *
     * @return array<int>
     */
    public static function getStrictDescendantIds(User $user): array
    {
        $ids = [(int) $user->id];
        self::collectDescendantIds($user, $ids);

        return array_values(array_unique($ids));
    }

    /**
     * Get strict hierarchy descendants, BUT filtered by the user's assigned organisation(s).
     * This combines Parent/Child hierarchy with Multi-Tenancy (Organisation) separation.
     *
     * @return array<int>
     */
    public static function getStrictDescendantsInOrganisation(User $user): array
    {
        $descendantIds = self::getStrictDescendantIds($user);
        
        $userOrgIds = self::getAccessibleOrganisationIds($user);
        if (empty($userOrgIds)) {
            return [(int) $user->id];
        }

        $orgUserIds = \App\Support\DashboardFilters::getOrganisationUserIds($userOrgIds);
        
        if (empty($orgUserIds)) {
            return [(int) $user->id];
        }

        // Return the intersection: ONLY descendants who are ALSO in the same organization
        $combinedIds = array_intersect($descendantIds, $orgUserIds);
        
        // A user can always see themselves, even if the pivot table has an issue
        $combinedIds[] = (int) $user->id;
        
        return array_values(array_unique($combinedIds));
    }

    /**
     * @param array<int> $ids
     */
    private static function collectDescendantIds(User $user, array &$ids): void
    {
        $children = $user->children()->get(['users.id']);

        foreach ($children as $child) {
            $childId = (int) $child->id;
            if (in_array($childId, $ids, true)) {
                continue;
            }

            $ids[] = $childId;
            self::collectDescendantIds($child, $ids);
        }
    }

    /**
     * Get all ancestor IDs for a user.
     *
     * @return array<int>
     */
    public static function getAncestorIds(User $user): array
    {
        $ids = [];
        self::collectAncestorIds($user, $ids);

        return array_values(array_unique($ids));
    }

    /**
     * @param array<int> $ids
     */
    private static function collectAncestorIds(User $user, array &$ids): void
    {
        $parents = $user->parents()->get(['users.id']);

        foreach ($parents as $parent) {
            $parentId = (int) $parent->id;
            if (in_array($parentId, $ids, true)) {
                continue;
            }

            $ids[] = $parentId;
            self::collectAncestorIds($parent, $ids);
        }
    }

    /**
     * Apply visible-user constraint to a query on the given columns.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array<int, string> $columns
     */
    public static function applyVisibleUserFilter($query, User $user, array $columns, ?string $table = null): void
    {
        $visibleUserIds = self::getVisibleUserIds($user);

        if (empty($visibleUserIds)) {
            return;
        }

        $query->where(function ($builder) use ($columns, $visibleUserIds, $table) {
            foreach ($columns as $index => $column) {
                $qualified = $table ? "{$table}.{$column}" : $column;

                if ($index === 0) {
                    $builder->whereIn($qualified, $visibleUserIds);
                    continue;
                }

                $builder->orWhereIn($qualified, $visibleUserIds);
            }
        });
    }
}
