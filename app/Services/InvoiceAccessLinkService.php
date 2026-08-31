<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceAccessLink;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InvoiceAccessLinkService
{
    public function activeOrCreate(Invoice $invoice, Client $client, ?User $actor = null): InvoiceAccessLink
    {
        $existing = InvoiceAccessLink::query()->where('invoice_id', $invoice->id)->first();
        if ($existing?->isActive() && $existing->client_id === $client->id) return $existing;

        return $this->regenerate($invoice, $client, $actor);
    }

    public function regenerate(Invoice $invoice, Client $client, ?User $actor = null): InvoiceAccessLink
    {
        return DB::transaction(function () use ($invoice, $client, $actor): InvoiceAccessLink {
            $token = Str::random(64);
            return InvoiceAccessLink::query()->updateOrCreate(
                ['invoice_id' => $invoice->id],
                [
                    'client_id' => $client->id,
                    'created_by_user_id' => $actor?->id,
                    'token_hash' => hash('sha256', $token),
                    'token_encrypted' => $token,
                    'expires_at' => now()->addDays(30),
                    'revoked_at' => null,
                    'last_accessed_at' => null,
                ],
            );
        }, 3);
    }

    public function revoke(Invoice $invoice): void
    {
        InvoiceAccessLink::query()->where('invoice_id', $invoice->id)->update(['revoked_at' => now()]);
    }

    public function findToken(string $token): ?InvoiceAccessLink
    {
        return InvoiceAccessLink::query()->with(['invoice.paymentPlan', 'client.portalAccount'])
            ->where('token_hash', hash('sha256', $token))->first();
    }

    public function url(InvoiceAccessLink $link): string
    {
        return route('secure-invoice.enter', ['token' => $link->token_encrypted]);
    }
}
