@extends('layouts.admin')
@section('title', 'Edit '.$invoice->invoice_number.' | LandPay')
@section('body_class', 'admin-page')

@section('content')
<section class="admin-section">
    <div class="container-fluid dashboard-container">

        <div class="admin-heading d-flex flex-wrap justify-content-between align-items-end gap-3">
            <div>
                <span class="eyebrow eyebrow-dark">Invoice</span>
                <h1>Edit {{$invoice->invoice_number}}</h1>
                <p class="mb-0">
                    {{$invoice->paymentPlan->title}}
                    <span aria-hidden="true">&middot;</span>
                    APN / Plan # {{$invoice->paymentPlan->apn ?: $invoice->paymentPlan->plan_number}}
                </p>
            </div>

            <a class="btn btn-outline-brand" href="{{route('admin.invoices.show',$invoice)}}">
                Cancel
            </a>
        </div>

        @if($errors->any())
            <div class="alert alert-danger mt-4">
                {{$errors->first()}}
            </div>
        @endif

        <form class="admin-next-card mt-3"
              method="post"
              action="{{route('admin.invoices.update',$invoice)}}"
              id="invoice-edit-form">
            @csrf
            @method('PUT')

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label" for="issue_date">Invoice date</label>
                    <input class="form-control"
                           type="date"
                           id="issue_date"
                           name="issue_date"
                           value="{{old('issue_date',$invoice->issue_date->format('Y-m-d'))}}"
                           required>
                </div>

                <div class="col-md-4">
                    <label class="form-label" for="due_date">Due date</label>
                    <input class="form-control"
                           type="date"
                           id="due_date"
                           name="due_date"
                           value="{{old('due_date',$invoice->due_date->format('Y-m-d'))}}"
                           required>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-3 mb-2">
                <div>
                    <h2 class="mb-1">Line items</h2>
                    <p class="text-muted mb-0">
                        Edit charges or use the three-dot menu to delete a line.
                    </p>
                </div>

                <button class="btn btn-sm btn-outline-brand"
                        id="add-invoice-item"
                        type="button">
                    Add line item
                </button>
            </div>

            <div id="invoice-items">
                @foreach(old('items',$invoice->items->sortBy('display_order')->values()->map(fn($item)=>[
                    'id'=>$item->id,
                    'type'=>$item->item_type->value,
                    'description'=>$item->description,
                    'amount'=>number_format($item->amount/100,2,'.',''),
                ])->all()) as $index=>$item)

                    <div class="invoice-edit-item border rounded p-2 mb-2">
                        @if(!empty($item['id']))
                            <input type="hidden"
                                   name="items[{{$index}}][id]"
                                   value="{{$item['id']}}">
                        @endif

                        <div class="row g-2 align-items-end">

                            <div class="col-md-3">
                                <label class="form-label">Type</label>
                                <select class="form-select"
                                        name="items[{{$index}}][type]"
                                        required>
                                    @foreach([
                                        'scheduled_purchase_payment'=>'Plan payment',
                                        'documentation_fee'=>'Documentation fee',
                                        'monthly_service_fee'=>'Monthly service fee',
                                        'late_fee_stage_1'=>'Stage-one late fee',
                                        'late_fee_stage_2'=>'Stage-two late fee',
                                        'administrative_fee'=>'Fee',
                                        'other'=>'Other / adjustment'
                                    ] as $value=>$label)
                                        <option value="{{$value}}"
                                            @selected($item['type']===$value)>
                                            {{$label}}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-5">
                                <label class="form-label">Description</label>
                                <input class="form-control"
                                       name="items[{{$index}}][description]"
                                       value="{{$item['description']}}"
                                       maxlength="500"
                                       required>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Amount</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input class="form-control"
                                           name="items[{{$index}}][amount]"
                                           value="{{$item['amount']}}"
                                           inputmode="decimal"
                                           required>
                                </div>
                            </div>

                            <div class="col-md-1 dropdown">
                                <button class="btn btn-link text-secondary text-decoration-none px-2 py-1 fs-4 lh-1"
                                        style="margin-top: -24px;"        
                                        type="button"
                                        data-bs-toggle="dropdown"
                                        aria-label="Line item actions">
                                    &#8942;
                                </button>

                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <button class="dropdown-item text-danger remove-invoice-item"
                                                type="button">
                                            Delete line item
                                        </button>
                                    </li>
                                </ul>
                            </div>

                        </div>
                    </div>

                @endforeach
            </div>

            <button class="btn btn-brand" type="submit">
                Save invoice
            </button>
        </form>

    </div>
</section>

<template id="invoice-edit-item-template">
    <div class="invoice-edit-item border rounded p-2 mb-2">
        <div class="row g-2 align-items-end">

            <div class="col-md-3">
                <label class="form-label">Type</label>
                <select class="form-select"
                        name="items[INDEX][type]"
                        required>
                    <option value="scheduled_purchase_payment">Plan payment</option>
                    <option value="documentation_fee">Documentation fee</option>
                    <option value="monthly_service_fee">Monthly service fee</option>
                    <option value="late_fee_stage_1">Stage-one late fee</option>
                    <option value="late_fee_stage_2">Stage-two late fee</option>
                    <option value="administrative_fee">Fee</option>
                    <option value="other">Other / adjustment</option>
                </select>
            </div>

            <div class="col-md-5">
                <label class="form-label">Description</label>
                <input class="form-control"
                       name="items[INDEX][description]"
                       maxlength="500"
                       required>
            </div>

            <div class="col-md-3">
                <label class="form-label">Amount</label>
                <div class="input-group">
                    <span class="input-group-text">$</span>
                    <input class="form-control"
                           name="items[INDEX][amount]"
                           inputmode="decimal"
                           required>
                </div>
            </div>

            <div class="col-md-1 dropdown">
                <button class="btn btn-link text-secondary text-decoration-none px-2 py-1 fs-5 lh-1"
                        type="button"
                        data-bs-toggle="dropdown"
                        aria-label="Line item actions">
                    &#8942;
                </button>

                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <button class="dropdown-item text-danger remove-invoice-item"
                                type="button">
                            Delete line item
                        </button>
                    </li>
                </ul>
            </div>

        </div>
    </div>
</template>
@endsection

@push('scripts')
<script>
(() => {
    const list = document.getElementById('invoice-items');
    const template = document.getElementById('invoice-edit-item-template');

    let nextIndex = list.children.length;

    document.getElementById('add-invoice-item').addEventListener('click', () => {
        list.insertAdjacentHTML(
            'beforeend',
            template.innerHTML.replaceAll('INDEX', nextIndex++)
        );
    });

    list.addEventListener('click', event => {
        const remove = event.target.closest('.remove-invoice-item');

        if (!remove) return;

        if (list.querySelectorAll('.invoice-edit-item').length === 1) {
            window.alert('An invoice must have at least one line item.');
            return;
        }

        remove.closest('.invoice-edit-item').remove();
    });
})();
</script>
@endpush

