<?php
namespace App\Http\Controllers\Portal;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentPlan;
use App\Models\ClientPaymentIntent;
use App\Models\SecureMessageThread;
use App\Services\ClientContactStatus;
use App\Services\FinancialBalanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
class DashboardController extends Controller {
 public function __construct(private readonly FinancialBalanceService $balances, private readonly ClientContactStatus $contactStatus){}
 public function __invoke(Request $request): View {
  $account=$request->user('client')->load('client');$planIds=$account->activePlanIds();
  $contactStatus=$this->contactStatus->forClient($account->client);
  $plans=PaymentPlan::query()->whereIn('id',$planIds)->with(['invoices','currentBillingTerms'])->orderBy('plan_number')->get();
  $allInvoices=Invoice::query()->whereIn('payment_plan_id',$planIds)->where('status','!=','voided')->with('paymentPlan')->latest('issue_date')->get();
  $invoiceBalances=$allInvoices->mapWithKeys(fn(Invoice $invoice)=>[$invoice->id=>$this->balances->invoiceBalance($invoice)]);
  $accountCredit=(int)$plans->sum(fn(PaymentPlan $plan)=>max(0,$this->balances->clientCredit($plan)));
  
  
$open = $allInvoices
    ->filter(
        fn (Invoice $invoice) => ($invoiceBalances[$invoice->id] ?? 0) > 0
    )
    ->sortBy([
        ['due_date', 'asc'],
        ['issue_date', 'asc'],
        ['id', 'asc'],
    ])
    ->values();

$openInvoiceRows = $open->map(
    fn (Invoice $invoice): array => [
        'invoice' => $invoice,
        'balance' => (int) ($invoiceBalances[$invoice->id] ?? 0),
    ]
);

$amountDue = (int) $openInvoiceRows->sum('balance');
$accountBalance = $amountDue;
$oldestDue = $open->first();

  $status=$this->status($amountDue,$oldestDue?->due_date,$accountCredit);
  $planSummaries=$plans->map(function(PaymentPlan $plan){$terms=$plan->currentBillingTerms;$monthly=($terms?->scheduled_payment_amount??$plan->customary_monthly_payment)+($terms?->monthly_service_fee??$plan->monthly_service_fee);return ['plan'=>$plan,'monthly_payment'=>$monthly];});
  $paymentQuery=Payment::query()->whereHas('financialTransaction',fn($q)=>$q->whereIn('payment_plan_id',$planIds))->whereDoesntHave('financialTransaction.reversedBy');
  $payments=(clone $paymentQuery)->with('financialTransaction.paymentPlan')->newestFirst()->limit(3)->get();
  $pendingPaymentIntents=ClientPaymentIntent::query()->where('client_id',$account->client_id)->where('status','announced')->whereHas('adminNotice',fn($notice)=>$notice->whereNull('dismissed_at'))->with('paymentPlan')->latest()->get();
  $unreadMessageThreads=SecureMessageThread::query()->where('client_id',$account->client_id)->unreadByClient()->orderByDesc('latest_message_at')->limit(3)->get();
  
$invoices = $allInvoices
    ->sortBy(function (Invoice $invoice) use ($invoiceBalances): array {
        $balance = (int) ($invoiceBalances[$invoice->id] ?? 0);

        return [
            $balance > 0 ? 0 : 1,
            $balance > 0
                ? $invoice->due_date->timestamp
                : -$invoice->issue_date->timestamp,
            $invoice->id,
        ];
    })
    ->take(3)
    ->values()
    ->map(
        fn (Invoice $invoice): array => [
            'invoice' => $invoice,
            'balance' => (int) ($invoiceBalances[$invoice->id] ?? 0),
        ]
    );
  
  return view('portal.dashboard',compact('account','planSummaries','invoices','openInvoiceRows','payments','amountDue','accountCredit','accountBalance','status','oldestDue','pendingPaymentIntents','unreadMessageThreads')+$contactStatus);
 
 
  }
private function status(int $amountDue, ?Carbon $dueDate, int $accountCredit): array
{
    if ($amountDue <= 0 || $dueDate === null) {
        return [
            'label' => 'Current - nothing due',
            'class' => 'status-current',
        ];
    }

    if ($accountCredit >= $amountDue) {
        return ['label' => 'Account credit available to cover open invoices', 'class' => 'status-current'];
    }
    if ($accountCredit > 0) {
        return ['label' => 'Account credit available · Payment still due', 'class' => 'status-due-soon'];
    }

    return [
        'label' => 'Payment due now · Late after '.$dueDate->format('M j, Y'),
        'class' => $dueDate->isPast()
            ? 'status-past-due'
            : ($dueDate->isToday() ? 'status-due' : 'status-due-soon'),
    ];
}
}
