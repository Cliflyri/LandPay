@extends('layouts.admin')
@section('title','Property Tax Batch | LandPay')
@section('body_class','admin-page')
@section('content')
<section class='admin-section'><div class='container-fluid dashboard-container'>
<div class='admin-heading'><div><span class='eyebrow eyebrow-dark'>Admin Actions</span><h1>{{$batch->exists?'Edit':'New'}} property tax batch</h1><p class='mb-0'>Save and review this draft before any invoices are created.</p></div></div>
@if($errors->any())<div class='alert alert-danger mt-4'><ul class='mb-0'>@foreach($errors->all() as $error)<li>{{$error}}</li>@endforeach</ul></div>@endif
<form class='admin-next-card mt-4' method='post' enctype='multipart/form-data' action={{$batch->exists?route('admin.property-tax-batches.update',$batch):route('admin.property-tax-batches.store')}}>@csrf @if($batch->exists)@method('put')@endif
<div class='row g-3'><div class='col-md-4'><label class='form-label'>Tax year</label><input class='form-control' type='number' name='tax_year' min='2000' max='2100' required value={{old('tax_year',$batch->tax_year)}}></div>
<div class='col-md-4'><label class='form-label'>Batch label</label><input class='form-control' name='label' maxlength='100' required value='{{old('label',$batch->label??'Annual')}}'><div class='form-text'>Usually Annual; may also be Supplemental, First half, or similar.</div></div>
<div class='col-md-4'><label class='form-label'>Invoice date</label><input class='form-control' type='date' name='issue_date' required value={{old('issue_date',$batch->issue_date?->format('Y-m-d'))}}></div>
<div class='col-md-4'><label class='form-label'>Due date</label><input class='form-control' type='date' name='due_date' required value={{old('due_date',$batch->due_date?->format('Y-m-d'))}}></div>
<div class='col-12'><label class='form-label'>Fallback description</label><input class='form-control' name='fallback_description' maxlength='500' value='{{old('fallback_description',$batch->fallback_description??($batch->tax_year.' Property Tax'))}}'><div class='form-text'>Used when the third imported field is blank. The invoice line may remain blank if this is cleared.</div></div>
<div class='col-lg-8'><label class='form-label'>Paste APN, amount, description</label><textarea class='form-control' name='source_text' rows='12' placeholder='123-45-678, 84.27, 2026 Property Tax'>{{old('source_text',$batch->source_text)}}</textarea></div>
<div class='col-lg-4'><label class='form-label'>Or upload CSV</label><input class='form-control' type='file' name='csv_file' accept='.csv,text/csv,text/plain'><div class='form-text'>Use pasted data or a file, not both.</div></div>
<div class='col-12'><div class='form-check'><input type='hidden' name='email_clients' value='0'><input class='form-check-input' type='checkbox' name='email_clients' value='1' id='email_clients' @checked(old('email_clients',$batch->email_clients??true))><label class='form-check-label' for='email_clients'>Email eligible clients on invoice creation</label></div><div class='form-text'>SMS eligibility is retained for future notification support.</div></div></div>
<button class='btn btn-brand mt-4'>Save draft and review</button> <a class='btn btn-outline-brand mt-4' href={{route('admin.property-tax-batches.index')}}>Cancel</a>
</form></div></section>
@endsection
