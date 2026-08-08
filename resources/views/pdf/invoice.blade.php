<!doctype html><html><head><meta charset="utf-8"><style>
body{font-family:DejaVu Sans,sans-serif;color:#173f40;font-size:12px} .header{border-bottom:3px solid #e8b949;padding-bottom:16px;margin-bottom:24px}.logo{width:120px}.meta{float:right;text-align:right}.clear{clear:both}h1{margin:0;font-size:25px}table{width:100%;border-collapse:collapse;margin-top:24px}th,td{padding:10px;border-bottom:1px solid #dfe6df;text-align:left}th{background:#edf3e9}.money{text-align:right}.total{font-size:16px;font-weight:bold;background:#f7f3df}.footer{margin-top:35px;color:#61716e;font-size:10px}
</style></head><body>
<div class="header"><img class="logo" src="{{ public_path('images/landpay-logo.png') }}"><div class="meta"><h1>Invoice</h1><strong>{{ $invoice->invoice_number }}</strong><br>Issued {{ $invoice->issue_date->format('M j, Y') }}<br><strong>Payment due upon receipt</strong><br>Late after {{ $invoice->due_date->format('M j, Y') }}</div><div class="clear"></div></div>
@php($primary=$invoice->paymentPlan->memberships->firstWhere('role','primary')?->client)
<p><strong>Bill to</strong><br>{{ $primary?->organization_name ?: trim(($primary?->first_name ?? '').' '.($primary?->last_name ?? '')) ?: 'Client' }}</p>
<p><strong>Plan</strong><br>{{ $invoice->paymentPlan->plan_number }} &middot; {{ $invoice->paymentPlan->title }}</p>
<table><thead><tr><th>Description</th><th class="money">Amount</th></tr></thead><tbody>
@foreach($invoice->items->sortBy('display_order') as $item)<tr><td>{{ $item->description }}@if($item->waiver_reason)<br><small>{{ $item->waiver_reason }}</small>@endif</td><td class="money">{{ \App\Support\Money::format($item->amount) }}</td></tr>@endforeach
@if(($creditApplied??0)>0)<tr><td>Account credit applied</td><td class="money">&minus; {{ \App\Support\Money::format($creditApplied) }}</td></tr>@endif
<tr class="total"><td>Balance due</td><td class="money">{{ \App\Support\Money::format($balance ?? 0) }}</td></tr></tbody></table>
<div class="footer"><strong>{{ \App\Models\AppSetting::valueFor('company_name', config('app.name','LandPay')) }}</strong><br>{{ \App\Models\AppSetting::valueFor('company_email','') }} {{ \App\Models\AppSetting::valueFor('company_phone','') }}</div>
</body></html>
