<?php

namespace Tests;

use App\Models\Brief;
use App\Models\BriefAssignHistory;
use App\Models\Lead;
use App\Models\Organisation;
use App\Models\Planner;
use App\Models\PlannerHistory;
use App\Models\User;
use App\Services\DashboardService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PlannerChartsAssignmentDaysTest extends TestCase
{
    protected User $user;

    /** @var array<int, int> */
    protected array $userIds = [];

    /** @var array<int, int> */
    protected array $organisationIds = [];

    /** @var array<int, int> */
    protected array $leadIds = [];

    /** @var array<int, int> */
    protected array $briefIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createUser('Planner Chart Owner');
        Auth::setUser($this->user);
    }

    protected function tearDown(): void
    {
        if (!empty($this->briefIds)) {
            PlannerHistory::withTrashed()->whereIn('brief_id', $this->briefIds)->forceDelete();
            Planner::withTrashed()->whereIn('brief_id', $this->briefIds)->forceDelete();
            BriefAssignHistory::withTrashed()->whereIn('brief_id', $this->briefIds)->forceDelete();
            Brief::withTrashed()->whereIn('id', $this->briefIds)->forceDelete();
        }

        if (!empty($this->leadIds)) {
            Lead::withTrashed()->whereIn('id', $this->leadIds)->forceDelete();
        }

        if (!empty($this->organisationIds)) {
            Organisation::withTrashed()->whereIn('id', $this->organisationIds)->forceDelete();
        }

        if (!empty($this->userIds)) {
            User::withTrashed()->whereIn('id', $this->userIds)->forceDelete();
        }

        parent::tearDown();
    }

    public function test_avg_assignment_days_is_the_average_of_completed_cycles_per_organisation(): void
    {
        $firstOrganisation = $this->createOrganisation('DGTOOHL');
        $secondOrganisation = $this->createOrganisation('Second Org');
        $emptyOrganisation = $this->createOrganisation('Empty Cycles');

        $plannerA = $this->createUser('Planner A');
        $plannerB = $this->createUser('Planner B');

        $firstLead = $this->createLead($firstOrganisation->id);
        $secondLead = $this->createLead($secondOrganisation->id);
        $emptyLead = $this->createLead($emptyOrganisation->id);

        $firstBrief = $this->createBrief($firstLead->id, 'Three cycles', $plannerA->id);
        $secondBrief = $this->createBrief($secondLead->id, 'One cycle', $plannerA->id);
        $unsubmittedBrief = $this->createBrief($emptyLead->id, 'Not submitted', $plannerB->id);

        $this->createPlanner($firstBrief->id, $plannerA->id);
        $this->createPlanner($secondBrief->id, $plannerA->id);
        $this->createPlanner($unsubmittedBrief->id, $plannerB->id);

        $cycleStart = Carbon::parse('2026-01-01 00:00:00');
        $this->assignAndSubmit($firstBrief->id, $plannerA, $cycleStart, $cycleStart->copy()->addDays(2));
        $this->createAssignment($firstBrief->id, $plannerA, $cycleStart->copy()->addHour());

        $secondAssignment = $cycleStart->copy()->addDays(2)->addSecond();
        $this->assignAndSubmit($firstBrief->id, $plannerB, $secondAssignment, $secondAssignment->copy()->addDays(3));

        $thirdAssignment = $secondAssignment->copy()->addDays(3)->addSecond();
        $this->assignAndSubmit($firstBrief->id, $plannerA, $thirdAssignment, $thirdAssignment->copy()->addDays(4));
        $this->createAssignment($firstBrief->id, $plannerB, $thirdAssignment->copy()->addDays(4)->addSecond());

        $otherStart = Carbon::parse('2026-02-01 00:00:00');
        $this->assignAndSubmit($secondBrief->id, $plannerA, $otherStart, $otherStart->copy()->addDay());
        $this->createAssignment($unsubmittedBrief->id, $plannerB, Carbon::parse('2026-03-01 00:00:00'));

        $charts = app(DashboardService::class)->getPlannerChartMetrics([
            'organisation_ids' => [
                $firstOrganisation->id,
                $secondOrganisation->id,
                $emptyOrganisation->id,
            ],
        ]);

        $rows = collect($charts['by_organisation'])->keyBy('organisation_id');

        $this->assertArrayNotHasKey('avg_assignment_days', $rows[$firstOrganisation->id]);
        $this->assertArrayNotHasKey('avg_assignment_days', $rows[$secondOrganisation->id]);
        $this->assertArrayNotHasKey('avg_assignment_days', $rows[$emptyOrganisation->id]);
        $this->assertArrayNotHasKey('avg_assignment_days', $charts['totals']);
        $this->assertEquals(3.0, $rows[$firstOrganisation->id]['avg_plan_submission_days']);
        $this->assertEquals(1.0, $rows[$secondOrganisation->id]['avg_plan_submission_days']);
        $this->assertEquals(0, $rows[$emptyOrganisation->id]['avg_plan_submission_days']);
        $this->assertEquals(2.5, $charts['totals']['avg_plan_submission_days']);
        $this->assertSame(2.5, $charts['plan_submission']['avg_submission_days']);
        $this->assertSame(60.0, $charts['plan_submission']['avg_submission_hours']);
        $this->assertSame(4, $charts['plan_submission']['submitted_plans']);

        $emptyCharts = app(DashboardService::class)->getPlannerChartMetrics([
            'organisation_ids' => [$emptyOrganisation->id],
        ]);

        $this->assertSame(0.0, $emptyCharts['plan_submission']['avg_submission_days']);
        $this->assertSame(0.0, $emptyCharts['plan_submission']['avg_submission_hours']);
        $this->assertSame(0, $emptyCharts['plan_submission']['submitted_plans']);
        $this->assertArrayHasKey('briefs', $rows[$firstOrganisation->id]);
        $this->assertArrayHasKey('brief_budget', $rows[$firstOrganisation->id]);
        $this->assertArrayHasKey('assigned_plans', $rows[$firstOrganisation->id]);
        $this->assertArrayHasKey('brief_status', $charts);
    }

    protected function createUser(string $name): User
    {
        $user = User::create([
            'name' => $name . ' ' . uniqid(),
            'email' => Str::slug($name) . '_' . uniqid() . '@example.com',
            'password' => app('hash')->make('password123'),
        ]);
        $this->userIds[] = $user->id;

        return $user;
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

    protected function createBrief(int $leadId, string $name, int $assignUserId): Brief
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

    protected function createPlanner(int $briefId, int $createdBy): Planner
    {
        return Planner::create([
            'uuid' => (string) Str::uuid(),
            'brief_id' => $briefId,
            'created_by' => $createdBy,
            'status' => '1',
        ]);
    }

    protected function assignAndSubmit(int $briefId, User $planner, Carbon $assignedAt, Carbon $submittedAt): void
    {
        $this->createAssignment($briefId, $planner, $assignedAt);

        $plannerRecord = Planner::query()->where('brief_id', $briefId)->first();
        $history = new PlannerHistory();
        $history->planner_id = $plannerRecord->id;
        $history->brief_id = $briefId;
        $history->created_by = $planner->id;
        $history->submitted_plan = ['plan.pdf'];
        $history->status = '1';
        $history->created_at = $submittedAt;
        $history->updated_at = $submittedAt;
        $history->save();
    }

    protected function createAssignment(int $briefId, User $planner, Carbon $assignedAt): void
    {
        $history = new BriefAssignHistory();
        $history->uuid = (string) Str::uuid();
        $history->brief_id = $briefId;
        $history->assign_by_id = $this->user->id;
        $history->assign_to_id = $planner->id;
        $history->status = '2';
        $history->created_at = $assignedAt;
        $history->updated_at = $assignedAt;
        $history->save();
    }
}
