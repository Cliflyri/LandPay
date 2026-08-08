@extends('layouts.admin')
@section('title','Settings | LandPay')
@section('body_class','admin-page')
@section('content')
<section class="admin-section"><div class="container site-container">
<div class="admin-heading"><div><span class="eyebrow eyebrow-dark">Administration</span><h1>Settings</h1><p class="mb-0">Manage company identity and reusable customer email templates.</p></div><div><a class="btn btn-brand" href="{{route('admin.payment-methods.index')}}">Payment methods</a> <a class="btn btn-outline-brand" href="{{route('admin.dashboard')}}">Back to dashboard</a></div></div>
@if(session('success'))<div class="alert alert-success mt-4">{{session('success')}}</div>@endif
@if($errors->any())<div class="alert alert-danger mt-4">{{$errors->first()}}</div>@endif
<ul class="nav nav-tabs settings-tabs mt-4" id="settingsTabs" role="tablist">
<li class="nav-item" role="presentation"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#company-settings" type="button" role="tab">Company</button></li>
<li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#smtp-settings" type="button" role="tab">SMTP</button></li>
<li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#reminder-settings" type="button" role="tab">Reminders</button></li>
<li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#template-settings" type="button" role="tab">Email templates</button></li>
<li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#cron-settings" type="button" role="tab">Cron Instructions</button></li>
</ul>
<div class="tab-content settings-tab-content" id="settingsTabContent">
<div class="tab-pane fade show active" id="company-settings" role="tabpanel" tabindex="0">
<div class="admin-next-card mt-4"><h2>Company and email identity</h2><form method="post" action="{{route('admin.settings.company.update')}}" class="row g-3">@csrf @method('put')
<div class="col-md-6"><label class="form-label" for="company_name">Company name</label><input class="form-control" id="company_name" name="company_name" value="{{old('company_name',$settings['company_name'])}}" required></div>
<div class="col-md-6"><label class="form-label" for="company_email">Company email</label><input class="form-control" type="email" id="company_email" name="company_email" value="{{old('company_email',$settings['company_email'])}}"></div>
<div class="col-md-6"><label class="form-label" for="company_phone">Company phone</label><input class="form-control" id="company_phone" name="company_phone" value="{{old('company_phone',$settings['company_phone'])}}"></div>
<div class="col-md-6"><label class="form-label" for="reply_to_email">Reply-to email</label><input class="form-control" type="email" id="reply_to_email" name="reply_to_email" value="{{old('reply_to_email',$settings['reply_to_email'])}}"></div>
<div class="col-12"><label class="form-label" for="email_footer">Email footer</label><textarea class="form-control" id="email_footer" name="email_footer" rows="2">{{old('email_footer',$settings['email_footer'])}}</textarea></div>
<div class="col-12"><button class="btn btn-brand">Save settings</button></div></form></div>
</div>
<div class="tab-pane fade" id="smtp-settings" role="tabpanel" tabindex="0">
<div class="admin-next-card mt-4"><div class="d-flex flex-wrap justify-content-between gap-3"><div><h2>SMTP delivery</h2><p class="text-muted mb-0">Credentials are stored encrypted. Leave the password blank to keep the saved password.</p></div>

<span class="dashboard-status d-inline-flex align-items-center justify-content-center {{$smtp['smtp_enabled']==='1'?'status-current':'status-draft'}}">
    {{$smtp['smtp_enabled']==='1' ? 'Enabled' : 'Disabled'}}
</span>

</div>
<form method="post" action="{{route('admin.settings.smtp.update')}}" class="row g-3 mt-1">@csrf @method('put')
<div class="col-12"><div class="form-check form-switch"><input type="hidden" name="smtp_enabled" value="0"><input class="form-check-input" type="checkbox" id="smtp_enabled" name="smtp_enabled" value="1" @checked(old('smtp_enabled',$smtp['smtp_enabled'])==='1')><label class="form-check-label" for="smtp_enabled">Use SMTP for outgoing email</label></div></div>
<div class="col-md-8"><label class="form-label" for="smtp_host">SMTP host</label><input class="form-control" id="smtp_host" name="smtp_host" value="{{old('smtp_host',$smtp['smtp_host'])}}" placeholder="smtp.example.com" required></div>
<div class="col-md-4"><label class="form-label" for="smtp_port">Port</label><input class="form-control" type="number" id="smtp_port" name="smtp_port" min="1" max="65535" value="{{old('smtp_port',$smtp['smtp_port'])}}" required></div>
<div class="col-md-4"><label class="form-label" for="smtp_security">Security</label><select class="form-select" id="smtp_security" name="smtp_security"><option value="tls" @selected(old('smtp_security',$smtp['smtp_security'])==='tls')>TLS / STARTTLS</option><option value="ssl" @selected(old('smtp_security',$smtp['smtp_security'])==='ssl')>SSL</option><option value="none" @selected(old('smtp_security',$smtp['smtp_security'])==='none')>None</option></select></div>
<div class="col-md-8"><label class="form-label" for="smtp_username">Username</label><input class="form-control" autocomplete="off" id="smtp_username" name="smtp_username" value="{{old('smtp_username',$smtp['smtp_username'])}}"></div>
<div class="col-md-6"><label class="form-label" for="smtp_password">Password</label><input class="form-control" type="password" autocomplete="new-password" id="smtp_password" name="smtp_password" placeholder="{{$smtp['smtp_password_set']==='1'?'Saved — leave blank to keep':'Enter SMTP password'}}"><small class="text-muted">The saved password is never displayed.</small></div>
<div class="col-md-6"><label class="form-label" for="smtp_ehlo_domain">EHLO domain <span class="text-muted">(optional)</span></label><input class="form-control" id="smtp_ehlo_domain" name="smtp_ehlo_domain" value="{{old('smtp_ehlo_domain',$smtp['smtp_ehlo_domain'])}}" placeholder="landpay.example.com"></div>
<div class="col-md-6"><label class="form-label" for="smtp_from_address">From address</label><input class="form-control" type="email" id="smtp_from_address" name="smtp_from_address" value="{{old('smtp_from_address',$smtp['smtp_from_address'])}}" required></div>
<div class="col-md-6"><label class="form-label" for="smtp_from_name">From name</label><input class="form-control" id="smtp_from_name" name="smtp_from_name" value="{{old('smtp_from_name',$smtp['smtp_from_name'])}}" required></div>
<div class="col-12"><button class="btn btn-brand">Save SMTP settings</button></div></form>
<hr class="my-4"><form method="post" action="{{route('admin.settings.smtp.test')}}" class="row g-2 align-items-end">@csrf<div class="col-md-8"><label class="form-label" for="test_email">Send test email to</label><input class="form-control" type="email" id="test_email" name="test_email" value="{{old('test_email',$settings['company_email'])}}" required></div><div class="col-md-4"><button class="btn btn-outline-brand w-100">Send test email</button></div></form></div>
</div>
<div class="tab-pane fade" id="reminder-settings" role="tabpanel" tabindex="0">
<div class="admin-next-card mt-4"><div class="d-flex flex-wrap justify-content-between gap-3"><div><h2>Automated reminders</h2><p class="text-muted mb-0">The scheduler runs daily at 8:00 AM in the application timezone. Duplicate sends are blocked.</p></div>

<span class="dashboard-status d-inline-flex align-items-center justify-content-center {{$reminderSettings['enabled'] ? 'status-current' : 'status-draft'}}">
    {{ $reminderSettings['enabled'] ? 'Enabled' : 'Disabled' }}
</span>

</div>
<form method="post" action="{{route('admin.settings.reminders.update')}}" class="row g-3 mt-1">@csrf @method('put')
<div class="col-12"><div class="form-check form-switch"><input type="hidden" name="enabled" value="0"><input class="form-check-input" type="checkbox" id="reminders_enabled" name="enabled" value="1" @checked($reminderSettings['enabled'])><label class="form-check-label" for="reminders_enabled">Automatically send eligible reminders</label></div></div>
<div class="col-md-4"><label class="form-label" for="before_days">Pre-due reminder</label><div class="input-group"><input class="form-control" type="number" min="0" max="30" id="before_days" name="before_days" value="{{$reminderSettings['before_days']}}" required><span class="input-group-text">days before</span></div><small class="text-muted">Use 0 to disable.</small></div>
<div class="col-md-4"><label class="form-label" for="after_interval">Past-due interval</label><div class="input-group"><span class="input-group-text">Every</span><input class="form-control" type="number" min="1" max="60" id="after_interval" name="after_interval" value="{{$reminderSettings['after_interval']}}" required><span class="input-group-text">days</span></div></div>
<div class="col-md-4"><label class="form-label" for="after_max">Maximum past-due reminders</label><input class="form-control" type="number" min="0" max="12" id="after_max" name="after_max" value="{{$reminderSettings['after_max']}}" required></div>
<div class="col-12"><div class="form-check"><input type="hidden" name="on_due" value="0"><input class="form-check-input" type="checkbox" id="on_due" name="on_due" value="1" @checked($reminderSettings['on_due'])><label class="form-check-label" for="on_due">Send a reminder on the due date</label></div></div>
<div class="col-12"><button class="btn btn-brand">Save reminder rules</button></div></form>
@if($upcomingReminders->isNotEmpty())<hr class="my-4"><h3 class="h5">Upcoming reminder preview</h3><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Send date</th><th>Invoice</th><th>Rule</th><th>Recipient</th></tr></thead><tbody>@foreach($upcomingReminders as $candidate)<tr><td>{{$candidate['send_date']->format('M j, Y')}}</td><td><a href="{{route('admin.invoices.show',$candidate['invoice'])}}">{{$candidate['invoice']->invoice_number}}</a></td><td>{{str($candidate['trigger_type'])->replace('_',' ')->title()}}</td><td>{{$candidate['invoice']->paymentPlan->memberships->firstWhere('receives_invoices',true)?->client?->email ?? 'No recipient'}}</td></tr>@endforeach</tbody></table></div>@endif
</div>
</div>
<div class="tab-pane fade" id="template-settings" role="tabpanel" tabindex="0">
<div class="admin-next-card mt-4"><h2>Email templates</h2><p class="text-muted">Templates use the LandPay logo and a responsive, professional email frame. Allowed variables:</p><div class="template-variable-list">@foreach($variables as $variable)<code>@{{ {{ $variable }} }}</code>@endforeach</div></div>
@foreach($templates as $template)<div class="admin-next-card mt-4"><form method="post" action="{{route('admin.settings.templates.update',$template)}}">@csrf @method('put')
<div class="d-flex justify-content-between gap-3 align-items-start"><div><h2 class="mb-1">{{$template->name}}</h2><small class="text-muted">{{$template->slug}}</small></div><div class="form-check form-switch"><input type="hidden" name="active" value="0"><input class="form-check-input" type="checkbox" id="active-{{$template->id}}" name="active" value="1" @checked($template->active)><label class="form-check-label" for="active-{{$template->id}}">Enabled</label></div></div>
<div class="mt-3"><label class="form-label" for="subject-{{$template->id}}">Subject</label><input class="form-control" id="subject-{{$template->id}}" name="subject" value="{{$template->subject}}" required></div>
<div class="mt-3"><label class="form-label" for="body-{{$template->id}}">Message body (basic HTML supported)</label><textarea class="form-control font-monospace" id="body-{{$template->id}}" name="body_html" rows="8" required>{{$template->body_html}}</textarea></div>
<div class="d-flex flex-wrap gap-2 mt-3"><button class="btn btn-brand">Save HTML template</button></form><button class="btn btn-outline-brand" type="button" data-template-preview="preview-{{$template->id}}">Preview HTML</button><form method="post" action="{{route('admin.settings.templates.restore',$template)}}" onsubmit="return confirm('Restore the default {{$template->name}} template?');">@csrf<button class="btn btn-outline-brand">Restore default</button></form></div><div class="email-template-preview mt-3 d-none" id="preview-{{$template->id}}"><div class="email-preview-header"><img src="{{asset('images/landpay-logo.png')}}" alt="LandPay"></div><div class="email-preview-body" data-preview-body>{!! $template->body_html !!}</div><div class="email-preview-signature"><img src="{{asset('images/landpay-logo.png')}}" alt="LandPay"><div><strong>{{$settings['company_name']}}</strong><br>{{$settings['company_email']}} @if($settings['company_phone'])&middot; {{$settings['company_phone']}}@endif<br>{{$settings['email_footer']}}</div></div></div></div>@endforeach
</div>
<div class="tab-pane fade" id="cron-settings" role="tabpanel" tabindex="0">
<div class="admin-next-card mt-4"><h2>Cron Instructions</h2><p class="text-muted">The Laravel schedule is ready. Add one cron entry on the NAS so it is checked every minute. The reminder task itself runs once daily at 8:00 AM.</p>
<ul class="nav nav-pills cron-tabs mb-3" role="tablist">
<li class="nav-item"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#cron-regular" type="button">Regular user</button></li>
<li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#cron-root" type="button">Root / sudo</button></li>
<li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#cron-status" type="button">Status</button></li>
</ul>
<div class="tab-content cron-tab-content">
<div class="tab-pane fade show active" id="cron-regular"><p>Signed in as <code>landpaydev</code>, open your user crontab:</p>@include('admin.settings.partials.copy-command',['command'=>'crontab -e'])<p class="mt-3">Paste this line, save, and exit:</p>@include('admin.settings.partials.copy-command',['command'=>"* * * * * cd '/media/F-4tb-WD-Red-NAS/Nas Web Root/LandPay' && /usr/bin/php artisan schedule:run >> /dev/null 2>&1"])</div>
<div class="tab-pane fade" id="cron-root"><p>From a sudo-capable account, edit the existing <code>landpaydev</code> crontab:</p>@include('admin.settings.partials.copy-command',['command'=>'sudo crontab -u landpaydev -e'])<p class="mt-3">Paste this same scheduler line:</p>@include('admin.settings.partials.copy-command',['command'=>"* * * * * cd '/media/F-4tb-WD-Red-NAS/Nas Web Root/LandPay' && /usr/bin/php artisan schedule:run >> /dev/null 2>&1"])</div>
<div class="tab-pane fade" id="cron-status"><p>Confirm the cron entry exists:</p>@include('admin.settings.partials.copy-command',['command'=>'crontab -l'])<p class="mt-3">From root/sudo, inspect the LandPay user crontab:</p>@include('admin.settings.partials.copy-command',['command'=>'sudo crontab -u landpaydev -l'])<p class="mt-3">Confirm Laravel sees the reminder schedule:</p>@include('admin.settings.partials.copy-command',['command'=>"cd '/media/F-4tb-WD-Red-NAS/Nas Web Root/LandPay' && /usr/bin/php artisan schedule:list"])</div>
</div></div>
</div>
</div>
</div></section>
@push('scripts')
<script>
document.addEventListener('click', function (event) {
    const button = event.target.closest('[data-template-preview]');
    if (!button) return;
    event.preventDefault();
    event.stopImmediatePropagation();
    const preview = document.getElementById(button.dataset.templatePreview);
    if (!preview) return;
    const editor = button.closest('.admin-next-card')?.querySelector('textarea[name="body_html"]');
    const previewBody = preview.querySelector('[data-preview-body]');
    if (editor && previewBody) previewBody.innerHTML = editor.value;
    preview.classList.toggle('d-none');
    button.textContent = preview.classList.contains('d-none') ? 'Preview HTML' : 'Hide preview';
}, true);
</script>
@endpush
@endsection
