<?php

namespace App\Services;

use App\Mail\AdminNoticeMail;
use App\Models\AdminNotice;
use App\Models\AppSetting;
use Illuminate\Support\Facades\Mail;
use Throwable;

class AdminNoticeEmailService
{
    private const CATEGORIES = [
        'invoice_first_viewed' => 'invoice',
        'billing_automation_failure' => 'invoice',
        'online_payment_received' => 'payments',
        'provider_payment_exception' => 'payments',
        'square_payment_anomaly' => 'payments',
        'client_payment_announced' => 'payments',
        'secure_message_reply' => 'secure_messages',
        'shared_document_uploaded' => 'documents',
        'client_contact_change' => 'account_portal',
        'portal_invitation_accepted' => 'account_portal',
    ];

    public function send(AdminNotice $notice): bool
    {
        $category = self::CATEGORIES[$notice->type] ?? null;
        if ($category === null || ! $this->enabled($category)) return false;

        $email = AppSetting::valueFor('admin_notice_email_address')
            ?: AppSetting::valueFor('reply_to_email')
            ?: AppSetting::valueFor('company_email');
        if (blank($email) || filter_var($email, FILTER_VALIDATE_EMAIL) === false) return false;

        $url = $this->url($notice);

        try {
            Mail::to(strtolower(trim($email)))->send(new AdminNoticeMail(
                $this->subject($notice),
                $notice->message,
                $url,
            ));
            return true;
        } catch (Throwable $exception) {
            report($exception);
            return false;
        }
    }

    private function enabled(string $category): bool
    {
        if ($category === 'secure_messages') {
            return AppSetting::valueFor(
                'admin_notice_email_secure_messages',
                AppSetting::valueFor('secure_message_admin_email_enabled', '0')
            ) === '1';
        }
        return AppSetting::valueFor('admin_notice_email_'.$category, '0') === '1';
    }

    private function subject(AdminNotice $notice): string
    {
        $name = $notice->client
            ? ($notice->client->organization_name ?: trim($notice->client->first_name.' '.$notice->client->last_name))
            : null;

        return match ($notice->type) {
            'invoice_first_viewed' => ($name ?: 'Client').' viewed invoice '.($notice->invoice?->invoice_number ?: ''),
            'online_payment_received' => ($name ?: 'Client').' made a payment',
            'client_payment_announced' => ($name ?: 'Client').' submitted a payment notice',
            'provider_payment_exception', 'square_payment_anomaly' => 'Payment requires administrator review',
            'secure_message_reply' => ($name ?: 'Client').' sent a secure message',
            'shared_document_uploaded' => ($name ?: 'Client').' uploaded a document',
            'client_contact_change' => ($name ?: 'Client').' requested account changes',
            'portal_invitation_accepted' => ($name ?: 'Client').' activated portal access',
            default => $notice->title,
        };
    }

    private function url(AdminNotice $notice): ?string
    {
        if ($notice->invoice) return route('admin.invoices.show', $notice->invoice);
        if ($notice->paymentIntent?->payment) return route('admin.payments.show', $notice->paymentIntent->payment);
        if ($notice->paymentIntent?->status === 'announced') return route('admin.payment-intents.receive', $notice->paymentIntent);
        if ($notice->secureMessageThread) return route('admin.messages.show', $notice->secureMessageThread);
        if ($notice->changeRequest) return route('admin.client-change-requests.show', $notice->changeRequest);
        if ($notice->client) return route('admin.clients.show', $notice->client);
        return route('admin.dashboard');
    }
}
