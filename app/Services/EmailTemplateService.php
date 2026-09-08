<?php

namespace App\Services;

use App\Enums\InvoiceItemType;
use App\Models\AppSetting;
use App\Models\Client;
use App\Models\EmailTemplate;
use App\Models\Invoice;
use App\Support\Money;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class EmailTemplateService
{
    public const VARIABLES = ['client_name', 'invoice_number', 'amount_due', 'due_date', 'issue_date', 'plan_number', 'plan_description', 'client_portal_url', 'invoice_portal_url', 'magic_invoice_link', 'late_fee_notice', 'reminder_date', 'days_until_due', 'days_past_due', 'grace_period_end_date', 'next_late_fee_date', 'next_late_fee_amount', 'next_late_fee_description', 'late_fees_assessed', 'past_due_reminder_number', 'payment_portal_url', 'payment_amount', 'payment_date', 'payment_method', 'payment_reference', 'remaining_contract_balance', 'invitation_link', 'invitation_expires', 'company_name', 'company_email', 'company_phone'];

    public const REMINDER_VARIABLES = ['client_name', 'invoice_number', 'amount_due', 'due_date', 'issue_date', 'plan_number', 'plan_description', 'client_portal_url', 'invoice_portal_url', 'magic_invoice_link', 'reminder_date', 'days_until_due', 'days_past_due', 'grace_period_end_date', 'next_late_fee_date', 'next_late_fee_amount', 'next_late_fee_description', 'late_fees_assessed', 'past_due_reminder_number', 'company_name', 'company_email', 'company_phone'];

    public const VARIABLE_DESCRIPTIONS = [
        'reminder_date' => 'Date this reminder is sent.',
        'days_until_due' => 'Days remaining before the due date; blank after it is due.',
        'days_past_due' => 'Days since the due date; blank until it becomes past due.',
        'grace_period_end_date' => 'Last date in the plan grace period.',
        'next_late_fee_date' => 'Date the next pending late fee is scheduled.',
        'next_late_fee_amount' => 'Amount of the next fee; percentage fees are current estimates.',
        'next_late_fee_description' => 'Ready-to-use sentence describing the next fee, amount, and date.',
        'late_fees_assessed' => 'Ready-to-use sentence totaling late fees already added.',
        'past_due_reminder_number' => 'Past-due reminder sequence number; blank for due reminders.',
    ];

    public const TEMPLATE_VARIABLES = [
        'payment-reminder' => self::REMINDER_VARIABLES,
        'payment-due-reminder' => self::REMINDER_VARIABLES,
        'payment-past-due-reminder' => self::REMINDER_VARIABLES,
        'invoice-email' => ['client_name', 'invoice_number', 'amount_due', 'due_date', 'issue_date', 'plan_number', 'plan_description', 'client_portal_url', 'invoice_portal_url', 'magic_invoice_link', 'company_name', 'company_email', 'company_phone'],
        'payment-receipt' => ['client_name', 'invoice_number', 'payment_amount', 'payment_date', 'payment_method', 'payment_reference', 'remaining_contract_balance', 'plan_number', 'plan_description', 'client_portal_url', 'payment_portal_url', 'company_name', 'company_email', 'company_phone'],
        'payment-reversal' => ['client_name', 'invoice_number', 'payment_amount', 'payment_date', 'payment_method', 'payment_reference', 'remaining_contract_balance', 'plan_number', 'plan_description', 'client_portal_url', 'payment_portal_url', 'company_name', 'company_email', 'company_phone'],
        'portal-invitation' => ['client_name', 'invitation_link', 'invitation_expires', 'company_name', 'company_email', 'company_phone'],
    ];

    /** @return array<string, array{name:string,subject:string,body_html:string}> */
    public function defaults(): array
    {
        return [
            'payment-due-reminder' => [
                'name' => 'Payment due reminder',
                'subject' => 'Payment reminder for invoice {{ invoice_number }}',
                'body_html' => '<p>Hello {{ client_name }},</p><p>This is a reminder that invoice <strong>{{ invoice_number }}</strong> has a balance of <strong>{{ amount_due }}</strong> due on {{ due_date }}.</p><p>{{ next_late_fee_description }}</p><p>{{ magic_invoice_link }}</p><p>If payment has already been sent, please disregard this message.</p>',
            ],
            'payment-past-due-reminder' => [
                'name' => 'Past-due payment reminder',
                'subject' => 'Past-due invoice {{ invoice_number }}',
                'body_html' => '<p>Hello {{ client_name }},</p><p>Invoice <strong>{{ invoice_number }}</strong> is <strong>{{ days_past_due }} days past due</strong> with <strong>{{ amount_due }}</strong> remaining.</p><p>{{ late_fees_assessed }}</p><p>{{ next_late_fee_description }}</p><p>{{ magic_invoice_link }}</p><p>Please contact us if you have questions or need to discuss the account.</p>',
            ],
            'payment-reminder' => [
                'name' => 'Payment reminder',
                'subject' => 'Payment reminder for invoice {{ invoice_number }}',
                'body_html' => '<p>Hello {{ client_name }},</p><p>This is a friendly reminder that invoice <strong>{{ invoice_number }}</strong> has a remaining balance of <strong>{{ amount_due }}</strong> and was due on {{ due_date }}.</p><p>{{ magic_invoice_link }}</p><p>If payment has already been sent, please disregard this message. Contact us if you have questions or need to discuss the account.</p><p>Visit your client portal: <a href="{{ client_portal_url }}">{{ client_portal_url }}</a></p>',
            ],
            'invoice-email' => [
                'name' => 'Invoice email',
                'subject' => 'Invoice {{ invoice_number }} from {{ company_name }}',
                'body_html' => '<p>Hello {{ client_name }},</p><p>Your invoice <strong>{{ invoice_number }}</strong> is ready. The amount due is <strong>{{ amount_due }}</strong> by {{ due_date }}.</p><p>{{ magic_invoice_link }}</p><p>Invoice details are included with this email. Please contact us if you have any questions.</p><p>Visit your client portal: <a href="{{ client_portal_url }}">{{ client_portal_url }}</a></p>',
            ],
            'payment-receipt' => [
                'name' => 'Payment receipt',
                'subject' => 'Payment receipt for {{ payment_amount }}',
                'body_html' => '<p>Hello {{ client_name }},</p><p>Thank you. We received your payment of <strong>{{ payment_amount }}</strong> on {{ payment_date }}.</p><p>View this payment: <a href="{{ payment_portal_url }}">{{ payment_portal_url }}</a></p><p>Your payment receipt is included below and attached as a PDF.</p><p>Visit your client portal: <a href="{{ client_portal_url }}">{{ client_portal_url }}</a></p>',
            ],
            'payment-reversal' => [
                'name' => 'Payment reversal notice',
                'subject' => 'Payment reversal notice for {{ payment_amount }}',
                'body_html' => '<p>Hello {{ client_name }},</p><p>A payment of <strong>{{ payment_amount }}</strong> dated {{ payment_date }} was reversed by the plan administrator.</p><p>View this payment: <a href="{{ payment_portal_url }}">{{ payment_portal_url }}</a></p><p>The earlier receipt should no longer be treated as valid. Please contact us if you have questions.</p><p>Visit your client portal: <a href="{{ client_portal_url }}">{{ client_portal_url }}</a></p>',
            ],
            'portal-invitation' => [
                'name' => 'Client portal invitation',
                'subject' => 'Set up your {{ company_name }} client portal',
                'body_html' => '<p>Hello {{ client_name }},</p><p>You have been invited to securely access your payment-plan account.</p><p><a href="{{ invitation_link }}" style="display:inline-block;padding:12px 20px;background:#173f40;color:#ffffff;text-decoration:none;border-radius:4px">Create my portal password</a></p><p>This single-use link expires {{ invitation_expires }}. If you did not expect this invitation, you may ignore this email.</p>',
            ],
        ];
    }

    /** @return Collection<int, EmailTemplate> */
    public function all(): Collection
    {
        foreach ($this->defaults() as $slug => $default) {
            EmailTemplate::query()->firstOrCreate(['slug' => $slug], $default + ['active' => true]);
        }
        foreach (['payment-reminder', 'invoice-email'] as $slug) {
            $template = EmailTemplate::query()->where('slug', $slug)->first();
            $oldLink = '<p>View this invoice: <a href="{{ invoice_portal_url }}">{{ invoice_portal_url }}</a></p>';
            if ($template && str_contains($template->body_html, $oldLink)) {
                $template->update(['body_html' => str_replace($oldLink, '<p>{{ magic_invoice_link }}</p>', $template->body_html)]);
            }
        }

        $reminder = EmailTemplate::query()->where('slug', 'payment-reminder')->first();
        if ($reminder && ! str_contains($reminder->body_html, '{{ late_fee_notice }}')) {
            $reminder->update(['body_html' => $reminder->body_html.'<p>{{ late_fee_notice }}</p>']);
        }

        $order = ['invoice-email', 'payment-due-reminder', 'payment-past-due-reminder', 'payment-receipt', 'payment-reversal'];

        return EmailTemplate::query()->where('slug', '!=', 'payment-reminder')->get()
            ->sortBy(fn (EmailTemplate $template) => ($position = array_search($template->slug, $order, true)) === false ? 100 : $position)
            ->values();
    }

    public function find(string $slug): EmailTemplate
    {
        $this->all();

        return EmailTemplate::query()->where('slug', $slug)->firstOrFail();
    }

    /** @return array{subject:string,body:string,variables:array<string,string>,uses_magic_invoice_link:bool} */
    public function render(string $slug, Invoice $invoice, Client $client, int $balance, ?string $magicInvoiceUrl = null, array $context = []): array
    {
        $template = $this->find($slug);
        $variables = $this->variables($invoice, $client, $balance, $magicInvoiceUrl, $context);

        return [
            'subject' => $this->replace($template->subject, $variables),

            'body' => $this->replace($template->body_html, $variables),
            'variables' => $variables,
            'uses_magic_invoice_link' => preg_match('/{{\\s*magic_invoice_link\\s*}}/', $template->body_html) === 1,
        ];
    }

    /** @param array<string, string> $variables @return array{subject:string,body:string,variables:array<string,string>} */
    public function renderVariables(string $slug, array $variables): array
    {
        $template = $this->find($slug);

        return ['subject' => $this->replace($template->subject, $variables), 'body' => $this->replace($template->body_html, $variables), 'variables' => $variables];
    }

    /** @return array<string, string> */
    public function variables(Invoice $invoice, Client $client, int $balance, ?string $magicInvoiceUrl = null, array $context = []): array
    {
        $plan = $invoice->paymentPlan;
        $variables = [
            'client_name' => $client->organization_name ?: trim($client->first_name.' '.$client->last_name) ?: 'Client',
            'invoice_number' => $invoice->invoice_number,
            'amount_due' => Money::format($balance),
            'due_date' => $invoice->due_date->format('F j, Y'),
            'issue_date' => $invoice->issue_date->format('F j, Y'),
            'plan_number' => $plan->plan_number,
            'plan_description' => $plan->title,
            'client_portal_url' => route('portal.dashboard'),
            'invoice_portal_url' => route('portal.invoices.show', $invoice),
            'magic_invoice_link' => $this->magicInvoiceButton($magicInvoiceUrl),
            'late_fee_notice' => $invoice->items()->whereIn('item_type', [InvoiceItemType::LateFeeStageOne->value, InvoiceItemType::LateFeeStageTwo->value])->exists() ? 'A late fee has been added to this invoice because the payment is delinquent. Please make payment as soon as possible.' : '',
            'company_name' => AppSetting::valueFor('company_name', config('app.name', 'LandPay')),
            'company_email' => AppSetting::valueFor('company_email', ''),
            'company_phone' => AppSetting::valueFor('company_phone', ''),
        ];

        return $variables + $context;
    }

    /** @param array<string, string> $variables */
    public function validateVariables(string $text, array $variables = []): void
    {
        preg_match_all('/{{\s*([a-z_]+)\s*}}/', $text, $matches);
        $unknown = array_diff(array_unique($matches[1] ?? []), $variables === [] ? self::VARIABLES : array_keys($variables));
        if ($unknown !== []) {
            throw ValidationException::withMessages(['template' => 'Unknown template variable(s): '.implode(', ', $unknown).'.']);
        }
    }

    /** @param array<string, string> $variables */
    private function replace(string $text, array $variables): string
    {
        $this->validateVariables($text, $variables);
        foreach ($variables as $key => $value) {
            $text = preg_replace_callback(
                '/{{\s*'.preg_quote($key, '/').'\s*}}/',
                fn () => $key === 'magic_invoice_link' ? $value : e($value),
                $text,
            );
        }

        return $text;
    }

    private function magicInvoiceButton(?string $url): string
    {
        $url = e($url ?: '#');

        return '<a href="'.$url.'" style="display:inline-block;padding:12px 20px;background:#d99a2b;color:#173f40;text-decoration:none;font-weight:bold;border-radius:6px">View and pay invoice</a>';
    }
}
