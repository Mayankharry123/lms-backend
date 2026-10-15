<?php

namespace App\Console\Commands;

use App\Services\MeetingService;
use Illuminate\Console\Command;

class CompleteExpiredMeetings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'meetings:complete-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically mark meetings as completed whose end time has passed and update lead status to Meeting Done';

    /**
     * Execute the console command.
     *
     * @param MeetingService $meetingService
     * @return int
     */
    public function handle(MeetingService $meetingService): int
    {
        $this->info('Checking for expired meetings...');

        $updatedCount = $meetingService->completeExpiredMeetings();

        if ($updatedCount > 0) {
            $this->info("Successfully updated {$updatedCount} expired meeting(s) to 'Meeting Done'.");
        } else {
            $this->info('No expired meetings found.');
        }

        return self::SUCCESS;
    }
}
