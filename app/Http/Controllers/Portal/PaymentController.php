<?php
namespace App\Http\Controllers\Portal;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\FinancialBalanceService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
class PaymentController extends Controller {
 public function __construct(private readonly FinancialBalanceService $balances) {}
 public function index(Request $request): View { $payments=Payment::query()->whereHas('financialTransaction',fn($q)=>$q->whereIn('payment_plan_id',$request->user('client')->activePlanIds()))->with(['financialTransaction.paymentPlan','financialTransaction.reversedBy'])->newestFirst()->paginate(20); return view('portal.payments.index',compact('payments')); }
 public function show(Request $request, Payment $payment): View { $this->authorizePayment($request,$payment); $payment->load(['financialTransaction.paymentPlan','financialTransaction.reversedBy','allocations.invoice','allocations.invoiceItem']); return view('portal.payments.show',['payment'=>$payment,'contractBalance'=>$this->balances->contractBalance($payment->financialTransaction->paymentPlan)]); }
 public function download(Request $request, Payment $payment): Response { $this->authorizePayment($request,$payment); $payment->load(['financialTransaction.paymentPlan','financialTransaction.reversedBy','allocations.invoice','allocations.invoiceItem','payer']); abort_if($payment->financialTransaction->reversedBy!==null,404); return Pdf::loadView('pdf.payment-receipt',['payment'=>$payment,'contractBalance'=>$this->balances->contractBalance($payment->financialTransaction->paymentPlan)])->download('receipt-'.$payment->financialTransaction->uuid.'.pdf'); }
 private function authorizePayment(Request $request, Payment $payment): void { $payment->loadMissing('financialTransaction'); abort_unless(in_array($payment->financialTransaction->payment_plan_id,$request->user('client')->activePlanIds(),true),404); }
}
