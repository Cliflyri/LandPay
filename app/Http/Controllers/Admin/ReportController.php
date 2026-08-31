<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InvoiceItemType;
use App\Enums\PaymentAllocationType;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\PaymentPlan;
use App\Services\FinancialBalanceService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    private const REPORTS = ['payments', 'receivables', 'contracts', 'fees'];

    public function __construct(private readonly FinancialBalanceService $balances) {}

    public function show(Request $request, string $report = 'payments'): View
    {
        abort_unless(in_array($report, self::REPORTS, true), 404);
        $this->applyDefaults($request, $report);
        $result = $this->build($request, $report);
        $rows = $this->paginate($result['rows'], $request);

        return view('admin.reports.show', [
            'report' => $report,
            'rows' => $rows,
            'totals' => $result['totals'],
            'filters' => $this->filters($request),
        ]);
    }

    public function export(Request $request, string $report): StreamedResponse
    {
        abort_unless(in_array($report, self::REPORTS, true), 404);
        $this->applyDefaults($request, $report);
        $result = $this->build($request, $report);
        [$headers, $values] = $this->csvDefinition($report);

        return response()->streamDownload(function () use ($headers, $values, $result): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);
            foreach ($result['rows'] as $row) fputcsv($out, $values($row));
            fclose($out);
        }, 'landpay-'.$report.'-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }

    private function build(Request $request, string $report): array
    {
        return match ($report) {
            'payments' => $this->payments($request),
            'receivables' => $this->receivables($request),
            'contracts' => $this->contracts($request),
            'fees' => $this->fees($request),
        };
    }

    private function payments(Request $request): array
    {
        $query = Payment::query()->with([
            'payer', 'financialTransaction.paymentPlan.memberships.client',
            'financialTransaction.reversedBy', 'allocations.invoiceItem', 'allocations.invoice',
        ])->newestFirst();
        $this->dates($query, $request, 'received_date');
        $this->paymentSearch($query, $request);

        $rows = $query->get()->map(function (Payment $payment): array {
            $allocations = $payment->allocations;
            $principal = $allocations->sum(fn ($a) =>
                $a->allocation_type === PaymentAllocationType::PurchaseBalance
                || ($a->allocation_type === PaymentAllocationType::InvoiceItem
                    && $a->invoiceItem?->item_type === InvoiceItemType::ScheduledPurchasePayment)
                    ? (int) $a->amount : 0);
            $fees = $allocations->sum(fn ($a) =>
                in_array($a->allocation_type, [PaymentAllocationType::ServiceFee, PaymentAllocationType::ProcessingFee], true)
                || ($a->allocation_type === PaymentAllocationType::InvoiceItem
                    && $a->invoiceItem?->item_type !== InvoiceItemType::ScheduledPurchasePayment)
                    ? (int) $a->amount : 0);
            $credit = $allocations->where('allocation_type', PaymentAllocationType::ClientCredit)->sum('amount');
            $reversed = (bool) $payment->financialTransaction->reversedBy;
            $plan = $payment->financialTransaction->paymentPlan;
            return [
                'model' => $payment, 'date' => $payment->received_date, 'client' => $payment->payer ?: $this->primaryClient($plan),
                'plan' => $plan, 'method' => str($payment->payment_method->value)->replace('_', ' ')->title(),
                'gross' => (int) $payment->gross_amount, 'fees' => $fees, 'invoice' => (int) $allocations->whereNotNull('invoice_id')->sum('amount'),
                'principal' => $principal, 'credit' => $credit, 'reversed' => $reversed,
                'net' => $reversed ? 0 : (int) $payment->gross_amount, 'reference' => $payment->reference,
            ];
        });
        return ['rows' => $rows, 'totals' => [
            'Collected' => $rows->where('reversed', false)->sum('gross'),
            'Fees' => $rows->where('reversed', false)->sum('fees'),
            'Applied to principal' => $rows->where('reversed', false)->sum('principal'),
            'Account credit' => $rows->where('reversed', false)->sum('credit'),
            'Reversed' => $rows->where('reversed', true)->sum('gross'),
        ]];
    }

    private function receivables(Request $request): array
    {
        $query = Invoice::query()->where('status', '!=', 'voided')
            ->with(['paymentPlan.memberships.client', 'items'])->orderBy('due_date');
        $this->dates($query, $request, 'issue_date');
        $this->invoiceSearch($query, $request);

        $today = now()->startOfDay();
        $rows = $query->get()->map(function (Invoice $invoice) use ($today): ?array {
            $balance = $this->balances->invoiceBalance($invoice);
            if ($balance <= 0) return null;
            $amount = (int) $invoice->items->sum('amount');
            $days = $invoice->due_date->isPast() ? $invoice->due_date->diffInDays($today) : 0;
            $bucket = match (true) {
                $days === 0 => 'Current / Not Yet Due', $days <= 30 => '1-30 Days Overdue', $days <= 60 => '31-60 Days Overdue',
                $days <= 90 => '61-90 Days Overdue', default => '90+ Days Overdue',
            };
            return [
                'model' => $invoice, 'client' => $this->primaryClient($invoice->paymentPlan),
                'plan' => $invoice->paymentPlan, 'issue' => $invoice->issue_date, 'due' => $invoice->due_date,
                'amount' => $amount, 'paid' => max(0, $amount - $balance), 'balance' => $balance,
                'days' => $days, 'bucket' => $bucket,
            ];
        })->filter()->values();
        $totals = collect(['Current / Not Yet Due', '1-30 Days Overdue', '31-60 Days Overdue', '61-90 Days Overdue', '90+ Days Overdue'])
            ->mapWithKeys(fn ($bucket) => [$bucket => $rows->where('bucket', $bucket)->sum('balance')])->all();
        if ($request->filled('aging') && array_key_exists($request->aging, $totals)) {
            $rows = $rows->where('bucket', $request->aging)->values();
        }
        return ['rows' => $rows, 'totals' => $totals];
    }

    private function contracts(Request $request): array
    {
        $query = PaymentPlan::query()->with(['memberships.client', 'invoices.items', 'currentBillingTerms'])
            ->orderBy('plan_number');
        $this->planSearch($query, $request);
        if ($request->filled('status') && $request->status !== 'all') $query->where('status', $request->status);

        $rows = $query->get()->map(function (PaymentPlan $plan): array {
            $contract = $this->balances->contractBalance($plan);
            $open = $plan->invoices->filter(fn ($invoice) => $invoice->status->value !== 'voided')->sum(fn ($invoice) => max(0, $this->balances->invoiceBalance($invoice)));
            $next = $plan->invoices->filter(fn ($invoice) => $invoice->status->value !== 'voided' && $this->balances->invoiceBalance($invoice) > 0)->sortBy('due_date')->first()?->due_date;
            $monthly = (int) ($plan->currentBillingTerms?->scheduled_payment_amount ?: $plan->customary_monthly_payment);
            $months = $monthly > 0 ? (int) ceil(max(0, $contract) / $monthly) : null;
            return [
                'model' => $plan, 'client' => $this->primaryClient($plan), 'purchase' => (int) $plan->purchase_price,
                'documentation' => max(0, (int) $plan->documentation_fee_standard - (int) $plan->documentation_fee_waived),
                'principal_paid' => $this->balances->purchasePrincipalPaid($plan), 'contract' => $contract,
                'open' => $open, 'credit' => max(0, $this->balances->clientCredit($plan)), 'next_due' => $next,
                'status' => str($plan->status)->replace('_', ' ')->title(), 'payoff' => $contract <= 0 ? 'Paid off' : ($months ? $months.' months' : 'Not available'),
            ];
        });
        return ['rows' => $rows, 'totals' => [
            'Purchase price' => $rows->sum('purchase'), 'Principal paid' => $rows->sum('principal_paid'),
            'Contract balance' => $rows->sum('contract'), 'Open invoices' => $rows->sum('open'),
            'Account credit' => $rows->sum('credit'),
        ]];
    }

    private function fees(Request $request): array
    {
        $query = InvoiceItem::query()->where('item_type', '!=', InvoiceItemType::ScheduledPurchasePayment->value)
            ->whereNull('retired_at')->with(['invoice.paymentPlan.memberships.client'])->orderByDesc('id');
        if ($request->filled('from')) $query->whereHas('invoice', fn ($q) => $q->whereDate('issue_date', '>=', $request->from));
        if ($request->filled('to')) $query->whereHas('invoice', fn ($q) => $q->whereDate('issue_date', '<=', $request->to));
        if ($request->filled('search')) {
            $term = '%'.trim($request->search).'%';
            $query->where(fn ($q) => $q->where('description', 'like', $term)->orWhereHas('invoice', fn ($i) =>
                $i->where('invoice_number', 'like', $term)->orWhereHas('paymentPlan', fn ($p) =>
                    $p->where('plan_number', 'like', $term)->orWhere('apn', 'like', $term)->orWhereHas('memberships.client', fn ($c) =>
                        $c->where('first_name', 'like', $term)->orWhere('last_name', 'like', $term)->orWhere('organization_name', 'like', $term)))));
        }
        $items = $query->get();
        $collected = DB::table('payment_allocations')->join('payments', 'payments.id', '=', 'payment_allocations.payment_id')
            ->join('financial_transactions', 'financial_transactions.id', '=', 'payments.financial_transaction_id')
            ->whereIn('payment_allocations.invoice_item_id', $items->pluck('id'))
            ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('financial_transactions as reversals')->whereColumn('reversals.reversal_of_transaction_id', 'financial_transactions.id'))
            ->groupBy('payment_allocations.invoice_item_id')->selectRaw('payment_allocations.invoice_item_id, SUM(payment_allocations.amount) total')->pluck('total', 'invoice_item_id');
        $rows = $items->map(function (InvoiceItem $item) use ($collected): array {
            $invoice = $item->invoice;
            $paid = min((int) $item->amount, (int) ($collected[$item->id] ?? 0));
            return [
                'model' => $item, 'source' => $invoice, 'source_type' => 'invoice', 'source_label' => $invoice->invoice_number,
                'date' => $invoice->issue_date, 'client' => $this->primaryClient($invoice->paymentPlan), 'plan' => $invoice->paymentPlan,
                'type' => str($item->item_type->value)->replace('_', ' ')->title(), 'description' => $item->description,
                'assessed' => (int) $item->standard_amount, 'waived' => (int) $item->waived_amount,
                'collected' => $paid, 'outstanding' => max(0, (int) $item->amount - $paid),
            ];
        });
        $direct = PaymentAllocation::query()
            ->whereNull('invoice_item_id')
            ->whereIn('allocation_type', [PaymentAllocationType::ServiceFee->value, PaymentAllocationType::ProcessingFee->value])
            ->with(['payment.payer', 'payment.financialTransaction.paymentPlan.memberships.client', 'payment.financialTransaction.reversedBy'])
            ->whereHas('payment', function ($query) use ($request): void {
                if ($request->filled('from')) $query->whereDate('received_date', '>=', $request->from);
                if ($request->filled('to')) $query->whereDate('received_date', '<=', $request->to);
            })->get()->reject(fn ($allocation) => (bool) $allocation->payment->financialTransaction->reversedBy)
            ->map(function ($allocation): array {
                $payment = $allocation->payment; $plan = $payment->financialTransaction->paymentPlan;
                return [
                    'model' => $allocation, 'source' => $payment, 'source_type' => 'payment',
                    'source_label' => 'Payment '.$payment->received_date->format('M j, Y'),
                    'date' => $payment->received_date, 'client' => $payment->payer ?: $this->primaryClient($plan), 'plan' => $plan,
                    'type' => str($allocation->allocation_type->value)->replace('_', ' ')->title(),
                    'description' => str($allocation->allocation_type->value)->replace('_', ' ')->title(),
                    'assessed' => (int) $allocation->amount, 'waived' => 0, 'collected' => (int) $allocation->amount, 'outstanding' => 0,
                ];
            });
        if ($request->filled('search')) {
            $term = str(trim($request->search))->lower()->value();
            $direct = $direct->filter(fn ($row) => str(collect([
                $row['source_label'], $row['type'], $row['description'], $row['plan']?->plan_number,
                $row['client'] ? $this->name($row['client']) : null,
            ])->filter()->implode(' '))->lower()->contains($term));
        }
        $rows = $rows->concat($direct)->sortByDesc('date')->values();
        return ['rows' => $rows, 'totals' => [
            'Assessed' => $rows->sum('assessed'), 'Waived' => $rows->sum('waived'),
            'Collected' => $rows->sum('collected'), 'Outstanding' => $rows->sum('outstanding'),
        ]];
    }

    private function applyDefaults(Request $request, string $report): void
    {
        if (in_array($report, ['payments', 'fees'], true)) {
            if (!$request->query->has('from')) $request->merge(['from' => now()->startOfYear()->toDateString()]);
            if (!$request->query->has('to')) $request->merge(['to' => now()->endOfYear()->toDateString()]);
        }
    }

    private function filters(Request $request): array
    {
        return ['from' => $request->string('from')->value(), 'to' => $request->string('to')->value(),
            'search' => trim($request->string('search')->value()), 'status' => $request->string('status')->value() ?: 'all',
            'aging' => $request->string('aging')->value()];
    }
    private function dates($query, Request $request, string $column): void
    {
        if ($request->filled('from')) $query->whereDate($column, '>=', $request->from);
        if ($request->filled('to')) $query->whereDate($column, '<=', $request->to);
    }
    private function paymentSearch($query, Request $request): void
    {
        if (! $request->filled('search')) return; $term = '%'.trim($request->search).'%';
        $query->where(fn ($q) => $q->where('reference', 'like', $term)
            ->orWhereHas('payer', fn ($c) => $c->where('first_name', 'like', $term)->orWhere('last_name', 'like', $term)->orWhere('organization_name', 'like', $term))
            ->orWhereHas('financialTransaction.paymentPlan', fn ($p) => $p->where('plan_number', 'like', $term)->orWhere('apn', 'like', $term)
                ->orWhereHas('memberships.client', fn ($c) => $c->where('first_name', 'like', $term)->orWhere('last_name', 'like', $term)->orWhere('organization_name', 'like', $term))));
    }
    private function invoiceSearch($query, Request $request): void
    {
        if (! $request->filled('search')) return; $term = '%'.trim($request->search).'%';
        $query->where(fn ($q) => $q->where('invoice_number', 'like', $term)->orWhereHas('paymentPlan', fn ($p) => $p->where('plan_number', 'like', $term)->orWhere('apn', 'like', $term)->orWhereHas('memberships.client', fn ($c) => $c->where('first_name', 'like', $term)->orWhere('last_name', 'like', $term)->orWhere('organization_name', 'like', $term))));
    }
    private function planSearch($query, Request $request): void
    {
        if (! $request->filled('search')) return; $term = '%'.trim($request->search).'%';
        $query->where(fn ($q) => $q->where('plan_number', 'like', $term)->orWhere('apn', 'like', $term)->orWhereHas('memberships.client', fn ($c) => $c->where('first_name', 'like', $term)->orWhere('last_name', 'like', $term)->orWhere('organization_name', 'like', $term)));
    }
    private function primaryClient(PaymentPlan $plan)
    {
        return $plan->memberships->firstWhere('role', 'primary')?->client ?: $plan->memberships->first()?->client;
    }
    private function paginate(Collection $rows, Request $request): LengthAwarePaginator
    {
        $page = max(1, $request->integer('page', 1)); $perPage = 50;
        return new LengthAwarePaginator($rows->forPage($page, $perPage)->values(), $rows->count(), $perPage, $page,
            ['path' => $request->url(), 'query' => $request->query()]);
    }
    private function csvDefinition(string $report): array
    {
        return match ($report) {
            'payments' => [['Date','Client','Plan','Method','Gross','Fees','Invoices','Principal','Credit','Status','Net','Reference'], fn ($r) => [$r['date']->toDateString(),$this->name($r['client']),$r['plan']?->plan_number,$r['method'],$r['gross']/100,$r['fees']/100,$r['invoice']/100,$r['principal']/100,$r['credit']/100,$r['reversed']?'Reversed':'Posted',$r['net']/100,$r['reference']]],
            'receivables' => [['Client','Plan','Invoice','Issued','Due','Original','Paid/Credited','Balance','Days overdue','Aging'], fn ($r) => [$this->name($r['client']),$r['plan']->plan_number,$r['model']->invoice_number,$r['issue']->toDateString(),$r['due']->toDateString(),$r['amount']/100,$r['paid']/100,$r['balance']/100,$r['days'],$r['bucket']]],
            'contracts' => [['Client','Plan','Purchase price','Documentation fee','Principal paid','Contract balance','Open invoices','Account credit','Next due','Status','Estimated payoff'], fn ($r) => [$this->name($r['client']),$r['model']->plan_number,$r['purchase']/100,$r['documentation']/100,$r['principal_paid']/100,$r['contract']/100,$r['open']/100,$r['credit']/100,$r['next_due']?->toDateString(),$r['status'],$r['payoff']]],
            'fees' => [['Date','Client','Plan','Invoice','Type','Description','Assessed','Waived','Collected','Outstanding'], fn ($r) => [$r['date']->toDateString(),$this->name($r['client']),$r['plan']->plan_number,$r['source_label'],$r['type'],$r['description'],$r['assessed']/100,$r['waived']/100,$r['collected']/100,$r['outstanding']/100]],
        };
    }
    private function name($client): string
    {
        return $client ? ($client->organization_name ?: trim($client->first_name.' '.$client->last_name)) : 'Not assigned';
    }
}
