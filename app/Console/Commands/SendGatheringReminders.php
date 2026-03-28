<?php

namespace App\Console\Commands;

use App\Services\GatheringService;
use Illuminate\Console\Command;

class SendGatheringReminders extends Command
{
    protected $signature = 'gatherings:send-reminders {--days=3 : Days before event}';

    protected $description = 'Send reminder notifications for upcoming gatherings';

    public function handle(GatheringService $gatheringService): int
    {
        $days = (int) $this->option('days');

        $this->info("Sending reminders for gatherings happening in {$days} day(s)...");

        $count = $gatheringService->sendReminders($days);

        $this->info("Sent {$count} reminder notification(s).");

        return self::SUCCESS;
    }
}
