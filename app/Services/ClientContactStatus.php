<?php

namespace App\Services;

use App\Models\Client;
use App\Models\ClientChangeRequest;

class ClientContactStatus
{
    public function forClient(Client $client): array
    {
        $pending=ClientChangeRequest::query()->where('client_id',$client->id)->where('status','pending')->latest()->first();
        $missingPhone=blank($client->primary_phone);
        $addressFields=['address_line_1','city','state_region','postal_code'];
        $missingAddress=collect($addressFields)->contains(fn($field)=>blank($client->{$field}));
        $contactUpdatePending=($missingPhone||$missingAddress)
            &&(!$missingPhone||filled(data_get($pending?->changes,'primary_phone.to')))
            &&(!$missingAddress||collect($addressFields)->every(fn($field)=>filled($client->{$field})||filled(data_get($pending?->changes,$field.'.to'))));
        return compact('pending','missingPhone','missingAddress','contactUpdatePending');
    }
}
