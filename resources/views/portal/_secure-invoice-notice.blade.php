@if($secureAccess ?? false)
<div class="container site-container mt-3">
    <div class="alert alert-info py-2 px-3 mb-0 secure-invoice-notice">
        <strong>Secure invoice access</strong> — You can view and pay this invoice without signing in.
        <a class="ms-1" href="{{ route('portal.login') }}">Sign in for full account access</a>
    </div>
</div>
@endif
