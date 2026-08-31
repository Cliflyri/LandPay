<!doctype html><html><body style="font-family:Arial,sans-serif;color:#173f40;line-height:1.6">
<p>Hello {{ $clientName }},</p>
<p>This is a reminder that invoice <strong>{{ $invoice->invoice_number }}</strong> has a remaining balance of <strong>{{ \App\Support\Money::format($balance) }}</strong>.</p>
<p>Payment was due upon receipt and is considered late after {{ $invoice->due_date->format('F j, Y') }}. If payment has already been sent, please disregard this reminder.</p>
<p><a href="{{ $secureUrl }}">View and pay invoice</a></p>
<p>For security, this link expires. Please contact your payment-plan administrator if you have questions or need to discuss the account.</p>
<p>Thank you,<br>{{ config('app.name', 'LandPay') }}</p>
</body></html>
