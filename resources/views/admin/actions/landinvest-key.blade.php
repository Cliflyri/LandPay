@extends('layouts.admin')
@section('title', 'LandInvest key | LandPay')
@section('content')
<section class="admin-section"><div class="container">
<h1>LandInvest signing key</h1>
<p>Copy these values into LandInvest now. The secret will not be displayed again. Keep it out of email, chat, and source control.</p>
<label class="form-label" for="key-id">Key ID</label><input id="key-id" class="form-control mb-3" readonly value="{{ $keyId }}">
<label class="form-label" for="key-secret">Secret (select and copy)</label><input id="key-secret" class="form-control font-monospace mb-3" readonly autocomplete="off" value="{{ $secret }}">
<p>Use the secret exactly as displayed. Do not decode it.</p>
<a class="btn btn-brand" href="{{ route('admin.landinvest.import') }}">Done</a>
</div></section>
@endsection
