<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\FinancialBalanceService;
use App\Services\InvoiceAccessLinkService;
use App\Services\InvoiceFirstViewService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class SecureInvoiceController extends Controller
{
    public function __construct(
        private readonly InvoiceAccessLinkService $links,
        private readonly FinancialBalanceService $balances,
        private readonly InvoiceFirstViewService $firstViews,
    ) {}

    public function enter(Request $request, string $token): RedirectResponse
    {
        $link = $this->links->findToken($token);
        if (! $link?->isActive() || $link->invoice->status->value === 'voided') {
            if ($link?->invoice && $link->client->portalAccount?->enabled) {
                if (($account = $request->user('client')) && in_array($link->invoice->payment_plan_id, $account->activePlanIds(), true)) {
                    return redirect()->route('portal.invoices.show', $link->invoice);
                }
                $request->session()->put('url.intended', route('portal.invoices.show', $link->invoice, absolute: false));
            }
            return redirect()->route('portal.login')->with('status', 'This secure invoice link is no longer available. Sign in to view the invoice.');
        }
        $request->session()->put('secure_invoice_link_id', $link->id);
        return redirect()->route('secure-invoice.show');
    }

    public function show(Request $request): View
    {
        $link = $request->attributes->get('secureInvoiceLink');
        $invoice = $link->invoice;
        $this->firstViews->record($invoice, $link->client);
        $invoice->load(['paymentPlan', 'items']);
        $balance = $this->balances->invoiceBalance($invoice);
        $invoiceAmount = (int) $invoice->items->sum('amount');
        $creditApplied = $this->balances->invoiceCreditApplied($invoice);
        $paidToDate = (int) DB::table('payment_allocations')->join('payments', 'payments.id', '=', 'payment_allocations.payment_id')
            ->join('financial_transactions', 'financial_transactions.id', '=', 'payments.financial_transaction_id')
            ->where('payment_allocations.invoice_id', $invoice->id)
            ->whereNotExists(fn ($query) => $query->selectRaw('1')->from('financial_transactions as reversals')
                ->whereColumn('reversals.reversal_of_transaction_id', 'financial_transactions.id'))
            ->sum('payment_allocations.amount');

        return view('portal.invoices.show', [
            'invoice' => $invoice, 'balance' => $balance, 'invoiceAmount' => $invoiceAmount,
            'creditApplied' => $creditApplied, 'paidToDate' => $paidToDate,
            'adjustments' => $balance - $invoiceAmount + $paidToDate + $creditApplied,
            'secureAccess' => true,
        ]);
    }

    public function download(Request $request): Response
    {
        $invoice = $request->attributes->get('secureInvoiceLink')->invoice;
        $invoice->load(['paymentPlan.memberships.client', 'items']);
        return Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice, 'balance' => $this->balances->invoiceBalance($invoice),
            'creditApplied' => $this->balances->invoiceCreditApplied($invoice),
        ])->download($invoice->invoice_number.'.pdf');
    }
}
