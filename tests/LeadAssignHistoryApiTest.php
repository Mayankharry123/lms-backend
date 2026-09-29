<?php

namespace Tests;

use App\Models\Lead;
use App\Models\LeadAssignHistory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;

class LeadAssignHistoryApiTest extends TestCase
{
    protected string $token;
    protected User $user;

    /** @var array<int, int> */
    protected array $createdLeadIds = [];

    /** @var array<int, int> */
    protected array $createdUserIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::first() ?? User::create([
            'name' => 'Assign History Tester',
            'email' => 'assign_history_' . uniqid() . '@example.com',
            'password' => app('hash')->make('password123'),
        ]);

        $this->token = auth()->login($this->user);
    }

    protected function tearDown(): void
    {
        if (!empty($this->createdLeadIds)) {
            LeadAssignHistory::withTrashed()
                ->whereIn('lead_id', $this->createdLeadIds)
                ->forceDelete();

            Lead::withTrashed()->whereIn('id', $this->createdLeadIds)->forceDelete();
        }

        if (!empty($this->createdUserIds)) {
            User::withTrashed()->whereIn('id', $this->createdUserIds)->forceDelete();
        }

        parent::tearDown();
    }

    protected function authGet(string $uri)
    {
        return $this->get($uri, ['Authorization' => "Bearer {$this->token}"]);
    }

    protected function createLead(): Lead
    {
        $lead = Lead::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Assign History Lead ' . uniqid(),
            'slug' => 'assign-history-' . uniqid(),
            'status' => '1',
        ]);

        $this->createdLeadIds[] = $lead->id;

        return $lead;
    }

    protected function createHistory(int $leadId, int $currentUserId, ?string $comment, ?string $createdAt = null): LeadAssignHistory
    {
        $history = new LeadAssignHistory();
        $history->uuid = (string) Str::uuid();
        $history->lead_id = $leadId;
        $history->assign_user_id = $currentUserId;
        $history->current_user_id = $currentUserId;
        $history->lead_comment = $comment;
        $history->status = '1';

        if ($createdAt !== null) {
            $history->created_at = Carbon::parse($createdAt);
            $history->updated_at = Carbon::parse($createdAt);
        }

        $history->save();

        return $history;
    }

    protected function createUser(string $name): User
    {
        $user = User::create([
            'name' => $name,
            'email' => 'assign_history_user_' . uniqid() . '@example.com',
            'password' => app('hash')->make('password123'),
        ]);

        $this->createdUserIds[] = $user->id;

        return $user;
    }

    public function test_assign_history_returns_only_current_user_and_comment(): void
    {
        $lead = $this->createLead();
        $otherLead = $this->createLead();
        $firstUser = $this->createUser('Follow Up User');
        $secondUser = $this->createUser('Meeting User');
        $otherUser = $this->createUser('Other Lead User');

        $this->createHistory($lead->id, $firstUser->id, 'Follow up required', '2026-09-25 10:00:00');
        $this->createHistory($lead->id, $secondUser->id, 'Meeting scheduled', '2026-09-25 11:00:00');
        $this->createHistory($otherLead->id, $otherUser->id, 'Should not appear');

        $this->authGet("/api/v1/leads/{$lead->id}/assign-history");
        $this->seeStatusCode(200);

        $data = json_decode($this->response->getContent(), true);

        $this->assertTrue($data['success']);
        $this->assertEquals('Lead assign history retrieved successfully.', $data['message']);
        $this->assertEquals([
            [
                'current_user_id' => $secondUser->id,
                'current_user_name' => 'Meeting User',
                'lead_comment' => 'Meeting scheduled',
                'created_at' => '2026-09-25 11:00:00 AM',
            ],
            [
                'current_user_id' => $firstUser->id,
                'current_user_name' => 'Follow Up User',
                'lead_comment' => 'Follow up required',
                'created_at' => '2026-09-25 10:00:00 AM',
            ],
        ], $data['data']);

        $this->assertSame(
            ['current_user_id', 'current_user_name', 'lead_comment', 'created_at'],
            array_keys($data['data'][0])
        );
        $this->assertSame(9, $data['meta']['pagination']['per_page']);
        $this->assertSame(2, $data['meta']['pagination']['total']);
    }

    public function test_assign_history_returns_chunks_of_nine(): void
    {
        $lead = $this->createLead();
        $user = $this->createUser('Chunk User');

        for ($i = 1; $i <= 10; $i++) {
            $this->createHistory($lead->id, $user->id, "Comment {$i}");
        }

        $this->authGet("/api/v1/leads/{$lead->id}/assign-history");
        $this->seeStatusCode(200);

        $data = json_decode($this->response->getContent(), true);

        $this->assertCount(9, $data['data']);
        $this->assertSame(9, $data['meta']['pagination']['per_page']);
        $this->assertSame(10, $data['meta']['pagination']['total']);
        $this->assertSame(1, $data['meta']['pagination']['current_page']);
        $this->assertSame(2, $data['meta']['pagination']['last_page']);
        $this->assertSame('Comment 10', $data['data'][0]['lead_comment']);
        $this->assertSame('Comment 2', $data['data'][8]['lead_comment']);

        $this->authGet("/api/v1/leads/{$lead->id}/assign-history?page=2");
        $this->seeStatusCode(200);

        $pageTwo = json_decode($this->response->getContent(), true);

        $this->assertCount(1, $pageTwo['data']);
        $this->assertSame(2, $pageTwo['meta']['pagination']['current_page']);
        $this->assertSame('Comment 1', $pageTwo['data'][0]['lead_comment']);
        $this->assertSame('Chunk User', $pageTwo['data'][0]['current_user_name']);
    }

    public function test_assign_history_returns_empty_data_when_none_exists(): void
    {
        $lead = $this->createLead();

        $this->authGet("/api/v1/leads/{$lead->id}/assign-history");
        $this->seeStatusCode(200);

        $data = json_decode($this->response->getContent(), true);

        $this->assertTrue($data['success']);
        $this->assertEquals('No assign history found for this lead.', $data['message']);
        $this->assertEquals([], $data['data']);
        $this->assertSame(9, $data['meta']['pagination']['per_page']);
        $this->assertSame(0, $data['meta']['pagination']['total']);
    }

    public function test_assign_history_rejects_unknown_lead(): void
    {
        $this->authGet('/api/v1/leads/9999999/assign-history');
        $this->seeStatusCode(422);

        $data = json_decode($this->response->getContent(), true);

        $this->assertFalse($data['success']);
        $this->assertEquals('Validation failed', $data['message']);
        $this->assertArrayHasKey('lead_id', $data['errors']);
    }

    public function test_assign_history_rejects_non_integer_lead_id(): void
    {
        $this->authGet('/api/v1/leads/invalid_string/assign-history');
        $this->seeStatusCode(422);

        $data = json_decode($this->response->getContent(), true);

        $this->assertFalse($data['success']);
        $this->assertEquals('Validation failed', $data['message']);
        $this->assertArrayHasKey('lead_id', $data['errors']);
    }
}
