<?php

namespace App\Console\Commands;

use App\Services\AutomaticInvoiceService;
use Illuminate\Console\Command;

class GenerateAutomaticInvoices extends Command
{
    protected $signature = 'invoices:generate';
    protected $description = 'Generate all scheduled invoices due through today';

    public function handle(AutomaticInvoiceService $service): int
    {
        $result = $service->run();
        $this->info("Created {$result['created']}; emailed {$result['emailed']}; failures {$result['failed']}.");
        return $result['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
