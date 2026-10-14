<?php

namespace Tests;

use App\Models\Brief;
use App\Models\BriefAssignHistory;
use App\Models\Lead;
use App\Models\Organisation;
use App\Models\User;
use Illuminate\Support\Str;

class BriefActivityApiTest extends TestCase
{
    protected string $token;
    protected User $user;
    protected Organisation $organisation;
    protected Lead $lead;
    protected Brief $brief;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organisation = Organisation::create([
            'name' => 'Brief Activity Org ' . uniqid(),
            'slug' => 'brief-activity-org-' . uniqid(),
            'status' => '1',
        ]);
        $this->user = User::create([
            'name' => 'Brief Activity User',
            'email' => 'brief_activity_' . uniqid() . '@example.com',
            'password' => app('hash')->make('password123'),
            'organisation_id' => $this->organisation->id,
            'status' => '1',
        ]);
        $this->token = auth()->login($this->user);

        $this->lead = Lead::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Brief Activity Lead ' . uniqid(),
            'slug' => 'brief-activity-lead-' . uniqid(),
            'organisation_id' => $this->organisation->id,
            'status' => '1',
        ]);
        $this->brief = Brief::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Brief Activity ' . uniqid(),
            'slug' => 'brief-activity-' . uniqid(),
            'contact_person_id' => $this->lead->id,
            'created_by' => $this->user->id,
            'status' => '1',
        ]);
    }

    protected function tearDown(): void
    {
        BriefAssignHistory::withTrashed()->where('brief_id', $this->brief->id)->forceDelete();
        Brief::withTrashed()->where('id', $this->brief->id)->forceDelete();
        Lead::withTrashed()->where('id', $this->lead->id)->forceDelete();
        User::withTrashed()->where('id', $this->user->id)->forceDelete();
        Organisation::withTrashed()->where('id', $this->organisation->id)->forceDelete();

        parent::tearDown();
    }

    public function test_activity_can_be_saved_with_a_reminder_and_retrieved(): void
    {
        $this->post("/api/v1/briefs/{$this->brief->id}/activity", [
            'comment' => 'Send revised brief',
            'reminder' => true,
            'reminder_at' => '2026-10-02 09:15:00',
            'reminder_before' => 2,
            'reminder_before_unit' => 'hours',
        ], ['Authorization' => "Bearer {$this->token}"]);

        $this->assertSame(200, $this->response->getStatusCode(), $this->response->getContent());
        $saved = json_decode($this->response->getContent(), true);
        $this->assertTrue($saved['data']['reminder']);
        $this->assertSame('2026-10-02 09:15:00', $saved['data']['reminder_at']);
        $this->assertSame(2, $saved['data']['reminder_before']);
        $this->assertSame('hours', $saved['data']['reminder_before_unit']);

        $history = BriefAssignHistory::where('brief_id', $this->brief->id)->firstOrFail();
        $this->assertTrue((bool) $history->reminder);
        $this->assertSame('Send revised brief', $history->comment);
        $this->assertSame('2026-10-02 09:15:00', $history->reminder_at->format('Y-m-d H:i:s'));
        $this->assertSame('Send revised brief', $this->brief->fresh()->comment);

        $this->get("/api/v1/briefs/{$this->brief->id}/activity", [
            'Authorization' => "Bearer {$this->token}",
        ]);

        $this->seeStatusCode(200);
        $activities = json_decode($this->response->getContent(), true)['data'];
        $this->assertCount(1, $activities);
        $this->assertSame($this->user->id, $activities[0]['current_user_id']);
        $this->assertSame('Brief Activity User', $activities[0]['current_user_name']);
        $this->assertSame('Send revised brief', $activities[0]['brief_comment']);
        $this->assertSame('hours', $activities[0]['reminder_before_unit']);
    }

    public function test_enabled_reminder_requires_all_reminder_fields(): void
    {
        $this->post("/api/v1/briefs/{$this->brief->id}/activity", [
            'comment' => 'Send revised brief',
            'reminder' => true,
        ], ['Authorization' => "Bearer {$this->token}"]);

        $this->seeStatusCode(422);
        $payload = json_decode($this->response->getContent(), true);
        $this->assertArrayHasKey('reminder_at', $payload['errors']);
        $this->assertArrayHasKey('reminder_before', $payload['errors']);
        $this->assertArrayHasKey('reminder_before_unit', $payload['errors']);
        $this->assertSame(0, BriefAssignHistory::where('brief_id', $this->brief->id)->count());
    }
}