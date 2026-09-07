<?php
namespace App\Console\Commands;
use App\Services\ClientAnnouncementService;
use Illuminate\Console\Command;
class ProcessClientAnnouncements extends Command{
 protected $signature='announcements:process';protected $description='Activate due client announcements and send notifications';
 public function handle(ClientAnnouncementService $service):int{$count=$service->processDue();$this->info("Processed {$count} announcement(s).");return self::SUCCESS;}
}
