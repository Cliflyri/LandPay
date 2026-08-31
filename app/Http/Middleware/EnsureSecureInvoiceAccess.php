<?php

namespace App\Http\Middleware;

use App\Models\InvoiceAccessLink;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSecureInvoiceAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $link = InvoiceAccessLink::query()->with(['invoice.paymentPlan', 'client.portalAccount'])
            ->find($request->session()->get('secure_invoice_link_id'));

        if (! $link?->isActive() || $link->invoice->status->value === 'voided') {
            $request->session()->forget('secure_invoice_link_id');
            if ($link?->invoice && $link->client->portalAccount?->enabled) {
                if (($account = $request->user('client')) && in_array($link->invoice->payment_plan_id, $account->activePlanIds(), true)) {
                    return redirect()->route('portal.invoices.show', $link->invoice);
                }
                $request->session()->put('url.intended', route('portal.invoices.show', $link->invoice, absolute: false));
            }
            return redirect()->route('portal.login')->with('status', 'This secure invoice link has expired. Sign in to view the invoice.');
        }

        $request->attributes->set('secureInvoiceLink', $link);
        $response = $next($request);
        $response->headers->set('Referrer-Policy', 'no-referrer');
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        return $response;
    }
}
