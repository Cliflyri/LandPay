<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InvoiceStatus;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\InvoiceAccessLinkService;
use App\Services\InvoiceReminderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InvoiceAccessLinkController extends Controller
{
    public function __construct(
        private readonly InvoiceAccessLinkService $links,
        private readonly InvoiceReminderService $recipients,
    ) {}

    public function store(Request $request, Invoice $invoice): RedirectResponse
    {
        abort_if($invoice->status === InvoiceStatus::Voided, 422);
        $invoice->load('paymentPlan.memberships.client');
        $client = $this->recipients->recipientMembership($invoice)?->client;
        abort_unless($client, 422);
        $link = $this->links->activeOrCreate($invoice, $client, $request->user());
        return back()->with('success', 'Secure payment link ready. It expires '.$link->expires_at->format('M j, Y').'.')
            ->with('secure_link_url', $this->links->url($link));
    }

    public function regenerate(Request $request, Invoice $invoice): RedirectResponse
    {
        abort_if($invoice->status === InvoiceStatus::Voided, 422);
        $invoice->load('paymentPlan.memberships.client');
        $client = $this->recipients->recipientMembership($invoice)?->client;
        abort_unless($client, 422);
        $link = $this->links->regenerate($invoice, $client, $request->user());
        return back()->with('success', 'Secure payment link regenerated. The previous link no longer works.')
            ->with('secure_link_url', $this->links->url($link));
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        $this->links->revoke($invoice);
        return back()->with('success', 'Secure payment link revoked.');
    }
}
