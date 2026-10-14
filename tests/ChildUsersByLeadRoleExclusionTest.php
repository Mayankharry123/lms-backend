<?php

namespace Tests;

use App\Models\Organisation;
use App\Models\Role;
use App\Models\User;
use App\Models\UserParent;
use Illuminate\Support\Str;

class ChildUsersByLeadRoleExclusionTest extends TestCase
{
    /** @var array<int, int> */
    protected array $userIds = [];

    /** @var array<int, int> */
    protected array $roleIds = [];

    /** @var array<int, int> */
    protected array $organisationIds = [];

    protected function tearDown(): void
    {
        if (!empty($this->userIds)) {
            UserParent::query()
                ->where(function ($query) {
                    $query->whereIn('user_id', $this->userIds)
                        ->orWhereIn('is_parent', $this->userIds);
                })
                ->delete();
            User::withTrashed()->whereIn('id', $this->userIds)->each(function (User $user) {
                $user->roles()->detach();
                $user->forceDelete();
            });
        }

        if (!empty($this->roleIds)) {
            Role::withTrashed()->whereIn('id', $this->roleIds)->forceDelete();
        }

        if (!empty($this->organisationIds)) {
            Organisation::withTrashed()->whereIn('id', $this->organisationIds)->forceDelete();
        }

        parent::tearDown();
    }

    public function test_lead_child_tree_omits_planner_roles_and_their_descendants(): void
    {
        $organisation = $this->createOrganisation();
        $parent = $this->createUser($organisation->id, 'Parent');
        $salesman = $this->createUser($organisation->id, 'Salesman');
        $planner = $this->createUser($organisation->id, 'Planner');
        $plannerChild = $this->createUser($organisation->id, 'Planner Child');
        $plannerAdmin = $this->createUser($organisation->id, 'Planner Admin');
        $plannerAdminChild = $this->createUser($organisation->id, 'Planner Admin Child');
        $dualRole = $this->createUser($organisation->id, 'Dual Role');
        $unroled = $this->createUser($organisation->id, 'No Role');

        $salesRole = $this->createRole('Salesman', 'salesman-' . uniqid());
        $plannerRole = $this->roleWithSlug('planner', 'Planner');
        $plannerAdminRole = $this->roleWithSlug('planner-admin', 'Planner Admin');

        $this->attachRole($salesman, $salesRole);
        $this->attachRole($planner, $plannerRole);
        $this->attachRole($plannerChild, $salesRole);
        $this->attachRole($plannerAdmin, $plannerAdminRole);
        $this->attachRole($plannerAdminChild, $salesRole);
        $this->attachRole($dualRole, $salesRole);
        $this->attachRole($dualRole, $plannerRole);

        $parent->children()->attach([
            $salesman->id,
            $planner->id,
            $plannerAdmin->id,
            $dualRole->id,
            $unroled->id,
        ]);
        $salesman->children()->attach($planner->id);
        $planner->children()->attach($plannerChild->id);
        $plannerAdmin->children()->attach($plannerAdminChild->id);

        $filtered = $parent->getChildTreeByOrganisation($organisation->id, [], [], ['planner-admin', 'planner']);
        $filteredIds = $this->collectIds($filtered);

        $this->assertEqualsCanonicalizing([$salesman->id, $unroled->id], $filteredIds);
        $this->assertArrayHasKey('assigned_leads_count', $filtered[0]);
        $this->assertSame([], $this->nodeById($filtered, $salesman->id)['children']);

        $unfilteredIds = $this->collectIds($parent->getChildTreeByOrganisation($organisation->id));
        $this->assertContains($planner->id, $unfilteredIds);
        $this->assertContains($plannerAdmin->id, $unfilteredIds);
        $this->assertContains($plannerChild->id, $unfilteredIds);
    }

    protected function createOrganisation(): Organisation
    {
        $organisation = Organisation::create([
            'name' => 'Lead Hierarchy Org ' . uniqid(),
            'slug' => 'lead-hierarchy-org-' . uniqid(),
            'status' => '1',
        ]);
        $this->organisationIds[] = $organisation->id;

        return $organisation;
    }

    protected function createUser(int $organisationId, string $name): User
    {
        $user = User::create([
            'name' => $name . ' ' . uniqid(),
            'email' => Str::slug($name) . '_' . uniqid() . '@example.com',
            'password' => app('hash')->make('password123'),
            'organisation_id' => $organisationId,
            'status' => '1',
        ]);
        $this->userIds[] = $user->id;

        return $user;
    }

    protected function createRole(string $name, string $slug): Role
    {
        $role = Role::create([
            'name' => $name . ' ' . uniqid(),
            'slug' => $slug,
            'display_name' => $name,
            'status' => '1',
        ]);
        $this->roleIds[] = $role->id;

        return $role;
    }

    protected function roleWithSlug(string $slug, string $name): Role
    {
        $role = Role::query()->where('slug', $slug)->first();
        if ($role) {
            return $role;
        }

        return $this->createRole($name, $slug);
    }

    protected function attachRole(User $user, Role $role): void
    {
        $user->roles()->attach($role->id, ['user_type' => User::class]);
    }

    /**
     * @param array<int, array<string, mixed>> $nodes
     * @return array<int, int>
     */
    protected function collectIds(array $nodes): array
    {
        $ids = [];

        foreach ($nodes as $node) {
            $ids[] = (int) $node['id'];
            $ids = array_merge($ids, $this->collectIds($node['children'] ?? []));
        }

        return $ids;
    }

    /**
     * @param array<int, array<string, mixed>> $nodes
     * @return array<string, mixed>
     */
    protected function nodeById(array $nodes, int $id): array
    {
        foreach ($nodes as $node) {
            if ((int) $node['id'] === $id) {
                return $node;
            }
        }

        $this->fail('Node ' . $id . ' was not found in the child tree.');
    }
}
