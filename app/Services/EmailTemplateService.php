<?php

namespace App\Services;

use App\Enums\InvoiceItemType;
use App\Models\AppSetting;
use App\Models\Client;
use App\Models\EmailTemplate;
use App\Models\Invoice;
use App\Support\Money;
use Illuminate\Validation\ValidationException;

class EmailTemplateService
{
    public const VARIABLES = ['client_name', 'invoice_number', 'amount_due', 'due_date', 'issue_date', 'plan_number', 'plan_description', 'client_portal_url', 'invoice_portal_url', 'late_fee_notice', 'payment_portal_url', 'payment_amount', 'payment_date', 'payment_method', 'payment_reference', 'remaining_contract_balance', 'invitation_link', 'invitation_expires', 'company_name', 'company_email', 'company_phone'];

    public const TEMPLATE_VARIABLES = [
        'payment-reminder' => ['client_name', 'invoice_number', 'amount_due', 'due_date', 'issue_date', 'plan_number', 'plan_description', 'client_portal_url', 'invoice_portal_url', 'late_fee_notice', 'company_name', 'company_email', 'company_phone'],
        'invoice-email' => ['client_name', 'invoice_number', 'amount_due', 'due_date', 'issue_date', 'plan_number', 'plan_description', 'client_portal_url', 'invoice_portal_url', 'company_name', 'company_email', 'company_phone'],
        'payment-receipt' => ['client_name', 'invoice_number', 'payment_amount', 'payment_date', 'payment_method', 'payment_reference', 'remaining_contract_balance', 'plan_number', 'plan_description', 'client_portal_url', 'payment_portal_url', 'company_name', 'company_email', 'company_phone'],
        'payment-reversal' => ['client_name', 'invoice_number', 'payment_amount', 'payment_date', 'payment_method', 'payment_reference', 'remaining_contract_balance', 'plan_number', 'plan_description', 'client_portal_url', 'payment_portal_url', 'company_name', 'company_email', 'company_phone'],
        'portal-invitation' => ['client_name', 'invitation_link', 'invitation_expires', 'company_name', 'company_email', 'company_phone'],
    ];

    /** @return array<string, array{name:string,subject:string,body_html:string}> */
    public function defaults(): array
    {
        return [
            'payment-reminder' => [
                'name' => 'Payment reminder',
                'subject' => 'Payment reminder for invoice {{ invoice_number }}',
                'body_html' => '<p>Hello {{ client_name }},</p><p>This is a friendly reminder that invoice <strong>{{ invoice_number }}</strong> has a remaining balance of <strong>{{ amount_due }}</strong> and was due on {{ due_date }}.</p><p>View this invoice: <a href="{{ invoice_portal_url }}">{{ invoice_portal_url }}</a></p><p>If payment has already been sent, please disregard this message. Contact us if you have questions or need to discuss the account.</p><p>Visit your client portal: <a href="{{ client_portal_url }}">{{ client_portal_url }}</a></p>',
            ],
            'invoice-email' => [
                'name' => 'Invoice email',
                'subject' => 'Invoice {{ invoice_number }} from {{ company_name }}',
                'body_html' => '<p>Hello {{ client_name }},</p><p>Your invoice <strong>{{ invoice_number }}</strong> is ready. The amount due is <strong>{{ amount_due }}</strong> by {{ due_date }}.</p><p>View this invoice: <a href="{{ invoice_portal_url }}">{{ invoice_portal_url }}</a></p><p>Invoice details are included with this email. Please contact us if you have any questions.</p><p>Visit your client portal: <a href="{{ client_portal_url }}">{{ client_portal_url }}</a></p>',
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

    /** @return \Illuminate\Database\Eloquent\Collection<int, EmailTemplate> */
    public function all(): \Illuminate\Database\Eloquent\Collection
    {
        foreach ($this->defaults() as $slug => $default) {
            EmailTemplate::query()->firstOrCreate(['slug' => $slug], $default + ['active' => true]);
        }
        $reminder = EmailTemplate::query()->where('slug', 'payment-reminder')->first();
        if ($reminder && ! str_contains($reminder->body_html, '{{ late_fee_notice }}')) {
            $reminder->update(['body_html' => $reminder->body_html.'<p>{{ late_fee_notice }}</p>']);
        }

        return EmailTemplate::query()->orderBy('name')->get();
    }

    public function find(string $slug): EmailTemplate
    {
        $this->all();
        return EmailTemplate::query()->where('slug', $slug)->firstOrFail();
    }

    /** @return array{subject:string,body:string,variables:array<string,string>} */
    public function render(string $slug, Invoice $invoice, Client $client, int $balance): array
    {
        $template = $this->find($slug);
        $variables = $this->variables($invoice, $client, $balance);

        return [
            'subject' => $this->replace($template->subject, $variables),

            'body' => $this->replace($template->body_html, $variables),
            'variables' => $variables,
        ];
    }

    /** @param array<string, string> $variables @return array{subject:string,body:string,variables:array<string,string>} */
    public function renderVariables(string $slug, array $variables): array
    {
        $template = $this->find($slug);
        return ['subject' => $this->replace($template->subject, $variables), 'body' => $this->replace($template->body_html, $variables), 'variables' => $variables];
    }

    /** @return array<string, string> */
    public function variables(Invoice $invoice, Client $client, int $balance): array
    {
        $plan = $invoice->paymentPlan;
        return [
            'client_name' => $client->organization_name ?: trim($client->first_name.' '.$client->last_name) ?: 'Client',
            'invoice_number' => $invoice->invoice_number,
            'amount_due' => Money::format($balance),
            'due_date' => $invoice->due_date->format('F j, Y'),
            'issue_date' => $invoice->issue_date->format('F j, Y'),
            'plan_number' => $plan->plan_number,
            'plan_description' => $plan->title,
            'client_portal_url' => route('portal.dashboard'),
            'invoice_portal_url' => route('portal.invoices.show', $invoice),
            'late_fee_notice' => $invoice->items()->whereIn('item_type', [InvoiceItemType::LateFeeStageOne->value, InvoiceItemType::LateFeeStageTwo->value])->exists() ? 'A late fee has been added to this invoice because the payment is delinquent. Please make payment as soon as possible.' : '',
            'company_name' => AppSetting::valueFor('company_name', config('app.name', 'LandPay')),
            'company_email' => AppSetting::valueFor('company_email', ''),
            'company_phone' => AppSetting::valueFor('company_phone', ''),
        ];
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
            $text = preg_replace_callback('/{{\s*'.preg_quote($key, '/').'\s*}}/', fn () => e($value), $text);
        }
        return $text;
    }
}
