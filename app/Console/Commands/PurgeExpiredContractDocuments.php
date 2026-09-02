<?php

namespace App\Console\Commands;

use App\Services\ContractDocumentService;
use Illuminate\Console\Command;

class PurgeExpiredContractDocuments extends Command
{
    protected $signature = 'contracts:purge-expired';
    protected $description = 'Delete generated contract files after their retention period';

    public function handle(ContractDocumentService $documents): int
    {
        $count = $documents->purgeExpired();
        $this->info("Deleted {$count} expired contract document(s).");

        return self::SUCCESS;
    }
}
