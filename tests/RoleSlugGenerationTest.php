<?php

namespace Tests;

use App\Models\Role;
use App\Services\RoleService;

class RoleSlugGenerationTest extends TestCase
{
    /** @var array<int, int> */
    protected array $roleIds = [];

    protected function tearDown(): void
    {
        if (!empty($this->roleIds)) {
            Role::withTrashed()->whereIn('id', $this->roleIds)->forceDelete();
        }

        parent::tearDown();
    }

    public function test_create_generates_slug_from_name_and_keeps_it_unique(): void
    {
        $service = app(RoleService::class);
        $suffix = uniqid();

        $first = $service->create([
            'name' => 'Planner Admin ' . $suffix,
            'description' => 'First',
        ]);
        $this->roleIds[] = $first->id;

        $second = $service->create([
            'name' => 'Planner-Admin ' . $suffix,
            'description' => 'Second',
        ]);
        $this->roleIds[] = $second->id;

        $this->assertSame('planner-admin-' . $suffix, $first->slug);
        $this->assertSame('planner-admin-' . $suffix . '-1', $second->slug);
    }

    public function test_update_regenerates_slug_only_when_name_changes(): void
    {
        $service = app(RoleService::class);
        $suffix = uniqid();

        $role = $service->create([
            'name' => 'Senior Planner ' . $suffix,
            'description' => 'Original',
        ]);
        $this->roleIds[] = $role->id;

        $service->update($role->id, [
            'description' => 'Updated description',
        ]);
        $this->assertSame('senior-planner-' . $suffix, $role->fresh()->slug);

        $service->update($role->id, [
            'name' => 'Lead Planner ' . $suffix,
        ]);
        $this->assertSame('lead-planner-' . $suffix, $role->fresh()->slug);
    }

    public function test_update_fills_a_missing_slug_without_renaming_the_role(): void
    {
        $service = app(RoleService::class);
        $suffix = uniqid();
        $role = $service->create([
            'name' => 'Sales Master ' . $suffix,
            'description' => 'Original',
        ]);
        $this->roleIds[] = $role->id;

        Role::query()->where('id', $role->id)->update(['slug' => null]);

        $service->update($role->id, [
            'description' => 'Still the same role',
        ]);

        $this->assertSame('sales-master-' . $suffix, $role->fresh()->slug);
    }
}
