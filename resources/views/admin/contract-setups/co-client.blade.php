<div class="admin-next-card mt-4">
    <h2>{{ $coHeading ?? '2. Second client' }} @unless($addingClient ?? false)<span class="text-muted fs-6">(optional)</span>@endunless</h2>
    <div class="row g-3 mt-1">
        <div class="col-md-4"><label class="form-label">Use</label><select class="form-select mode-select" name="co_mode" data-prefix="co">@unless($addingClient ?? false)<option value="none" @selected($value('co_mode','none')==='none')>No second client</option>@endunless<option value="existing" @selected($value('co_mode', ($addingClient ?? false) ? 'existing' : 'none')==='existing')>Existing client</option><option value="new" @selected($value('co_mode')==='new')>New client</option></select></div>
        <div class="col-md-8 mode-existing" data-owner="co"><label class="form-label" for="contract_co_client_search">Client</label><div class="client-picker" data-contract-client-picker="co"><input class="form-control client-search" id="contract_co_client_search" autocomplete="off" placeholder="Start typing a client name, email, or phone"><div class="client-results list-group"></div><div class="selected-client mt-2"></div></div></div>
        <input type="hidden" name="co_client_id" value="{{ $value('co_client_id') }}">
    </div>
    <div class="row g-3 mt-1 mode-new" data-owner="co">
        <div class="col-md-3"><label class="form-label">Type</label><select class="form-select" name="co_client_type"><option value="individual" @selected($value('co_client_type','individual')==='individual')>Individual</option><option value="organization" @selected($value('co_client_type')==='organization')>Organization</option></select></div>
        <div class="col-md-9"><label class="form-label">Organization name</label><input class="form-control" name="co_organization_name" value="{{$value('co_organization_name')}}"></div>
        <div class="col-md-6"><label class="form-label">First name</label><input class="form-control" name="co_first_name" value="{{$value('co_first_name')}}"></div>
        <div class="col-md-6"><label class="form-label">Last name</label><input class="form-control" name="co_last_name" value="{{$value('co_last_name')}}"></div>
        <div class="col-md-6"><label class="form-label">Email</label><input class="form-control" type="email" name="co_email" value="{{$value('co_email')}}"></div>
        <div class="col-md-6"><label class="form-label">Phone</label><input class="form-control" name="co_phone" value="{{$value('co_phone')}}"></div>
        <div class="col-md-8"><label class="form-label">Address</label><input class="form-control" name="co_address_line_1" value="{{$value('co_address_line_1')}}"></div>
        <div class="col-md-4"><label class="form-label">Address line 2</label><input class="form-control" name="co_address_line_2" value="{{$value('co_address_line_2')}}"></div>
        <div class="col-md-5"><label class="form-label">City</label><input class="form-control" name="co_city" value="{{$value('co_city')}}"></div>
        <div class="col-md-3"><label class="form-label">State</label><input class="form-control" name="co_state_region" value="{{$value('co_state_region','AZ')}}"></div>
        <div class="col-md-4"><label class="form-label">ZIP code</label><input class="form-control" name="co_postal_code" value="{{$value('co_postal_code')}}"></div>
    </div>
    @if(isset($plan))
    <div class="row g-3 mt-2">
        <div class="col-md-4"><label class="form-label" for="co_effective_from">Membership effective date</label><input id="co_effective_from" class="form-control" type="date" name="co_effective_from" value="{{ $value('co_effective_from',today()->toDateString()) }}" required><small class="text-muted">Plan access begins on this date. Existing membership settings remain unchanged.</small></div>
        <div class="col-md-8">
            <input type="hidden" name="co_receives_invoices" value="0">
            <div class="form-check"><input class="form-check-input" type="checkbox" id="co_receives_invoices" name="co_receives_invoices" value="1" @checked($value('co_receives_invoices',true))><label class="form-check-label" for="co_receives_invoices">Eligible to receive invoices (existing primary-recipient rules apply)</label></div>
            <div class="form-check mt-2"><input class="form-check-input" type="checkbox" id="invite_co_client" name="invite_co_client" value="1" @checked($value('invite_co_client',false))><label class="form-check-label" for="invite_co_client">Send portal invitation if they do not already have portal access</label></div>
        </div>
    </div>
    @endif
</div>
