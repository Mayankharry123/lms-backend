<?php

/**
 * PermissionSeeder
 * 
 * @author Achal Sharma
 * @package Database\Seeders
 * @version 1.0.1
 */

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Seeds all LMS permissions (API + sidebar menu tree) and assigns them to roles.
 *
 * Run: php artisan db:seed --class=PermissionSeeder
 */
class PermissionSeeder extends Seeder
{
    /**
     * Standard CRUD actions seeded for each module.
     */
    protected array $actions = [
        'read' => ['display' => 'View', 'description' => 'View list and details'],
        'create' => ['display' => 'Create', 'description' => 'Create new records'],
        'update' => ['display' => 'Update', 'description' => 'Edit existing records'],
        'delete' => ['display' => 'Delete', 'description' => 'Delete records'],
    ];

    /**
     * Sidebar menu groups (parents). Children are module permissions linked below.
     */
    protected array $menuGroups = [
        [
            'name' => 'menu.dashboard',
            'display_name' => 'Dashboard',
            'description' => 'Dashboard and analytics',
            'url' => '/dashboard',
            'icon_text' => 'layout-dashboard',
            'order' => 1,
            'modules' => ['dashboard'],
        ],
        [
            'name' => 'menu.sales',
            'display_name' => 'Sales & CRM',
            'description' => 'Leads, briefs, campaigns, meetings',
            'url' => null,
            'icon_text' => 'users',
            'order' => 2,
            'modules' => ['leads', 'briefs', 'miss-campaigns', 'meetings'],
        ],
        [
            'name' => 'menu.planning',
            'display_name' => 'Planning',
            'description' => 'Planners and planner history',
            'url' => null,
            'icon_text' => 'calendar',
            'order' => 3,
            'modules' => ['planners', 'planner-histories', 'planner-statuses'],
        ],
        [
            'name' => 'menu.accounts',
            'display_name' => 'Accounts',
            'description' => 'Agencies, brands, and relationships',
            'url' => null,
            'icon_text' => 'building',
            'order' => 4,
            'modules' => [
                'agency-groups',
                'agencies',
                'agency-types',
                'brands',
                'brand-types',
                'brand-agency-relationships',
            ],
        ],
        [
            'name' => 'menu.master-data',
            'display_name' => 'Master Data',
            'description' => 'Reference and lookup data',
            'url' => null,
            'icon_text' => 'database',
            'order' => 5,
            'modules' => [
                'industries',
                'departments',
                'designations',
                'lead-sources',
                'lead-sub-sources',
                'lead-types',
                'media-types',
                'organisations',
                'regions',
                'countries',
                'states',
                'cities',
                'zones',
                'call-statuses',
                'statuses',
                'priorities',
                'brief-statuses',
            ],
        ],
        [
            'name' => 'menu.administration',
            'display_name' => 'Administration',
            'description' => 'Users, roles, permissions, and logs',
            'url' => null,
            'icon_text' => 'settings',
            'order' => 6,
            'modules' => ['users', 'roles', 'activity-logs'],
        ],
        [
            'name' => 'menu.profile',
            'display_name' => 'My Account',
            'description' => 'Profile and personal settings',
            'url' => '/profile',
            'icon_text' => 'user',
            'order' => 7,
            'modules' => ['profile'],
        ],
    ];

    /**
     * Extra permissions (not full CRUD) per module.
     */
    protected array $extraPermissions = [
        'dashboard' => [
            ['name' => 'dashboard.overview', 'display_name' => 'Dashboard – Overview Tab', 'description' => 'View overview tab, stats, and lists', 'url' => null],
            ['name' => 'dashboard.sales', 'display_name' => 'Dashboard – Sales Tab', 'description' => 'View sales dashboard tab', 'url' => null],
            ['name' => 'dashboard.planner', 'display_name' => 'Dashboard – Planner Tab', 'description' => 'View planner dashboard tab', 'url' => null],
            ['name' => 'dashboard.overview.stats', 'display_name' => 'Dashboard – Overview Stats', 'description' => 'View overview KPI stat cards', 'url' => null],
            ['name' => 'dashboard.overview.assignments', 'display_name' => 'Dashboard – Pending Assignments', 'description' => 'View pending assignments panel', 'url' => null],
            ['name' => 'dashboard.overview.meetings', 'display_name' => 'Dashboard – Meetings', 'description' => 'View meetings panel', 'url' => null],
            ['name' => 'dashboard.charts.leads', 'display_name' => 'Dashboard – Leads Chart', 'description' => 'View total leads chart', 'url' => null],
            ['name' => 'dashboard.charts.pre-leads', 'display_name' => 'Dashboard – Pre Leads Chart', 'description' => 'View pre leads chart', 'url' => null],
            ['name' => 'dashboard.charts.briefs', 'display_name' => 'Dashboard – Briefs Chart', 'description' => 'View briefs chart', 'url' => null],
            ['name' => 'dashboard.charts.brief-budget', 'display_name' => 'Dashboard – Brief Budget Chart', 'description' => 'View brief budget chart', 'url' => null],
            ['name' => 'dashboard.charts.pipeline', 'display_name' => 'Dashboard – Sales Pipeline Chart', 'description' => 'View sales pipeline chart', 'url' => null],
            ['name' => 'dashboard.charts.brief-status', 'display_name' => 'Dashboard – Brief Status Chart', 'description' => 'View brief status chart', 'url' => null],
        ],
        'leads' => [
            ['name' => 'leads.assign', 'display_name' => 'Assign Leads', 'description' => 'Assign leads to users', 'url' => null],
        ],
        'briefs' => [
            ['name' => 'briefs.assign', 'display_name' => 'Assign Briefs', 'description' => 'Assign briefs to users', 'url' => null],
        ],
        'profile' => [],
        'permissions' => [],
        'notifications' => [],
    ];

    /**
     * Modules that only need read access (lookup / reference data).
     */
    protected array $readOnlyModules = [
        'call-statuses',
        'statuses',
        'countries',
        'states',
        'cities',
        'regions',
        'organisations',
        'lead-types',
        'media-types',
        'planner-statuses',
        'planner-histories',
        'brief-statuses',
        'activity-logs',
        'notifications',
    ];

    /**
     * Frontend route paths for sidebar links (read permission).
     */
    protected array $moduleUrls = [
        'dashboard' => '/dashboard',
        'leads' => '/leads',
        'briefs' => '/briefs',
        'miss-campaigns' => '/miss-campaigns',
        'meetings' => '/meetings',
        'planners' => '/planners',
        'planner-statuses' => '/planner-statuses',
        'agency-groups' => '/agency-groups',
        'agencies' => '/agencies',
        'agency-types' => '/agency-types',
        'brands' => '/brands',
        'brand-types' => '/brand-types',
        'brand-agency-relationships' => '/brand-agency-relationships',
        'industries' => '/industries',
        'departments' => '/departments',
        'designations' => '/designations',
        'lead-sources' => '/lead-sources',
        'lead-sub-sources' => '/lead-sub-sources',
        'lead-types' => '/lead-types',
        'media-types' => '/media-types',
        'organisations' => '/organisations',
        'regions' => '/regions',
        'countries' => '/countries',
        'states' => '/states',
        'cities' => '/cities',
        'zones' => '/zones',
        'priorities' => '/priorities',
        'brief-statuses' => '/brief-statuses',
        'users' => '/users',
        'roles' => '/roles',
        'profile' => '/profile',
    ];

    protected array $permissionIdByName = [];
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $now = now();

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('permission_role')->truncate();
        DB::table('permission_user')->truncate();
        DB::table('permissions')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->seedStandalonePermissions($now);
        $this->seedMenuAndModulePermissions($now);
        $this->assignPermissionsToRoles();
    }

    /**
     * Global / system permissions (not tied to a menu group).
     */
    protected function seedStandalonePermissions($now): void
    {
        $standalone = [
            [
                'name' => 'admin.access',
                'display_name' => 'Admin Access',
                'description' => 'Full administrative access to the system',
                'url' => null,
                'icon_text' => 'shield',
                'order' => 0,
            ],
        ];

        foreach ($standalone as $permission) {
            $this->insertPermission($permission, null, $now);
        }

        $this->seedModulePermissions('notifications', null, $now, ['read', 'update']);
    }

    /**
     * Seed sidebar parents and module CRUD permissions.
     */
    protected function seedMenuAndModulePermissions($now): void
    {
        foreach ($this->menuGroups as $group) {
            $parentId = $this->insertPermission([
                'name' => $group['name'],
                'display_name' => $group['display_name'],
                'description' => $group['description'],
                'url' => $group['url'],
                'icon_text' => $group['icon_text'],
                'order' => $group['order'],
            ], null, $now);

            foreach ($group['modules'] as $index => $module) {
                $this->seedModulePermissions($module, $parentId, $now, null, $index);
            }
        }
    }

    /**
     * Seed CRUD (+ extras) for one module under a menu parent.
     */
    protected function seedModulePermissions(
        string $module,
        ?int $parentId,
        $now,
        ?array $onlyActions = null,
        int $orderOffset = 0
    ): void {
        $actions = $onlyActions ?? (
            in_array($module, $this->readOnlyModules, true)
                ? ['read']
                : array_keys($this->actions)
        );

        $moduleTitle = $this->moduleDisplayName($module);
        $baseOrder = ($orderOffset + 1) * 10;

        foreach ($actions as $i => $action) {
            $name = "{$module}.{$action}";
            $actionMeta = $this->actions[$action] ?? ['display' => ucfirst($action), 'description' => ucfirst($action)];

            $this->insertPermission([
                'name' => $name,
                'display_name' => "{$moduleTitle} – {$actionMeta['display']}",
                'description' => "{$actionMeta['description']} for {$moduleTitle}",
                'url' => $action === 'read' ? ($this->moduleUrls[$module] ?? null) : null,
                'icon_text' => null,
                'order' => $baseOrder + $i,
            ], $parentId, $now);
        }

        if (!empty($this->extraPermissions[$module])) {
            foreach ($this->extraPermissions[$module] as $j => $extra) {
                if (isset($extra['actions'])) {
                    continue;
                }
                $this->insertPermission(array_merge([
                    'order' => $baseOrder + count($actions) + $j,
                    'icon_text' => null,
                ], $extra), $parentId, $now);
            }
        }
    }

    /**
     * Insert a permission record and cache its ID.
     *
     * @param array $data
     * @param int|null $parentId
     * @param mixed $now
     * @return int
     */
    protected function insertPermission(array $data, ?int $parentId, $now): int
    {
        $name = $data['name'];

        DB::table('permissions')->insert([
            'uuid' => (string) Str::uuid(),
            'name' => $name,
            'slug' => Str::slug(str_replace('.', '-', $name)),
            'display_name' => $data['display_name'],
            'description' => $data['description'] ?? $data['display_name'],
            'url' => $data['url'] ?? null,
            'icon_file' => $data['icon_file'] ?? null,
            'icon_text' => $data['icon_text'] ?? null,
            'is_parent' => $parentId,
            'status' => $data['status'] ?? '1',
            'order' => $data['order'] ?? 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $id = (int) DB::table('permissions')->where('name', $name)->value('id');
        $this->permissionIdByName[$name] = $id;

        return $id;
    }

    /**
     * Convert module slug to human-readable display name.
     *
     * @param string $module
     * @return string
     */
    protected function moduleDisplayName(string $module): string
    {
        return str_replace('-', ' ', ucwords($module, '-'));
    }

    /**
     * Assign permissions to default roles (roles must already exist).
     */
    protected function assignPermissionsToRoles(): void
    {
        $roleIds = DB::table('roles')->pluck('id', 'name')->toArray();

        if (empty($roleIds)) {
            $this->command?->warn('No roles found. Run LaratrustDummySeeder or RoleSeeder first.');

            return;
        }

        $managerModules = [
            'dashboard', 'leads', 'briefs', 'miss-campaigns', 'meetings',
            'planners', 'planner-histories', 'planner-statuses',
            'agency-groups', 'agencies', 'agency-types', 'brands', 'brand-types',
            'brand-agency-relationships',
            'industries', 'departments', 'designations', 'lead-sources', 'lead-sub-sources',
            'lead-types', 'media-types', 'organisations', 'regions', 'countries',
            'states', 'cities', 'zones', 'call-statuses', 'statuses', 'priorities',
            'brief-statuses', 'notifications',
            'users', 'profile', 'roles',
        ];

        $userModules = [
            'dashboard', 'leads', 'briefs', 'meetings', 'planners',
            'agencies', 'brands', 'notifications', 'profile',
            'lead-sources', 'lead-sub-sources', 'countries', 'states', 'cities',
            'call-statuses', 'statuses', 'priorities', 'brief-statuses',
        ];

        $assign = function (array $names, string $roleName) use ($roleIds) {
            $roleId = $roleIds[$roleName] ?? null;
            if (!$roleId) {
                return;
            }

            foreach ($names as $name) {
                $permissionId = $this->permissionIdByName[$name] ?? null;
                if ($permissionId) {
                    DB::table('permission_role')->updateOrInsert(
                        ['permission_id' => $permissionId, 'role_id' => $roleId],
                        ['created_at' => now(), 'updated_at' => now()]
                    );
                }
            }
        };

        // Admin: every permission including menu parents and admin.access
        if (isset($roleIds['admin'])) {
            foreach ($this->permissionIdByName as $permissionId) {
                DB::table('permission_role')->updateOrInsert(
                    ['permission_id' => $permissionId, 'role_id' => $roleIds['admin']],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }
        }

        $managerPermissions = $this->resolveModulePermissions($managerModules, [
            'users' => ['read', 'create', 'update'],
            'roles' => ['read'],
            'leads' => ['read', 'create', 'update', 'assign'],
            'briefs' => ['read', 'create', 'update', 'assign'],
        ]);

        $userPermissions = $this->resolveModulePermissions($userModules, [
            'leads' => ['read', 'create', 'update'],
            'briefs' => ['read', 'create', 'update'],
            'meetings' => ['read', 'create', 'update'],
            'planners' => ['read', 'create', 'update'],
            'agencies' => ['read', 'create'],
            'brands' => ['read', 'create'],
            'notifications' => ['read', 'update'],
            'profile' => ['read', 'update'],
        ]);

        $userPermissions = array_merge($userPermissions, [
            'dashboard.overview',
            'dashboard.overview.stats',
            'dashboard.overview.assignments',
            'dashboard.overview.meetings',
            'dashboard.charts.leads',
            'dashboard.charts.pre-leads',
        ]);

        $assign($managerPermissions, 'manager');
        $assign($userPermissions, 'user');

        // Menu parent visibility for non-admin roles
        $menuParents = array_column($this->menuGroups, 'name');
        $assign(array_merge($menuParents, $managerPermissions), 'manager');
        $assign(array_merge(
            ['menu.dashboard', 'menu.sales', 'menu.planning', 'menu.profile'],
            $userPermissions
        ), 'user');
    }

    /**
     * Build permission name list from modules and optional action overrides.
     */
    protected function resolveModulePermissions(array $modules, array $actionOverrides = []): array
    {
        $names = [];

        foreach ($modules as $module) {
            if (isset($actionOverrides[$module])) {
                foreach ($actionOverrides[$module] as $action) {
                    $names[] = "{$module}.{$action}";
                }
                continue;
            }

            $actions = in_array($module, $this->readOnlyModules, true)
                ? ['read']
                : array_keys($this->actions);

            foreach ($actions as $action) {
                $names[] = "{$module}.{$action}";
            }

            if (!empty($this->extraPermissions[$module])) {
                foreach ($this->extraPermissions[$module] as $extra) {
                    if (!empty($extra['name']) && empty($extra['actions'])) {
                        $names[] = $extra['name'];
                    }
                }
            }
        }

        return array_values(array_unique($names));
    }
}