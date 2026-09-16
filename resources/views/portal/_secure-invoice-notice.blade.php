@if($secureAccess ?? false)
<div class="container site-container mt-3">
    <div class="alert alert-info py-2 px-3 mb-0 secure-invoice-notice">
        <strong>Secure invoice access</strong> — You can view and pay this invoice without signing in.
        <a class="ms-1" href="{{ route('portal.login') }}">Sign in for full account access</a>
        @if($offerPortalActivation ?? false)
            <div class="mt-2">Want all your invoices, payment history, documents, and messages in one place?
                <form class="d-inline" method="post" action="{{route('secure-invoice.portal-invitation.store')}}">@csrf<button class="btn btn-sm btn-outline-brand ms-1">Activate my client portal</button></form>
            </div>
        @endif
    </div>
</div>
@endif
