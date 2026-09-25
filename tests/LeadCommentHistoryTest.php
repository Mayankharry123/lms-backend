<?php

namespace Tests;

use App\Models\Lead;
use App\Models\LeadAssignHistory;
use App\Models\User;
use App\Repositories\LeadRepository;
use Illuminate\Support\Str;

class LeadCommentHistoryTest extends TestCase
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
            'name' => 'Lead History Tester',
            'email' => 'lead_history_' . uniqid() . '@example.com',
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

    public function test_create_lead_with_comment_writes_history(): void
    {
        $comment = 'Created with comment ' . uniqid();
        $lead = $this->leadRepository->createLead([
            'name' => 'History Create Comment ' . Str::upper(Str::random(6)),
            'comment' => $comment,
            'status' => '1',
        ]);
        $this->createdLeadIds[] = $lead->id;

        $history = LeadAssignHistory::where('lead_id', $lead->id)->get();
        $this->assertCount(1, $history);
        $this->assertSame($comment, $history->first()->lead_comment);
        $this->assertSame($this->user->id, (int) $history->first()->current_user_id);
    }

    public function test_create_lead_without_comment_or_status_change_skips_history(): void
    {
        $lead = $this->leadRepository->createLead([
            'name' => 'History Create No Comment ' . Str::upper(Str::random(6)),
            'status' => '1',
        ]);
        $this->createdLeadIds[] = $lead->id;

        $this->assertSame(0, LeadAssignHistory::where('lead_id', $lead->id)->count());
    }

    public function test_update_comment_writes_history_and_skips_duplicates(): void
    {
        $lead = $this->leadRepository->createLead([
            'name' => 'History Update Comment ' . Str::upper(Str::random(6)),
            'status' => '1',
        ]);
        $this->createdLeadIds[] = $lead->id;

        $comment = 'Updated comment ' . uniqid();
        $this->leadRepository->updateLead($lead->id, ['comment' => $comment]);

        $history = LeadAssignHistory::where('lead_id', $lead->id)->orderBy('id')->get();
        $this->assertCount(1, $history);
        $this->assertSame($comment, $history->first()->lead_comment);
        $this->assertNull($history->first()->last_call_status_date_time);

        $this->leadRepository->updateLead($lead->id, ['comment' => $comment]);
        $this->assertSame(1, LeadAssignHistory::where('lead_id', $lead->id)->count());

        $this->leadRepository->updateLead($lead->id, ['name' => $lead->name . ' Renamed']);
        $this->assertSame(1, LeadAssignHistory::where('lead_id', $lead->id)->count());
    }

    public function test_update_status_and_comment_together_writes_one_history(): void
    {
        $lead = $this->leadRepository->createLead([
            'name' => 'History Both Fields ' . Str::upper(Str::random(6)),
            'status' => '1',
        ]);
        $this->createdLeadIds[] = $lead->id;

        $comment = 'Status and comment ' . uniqid();
        $this->leadRepository->updateLead($lead->id, [
            'status' => '2',
            'comment' => $comment,
        ]);

        $history = LeadAssignHistory::where('lead_id', $lead->id)->get();
        $this->assertCount(1, $history);
        $this->assertSame($comment, $history->first()->lead_comment);
        $this->assertSame('2', (string) $history->first()->status);
    }
}
