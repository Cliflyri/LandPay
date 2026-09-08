@extends('layouts.admin')
@section('title','Property Tax Batches | LandPay')
@section('body_class','admin-page')
@section('content')
<section class='admin-section'><div class='container-fluid dashboard-container'>
<div class='admin-heading d-flex justify-content-between align-items-end'><div><span class='eyebrow eyebrow-dark'>Admin Actions</span><h1>Property tax batches</h1><p class='mb-0'>Draft batches create no invoices until explicitly confirmed.</p></div>

<a class='btn btn-brand' href={{route('admin.property-tax-batches.create')}}>New batch</a></div>

<div class='admin-next-card mt-4 table-responsive'><table class='table align-middle'><thead><tr><th>Tax year / label</th><th>Dates</th><th>Status</th><th>Rows</th><th>Updated</th></tr></thead><tbody>
@forelse($batches as $batch)<tr><td><a href={{route('admin.property-tax-batches.show',$batch)}}>{{$batch->tax_year}} · {{$batch->label}}</a></td><td>{{$batch->issue_date->format('M j, Y')}} / {{$batch->due_date->format('M j, Y')}}</td><td>{{$batch->status==='not_issued'&&$batch->existing_invoice_count?'Not issued — existing invoices detected':str($batch->status)->replace('_',' ')->title()}}</td><td>{{$batch->rows_count}}</td><td>{{$batch->updated_at->format('M j, Y g:i A')}}</td></tr>
@empty<tr><td colspan='5'>No property-tax batches yet.</td></tr>@endforelse
</tbody></table>{{$batches->links()}}</div></div></section>
@endsection
