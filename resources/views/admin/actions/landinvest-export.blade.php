@extends('layouts.admin')
@section('title', 'Export for LandInvest | LandPay')
@section('body_class', 'admin-page')
@section('content')
<section class="admin-section"><div class="container-fluid dashboard-container">
<div class="admin-heading"><div><span class="eyebrow eyebrow-dark">Administration</span><h1>Export for LandInvest</h1><p>Copy the entire CSV and paste it into LandInvest's import action.</p></div></div>
<div class="admin-next-card mt-4">
<p>{{ $planCount }} plans &middot; Generated {{ now()->format('M j, Y g:i A T') }}. Includes primary client names and property names (plan title, or description when untitled).</p>
<p class="text-muted">One row per plan, including historical and inactive plans. Duplicate APNs are preserved for review during import. This export does not change any records.</p>
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
