@extends('layouts.admin')
@section('title', 'Import from LandInvest | LandPay')
@section('body_class', 'admin-page')
@section('content')
<section class="admin-section"><div class="container-fluid dashboard-container">
<div class="admin-heading"><div><span class="eyebrow eyebrow-dark">Administration</span><h1>Import from LandInvest</h1><p>Import property repayment status from landinvest for display on dashboard and plan pages</p></div></div>
@if(session('import_summary'))<div class="alert alert-success" role="status">{{ session('import_summary') }}</div>@endif
@if(session('import_unmatched'))<div class="alert alert-warning">Retained without a matching LandPay property: {{ implode(', ', session('import_unmatched')) }}</div>@endif
@if($snapshot)
<p class="text-muted">Export generated: {{ $snapshot['generated_at'] }} · Imported: {{ $snapshot['imported_at'] ?? 'Unknown' }} (UTC)</p>
@endif
<form method="post" action="{{ route('admin.landinvest.import.store') }}">
@csrf
<label for="repayment-snapshot" class="form-label">Complete LandInvest JSON snapshot</label>
<p class="small text-muted">Each successful import replaces all previous property statuses. Missing properties show an outlined gray circle with a slash. This information is private and visible only to administrators.</p>
@error('snapshot')<div class="alert alert-danger" role="alert">{{ $message }}</div>@enderror
<textarea id="repayment-snapshot" name="snapshot" class="form-control font-monospace" rows="18" required spellcheck="false">{{ old('snapshot') }}</textarea>
<div class="d-flex gap-3 mt-3"><button class="btn btn-brand" type="submit">Save snapshot</button><a class="btn btn-outline-brand" href="{{ route('admin.actions.index') }}">Back to Admin Actions</a></div>
</form>
</div></section>
@endsection
