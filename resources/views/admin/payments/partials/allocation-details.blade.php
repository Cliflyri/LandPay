@php($allocatedInvoice = $allocation->invoice)
<details class="border rounded mb-2">
    <summary class="p-3" style="cursor:pointer">
        <span class="d-inline-flex flex-wrap align-items-center justify-content-between gap-2" style="width:calc(100% - 1.5rem)">
            <span>{{ $allocation->invoiceItem?->description ?? str($allocation->allocation_type->value)->replace('_',' ')->title() }}</span>
            <span class="ms-auto">
                @if($allocatedInvoice)
                    <span class="text-muted">Invoice:</span> <a class="me-3" href="{{ route('admin.invoices.show', $allocatedInvoice) }}">{{ $allocatedInvoice->invoice_number }}</a>
                @endif
                <strong>{{ \App\Support\Money::format($allocation->amount) }}</strong>
            </span>
        </span>
    </summary>
    <div class="border-top p-3">
        <p class="mb-3">
            <strong>{{ $reversal ? 'Originally applied by this payment' : 'Applied by this payment' }}: {{ \App\Support\Money::format($allocation->amount) }}</strong>
            @if($reversal)<span class="text-danger d-block">This payment was reversed; this allocation is historical.</span>@endif
        </p>
        @if($allocatedInvoice)
            <div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
                <div>
                    <strong>{{ $allocatedInvoice->invoice_number }}</strong>
                    <span class="badge text-bg-light">{{ str($allocatedInvoice->status->value)->replace('_',' ')->title() }}</span>
                    <div class="text-muted small">Issued {{ $allocatedInvoice->issue_date->format('M j, Y') }} · Due {{ $allocatedInvoice->due_date->format('M j, Y') }}</div>
                </div>
                <a href="{{ route('admin.invoices.show', $allocatedInvoice) }}">Open full invoice</a>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <caption class="caption-top">Current invoice charges</caption>
                    <thead><tr><th>Description</th><th class="text-end">Standard</th><th class="text-end">Waived</th><th class="text-end">Amount</th></tr></thead>
                    <tbody>
                    @foreach($allocatedInvoice->items->sortBy('display_order') as $item)
                        <tr @class(['table-info' => $item->id === $allocation->invoice_item_id])>
                            <td>
                                {{ $item->description }}
                                @if($item->waiver_reason)<small class="d-block text-muted">{{ $item->waiver_reason }}</small>@endif
                                @if($item->id === $allocation->invoice_item_id)
                                    <small class="d-block fw-semibold">{{ $reversal ? 'Originally applied' : 'Applied by this payment' }}: {{ \App\Support\Money::format($allocation->amount) }}</small>
                                @endif
                            </td>
                            <td class="money-cell">{{ \App\Support\Money::format($item->standard_amount) }}</td>
                            <td class="money-cell">{{ \App\Support\Money::format($item->waived_amount) }}</td>
                            <td class="money-cell">{{ \App\Support\Money::format($item->amount) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @if($allocation->invoiceItem && !$allocatedInvoice->items->contains('id', $allocation->invoice_item_id))
                <p class="alert alert-info">Historical charge: {{ $allocation->invoiceItem->description }} ({{ \App\Support\Money::format($allocation->invoiceItem->amount) }}). This charge was removed or replaced; no current charge is highlighted.</p>
            @endif
            <dl class="row mb-0">
                <dt class="col-7">Current invoice amount</dt><dd class="col-5 text-end">{{ \App\Support\Money::format($allocatedInvoice->items->sum('amount')) }}</dd>
                <dt class="col-7">Total paid to date</dt><dd class="col-5 text-end">{{ \App\Support\Money::format($invoiceDetails[$allocatedInvoice->id]['paid']) }}</dd>
                <dt class="col-7">Current invoice balance</dt><dd class="col-5 text-end">{{ \App\Support\Money::format($invoiceDetails[$allocatedInvoice->id]['balance']) }}</dd>
            </dl>
        @else
            <p class="mb-0">
                @switch($allocation->allocation_type->value)
                    @case('purchase_balance') Applied directly to the contract principal balance. @break
                    @case('client_credit') Recorded as account credit for future use. @break
                    @case('processing_fee') Collected as this payment's processing fee, without an invoice charge. @break
                    @case('service_fee') Collected as a monthly service fee without an invoice charge.
                        @if($allocation->billing_month) Billing month: {{ $allocation->billing_month }}. @endif
                        @break
                    @default No linked invoice is available for this allocation.
                @endswitch
            </p>
        @endif
    </div>
</details>
