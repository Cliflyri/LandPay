<?php

namespace App\Services;

use App\Models\Client;
use App\Models\PortalAccount;
use App\Models\PortalInvitation;
use Illuminate\Support\Facades\DB;

class PortalAccessService
{
    public function revoke(Client $client): void
    {
        DB::transaction(function () use ($client): void {
            PortalAccount::query()
                ->where('client_id', $client->id)
                ->lockForUpdate()
                ->update(['enabled' => false, 'remember_token' => null]);

            PortalInvitation::query()
                ->where('client_id', $client->id)
                ->whereNull('accepted_at')
                ->whereNull('revoked_at')
                ->update(['revoked_at' => now()]);
        });
    }
}