@extends('layouts.admin')
@section('title','Admin Actions | LandPay')
@section('body_class','admin-page')
@section('content')
<section class='admin-section'><div class='container-fluid dashboard-container'>
<div class='admin-heading'><div><span class='eyebrow eyebrow-dark'>Administration</span><h1>Admin Actions</h1><p class='mb-0'>Create records, communicate with clients, and manage occasional workflows.</p></div></div>
<h2 class='h4 mt-4'>Create</h2><div class='row g-4'>
<div class='col-md-6'><div class='admin-next-card h-100'><h3>New contract setup</h3><p>Create a client and payment plan through the guided setup.</p><a class='btn btn-brand' href={{route('admin.contract-setups.create')}}>Start contract setup</a></div></div>
<div class='col-md-6'><div class='admin-next-card h-100'><h3>Property tax invoices</h3><p>Import APNs, review matches, save drafts, and issue annual invoices.</p><a class='btn btn-brand' href={{route('admin.property-tax-batches.create')}}>Start a batch</a> <a class='btn btn-outline-brand' href={{route('admin.property-tax-batches.index')}}>View batches</a></div></div>
</div>
<h2 class='h4 mt-4'>Communicate</h2><div class='row g-4'>
<div class='col-md-6'><div class='admin-next-card h-100'><h3>Client banner notification</h3><p>Create an announcement for client portals.</p><a class='btn btn-outline-brand' href={{route('admin.announcements.create')}}>Create announcement</a></div></div>
<div class='col-md-6'><div class='admin-next-card h-100'><h3>Secure client message</h3><p>Send a secure message to a client.</p><a class='btn btn-outline-brand' href={{route('admin.messages.create')}}>Compose message</a></div></div>
</div>
<h2 class='h4 mt-4'>Review and maintain</h2><div class='admin-next-card'><div class='d-flex flex-wrap gap-3'>
<a href={{route('admin.property-tax-batches.index',['status'=>'draft'])}}><strong>{{$taxCounts['draft']}}</strong> draft property-tax batches</a>
<a href={{route('admin.property-tax-batches.index',['status'=>'partially_issued'])}}><strong>{{$taxCounts['partial']}}</strong> partially issued batches</a>
<a href={{route('admin.property-tax-batches.index',['email'=>'failed'])}}><strong>{{$taxCounts['failed']}}</strong> batches with failed emails</a>
</div></div>
</div></section>
@endsection
