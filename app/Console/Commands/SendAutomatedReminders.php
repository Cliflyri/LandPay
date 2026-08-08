<?php

namespace App\Console\Commands;

use App\Services\ReminderAutomationService;
use Illuminate\Console\Command;

class SendAutomatedReminders extends Command
{
    protected $signature = 'reminders:send';
    protected $description = 'Send eligible automated invoice reminders';

    public function handle(ReminderAutomationService $automation): int
    {
        $result = $automation->run();
        $this->info("Automated reminders: {$result['sent']} sent, {$result['failed']} failed, {$result['skipped']} skipped.");
        return $result['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
