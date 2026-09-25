<?php

namespace Tests;

use App\Models\CallStatus;
use App\Models\Lead;
use App\Models\Priority;
use App\Models\Status;
use App\Models\User;

class UserLeadPerformanceTest extends TestCase
{
    protected string $token;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::first() ?? User::create([
            'name' => 'Test Admin',
            'email' => 'admin_test_' . uniqid() . '@example.com',
            'password' => app('hash')->make('password123'),
        ]);

        $this->token = auth()->login($this->user);
    }

    protected function authGet(string $uri)
    {
        return $this->get($uri, ['Authorization' => "Bearer {$this->token}"]);
    }

    public function test_user_performance_with_assigned_leads(): void
    {
        // Find or create an assigned user
        $assignedUser = User::where('id', '!=', $this->user->id)->first() ?? User::create([
            'name' => 'Assigned User',
            'email' => 'assigned_' . uniqid() . '@example.com',
            'password' => app('hash')->make('password123'),
        ]);

        $callStatus = CallStatus::first();
        $leadStatus = Status::first();
        $priority = Priority::first();

        // Create a test lead
        $lead = Lead::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'name' => 'Performance Lead Test',
            'slug' => 'performance-lead-test-' . uniqid(),
            'current_assign_user' => $assignedUser->id,
            'call_status' => $callStatus?->id,
            'lead_status' => $leadStatus?->id,
            'priority_id' => $priority?->id,
            'status' => '1',
        ]);

        $response = $this->authGet("/api/v1/leads/user-performance/{$assignedUser->id}");
        $this->seeStatusCode(200);

        $data = json_decode($this->response->getContent(), true);

        $this->assertTrue($data['success']);
        $this->assertEquals('User lead performance retrieved successfully.', $data['message']);
        $this->assertIsArray($data['data']);
        $this->assertNotEmpty($data['data']);

        $item = collect($data['data'])->firstWhere('lead_id', $lead->id);
        $this->assertNotNull($item);
        $this->assertEquals($lead->id, $item['lead_id']);
        $this->assertEquals($lead->name, $item['contact_person_name']);
        $this->assertEquals($callStatus?->id, $item['call_status_id']);
        $this->assertEquals($callStatus?->name, $item['call_status']);
        $this->assertEquals($leadStatus?->id, $item['lead_status_id']);
        $this->assertEquals($leadStatus?->name, $item['lead_status']);
        $this->assertEquals($priority?->id, $item['priority_id']);
        $this->assertEquals($priority?->name, $item['priority']);

        // Clean up test lead
        $lead->forceDelete();
    }

    public function test_user_performance_with_no_assigned_leads(): void
    {
        $userWithoutLeads = User::create([
            'name' => 'No Leads User',
            'email' => 'noleads_' . uniqid() . '@example.com',
            'password' => app('hash')->make('password123'),
        ]);

        $response = $this->authGet("/api/v1/leads/user-performance/{$userWithoutLeads->id}");
        $this->seeStatusCode(200);

        $data = json_decode($this->response->getContent(), true);

        $this->assertTrue($data['success']);
        $this->assertEquals('User lead performance retrieved successfully.', $data['message']);
        $this->assertEquals([], $data['data']);

        $userWithoutLeads->forceDelete();
    }

    public function test_user_performance_with_non_existent_user(): void
    {
        $response = $this->authGet('/api/v1/leads/user-performance/9999999');
        $this->seeStatusCode(422);

        $data = json_decode($this->response->getContent(), true);

        $this->assertFalse($data['success']);
        $this->assertEquals('Validation failed', $data['message']);
        $this->assertArrayHasKey('user_id', $data['errors']);
    }

    public function test_user_performance_with_invalid_string_user_id(): void
    {
        $response = $this->authGet('/api/v1/leads/user-performance/invalid_string');
        $this->seeStatusCode(422);

        $data = json_decode($this->response->getContent(), true);

        $this->assertFalse($data['success']);
        $this->assertEquals('Validation failed', $data['message']);
        $this->assertArrayHasKey('user_id', $data['errors']);
    }

    public function test_user_performance_handles_null_statuses(): void
    {
        $assignedUser = User::create([
            'name' => 'Null Status User',
            'email' => 'nullstatus_' . uniqid() . '@example.com',
            'password' => app('hash')->make('password123'),
        ]);

        $lead = Lead::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'name' => 'Null Status Lead',
            'slug' => 'null-status-lead-' . uniqid(),
            'current_assign_user' => $assignedUser->id,
            'call_status' => null,
            'lead_status' => null,
            'priority_id' => null,
            'status' => '1',
        ]);

        $response = $this->authGet("/api/v1/leads/user-performance/{$assignedUser->id}");
        $this->seeStatusCode(200);

        $data = json_decode($this->response->getContent(), true);

        $this->assertTrue($data['success']);
        $this->assertNotEmpty($data['data']);

        $item = $data['data'][0];
        $this->assertEquals($lead->id, $item['lead_id']);
        $this->assertEquals($lead->name, $item['contact_person_name']);
        $this->assertNull($item['call_status_id']);
        $this->assertNull($item['call_status']);
        $this->assertNull($item['lead_status_id']);
        $this->assertNull($item['lead_status']);
        $this->assertNull($item['priority_id']);
        $this->assertNull($item['priority']);

        $lead->forceDelete();
        $assignedUser->forceDelete();
    }
}
