<?php

namespace App\Services;

use App\Models\Client;
use App\Models\ClientSmsConsentEvent;
use App\Models\ClientSmsPreference;
use App\Models\PortalAccount;
use App\Models\User;
use Illuminate\Http\Request;

class ClientSmsPreferenceService
{
    public function __construct(private readonly PhoneNumberService $phones) {}

    public function set(Client $client, bool $enabled, string $source, Request $request, ?User $user = null, ?PortalAccount $account = null): ClientSmsPreference
    {
        $phone = $enabled ? $this->phones->requiredE164($client->primary_phone, $client->country_code ?: 'US') : $this->phones->e164($client->primary_phone, $client->country_code ?: 'US');
        $preference = ClientSmsPreference::query()->firstOrNew(['client_id' => $client->id]);
        $preference->fill($enabled ? [
            'enabled' => true, 'sms_phone_e164' => $phone, 'opted_in_at' => now(), 'opt_in_source' => $source,
            'opted_out_at' => null, 'opt_out_source' => null, 'stopped_at' => null,
        ] : [
            'enabled' => false, 'opted_out_at' => now(), 'opt_out_source' => $source,
        ])->save();
        ClientSmsConsentEvent::query()->create([
            'client_id' => $client->id, 'enabled' => $enabled, 'source' => $source, 'phone_snapshot' => $phone,
            'changed_by_user_id' => $user?->id, 'portal_account_id' => $account?->id,
            'ip_address' => $request->ip(), 'user_agent' => str($request->userAgent())->limit(500),
        ]);
        return $preference;
    }

    public function stop(Client $client, string $phone): void
    {
        $preference = ClientSmsPreference::query()->firstOrNew(['client_id' => $client->id]);
        $preference->fill(['enabled' => false, 'sms_phone_e164' => $phone, 'opted_out_at' => now(), 'opt_out_source' => 'twilio_stop', 'stopped_at' => now()])->save();
        ClientSmsConsentEvent::query()->create(['client_id' => $client->id, 'enabled' => false, 'source' => 'twilio_stop', 'phone_snapshot' => $phone]);
    }
}
