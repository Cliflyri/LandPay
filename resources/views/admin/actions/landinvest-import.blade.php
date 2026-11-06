@extends('layouts.admin')
@section('title', 'Import from LandInvest | LandPay')
@section('body_class', 'admin-page')
@section('content')
<section class="admin-section"><div class="container-fluid dashboard-container">
<div class="admin-heading"><div><span class="eyebrow eyebrow-dark">Administration</span><h1>Import from LandInvest</h1><p>Import property repayment status from landinvest for display on dashboard and plan pages</p></div></div>
@if(session('import_summary'))<div class="alert alert-success" role="status">{{ session('import_summary') }}</div>@endif
@if(session('import_unmatched'))<div class="alert alert-warning">Retained without a matching LandPay property: {{ implode(', ', session('import_unmatched')) }}</div>@endif
@if($snapshot)
<p class="text-muted">Export generated: {{ $snapshot['generated_at'] }} · Imported: {{ $snapshot['imported_at'] ?? 'Unknown' }} (UTC) · Source: {{ ($snapshot['source'] ?? 'manual') === 'https' ? 'LandInvest HTTPS' : 'Manual import' }}</p>
<p>{{ count($snapshot['properties']) }} properties. @foreach(array_count_values(array_column($snapshot['properties'], 'status')) as $status => $count) {{ str_replace('_', ' ', $status) }}: {{ $count }}. @endforeach</p>
@endif
<details class="mb-4"><summary>Automatic updates — {{ $connection['enabled'] ? 'Enabled' : 'Disabled' }}</summary>
<div class="mt-3">
<p>Endpoint: <code>{{ secure_url('/webhooks/landinvest/repayments') }}</code></p>
<p>Key ID: {{ $connection['key_id'] ?? 'Not configured' }} · Created: {{ $connection['created_at'] ?? 'Never' }}<br>
Last accepted HTTPS update: {{ $connection['last_received_at'] ?? 'Never' }}</p>
@if($connection['previous_expires_at'])<p>Previous key expires: {{ $connection['previous_expires_at'] }}</p>@endif
<p>Keys are displayed once when generated. Rotation requires updating LandInvest. Revocation immediately invalidates both keys and disables automatic imports. Manual imports remain available.</p>
<form method="post" action="{{ route('admin.landinvest.connection') }}">
@csrf
@error('password')<div class="alert alert-danger">{{ $message }}</div>@enderror
<label for="connection-password" class="form-label">Confirm your administrator password</label>
<input id="connection-password" type="password" name="password" required autocomplete="current-password" class="form-control mb-3">
@if($connection['key_id'])
<label class="d-block mb-3"><input type="checkbox" name="overlap" value="1"> When rotating, accept the previous key for 24 hours</label>
<button class="btn btn-outline-brand" name="operation" value="rotate">Rotate key</button>
<button class="btn btn-outline-danger" name="operation" value="revoke">Revoke all keys</button>
<button class="btn btn-outline-brand" name="operation" value="{{ $connection['enabled'] ? 'disable' : 'enable' }}">{{ $connection['enabled'] ? 'Disable' : 'Enable' }} automatic imports</button>
@else
<button class="btn btn-outline-brand" name="operation" value="generate">Generate key</button>
@endif
</form></div></details>
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
