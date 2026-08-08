<!doctype html><html><body style="margin:0;background:#f3f5f2;color:#173f40;font-family:Arial,sans-serif;line-height:1.6">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f3f5f2;padding:28px 12px"><tr><td align="center">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:640px;background:#fff;border:1px solid #dfe6df;border-radius:10px;overflow:hidden">
<tr><td style="padding:22px 30px;background:#173f40"><img src="{{ $message->embed(public_path('images/landpay-logo.png')) }}" alt="LandPay" style="display:block;width:116px;height:auto"></td></tr>
<tr><td style="padding:30px">{!! $renderedBody !!}</td></tr>
<tr><td style="padding:20px 30px;background:#edf3e9;color:#52635f;font-size:12px"><table role="presentation"><tr><td style="padding-right:15px"><img src="{{ $message->embed(public_path('images/landpay-logo.png')) }}" alt="LandPay" style="display:block;width:72px;height:auto"></td><td><strong>{{ \App\Models\AppSetting::valueFor('company_name', config('app.name','LandPay')) }}</strong><br>
@if(\App\Models\AppSetting::valueFor('company_email')){{ \App\Models\AppSetting::valueFor('company_email') }}@endif
@if(\App\Models\AppSetting::valueFor('company_phone')) &middot; {{ \App\Models\AppSetting::valueFor('company_phone') }}@endif
<br>{{ \App\Models\AppSetting::valueFor('email_footer', 'Thank you for choosing LandPay.') }}</td></tr></table></td></tr>
</table></td></tr></table></body></html>
