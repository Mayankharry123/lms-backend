<?php

namespace Tests;

use App\Models\CallStatus;
use App\Models\Lead;
use App\Models\LeadAssignHistory;
use App\Models\Priority;
use App\Models\Status;
use App\Models\User;
use App\Repositories\LeadRepository;
use Illuminate\Support\Str;

class LeadActivityApiTest extends TestCase
{
    protected string $token;
    protected User $user;
    protected LeadRepository $leadRepository;

    /** @var array<int, int> */
    protected array $createdLeadIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::first() ?? User::create([
            'name' => 'Lead Activity Tester',
            'email' => 'lead_activity_' . uniqid() . '@example.com',
            'password' => app('hash')->make('password123'),
        ]);

        $this->token = auth()->login($this->user);
        $this->leadRepository = $this->app->make(LeadRepository::class);
    }

    protected function tearDown(): void
    {
        if (!empty($this->createdLeadIds)) {
            LeadAssignHistory::withTrashed()
                ->whereIn('lead_id', $this->createdLeadIds)
                ->forceDelete();

            Lead::withTrashed()->whereIn('id', $this->createdLeadIds)->forceDelete();
        }

        parent::tearDown();
    }

    public function test_valid_activity_without_reminder(): void
    {
        $callStatus = $this->requireCallStatus();
        $lead = $this->createTestLead();
        $comment = 'Customer requested a follow-up call.';

        $this->postActivity($lead->id, [
            'call_status_id' => $callStatus->id,
            'comment' => $comment,
            'reminder' => false,
        ]);

        $this->seeStatusCode(200);
        $payload = json_decode($this->response->getContent(), true);
        $this->assertTrue($payload['success']);
        $this->assertSame('Lead activity saved successfully.', $payload['message']);
        $this->assertSame(
            ['call_status_relation', 'lead_status_relation', 'reminder'],
            array_keys($payload['data'])
        );
        $this->assertFalse($payload['data']['reminder']);
        $this->assertSame($callStatus->id, $payload['data']['call_status_relation']['id']);
        $this->assertSame($callStatus->name, $payload['data']['call_status_relation']['name']);
        $this->assertArrayNotHasKey('comment', $payload['data']);
        $this->assertArrayNotHasKey('reminder_at', $payload['data']);

        $history = LeadAssignHistory::where('lead_id', $lead->id)->orderByDesc('id')->first();
        $this->assertNotNull($history);
        $this->assertSame($comment, $history->lead_comment);
        $this->assertSame($callStatus->id, (int) $history->call_status_id);
        $this->assertFalse((bool) $history->reminder);
        $this->assertNull($history->reminder_at);
        $this->assertNull($history->reminder_before);
        $this->assertNull($history->reminder_before_unit);
    }

    public function test_form_data_string_false_reminder_is_accepted(): void
    {
        $callStatus = $this->requireCallStatus();
        $lead = $this->createTestLead();

        $this->postActivity($lead->id, [
            'call_status_id' => (string) $callStatus->id,
            'comment' => 'Form data follow-up',
            'reminder' => 'false',
        ]);

        $this->seeStatusCode(200);
        $payload = json_decode($this->response->getContent(), true);
        $this->assertFalse($payload['data']['reminder']);
        $this->assertArrayNotHasKey('reminder_at', $payload['data']);

        $history = LeadAssignHistory::where('lead_id', $lead->id)->orderByDesc('id')->first();
        $this->assertFalse((bool) $history->reminder);
        $this->assertNull($history->reminder_at);
    }

    public function test_valid_activity_with_reminder(): void
    {
        $callStatus = $this->requireCallStatus();
        $lead = $this->createTestLead();

        $this->postActivity($lead->id, [
            'call_status_id' => $callStatus->id,
            'comment' => 'Customer requested a follow-up call.',
            'reminder' => true,
            'reminder_at' => '2026-09-25 15:30:00',
            'reminder_before' => 30,
            'reminder_before_unit' => 'minutes',
        ]);

        $this->seeStatusCode(200);
        $payload = json_decode($this->response->getContent(), true);
        $this->assertTrue($payload['data']['reminder']);
        $this->assertSame('2026-09-25 15:30:00', $payload['data']['reminder_at']);
        $this->assertSame(30, $payload['data']['reminder_before']);
        $this->assertSame('minutes', $payload['data']['reminder_before_unit']);
        $this->assertSame(['id', 'name'], array_keys($payload['data']['call_status_relation']));

        $history = LeadAssignHistory::where('lead_id', $lead->id)->orderByDesc('id')->first();
        $this->assertTrue((bool) $history->reminder);
        $this->assertSame('2026-09-25 15:30:00', $history->reminder_at->format('Y-m-d H:i:s'));
        $this->assertSame(30, (int) $history->reminder_before);
        $this->assertSame('minutes', $history->reminder_before_unit);
    }

    public function test_reminder_enabled_requires_reminder_at(): void
    {
        $callStatus = $this->requireCallStatus();
        $lead = $this->createTestLead();

        $this->postActivity($lead->id, [
            'call_status_id' => $callStatus->id,
            'comment' => 'Follow up',
            'reminder' => true,
            'reminder_before' => 30,
            'reminder_before_unit' => 'minutes',
        ]);

        $this->seeStatusCode(422);
        $payload = json_decode($this->response->getContent(), true);
        $this->assertArrayHasKey('reminder_at', $payload['errors']);
    }

    public function test_reminder_enabled_requires_reminder_before(): void
    {
        $callStatus = $this->requireCallStatus();
        $lead = $this->createTestLead();

        $this->postActivity($lead->id, [
            'call_status_id' => $callStatus->id,
            'comment' => 'Follow up',
            'reminder' => true,
            'reminder_at' => '2026-09-25 15:30:00',
            'reminder_before_unit' => 'minutes',
        ]);

        $this->seeStatusCode(422);
        $payload = json_decode($this->response->getContent(), true);
        $this->assertArrayHasKey('reminder_before', $payload['errors']);
    }

    public function test_reminder_enabled_requires_reminder_before_unit(): void
    {
        $callStatus = $this->requireCallStatus();
        $lead = $this->createTestLead();

        $this->postActivity($lead->id, [
            'call_status_id' => $callStatus->id,
            'comment' => 'Follow up',
            'reminder' => true,
            'reminder_at' => '2026-09-25 15:30:00',
            'reminder_before' => 30,
        ]);

        $this->seeStatusCode(422);
        $payload = json_decode($this->response->getContent(), true);
        $this->assertArrayHasKey('reminder_before_unit', $payload['errors']);
    }

    public function test_invalid_call_status_id_is_rejected(): void
    {
        $lead = $this->createTestLead();

        $this->postActivity($lead->id, [
            'call_status_id' => 9999999,
            'comment' => 'Follow up',
            'reminder' => false,
        ]);

        $this->seeStatusCode(422);
        $payload = json_decode($this->response->getContent(), true);
        $this->assertArrayHasKey('call_status_id', $payload['errors']);
    }

    public function test_invalid_lead_id_is_not_found(): void
    {
        $callStatus = $this->requireCallStatus();

        $this->postActivity(9999999, [
            'call_status_id' => $callStatus->id,
            'comment' => 'Follow up',
            'reminder' => false,
        ]);

        $this->seeStatusCode(404);
    }

    public function test_reminder_disabled_ignores_supplied_reminder_fields(): void
    {
        $callStatus = $this->requireCallStatus();
        $leadStatus = Status::first();
        $priority = Priority::first();
        $lead = $this->createTestLead([
            'lead_status' => $leadStatus?->id,
            'priority_id' => $priority?->id,
        ]);

        $this->postActivity($lead->id, [
            'call_status_id' => $callStatus->id,
            'comment' => 'Follow up',
            'reminder' => false,
            'reminder_at' => '2026-09-25 15:30:00',
            'reminder_before' => 30,
            'reminder_before_unit' => 'minutes',
            'lead_id' => 123456,
        ]);

        $this->seeStatusCode(200);
        $payload = json_decode($this->response->getContent(), true);
        $this->assertFalse($payload['data']['reminder']);
        $this->assertArrayNotHasKey('reminder_at', $payload['data']);
        $this->assertArrayNotHasKey('reminder_before', $payload['data']);
        $this->assertArrayNotHasKey('reminder_before_unit', $payload['data']);

        $lead->refresh();
        $this->assertSame($callStatus->id, (int) $lead->call_status);
        $this->assertSame($leadStatus?->id, $lead->lead_status ? (int) $lead->lead_status : null);
        $this->assertSame($priority?->id, $lead->priority_id ? (int) $lead->priority_id : null);

        $history = LeadAssignHistory::where('lead_id', $lead->id)->orderByDesc('id')->first();
        $this->assertSame($lead->id, (int) $history->lead_id);
        $this->assertFalse((bool) $history->reminder);
        $this->assertNull($history->reminder_at);
        $this->assertNull($history->reminder_before);
        $this->assertNull($history->reminder_before_unit);
    }

    /**
     * @param array<string, mixed> $body
     */
    private function postActivity(int $leadId, array $body): void
    {
        $this->post("/api/v1/leads/{$leadId}/activity", $body, [
            'Authorization' => "Bearer {$this->token}",
        ]);
    }

    private function requireCallStatus(): CallStatus
    {
        $callStatus = CallStatus::first();
        if (!$callStatus) {
            $this->markTestSkipped('No call statuses available.');
        }

        return $callStatus;
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function createTestLead(array $overrides = []): Lead
    {
        $lead = $this->leadRepository->createLead(array_merge([
            'name' => 'Activity Lead ' . Str::upper(Str::random(6)),
            'status' => '1',
        ], $overrides));

        $this->createdLeadIds[] = $lead->id;

        return $lead;
    }
}
