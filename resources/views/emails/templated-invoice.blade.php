<!doctype html><html><body style="margin:0;background:#f3f5f2;color:#173f40;font-family:Arial,sans-serif;line-height:1.6">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f3f5f2;padding:28px 12px"><tr><td align="center">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:640px;background:#fff;border:1px solid #dfe6df;border-radius:10px;overflow:hidden">
<tr><td style="padding:22px 30px;background:#173f40"><img src="{{ $message->embed(public_path('images/landpay-logo.png')) }}" alt="LandPay" style="display:block;width:116px;height:auto"></td></tr>
<tr><td style="padding:30px">{!! $renderedBody !!}
@if(in_array($deliveryFormat,['inline','both'],true))
<table role="presentation" width="100%" cellpadding="8" cellspacing="0" style="margin-top:24px;border-collapse:collapse;border:1px solid #dfe6df">
<tr style="background:#f5f7f3"><td><strong>Invoice</strong></td><td align="right">{{ $invoice->invoice_number }}</td></tr>
<tr><td>Issue date</td><td align="right">{{ $invoice->issue_date->format('M j, Y') }}</td></tr>
<tr><td>Payment due</td><td align="right"><strong>Upon receipt</strong></td></tr>
<tr><td>Late after</td><td align="right">{{ $invoice->due_date->format('M j, Y') }}</td></tr>
@foreach($invoice->items->sortBy('display_order') as $item)<tr><td>{{ $item->description }}</td><td align="right">{{ \App\Support\Money::format($item->amount) }}</td></tr>@endforeach
<tr style="background:#edf3e9;font-size:17px"><td><strong>Balance due</strong></td><td align="right"><strong>{{ \App\Support\Money::format($balance ?? 0) }}</strong></td></tr>
</table>
@endif
@if(in_array($deliveryFormat,['pdf','both'],true))<p style="margin-top:22px;color:#61716e;font-size:13px">A PDF copy of this invoice is attached.</p>@endif
</td></tr>
<tr><td style="padding:20px 30px;background:#edf3e9;color:#52635f;font-size:12px">
<table role="presentation"><tr><td style="padding-right:15px"><img src="{{ $message->embed(public_path('images/landpay-logo.png')) }}" alt="LandPay" style="display:block;width:72px;height:auto"></td><td><strong>{{ \App\Models\AppSetting::valueFor('company_name', config('app.name','LandPay')) }}</strong><br>
@if(\App\Models\AppSetting::valueFor('company_email')){{ \App\Models\AppSetting::valueFor('company_email') }}@endif
@if(\App\Models\AppSetting::valueFor('company_phone')) &middot; {{ \App\Models\AppSetting::valueFor('company_phone') }}@endif
<br>{{ \App\Models\AppSetting::valueFor('email_footer', 'Thank you for choosing LandPay.') }}</td></tr></table>
</td></tr></table></td></tr></table></body></html>
