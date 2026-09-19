<?php

namespace App\Console\Commands;

use App\Services\ReportSnapshotService;
use Illuminate\Console\Command;

class CreateReportSnapshot extends Command
{
    protected $signature = 'reports:snapshot {--force}';
    protected $description = 'Create a scheduled CSV snapshot of all admin reports';

    public function handle(ReportSnapshotService $snapshots): int
    {
        if (!$this->option('force') && !$snapshots->isDue()) return self::SUCCESS;
        $snapshot = $snapshots->create($this->option('force') ? 'manual' : 'scheduled');
        $this->info('Created '.$snapshot['id']);
        return self::SUCCESS;
    }
}
