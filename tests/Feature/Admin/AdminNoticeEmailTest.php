<?php

namespace Tests\Feature\Admin;

use App\Mail\AdminNoticeMail;
use App\Models\AdminNotice;
use App\Models\AppSetting;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use App\Services\AdminNoticeEmailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminNoticeEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_enabled_category_emails_a_useful_invoice_notice_and_disabled_category_does_not(): void
    {
        Mail::fake();
        AppSetting::putMany([
            'admin_notice_email_address' => 'admin@example.com',
            'admin_notice_email_invoice' => '1',
            'admin_notice_email_payments' => '0',
        ]);

        $client = new Client(['first_name' => 'John', 'last_name' => 'Smith']);
        $client->id = 4;
        $invoice = new Invoice(['invoice_number' => 'INV-100']);
        $invoice->id = 7;
        $notice = new AdminNotice(['type' => 'invoice_first_viewed', 'message' => 'John Smith first viewed invoice INV-100.']);
        $notice->setRelation('client', $client);
        $notice->setRelation('invoice', $invoice);

        $this->assertTrue(app(AdminNoticeEmailService::class)->send($notice));
        Mail::assertSent(AdminNoticeMail::class, fn (AdminNoticeMail $mail) =>
            $mail->noticeSubject === 'John Smith viewed invoice INV-100'
            && $mail->adminUrl === route('admin.invoices.show', $invoice)
        );

        $paymentNotice = new AdminNotice(['type' => 'online_payment_received', 'message' => 'Payment received.']);
        $this->assertFalse(app(AdminNoticeEmailService::class)->send($paymentNotice));
        Mail::assertSentCount(1);
    }

    public function test_disabling_secure_message_email_requires_explicit_acknowledgement(): void
    {
        $admin = User::factory()->create();
        $this->actingAs($admin)->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('Email administrator about')
            ->assertSee('New secure messages');
        AppSetting::putMany(['admin_notice_email_secure_messages' => '1']);

        $this->actingAs($admin)->put(route('admin.settings.notifications.update'), [
            'admin_notice_email_invoice' => '1',
            'admin_notice_email_payments' => '1',
            'admin_notice_email_secure_messages' => '1',
            'admin_notice_email_documents' => '1',
            'admin_notice_email_account_portal' => '1',
            'admin_notice_email_address' => 'admin@example.com',
        ])->assertSessionHasNoErrors();
        $this->assertSame('1', AppSetting::valueFor('admin_notice_email_secure_messages'));

        $this->put(route('admin.settings.notifications.update'), [
            'admin_notice_email_secure_messages' => '0',
        ])->assertSessionHasErrors('secure_message_email_opt_out_ack');

        $this->put(route('admin.settings.notifications.update'), [
            'admin_notice_email_secure_messages' => '0',
            'secure_message_email_opt_out_ack' => '1',
        ])->assertSessionHasNoErrors();

        $this->assertSame('0', AppSetting::valueFor('admin_notice_email_secure_messages'));
    }
}
