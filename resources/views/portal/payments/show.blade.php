@extends('layouts.app')
@section('title','Payment receipt | LandPay')
@section('body_class','admin-page')
@section('content')
<section class="admin-section"><div class="container site-container">
    <div class="admin-heading d-flex justify-content-between align-items-end">
        <div><span class="eyebrow eyebrow-dark">Payment receipt</span>
        <h1>{{\App\Support\Money::format($payment->gross_amount)}}</h1>
        <p>{{$payment->financialTransaction->paymentPlan->plan_number}} <span aria-hidden="true">&middot;</span> {{$payment->received_date->format('M j, Y')}}</p></div><div>@unless($payment->financialTransaction->reversedBy)<a class="btn btn-brand" href="{{route('portal.payments.download',$payment)}}">Download receipt</a>@endunless <a class="btn btn-outline-brand" href="{{route('portal.payments.index')}}">Back</a></div></div>@if($payment->financialTransaction->reversedBy)<div class="alert alert-warning mt-4">This payment was reversed.</div>@endif<div class="admin-next-card mt-4"><h2>Allocation</h2><div class="table-responsive"><table class="table"><thead><tr><th>Applied to</th><th>Invoice</th><th class="text-end">Amount</th></tr></thead><tbody>@foreach($payment->allocations as $allocation)<tr><td>{{$allocation->invoiceItem?->description ?? str($allocation->allocation_type->value)->replace('_',' ')->title()}}</td>
        <td>
    @if ($allocation->invoice)
        <a href="{{ route('portal.invoices.show', $allocation->invoice) }}" style="color:#2f5d3a;">
            {{ $allocation->invoice->invoice_number }}
        </a>
    @else
        &mdash;
    @endif
</td>
        <td class="money-cell">{{\App\Support\Money::format($allocation->amount)}}</td></tr>@endforeach</tbody></table></div><p class="text-end mb-0"><strong>Remaining contract balance: {{\App\Support\Money::format($contractBalance)}}</strong></p></div></div></section>
@endsection
