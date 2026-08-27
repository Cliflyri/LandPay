<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\AuditLog;
use App\Models\EmailTemplate;
use App\Models\User;
use App\Services\EmailTemplateService;
use App\Services\SmtpConfigurationService;
use App\Services\ReminderAutomationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class SettingsController extends Controller
{
    public function __construct(
        private readonly EmailTemplateService $templates,
        private readonly SmtpConfigurationService $smtp,
        private readonly ReminderAutomationService $automation,
    ) {}

    public function index(): View
    {
        return view('admin.settings.index', [
            'settings' => [
                'company_name' => AppSetting::valueFor('company_name', config('app.name', 'LandPay')),
                'company_email' => AppSetting::valueFor('company_email', ''),
                'company_phone' => AppSetting::valueFor('company_phone', ''),
                'reply_to_email' => AppSetting::valueFor('reply_to_email', ''),
                'email_footer' => AppSetting::valueFor('email_footer', 'Thank you for choosing LandPay.'),
            ],
            'templates' => $this->templates->all(),
            'templateVariables' => EmailTemplateService::TEMPLATE_VARIABLES,
            'smtp' => $this->smtp->values(),
            'reminderSettings' => $this->automation->settings(),
            'upcomingReminders' => $this->automation->eligible(now()->startOfDay(), true)->take(10),
        ]);
    }

    public function updateCompany(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:120'],
            'company_email' => ['nullable', 'email', 'max:254'],
            'company_phone' => ['nullable', 'string', 'max:50'],
            'reply_to_email' => ['nullable', 'email', 'max:254'],
            'email_footer' => ['nullable', 'string', 'max:1000'],
        ]);
        AppSetting::putMany($data);
        return back()->with('success', 'Company and email settings saved.');
    }

    public function updateTemplate(Request $request, EmailTemplate $template): RedirectResponse
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'body_html' => ['required', 'string', 'max:20000'],
        ]);
        $this->templates->validateVariables($data['subject'].' '.$data['body_html']);
        $template->update($data);
        return redirect()->route('admin.settings.index', ['section' => 'templates', 'template' => $template->id])->with('success', $template->name.' template saved.');
    }


    public function updateSmtp(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'smtp_enabled' => ['nullable', 'boolean'],
            'smtp_host' => ['required', 'string', 'max:255'],
            'smtp_port' => ['required', 'integer', 'between:1,65535'],
            'smtp_security' => ['required', 'in:tls,ssl,none'],
            'smtp_username' => ['nullable', 'string', 'max:255'],
            'smtp_password' => ['nullable', 'string', 'max:1000'],
            'smtp_from_address' => ['required', 'email', 'max:254'],
            'smtp_from_name' => ['required', 'string', 'max:120'],
            'smtp_ehlo_domain' => ['nullable', 'string', 'max:255'],
        ]);
        $password = $data['smtp_password'] ?? null;
        unset($data['smtp_password']);
        $data['smtp_enabled'] = $request->boolean('smtp_enabled') ? '1' : '0';
        $data['smtp_port'] = (string) $data['smtp_port'];
        AppSetting::putMany($data);
        if (filled($password)) {
            AppSetting::putEncrypted('smtp_password', $password);
        }
        $this->smtp->apply();
        return back()->with('success', 'SMTP settings saved. Use Send test email to verify delivery.');
    }

    public function testSmtp(Request $request): RedirectResponse
    {
        $data = $request->validate(['test_email' => ['required', 'email', 'max:254']]);
        if (! $this->smtp->apply()) {
            throw ValidationException::withMessages(['smtp' => 'Enable SMTP and save a valid host and From address before testing.']);
        }
        try {
            Mail::raw('This is a LandPay SMTP test. If you received it, outgoing email is configured correctly.', fn ($message) => $message->to($data['test_email'])->subject('LandPay SMTP test'));
        } catch (Throwable $exception) {
            report($exception);
            throw ValidationException::withMessages(['smtp' => 'SMTP test failed: '.str($exception->getMessage())->limit(300)]);
        }
        return back()->with('success', 'Test email sent to '.$data['test_email'].'.');
    }

    public function logoutAllDevices(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $loginKey = Auth::guard('web')->getName();

        $sessionIds = DB::table(config('session.table', 'sessions'))
            ->where('user_id', $user->id)
            ->get(['id', 'payload'])
            ->filter(function (object $session) use ($loginKey, $user): bool {
                $payload = json_decode((string) base64_decode($session->payload, true), true);

                return is_array($payload)
                    && (string) ($payload[$loginKey] ?? '') === (string) $user->getAuthIdentifier();
            })
            ->pluck('id');

        DB::table(config('session.table', 'sessions'))->whereIn('id', $sessionIds)->delete();

        AuditLog::query()->create([
            'actor_type' => 'administrator',
            'actor_user_id' => $user->id,
            'event' => 'administrator.logged_out_all_devices',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'before_values' => null,
            'after_values' => null,
            'ip_address' => $request->ip(),
            'user_agent' => str($request->userAgent())->limit(500),
        ]);

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    public function updateReminders(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'before_days' => ['required', 'integer', 'between:0,30'],
            'on_due' => ['nullable', 'boolean'],
            'after_interval' => ['required', 'integer', 'between:1,60'],
            'after_max' => ['required', 'integer', 'between:0,12'],
        ]);
        AppSetting::putMany([
            'reminders_automated_enabled' => $request->boolean('enabled') ? '1' : '0',
            'reminders_before_days' => (string) $data['before_days'],
            'reminders_on_due' => $request->boolean('on_due') ? '1' : '0',
            'reminders_after_interval' => (string) $data['after_interval'],
            'reminders_after_max' => (string) $data['after_max'],
        ]);
        return back()->with('success', 'Automated reminder rules saved.');
    }

    public function restoreTemplate(EmailTemplate $template): RedirectResponse
    {
        $default = $this->templates->defaults()[$template->slug] ?? null;
        abort_if($default === null, 404);
        $template->update($default + ['active' => true]);
        return redirect()->route('admin.settings.index', ['section' => 'templates', 'template' => $template->id])->with('success', $template->name.' restored to its default.');
    }
}
