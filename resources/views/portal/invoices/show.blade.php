@extends('layouts.app')

@section('title', $invoice->invoice_number.' | LandPay')
@section('body_class', 'admin-page')

@section('content')
@php($statusLabel = str($invoice->status->value)->replace('_', ' ')->title())

<section class="admin-section">
    <div class="container site-container">
        <div class="admin-heading d-flex justify-content-between align-items-end">
            <div>
                <span class="eyebrow eyebrow-dark">Invoice</span>

                <div class="d-flex flex-wrap align-items-center gap-2">
                    <h1 class="mb-0">{{ $invoice->invoice_number }}</h1>

                    <span class="dashboard-status status-{{ str($invoice->status->value)->slug() }}">
                        {{ $statusLabel }}
                    </span>
                </div>

                <p class="mb-0 mt-2">
                    {{ $invoice->paymentPlan->plan_number }}
                    <span aria-hidden="true">&middot;</span>
                    Payment due upon receipt
                    <span aria-hidden="true">&middot;</span>
                    Late after {{ $invoice->due_date->format('M j, Y') }}
                </p>
            </div>

            <div>
                <a
                    class="btn btn-brand"
                    href="{{ route('portal.invoices.download', $invoice) }}"
                >
                    Download PDF
                </a>

                <a
                    class="btn btn-outline-brand"
                    href="{{ route('portal.invoices.index') }}"
                >
                    Back
                </a>
            </div>
        </div>

        <div class="row g-4 mt-2 align-items-start">
            <div class="col-lg-7">
                <div class="admin-next-card">
                    <h2>Charges</h2>

                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Description</th>
                                    <th class="text-end">Amount</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($invoice->items->sortBy('display_order') as $item)
                                    <tr>
                                        <td>
                                            {{ $item->description }}

                                            @if ($item->waiver_reason)
                                                <small class="d-block text-muted">
                                                    {{ $item->waiver_reason }}
                                                </small>
                                            @endif
                                        </td>

                                        <td class="money-cell">
                                            {{ \App\Support\Money::format($item->amount) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <aside
                    class="admin-next-card invoice-financial-summary"
                    aria-labelledby="client-invoice-summary-title"
                >
                    <div class="d-flex justify-content-between align-items-center gap-3">
                        <h2 id="client-invoice-summary-title" class="mb-0">
                            Invoice summary
                        </h2>

                        <span class="dashboard-status status-{{ str($invoice->status->value)->slug() }}">
                            {{ $statusLabel }}
                        </span>
                    </div>

                    <dl class="payment-preview-summary mb-0">
                        <div class="invoice-summary-primary">
                            <dt>Invoice amount</dt>
                            <dd>{{ \App\Support\Money::format($invoiceAmount) }}</dd>
                        </div>

                        @if ($creditApplied > 0)
                            <div>
                                <dt>Account credit applied</dt>
                                <dd>
                                    &minus; {{ \App\Support\Money::format($creditApplied) }}
                                </dd>
                            </div>
                        @endif

                        <div>
                            <dt>Paid to date</dt>
                            <dd>
                                @if ($paidToDate > 0)
                                    &minus; {{ \App\Support\Money::format($paidToDate) }}
                                @else
                                    {{ \App\Support\Money::format(0) }}
                                @endif
                            </dd>
                        </div>

                        @if ($adjustments !== 0)
                            <div>
                                <dt>Adjustments</dt>
                                <dd>
                                    @if ($adjustments < 0)
                                        &minus; {{ \App\Support\Money::format(abs($adjustments)) }}
                                    @else
                                        + {{ \App\Support\Money::format($adjustments) }}
                                    @endif
                                </dd>
                            </div>
                        @endif

                        <div class="invoice-summary-balance {{ $balance > 0 ? 'has-balance' : '' }}">
                            <dt>Balance due</dt>
                            <dd>{{ \App\Support\Money::format($balance) }}</dd>
                        </div>
                    </dl>

                    @if ($balance > 0)
                        <a
                            class="btn btn-sun mt-3"
                            href="{{ route('portal.make-payment.create', [
                                'plan' => $invoice->payment_plan_id,
                                'amount' => number_format($balance / 100, 2, '.', ''),
                            ]) }}"
                        >
                            Make a payment
                        </a>
                    @endif
                </aside>
            </div>
        </div>
    </div>
</section>
@endsection