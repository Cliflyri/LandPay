@extends('layouts.admin')
@section('title','Property Tax Batches | LandPay')
@section('body_class','admin-page')
@section('content')
<section class='admin-section'><div class='container-fluid dashboard-container'>
<div class='admin-heading d-flex justify-content-between align-items-end'><div><span class='eyebrow eyebrow-dark'>Admin Actions</span><h1>Property tax batches</h1><p class='mb-0'>Draft batches create no invoices until explicitly confirmed.</p></div>

<a class='btn btn-brand' href={{route('admin.property-tax-batches.create')}}>New batch</a></div>

<div class='admin-next-card mt-4 table-responsive'><table class='table align-middle'><thead><tr><th>Tax year / label</th><th>Dates</th><th>Status</th><th>Processed matches</th><th>Updated</th></tr></thead><tbody>
@forelse($batches as $batch)<tr><td><a href={{route('admin.property-tax-batches.show',$batch)}}>{{$batch->tax_year}} · {{$batch->label}}</a>@if(filled($batch->property_county))<small class="d-block text-muted">County: {{$batch->property_county}}</small>@endif</td><td>{{$batch->issue_date->format('M j, Y')}} / {{$batch->due_date->format('M j, Y')}}</td>
<td>
    <span style="display:inline-flex; align-items:center; gap:6px; padding:5px 10px; border-radius:6px; font-size:.85rem; font-weight:600; {{ $batch->status === 'issued' ? 'background:#eaf5ee; color:#286442; border:1px solid #cfe5d6;' : 'background:#f0f1f3; color:#555d66; border:1px solid #dfe2e6;' }}">
        <span aria-hidden="true">{{ $batch->status === 'issued' ? '✓' : '✎' }}</span>
        {{ $batch->status === 'not_issued' && $batch->existing_invoice_count
            ? 'Not issued — existing invoices detected'
            : str($batch->status)->replace('_', ' ')->title() }}
    </span>
</td>
<td>{{$batch->processed_matches_count}}</td><td>{{$batch->updated_at->format('M j, Y g:i A')}}</td></tr>
@empty<tr><td colspan='5'>No property-tax batches yet.</td></tr>@endforelse
</tbody></table>{{$batches->links()}}</div></div></section>
@endsection
