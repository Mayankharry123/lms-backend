<?php

namespace Tests;

use App\Models\Brief;
use App\Models\Dashboard;
use App\Models\Lead;
use App\Models\Organisation;
use App\Models\Planner;
use App\Models\User;
use Illuminate\Support\Str;

class PlannerChartsAssignedPlansTest extends TestCase
{
    protected User $user;

    /** @var array<int, int> */
    protected array $organisationIds = [];

    /** @var array<int, int> */
    protected array $leadIds = [];

    /** @var array<int, int> */
    protected array $briefIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Planner Charts Tester',
            'email' => 'planner_charts_' . uniqid() . '@example.com',
            'password' => app('hash')->make('password123'),
        ]);
    }

    protected function tearDown(): void
    {
        if (!empty($this->briefIds)) {
            Planner::withTrashed()->whereIn('brief_id', $this->briefIds)->forceDelete();
            Brief::withTrashed()->whereIn('id', $this->briefIds)->forceDelete();
        }

        if (!empty($this->leadIds)) {
            Lead::withTrashed()->whereIn('id', $this->leadIds)->forceDelete();
        }

        if (!empty($this->organisationIds)) {
            Organisation::withTrashed()->whereIn('id', $this->organisationIds)->forceDelete();
        }

        $this->user->forceDelete();

        parent::tearDown();
    }

    public function test_assigned_plans_counts_briefs_that_have_a_planner_per_organisation(): void
    {
        $organisation = $this->createOrganisation('DGTOOHL');
        $otherOrganisation = $this->createOrganisation('Other Org');
        $lead = $this->createLead($organisation->id);
        $otherLead = $this->createLead($otherOrganisation->id);

        $this->createBrief($lead->id, 'Assigned twice', $this->user->id);
        $this->createBrief($lead->id, 'Assigned once', $this->user->id);
        $this->createBrief($lead->id, 'Assigned again', $this->user->id);
        $this->createBrief($lead->id, 'Planner record only');
        $this->createBrief($lead->id, 'No planner');
        $this->createBrief($otherLead->id, 'Other org assigned', $this->user->id);

        $dashboard = new Dashboard();
        $row = $dashboard->fetchPlannerOrganisationRow(
            ['organisation_ids' => [$organisation->id]],
            $this->user,
            $organisation->id,
            $organisation->name
        );
        $otherRow = $dashboard->fetchPlannerOrganisationRow(
            ['organisation_ids' => [$otherOrganisation->id]],
            $this->user,
            $otherOrganisation->id,
            $otherOrganisation->name
        );

        $this->assertSame(3, $row['assigned_plans']);
        $this->assertSame(1, $otherRow['assigned_plans']);
        $this->assertSame(4, $row['assigned_plans'] + $otherRow['assigned_plans']);
        $this->assertSame($organisation->id, $row['organisation_id']);
        $this->assertArrayHasKey('avg_assignment_days', $row);
    }

    protected function createOrganisation(string $name): Organisation
    {
        $organisation = Organisation::create([
            'name' => $name . ' ' . uniqid(),
            'slug' => Str::slug($name) . '-' . uniqid(),
            'status' => '1',
        ]);
        $this->organisationIds[] = $organisation->id;

        return $organisation;
    }

    protected function createLead(int $organisationId): Lead
    {
        $lead = Lead::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Planner Chart Lead ' . uniqid(),
            'slug' => 'planner-chart-lead-' . uniqid(),
            'organisation_id' => $organisationId,
            'status' => '1',
        ]);
        $this->leadIds[] = $lead->id;

        return $lead;
    }

    protected function createBrief(int $leadId, string $name, ?int $assignUserId = null): Brief
    {
        $brief = Brief::create([
            'uuid' => (string) Str::uuid(),
            'name' => $name . ' ' . uniqid(),
            'slug' => Str::slug($name) . '-' . uniqid(),
            'contact_person_id' => $leadId,
            'created_by' => $this->user->id,
            'assign_user_id' => $assignUserId,
            'status' => '1',
        ]);
        $this->briefIds[] = $brief->id;

        return $brief;
    }
}
