<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Upserts dashboard section/chart permissions without truncating existing data.
 *
 * Run: php artisan db:seed --class=DashboardChartPermissionSeeder
 */
class DashboardChartPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $parentId = (int) DB::table('permissions')->where('name', 'menu.dashboard')->value('id');

        if (!$parentId) {
            $this->command?->warn('menu.dashboard permission not found. Creating parent permission...');

            DB::table('permissions')->insert([
                'uuid' => (string) Str::uuid(),
                'name' => 'menu.dashboard',
                'slug' => 'menu-dashboard',
                'display_name' => 'Dashboard',
                'description' => 'Dashboard and analytics',
                'url' => '/dashboard',
                'icon_file' => null,
                'icon_text' => 'layout-dashboard',
                'is_parent' => null,
                'status' => '1',
                'order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $parentId = (int) DB::table('permissions')->where('name', 'menu.dashboard')->value('id');

            if ($parentId) {
                $roleIds = DB::table('roles')->pluck('id', 'name')->toArray();
                foreach (['admin', 'manager', 'user'] as $roleName) {
                    $roleId = $roleIds[$roleName] ?? null;
                    if ($roleId) {
                        DB::table('permission_role')->updateOrInsert(
                            ['permission_id' => $parentId, 'role_id' => $roleId],
                            ['created_at' => $now, 'updated_at' => $now]
                        );
                    }
                }
            }
        }

        if (!$parentId) {
            $this->command?->error('Unable to create menu.dashboard permission.');

            return;
        }

        $permissions = [
            ['name' => 'dashboard.overview', 'display_name' => 'Dashboard – Overview Tab', 'description' => 'View overview tab, stats, and lists', 'order' => 15],
            ['name' => 'dashboard.sales', 'display_name' => 'Dashboard – Sales Tab', 'description' => 'View sales dashboard tab', 'order' => 16],
            ['name' => 'dashboard.planner', 'display_name' => 'Dashboard – Planner Tab', 'description' => 'View planner dashboard tab', 'order' => 17],
            ['name' => 'dashboard.overview.stats', 'display_name' => 'Dashboard – Overview Stats', 'description' => 'View overview KPI stat cards', 'order' => 18],
            ['name' => 'dashboard.overview.assignments', 'display_name' => 'Dashboard – Pending Assignments', 'description' => 'View pending assignments panel', 'order' => 19],
            ['name' => 'dashboard.overview.meetings', 'display_name' => 'Dashboard – Meetings', 'description' => 'View meetings panel', 'order' => 20],
            ['name' => 'dashboard.charts.leads', 'display_name' => 'Dashboard – Leads Chart', 'description' => 'View total leads chart', 'order' => 21],
            ['name' => 'dashboard.charts.pre-leads', 'display_name' => 'Dashboard – Pre Leads Chart', 'description' => 'View pre leads chart', 'order' => 22],
            ['name' => 'dashboard.charts.briefs', 'display_name' => 'Dashboard – Briefs Chart', 'description' => 'View briefs chart', 'order' => 23],
            ['name' => 'dashboard.charts.brief-budget', 'display_name' => 'Dashboard – Brief Budget Chart', 'description' => 'View brief budget chart', 'order' => 24],
            ['name' => 'dashboard.charts.pipeline', 'display_name' => 'Dashboard – Sales Pipeline Chart', 'description' => 'View sales pipeline chart', 'order' => 25],
            ['name' => 'dashboard.charts.brief-status', 'display_name' => 'Dashboard – Brief Status Chart', 'description' => 'View brief status chart', 'order' => 26],
            ['name' => 'dashboard.operations', 'display_name' => 'Dashboard – Operations Tab', 'description' => 'View operations dashboard tab', 'order' => 27],
            ['name' => 'dashboard.finance', 'display_name' => 'Dashboard – Finance Tab', 'description' => 'View finance dashboard tab', 'order' => 28],
        ];

        $permissionIds = [];

        foreach ($permissions as $permission) {
            $existingId = DB::table('permissions')->where('name', $permission['name'])->value('id');

            if ($existingId) {
                DB::table('permissions')->where('id', $existingId)->update([
                    'display_name' => $permission['display_name'],
                    'description' => $permission['description'],
                    'is_parent' => $parentId,
                    'order' => $permission['order'],
                    'status' => '1',
                    'updated_at' => $now,
                ]);
                $permissionIds[] = (int) $existingId;
                continue;
            }

            DB::table('permissions')->insert([
                'uuid' => (string) Str::uuid(),
                'name' => $permission['name'],
                'slug' => Str::slug(str_replace('.', '-', $permission['name'])),
                'display_name' => $permission['display_name'],
                'description' => $permission['description'],
                'url' => null,
                'icon_file' => null,
                'icon_text' => null,
                'is_parent' => $parentId,
                'status' => '1',
                'order' => $permission['order'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $permissionIds[] = (int) DB::table('permissions')->where('name', $permission['name'])->value('id');
        }

        $roleIds = DB::table('roles')->pluck('id', 'name')->toArray();

        foreach (['admin', 'manager'] as $roleName) {
            $roleId = $roleIds[$roleName] ?? null;
            if (!$roleId) {
                continue;
            }

            foreach ($permissionIds as $permissionId) {
                DB::table('permission_role')->updateOrInsert(
                    ['permission_id' => $permissionId, 'role_id' => $roleId],
                    ['created_at' => $now, 'updated_at' => $now]
                );
            }
        }

        $overviewPermissionId = DB::table('permissions')->where('name', 'dashboard.overview')->value('id');
        $dashboardRoleIds = $overviewPermissionId
            ? DB::table('permission_role')->where('permission_id', $overviewPermissionId)->pluck('role_id')
            : collect();

        foreach (['Super Admin', 'admin', 'manager'] as $roleName) {
            if (!empty($roleIds[$roleName])) {
                $dashboardRoleIds->push($roleIds[$roleName]);
            }
        }

        $operationsFinanceIds = DB::table('permissions')
            ->whereIn('name', ['dashboard.operations', 'dashboard.finance'])
            ->pluck('id');

        foreach ($dashboardRoleIds->unique() as $roleId) {
            foreach ($operationsFinanceIds as $permissionId) {
                DB::table('permission_role')->updateOrInsert(
                    ['permission_id' => $permissionId, 'role_id' => $roleId],
                    ['created_at' => $now, 'updated_at' => $now]
                );
            }
        }

        $userRoleId = $roleIds['user'] ?? null;
        if ($userRoleId) {
            $userPermissionNames = [
                'dashboard.overview',
                'dashboard.overview.stats',
                'dashboard.overview.assignments',
                'dashboard.overview.meetings',
                'dashboard.charts.leads',
                'dashboard.charts.pre-leads',
            ];

            foreach ($userPermissionNames as $name) {
                $permissionId = DB::table('permissions')->where('name', $name)->value('id');
                if ($permissionId) {
                    DB::table('permission_role')->updateOrInsert(
                        ['permission_id' => $permissionId, 'role_id' => $userRoleId],
                        ['created_at' => $now, 'updated_at' => $now]
                    );
                }
            }
        }

        $this->command?->info('Dashboard section/chart permissions upserted.');
    }
}