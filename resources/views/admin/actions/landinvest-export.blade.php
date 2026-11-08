@extends('layouts.admin')
@section('title', 'Export for LandInvest | LandPay')
@section('body_class', 'admin-page')
@section('content')
<section class="admin-section"><div class="container-fluid dashboard-container">
<div class="admin-heading"><div><span class="eyebrow eyebrow-dark">Administration</span><h1>Export for LandInvest</h1><p>Copy the entire CSV and paste it into LandInvest's import action.</p></div></div>
@if(session('import_summary'))<div class="alert alert-success">{{ session('import_summary') }}</div>@endif
<details class="mb-4"><summary>HTTPS export access — {{ $connection['enabled'] ? 'Enabled' : 'Disabled' }}
@if($connection['enabled']) - can auto <strong style="color: #b45309;">pull</strong> from LandInvest
@endif
</summary>
<div class="mt-3">
<p>Endpoint: <code>{{ secure_url('/webhooks/landinvest/export') }}</code></p>
<p>Key ID: {{ $connection['key_id'] ?? 'Not configured' }} · Created: {{ $connection['created_at'] ?? 'Never' }}<br>
Last successful authenticated export: {{ $connection['last_received_at'] ?? 'Never' }}</p>
@if($connection['previous_expires_at'])<p>Previous key expires: {{ $connection['previous_expires_at'] }}</p>@endif
<p>Keys are displayed once when generated. Rotation requires updating LandInvest. Revocation immediately invalidates both keys and disables HTTPS export access. Manual CSV exports remain available.</p>
<form method="post" action="{{ route('admin.landinvest.export.connection') }}">
@csrf
@error('password')<div class="alert alert-danger">{{ $message }}</div>@enderror
<label for="connection-password" class="form-label">Confirm your administrator password</label>
<input id="connection-password" type="password" name="password" required autocomplete="current-password" class="form-control mb-3">
@if($connection['key_id'])
<label class="d-block mb-3"><input type="checkbox" name="overlap" value="1"> When rotating, accept the previous key for 24 hours</label>
<button class="btn btn-outline-brand" name="operation" value="rotate">Rotate key</button>
<button class="btn btn-outline-danger" name="operation" value="revoke">Revoke all keys</button>
<button class="btn btn-outline-brand" name="operation" value="{{ $connection['enabled'] ? 'disable' : 'enable' }}">{{ $connection['enabled'] ? 'Disable' : 'Enable' }} HTTPS export access</button>
@else
<button class="btn btn-outline-brand" name="operation" value="generate">Generate key</button>
@endif
</form></div></details>
<div class="admin-next-card mt-4">
<p>{{ $planCount }} plans &middot; Generated {{ now()->format('M j, Y g:i A T') }}. Includes primary client names and property names (plan title, or description when untitled).</p>
<p class="text-muted">One row per non-test plan, including historical and inactive plans. Test plans and plans associated with test clients are excluded. Duplicate APNs are preserved for review during import. This export does not change any records.</p>
@if($missingApns)<div class="alert alert-warning">{{ $missingApns }} plans have no APN. Their rows are included, but need review before matching in LandInvest.</div>@endif
<label class="form-label" for="landinvest-csv">Complete LandPay CSV</label>
<textarea id="landinvest-csv" class="form-control font-monospace" rows="18" readonly spellcheck="false">{{ $csv }}</textarea>
<div class="d-flex align-items-center gap-3 mt-3"><button type="button" id="copy-landinvest-csv" class="btn btn-brand">Copy CSV</button><a href="{{ route('admin.actions.index') }}" class="btn btn-outline-brand">Back to Admin Actions</a><span id="landinvest-copy-status" role="status" aria-live="polite"></span></div>
</div></div></section>
@endsection
@push('scripts')
<script>
(() => {
    const field = document.getElementById('landinvest-csv');
    const status = document.getElementById('landinvest-copy-status');
    document.getElementById('copy-landinvest-csv').addEventListener('click', async () => {
        try {
            if (window.isSecureContext && navigator.clipboard) {
                await navigator.clipboard.writeText(field.value);
            } else {
                field.focus(); field.select();
                if (!document.execCommand('copy')) throw new Error('Clipboard unavailable');
            }
            status.textContent = 'CSV copied.';
        } catch (error) {
            field.focus(); field.select();
            status.textContent = 'Copy unavailable. The CSV is selected; press Ctrl+C (or Command+C).';
        }
    });
})();
</script>
@endpush
