@extends('layouts.admin')
@section('title','Payment receipt | LandPay')
@section('body_class','admin-page')
@section('content')
@php($transaction=$payment->financialTransaction)
<section class="admin-section">
    <div class="container-fluid dashboard-container">
        <div class="admin-heading d-flex flex-wrap justify-content-between align-items-end gap-3">
            <div>
                <span class="eyebrow eyebrow-dark">Payment receipt</span>
                <h1>{{ \App\Support\Money::format($payment->gross_amount) }}</h1>
                <p class="mb-0">{{ $transaction->paymentPlan->title }} <span aria-hidden="true">&middot;</span> {{ $payment->received_date->format('M j, Y') }}</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                @unless($reversal)
                    <form method="post" action="{{ route('admin.payments.receipt-email.store',$payment) }}">
                        @csrf
                        <button class="btn btn-brand" type="submit">Email receipt</button>
                    </form>
                @endunless
                <a class="btn btn-outline-brand" href="{{ route('admin.plans.show',$transaction->paymentPlan) }}">Back to plan</a>
            </div>
        </div>

        @if(session('success'))<div class="alert alert-success mt-4" role="status">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="alert alert-danger mt-4" role="alert">{{ $errors->first() }}</div>@endif
        @if($reversal)
            <div class="alert alert-warning mt-4">
                <strong>Canceled.</strong> {{ $reversal->reason }} on {{ $reversal->posted_at->format('M j, Y g:i A') }}. The original payment remains in the audit ledger.
            </div>
        @endif

        <div class="admin-next-card h-auto mt-4">
            <h2>Payment details</h2>
            <dl class="row g-2 mb-4 mt-3">
                <dt class="col-sm-2">Method</dt>
                <dd class="col-sm-4">{{ str($payment->payment_method->value)->replace('_',' ')->title() }}</dd>
                <dt class="col-sm-2">Payer</dt>
                <dd class="col-sm-4">{{ $payment->payer?->organization_name ?: trim(($payment->payer?->first_name ?? '').' '.($payment->payer?->last_name ?? '')) ?: 'Not specified' }}</dd>
                @if(in_array($payment->clientPaymentIntent?->provider, ['square', 'stripe'], true))
                    <dt class="col-sm-2">Provider</dt>
                    <dd class="col-sm-4">{{ str($payment->clientPaymentIntent->provider)->title() }}</dd>
                @endif
                <dt class="col-sm-2">Reference</dt>
                <dd class="col-sm-4">{{ $payment->external_reference ?: 'None' }}</dd>
                <dt class="col-sm-2">Transaction</dt>
                <dd class="col-sm-4 text-break"><code>{{ $transaction->uuid }}</code></dd>
                @if($payment->overpayment_amount>0)
                    <dt class="col-sm-2">Overpayment</dt>
                    <dd class="col-sm-4">{{ \App\Support\Money::format($payment->overpayment_amount) }} to {{ str($payment->overpayment_disposition->value)->replace('_',' ') }}</dd>
                @endif
            </dl>

            <hr>
            <h2>Allocation</h2>
            <div class="table-responsive mt-3">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr><th>Applied to</th><th>Invoice</th><th class="text-end">Amount</th></tr>
                    </thead>
                    <tbody>
                        @foreach($payment->allocations as $allocation)
                            <tr>
                                <td>{{ $allocation->invoiceItem?->description ?? str($allocation->allocation_type->value)->replace('_',' ')->title() }}</td>
                                <td>{{ $allocation->invoice?->invoice_number ?? '-' }}</td>
                                <td class="money-cell">{{ \App\Support\Money::format($allocation->amount) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        @if($payment->emailDeliveries->isNotEmpty())
            <section class="admin-next-card h-auto mt-4" aria-labelledby="email-history-title">
                <h2 id="email-history-title">Email history</h2>
                <div class="table-responsive mt-3">
                    <table class="table align-middle mb-0">
                        <thead><tr><th>Type</th><th>Recipient</th><th>Status</th><th>Sent</th></tr></thead>
                        <tbody>
                            @foreach($payment->emailDeliveries->sortByDesc('created_at') as $delivery)
                                <tr>
                                    <td>{{ str($delivery->template_slug)->replace('-',' ')->title() }}</td>
                                    <td>{{ $delivery->recipient_email }}</td>
                                    <td>{{ str($delivery->status)->title() }}</td>
                                    <td>{{ $delivery->sent_at?->format('M j, Y g:i A') ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        @unless($reversal)
            <form class="border border-danger-subtle rounded p-3 mt-4"
                  method="post"
                  action="{{ route('admin.payments.reverse',$payment) }}"
                  onsubmit="return confirm('Cancel this payment? Its financial effects will be reversed and the original record will be preserved.');">
                @csrf
                <h2 class="h5 mb-2">Cancel payment</h2>
                <p class="text-muted mb-3">Cancel an incorrect or duplicate payment. LandPay reverses its financial effects while preserving the original entry.</p>
                <div class="row g-3 align-items-end">
                    <div class="col-lg">
                        <label class="form-label" for="reason">Cancellation reason</label>
                        <textarea class="form-control" id="reason" name="reason" rows="2" maxlength="500" required></textarea>
                    </div>
                    <div class="col-lg-auto">
                        <button class="btn btn-outline-danger" type="submit">Cancel payment</button>
                    </div>
                </div>
            </form>
        @endunless
    </div>
</section>
@endsection
