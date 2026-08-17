@if($missingPhone||$missingAddress)
<div class="alert {{$contactUpdatePending?'alert-info':'alert-warning'}} mt-4">
    @if($contactUpdatePending)
        <strong>Contact update pending.</strong> Your phone and address update has been submitted for administrator review.
    @else
        <strong>Please complete your contact information.</strong> Please add {{($missingPhone&&$missingAddress)?'your phone number and mailing address':($missingPhone?'your phone number':'your mailing address')}} so we can keep your account records current. <a class="alert-link" href="{{route('portal.account.edit')}}">Update contact information</a>.
    @endif
</div>
@endif
