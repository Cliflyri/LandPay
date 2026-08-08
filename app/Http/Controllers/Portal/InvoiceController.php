<?php
namespace App\Http\Controllers\Portal;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\FinancialBalanceService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
class InvoiceController extends Controller {
 public function __construct(private readonly FinancialBalanceService $balances) {}
 public function index(Request $request): View { $invoices=Invoice::query()->whereIn('payment_plan_id',$request->user('client')->activePlanIds())->where('status','!=','voided')->with('paymentPlan')->latest('issue_date')->paginate(20); $balances=$invoices->getCollection()->mapWithKeys(fn(Invoice $invoice)=>[$invoice->id=>$this->balances->invoiceBalance($invoice)]); return view('portal.invoices.index',compact('invoices','balances')); }
 public function show(Request $request, Invoice $invoice): View { $this->authorizeInvoice($request,$invoice); $invoice->load(['paymentPlan.memberships.client','items']); return view('portal.invoices.show',['invoice'=>$invoice,'balance'=>$this->balances->invoiceBalance($invoice),'creditApplied'=>$this->balances->invoiceCreditApplied($invoice)]); }
 public function download(Request $request, Invoice $invoice): Response { $this->authorizeInvoice($request,$invoice); $invoice->load(['paymentPlan.memberships.client','items']); return Pdf::loadView('pdf.invoice',['invoice'=>$invoice,'balance'=>$this->balances->invoiceBalance($invoice),'creditApplied'=>$this->balances->invoiceCreditApplied($invoice)])->download($invoice->invoice_number.'.pdf'); }
 private function authorizeInvoice(Request $request, Invoice $invoice): void { abort_unless($invoice->status->value !== 'voided' && in_array($invoice->payment_plan_id,$request->user('client')->activePlanIds(),true),404); }
}
