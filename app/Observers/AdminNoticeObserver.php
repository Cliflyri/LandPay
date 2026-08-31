<?php

namespace App\Observers;

use App\Models\AdminNotice;
use App\Services\AdminNoticeEmailService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class AdminNoticeObserver implements ShouldHandleEventsAfterCommit
{
    public function __construct(private readonly AdminNoticeEmailService $emails) {}

    public function created(AdminNotice $notice): void
    {
        $this->emails->send($notice);
    }
}
