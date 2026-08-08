{{ html_entity_decode(strip_tags(str_replace(['</p>', '<br>', '<br/>', '<br />'], ["\n\n", "\n", "\n", "\n"], $renderedBody))) }}

{{ \App\Models\AppSetting::valueFor('company_name', config('app.name','LandPay')) }}
{{ \App\Models\AppSetting::valueFor('company_email', '') }}@if(\App\Models\AppSetting::valueFor('company_phone')) · {{ \App\Models\AppSetting::valueFor('company_phone') }}@endif
{{ \App\Models\AppSetting::valueFor('email_footer', 'Thank you for choosing LandPay.') }}
