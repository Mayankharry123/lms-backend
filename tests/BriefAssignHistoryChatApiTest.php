<?php

namespace Tests;

use App\Models\Brief;
use App\Models\BriefAssignHistory;
use App\Models\Lead;
use App\Models\Organisation;
use App\Models\User;
use Illuminate\Support\Str;

class BriefAssignHistoryChatApiTest extends TestCase
{
    protected string $token;
    protected User $authenticatedUser;
    protected Organisation $organisation;
    protected Lead $lead;
    protected Brief $brief;
    protected array $createdUserIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->organisation = Organisation::create([
            'name' => 'Brief Chat Org ' . uniqid(),
            'slug' => 'brief-chat-org-' . uniqid(),
            'status' => '1',
        ]);
        $this->lead = Lead::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Brief Chat Lead ' . uniqid(),
            'slug' => 'brief-chat-lead-' . uniqid(),
            'organisation_id' => $this->organisation->id,
            'status' => '1',
        ]);
        $this->brief = Brief::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Brief Chat ' . uniqid(),
            'slug' => 'brief-chat-' . uniqid(),
            'contact_person_id' => $this->lead->id,
            'status' => '1',
        ]);

        $this->authenticatedUser = $this->createUser('Chat Reader');
        $this->token = auth()->login($this->authenticatedUser);
    }

    protected function tearDown(): void
    {
        BriefAssignHistory::withTrashed()->where('brief_id', $this->brief->id)->forceDelete();
        Brief::withTrashed()->where('id', $this->brief->id)->forceDelete();
        Lead::withTrashed()->where('id', $this->lead->id)->forceDelete();
        User::withTrashed()->whereIn('id', $this->createdUserIds)->forceDelete();
        Organisation::withTrashed()->where('id', $this->organisation->id)->forceDelete();

        parent::tearDown();
    }

    public function test_assign_history_chat_returns_only_chat_fields_for_the_brief(): void
    {
        $olderAssigner = $this->createUser('Older Assigner');
        $newerAssigner = $this->createUser('Newer Assigner');
        $this->createHistory($olderAssigner, 'Earlier note', '2026-09-27 10:00:00');
        $this->createHistory($newerAssigner, 'Latest note', '2026-09-28 20:53:38');

        $this->authGet("/api/v1/briefs/{$this->brief->id}/assign-histories-chat");
        $this->seeStatusCode(200);

        $response = json_decode($this->response->getContent(), true);

        $this->assertTrue($response['success']);
        $this->assertSame([
            [
                'current_user_id' => $newerAssigner->id,
                'current_user_name' => 'Newer Assigner',
                'brief_comment' => 'Latest note',
                'created_at' => '2026-09-28 20:53:38',
            ],
            [
                'current_user_id' => $olderAssigner->id,
                'current_user_name' => 'Older Assigner',
                'brief_comment' => 'Earlier note',
                'created_at' => '2026-09-27 10:00:00',
            ],
        ], $response['data']);
        $this->assertSame(
            ['current_user_id', 'current_user_name', 'brief_comment', 'created_at'],
            array_keys($response['data'][0])
        );
    }

    protected function authGet(string $uri)
    {
        return $this->get($uri, ['Authorization' => "Bearer {$this->token}"]);
    }

    protected function createHistory(User $assigner, string $comment, string $createdAt): BriefAssignHistory
    {
        $history = BriefAssignHistory::create([
            'uuid' => (string) Str::uuid(),
            'brief_id' => $this->brief->id,
            'assign_by_id' => $assigner->id,
            'assign_to_id' => $this->authenticatedUser->id,
            'comment' => $comment,
            'status' => '2',
        ]);
        $history->forceFill(['created_at' => $createdAt])->save();

        return $history;
    }

    protected function createUser(string $name): User
    {
        $user = User::create([
            'name' => $name,
            'email' => 'brief_chat_' . uniqid() . '@example.com',
            'password' => app('hash')->make('password123'),
            'organisation_id' => $this->organisation->id,
            'status' => '1',
        ]);
        $this->createdUserIds[] = $user->id;

        return $user;
    }
}