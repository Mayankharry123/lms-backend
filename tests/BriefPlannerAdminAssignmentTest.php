<?php

namespace Tests;

use App\Models\Brief;
use App\Models\BriefAssignHistory;
use App\Models\Lead;
use App\Models\Organisation;
use App\Models\Role;
use App\Models\User;
use App\Services\BriefService;
use Illuminate\Support\Str;

class BriefPlannerAdminAssignmentTest extends TestCase
{
    /** @var array<int, int> */
    protected array $organisationIds = [];

    /** @var array<int, int> */
    protected array $leadIds = [];

    /** @var array<int, int> */
    protected array $briefIds = [];

    /** @var array<int, int> */
    protected array $userIds = [];

    protected ?int $createdRoleId = null;

    protected function tearDown(): void
    {
        if (!empty($this->briefIds)) {
            BriefAssignHistory::withTrashed()->whereIn('brief_id', $this->briefIds)->forceDelete();
            Brief::withTrashed()->whereIn('id', $this->briefIds)->forceDelete();
        }

        if (!empty($this->userIds)) {
            User::withTrashed()->whereIn('id', $this->userIds)->forceDelete();
        }

        if ($this->createdRoleId) {
            Role::withTrashed()->where('id', $this->createdRoleId)->forceDelete();
        }

        if (!empty($this->leadIds)) {
            Lead::withTrashed()->whereIn('id', $this->leadIds)->forceDelete();
        }

        if (!empty($this->organisationIds)) {
            Organisation::withTrashed()->whereIn('id', $this->organisationIds)->forceDelete();
        }

        parent::tearDown();
    }

    public function test_create_assigns_planner_admin_from_the_same_organisation_when_assignee_is_empty(): void
    {
        $organisation = $this->createOrganisation();
        $otherOrganisation = $this->createOrganisation();
        $plannerAdmin = $this->createPlannerAdmin($organisation->id, 'Org Planner Admin');
        $this->createPlannerAdmin($otherOrganisation->id, 'Other Org Planner Admin');
        $lead = $this->createLead($organisation->id);

        $brief = app(BriefService::class)->createBrief($this->briefData($lead->id, [
            'assign_user_id' => null,
        ]));
        $this->briefIds[] = $brief->id;

        $this->assertSame($plannerAdmin->id, (int) $brief->fresh()->assign_user_id);
    }

    public function test_create_keeps_a_provided_assignee(): void
    {
        $organisation = $this->createOrganisation();
        $this->createPlannerAdmin($organisation->id, 'Unused Planner Admin');
        $assignee = $this->createUser($organisation->id, 'Chosen Assignee');
        $lead = $this->createLead($organisation->id);

        $brief = app(BriefService::class)->createBrief($this->briefData($lead->id, [
            'assign_user_id' => $assignee->id,
        ]));
        $this->briefIds[] = $brief->id;

        $this->assertSame($assignee->id, (int) $brief->fresh()->assign_user_id);
    }

    public function test_update_assigns_planner_admin_only_when_assignee_is_null(): void
    {
        $organisation = $this->createOrganisation();
        $plannerAdmin = $this->createPlannerAdmin($organisation->id, 'Update Planner Admin');
        $assignee = $this->createUser($organisation->id, 'Existing Assignee');
        $lead = $this->createLead($organisation->id);
        $service = app(BriefService::class);

        $brief = $service->createBrief($this->briefData($lead->id, [
            'assign_user_id' => $assignee->id,
            'created_by' => $assignee->id,
        ]));
        $this->briefIds[] = $brief->id;

        $unchanged = $service->updateBrief($brief->id, [
            'name' => $brief->name,
        ]);
        $this->assertSame($assignee->id, (int) $unchanged->assign_user_id);

        $reassigned = $service->updateBrief($brief->id, [
            'assign_user_id' => null,
        ]);
        $this->assertSame($plannerAdmin->id, (int) $reassigned->assign_user_id);
    }

    public function test_create_leaves_assignee_empty_when_organisation_has_no_planner_admin(): void
    {
        $organisation = $this->createOrganisation();
        $lead = $this->createLead($organisation->id);

        $brief = app(BriefService::class)->createBrief($this->briefData($lead->id, [
            'assign_user_id' => null,
        ]));
        $this->briefIds[] = $brief->id;

        $this->assertNull($brief->fresh()->assign_user_id);
    }

    public function test_create_logs_a_comment_even_when_the_brief_has_no_assignee(): void
    {
        $organisation = $this->createOrganisation();
        $creator = $this->createUser($organisation->id, 'Comment Creator');
        $lead = $this->createLead($organisation->id);

        $brief = app(BriefService::class)->createBrief($this->briefData($lead->id, [
            'created_by' => $creator->id,
            'assign_user_id' => null,
            'comment' => 'Initial comment',
        ]));
        $this->briefIds[] = $brief->id;

        $this->assertTrue(BriefAssignHistory::where('brief_id', $brief->id)
            ->where('comment', 'Initial comment')
            ->where('assign_by_id', $creator->id)
            ->where('assign_to_id', $creator->id)
            ->exists());
    }

    public function test_comment_changes_create_history_without_duplicates_for_unchanged_values(): void
    {
        $organisation = $this->createOrganisation();
        $creator = $this->createUser($organisation->id, 'Comment Editor');
        $lead = $this->createLead($organisation->id);
        $service = app(BriefService::class);

        $brief = $service->createBrief($this->briefData($lead->id, [
            'created_by' => $creator->id,
            'assign_user_id' => null,
            'comment' => null,
        ]));
        $this->briefIds[] = $brief->id;

        $service->updateBrief($brief->id, ['comment' => 'First comment']);
        $this->assertSame(1, BriefAssignHistory::where('brief_id', $brief->id)->count());

        $service->updateBrief($brief->id, ['comment' => 'First comment']);
        $this->assertSame(1, BriefAssignHistory::where('brief_id', $brief->id)->count());

        $service->updateBrief($brief->id, ['comment' => 'Updated comment']);
        $this->assertSame(2, BriefAssignHistory::where('brief_id', $brief->id)->count());
        $this->assertTrue(BriefAssignHistory::where('brief_id', $brief->id)
            ->where('comment', 'Updated comment')
            ->exists());
    }

    protected function briefData(int $leadId, array $overrides = []): array
    {
        $name = 'Planner Admin Brief ' . uniqid();

        return array_merge([
            'uuid' => (string) Str::uuid(),
            'name' => $name,
            'slug' => Str::slug($name),
            'contact_person_id' => $leadId,
            'status' => '1',
        ], $overrides);
    }

    protected function createOrganisation(): Organisation
    {
        $organisation = Organisation::create([
            'name' => 'Planner Org ' . uniqid(),
            'slug' => 'planner-org-' . uniqid(),
            'status' => '1',
        ]);
        $this->organisationIds[] = $organisation->id;

        return $organisation;
    }

    protected function createLead(int $organisationId): Lead
    {
        $lead = Lead::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Planner Lead ' . uniqid(),
            'slug' => 'planner-lead-' . uniqid(),
            'organisation_id' => $organisationId,
            'status' => '1',
        ]);
        $this->leadIds[] = $lead->id;

        return $lead;
    }

    protected function createUser(int $organisationId, string $name): User
    {
        $user = User::create([
            'name' => $name . ' ' . uniqid(),
            'email' => 'planner_admin_' . uniqid() . '@example.com',
            'password' => app('hash')->make('password123'),
            'organisation_id' => $organisationId,
            'status' => '1',
        ]);
        $this->userIds[] = $user->id;

        return $user;
    }

    protected function createPlannerAdmin(int $organisationId, string $name): User
    {
        $user = $this->createUser($organisationId, $name);
        $role = Role::query()->where('slug', 'planner-admin')->first();

        if (!$role) {
            $role = Role::create([
                'name' => 'Planner Admin',
                'slug' => 'planner-admin',
                'display_name' => 'Planner Admin',
                'status' => '1',
            ]);
            $this->createdRoleId = $role->id;
        }

        $user->roles()->attach($role->id, ['user_type' => User::class]);

        return $user;
    }
}
