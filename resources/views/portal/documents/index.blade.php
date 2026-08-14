@extends('layouts.app')
@section('title','Messages & documents | LandPay')
@section('body_class','admin-page')
@section('content')
<section class="admin-section"><div class="container site-container">
<div class="admin-heading d-flex flex-wrap justify-content-between align-items-end gap-3"><div><span class="eyebrow eyebrow-dark">Client portal</span><h1>Messages &amp; documents</h1></div><a class="btn btn-outline-brand" href="{{route('portal.dashboard')}}">Dashboard</a></div>
<nav class="nav nav-tabs mt-4" aria-label="Messages and documents"><a class="nav-link" href="{{route('portal.messages.index')}}">Messages</a><a class="nav-link active" aria-current="page" href="{{route('portal.documents.index')}}">Documents</a></nav>
@if(session('success'))<div class="alert alert-success mt-4">{{session('success')}}</div>@endif
@if($errors->any())<div class="alert alert-danger mt-4">{{$errors->first()}}</div>@endif
<div class="admin-next-card h-auto mt-4"><div class="d-flex flex-wrap justify-content-between align-items-center gap-3"><div><h2 class="mb-1">Shared documents</h2><p class="mb-0">Files shared between you and LandPay.</p></div><button class="btn btn-sun" type="button" data-bs-toggle="collapse" data-bs-target="#document-upload">Upload document</button></div>
<div class="collapse mt-4" id="document-upload"><form method="post" action="{{route('portal.documents.store')}}" enctype="multipart/form-data">@csrf<div class="row g-3"><div class="col-md-5"><label class="form-label" for="document">File</label><input class="form-control" id="document" name="document" type="file" accept=".pdf,.jpg,.jpeg,.png,.docx" required><div class="form-text">PDF, JPG, PNG, or DOCX; up to 10 MB.</div></div><div class="col-md-4"><label class="form-label" for="payment_plan_id">Payment plan (optional)</label><select class="form-select" id="payment_plan_id" name="payment_plan_id"><option value="">No plan</option>@foreach($plans as $plan)<option value="{{$plan->id}}">{{$plan->plan_number}}</option>@endforeach</select></div><div class="col-md-3"><label class="form-label" for="category">Category</label><select class="form-select" id="category" name="category">@foreach(['general'=>'General','contract'=>'Contract','closing_document'=>'Closing document','identification'=>'Identification','property_image'=>'Property image'] as $value=>$label)<option value="{{$value}}">{{$label}}</option>@endforeach</select></div></div><button class="btn btn-brand mt-3">Upload securely</button></form></div>
<div class="list-group list-group-flush mt-3">
@forelse($documents as $document)
<div class="list-group-item px-0 py-3 d-flex flex-wrap justify-content-between align-items-center gap-3">
<div><strong>{{$document->name}}</strong><div class="small text-muted">{{str($document->category)->replace('_',' ')->title()}} @if($document->paymentPlan) &middot; Plan {{$document->paymentPlan->plan_number}} @endif &middot; {{$document->created_at->format('M j, Y')}} &middot; {{$document->uploaded_by_client_id ? 'Uploaded by you' : 'Shared by LandPay'}}</div></div>
<a class="btn btn-sm btn-outline-brand" href="{{route('portal.documents.download',$document)}}">Download</a>
</div>
@empty
<p class="mb-0 mt-3">No shared documents yet.</p>
@endforelse
</div></div>{{$documents->links()}}
</div></section>
@endsection
