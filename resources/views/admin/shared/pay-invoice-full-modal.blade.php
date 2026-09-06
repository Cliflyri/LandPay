@php
$fullPaymentBalance=app(\App\Services\FinancialBalanceService::class)->invoiceBalance($invoice);
$fullPaymentPreview=$fullPaymentBalance>0?app(\App\Services\PaymentService::class)->preview($invoice->paymentPlan,$fullPaymentBalance,'regular',invoiceId:$invoice->id):null;
$fullPaymentModalId='pay-invoice-full-'.$invoice->id.'-'.$returnTo;
@endphp
@if($fullPaymentPreview && in_array($invoice->status,[\App\Enums\InvoiceStatus::Issued,\App\Enums\InvoiceStatus::PartiallyPaid],true))
<div class="modal fade" id="{{$fullPaymentModalId}}" tabindex="-1" aria-labelledby="{{$fullPaymentModalId}}-title" aria-hidden="true"><div class="modal-dialog modal-lg"><div class="modal-content">
<div class="modal-header"><h2 class="modal-title fs-4" id="{{$fullPaymentModalId}}-title">Pay invoice in full</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
<form method="post" action="{{route('admin.invoices.pay-in-full',$invoice)}}">@csrf<div class="modal-body">
<p>Review payment for <strong>{{$invoice->invoice_number}}{{isset($clientName) ? ' - '.$clientName : ''}}</strong>. The amount and allocations are fixed to the invoice's current balance.</p>
    
<dl class="payment-preview-summary"><div><dt>Invoice</dt><dd>{{$invoice->invoice_number}}</dd></div><div><dt>Due date</dt><dd>{{$invoice->due_date->format('M j, Y')}}</dd></div>
@foreach($fullPaymentPreview['allocations'] as $allocation)<div><dt>{{$allocation['label']}}</dt><dd>{{\App\Support\Money::format($allocation['amount'])}}</dd></div>@endforeach
<div class="invoice-summary-primary"><dt>Payment amount</dt><dd>{{\App\Support\Money::format($fullPaymentBalance)}}</dd></div><div><dt>Invoice balance after payment</dt><dd>{{\App\Support\Money::format(0)}}</dd></div></dl>
<div class="row g-3 mt-1"><div class="col-md-4"><label class="form-label" for="{{$fullPaymentModalId}}-date">Received date</label><input class="form-control" id="{{$fullPaymentModalId}}-date" type="date" name="received_date" value="{{today()->toDateString()}}" required></div>
<div class="col-md-4"><label class="form-label" for="{{$fullPaymentModalId}}-method">Payment method</label><select class="form-select" id="{{$fullPaymentModalId}}-method" name="payment_method" required>@foreach(\App\Enums\PaymentMethod::cases() as $method)<option value="{{$method->value}}">{{str($method->value)->replace('_',' ')->title()}}</option>@endforeach</select></div>
<div class="col-md-4"><label class="form-label" for="{{$fullPaymentModalId}}-reference">Reference <span class="text-muted">(optional)</span></label><input class="form-control" id="{{$fullPaymentModalId}}-reference" name="external_reference" maxlength="150"></div></div><input type="hidden" name="return_to" value="{{$returnTo}}"><input type="hidden" name="idempotency_token" value="{{(string)\Illuminate\Support\Str::uuid()}}"></div>
<div class="modal-footer"><button class="btn btn-outline-brand" type="button" data-bs-dismiss="modal">Cancel</button><button class="btn btn-brand" type="submit">Confirm payment of {{\App\Support\Money::format($fullPaymentBalance)}}</button></div></form>
</div></div></div>
@endif
