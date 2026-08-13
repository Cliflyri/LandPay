<?php
namespace App\Console\Commands;
use App\Services\LateFeeAssessmentService;
use Illuminate\Console\Command;
class AssessLateFees extends Command {
 protected $signature='late-fees:assess';
 protected $description='Assess eligible invoice late fees';
 public function handle(LateFeeAssessmentService $service):int{$result=$service->run();$this->info('Late fee assessment complete.');return $result['failed']>0?self::FAILURE:self::SUCCESS;}
}
