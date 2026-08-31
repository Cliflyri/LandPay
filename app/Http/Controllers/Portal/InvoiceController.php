<?php
namespace App\Http\Controllers\Portal;

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\FinancialBalanceService;
use App\Services\InvoiceFirstViewService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
class InvoiceController extends Controller {
 public function __construct(private readonly FinancialBalanceService $balances,private readonly InvoiceFirstViewService $firstViews) {}
 
 
public function index(Request $request): View
{
    $allInvoices = Invoice::query()
        ->whereIn(
            'payment_plan_id',
            $request->user('client')->activePlanIds()
        )
        ->where('status', '!=', 'voided')
        ->with('paymentPlan')
        ->get();

    $balances = $allInvoices->mapWithKeys(
        fn (Invoice $invoice): array => [
            $invoice->id => $this->balances->invoiceBalance($invoice),
        ]
    );

    $sortedInvoices = $allInvoices
        ->sortBy(function (Invoice $invoice) use ($balances): array {
            $balance = (int) ($balances[$invoice->id] ?? 0);

            return [
                $balance > 0 ? 0 : 1,
                $balance > 0
                    ? $invoice->due_date->timestamp
                    : -$invoice->issue_date->timestamp,
                $invoice->id,
            ];
        })
        ->values();

    $page = max(1, (int) $request->integer('page', 1));
    $perPage = 20;

    $invoices = new \Illuminate\Pagination\LengthAwarePaginator(
        $sortedInvoices->forPage($page, $perPage)->values(),
        $sortedInvoices->count(),
        $perPage,
        $page,
        [
            'path' => $request->url(),
            'query' => $request->query(),
        ]
    );

    return view(
        'portal.invoices.index',
        compact('invoices', 'balances')
    );
}
 
public function show(Request $request, Invoice $invoice): View
{
    $this->authorizeInvoice($request, $invoice);
    $this->firstViews->record($invoice,$request->user('client'));

    $invoice->load([
        'paymentPlan',
        'items',
    ]);

    $balance = $this->balances->invoiceBalance($invoice);
    $invoiceAmount = (int) $invoice->items->sum('amount');
    $creditApplied = $this->balances->invoiceCreditApplied($invoice);

    $paidToDate = (int) DB::table('payment_allocations')
        ->join(
            'payments',
            'payments.id',
            '=',
            'payment_allocations.payment_id'
        )
        ->join(
            'financial_transactions',
            'financial_transactions.id',
            '=',
            'payments.financial_transaction_id'
        )
        ->where('payment_allocations.invoice_id', $invoice->id)
        ->whereNotExists(
            fn ($query) => $query
                ->selectRaw('1')
                ->from('financial_transactions as reversals')
                ->whereColumn(
                    'reversals.reversal_of_transaction_id',
                    'financial_transactions.id'
                )
        )
        ->sum('payment_allocations.amount');

    $adjustments = $balance
        - $invoiceAmount
        + $paidToDate
        + $creditApplied;

    return view('portal.invoices.show', [
        'invoice' => $invoice,
        'balance' => $balance,
        'invoiceAmount' => $invoiceAmount,
        'creditApplied' => $creditApplied,
        'paidToDate' => $paidToDate,
        'adjustments' => $adjustments,
    ]);
}
 
 public function download(Request $request, Invoice $invoice): Response { $this->authorizeInvoice($request,$invoice); $invoice->load(['paymentPlan.memberships.client','items']); return Pdf::loadView('pdf.invoice',['invoice'=>$invoice,'balance'=>$this->balances->invoiceBalance($invoice),'creditApplied'=>$this->balances->invoiceCreditApplied($invoice)])->download($invoice->invoice_number.'.pdf'); }
 private function authorizeInvoice(Request $request, Invoice $invoice): void { abort_unless($invoice->status->value !== 'voided' && in_array($invoice->payment_plan_id,$request->user('client')->activePlanIds(),true),404); }
}
