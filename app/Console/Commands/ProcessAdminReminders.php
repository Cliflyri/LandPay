<?php
namespace App\Console\Commands;
use App\Services\AdminReminderService;
use Illuminate\Console\Command;
class ProcessAdminReminders extends Command{
 protected $signature='admin-reminders:process';
 protected $description='Create due monthly administrator reminder occurrences';
 public function handle(AdminReminderService $reminders):int{
  $this->info($reminders->processDue().' reminder occurrence(s) created.');return self::SUCCESS;
 }
}
