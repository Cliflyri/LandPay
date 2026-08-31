<!doctype html><html><body style="margin:0;background:#f3f5f2;color:#173f40;font-family:Arial,sans-serif;line-height:1.6">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f3f5f2;padding:28px 12px"><tr><td align="center">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:640px;background:#fff;border:1px solid #dfe6df;border-radius:10px">
<tr><td style="padding:30px">{!! $renderedBody !!}@unless($magicLinkEmbedded)<p style="margin:26px 0 8px"><a href="{{ $secureUrl }}" style="display:inline-block;padding:12px 20px;background:#d99a2b;color:#173f40;text-decoration:none;font-weight:bold;border-radius:6px">View and pay invoice</a></p><p style="font-size:12px;color:#61716e">For security, this link expires. Sign in is required for other account information.</p>@endunless</td></tr>
</table></td></tr></table></body></html>
