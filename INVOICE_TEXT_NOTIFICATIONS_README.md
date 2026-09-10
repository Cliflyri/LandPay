# Invoice SMS Notifications — Approved Implementation Plan

Status: approved; not yet implemented.

## Requirements

Add two independent, unchecked-by-default portal preferences:

- **Send me a text when a new invoice is created.**
- **Send me a text when an invoice reminder is created.**

Use a dedicated immediate-update route, outside the existing contact-change approval workflow. Unchecking immediately opts out of that type. Portal impersonation remains read-only.

Use `clients.primary_phone`; do not duplicate phone data. Save the exact consented number as `consented_phone_e164` (for example, `(928) 555-0123` becomes `+19285550123`). This is a consent snapshot and Twilio-ready destination, not a replacement for `primary_phone`. A primary-phone change disables both preferences until fresh consent.

A send requires: global SMS enabled; applicable preference enabled; no STOP block; a valid current primary phone matching `consented_phone_e164`; and no conflicting idempotent delivery.

Keep SMS short. Identify the company with `AppSetting::valueFor('company_name', config('app.name', 'LandPay'))`; include only invoice number, amount, due date, and a portal-login link/instruction. Do not include sensitive property details.

## Current code and integration

Portal/client controls belong in `resources/views/portal/account/show.blade.php`, a new `Portal/SmsPreferenceController`, the admin client show/edit views, and `Admin/ClientController`.

Automatic invoices currently flow:

```text
invoices:generate -> GenerateAutomaticInvoices
 -> AutomaticInvoiceService -> MonthlyInvoiceService::issue()
```

Call `InvoiceSmsService` from `AutomaticInvoiceService` after issuance succeeds. Do not call it inside `MonthlyInvoiceService`, which administrators also use. SMS errors create an `AdminNotice` and never roll back invoices.

Reminders currently flow:

```text
reminders:send -> ReminderAutomationService
 -> InvoiceReminderService -> InvoiceReminderMail
```

Send opted-in automated SMS from the same reminder candidate already processed by `ReminderAutomationService`. Do not add a scheduler or duplicate selection logic. SMS failure must not undo reminder/email results.

Add **Send SMS reminder** beside the current **Send reminder** button. It honors all send gates and may intentionally repeat.

Both administrator invoice paths in `Admin/InvoiceController.php`—monthly and ad-hoc manual—receive an unchecked **Send invoice SMS** checkbox that survives preview. No administrator-created invoice sends SMS unless explicitly checked.

## Scheduler finding

```php
Schedule::command('invoices:generate')
    ->dailyAt('06:00')->timezone(config('app.timezone'))->withoutOverlapping();
Schedule::command('reminders:send')
    ->dailyAt('07:00')->timezone(config('app.timezone'))->withoutOverlapping();
```

The Settings page incorrectly says reminders run at 08:00. Decide separately whether reminders should run at 06:05 or remain at 07:00, then align code and UI. SMS needs no new command.

## Data model

Create `client_sms_preferences`:

```text
id, client_id
notification_type        invoice_created | invoice_reminder
enabled, consented_phone_e164
opted_in_at, opt_in_source
opted_out_at, opt_out_source
stopped_at, stop_source
timestamps
unique(client_id, notification_type)
```

Create append-only `client_sms_consent_events`:

```text
id, client_id, notification_type, enabled
source                   portal | admin | twilio_stop | phone_changed
phone_snapshot
changed_by_user_id nullable, portal_account_id nullable
ip_address nullable, user_agent nullable, created_at
```

Every portal/admin change, STOP, and phone invalidation appends an event.

Create `sms_deliveries`:

```text
id, invoice_id, invoice_reminder_id nullable, payment_plan_id
recipient_client_id, recipient_phone
message_type             invoice_created | automated_reminder | manual_reminder
message_snapshot, idempotency_key unique
twilio_message_sid nullable unique
status                   pending | sent | delivered | failed | undelivered
sent_by_user_id nullable
sent_at, delivered_at, failed_at nullable
failure_message nullable, timestamps
```

Create the delivery before contacting Twilio. Existing `pending`, `sent`, or `delivered` automatic records do not send again. Retry failures by updating the same row.

```text
invoice-created:{invoice_uuid}
invoice-reminder:{invoice_uuid}:{trigger_type}:{trigger_date}
manual-reminder:{generated_uuid}
```

The first unique key guarantees no more than one `invoice_created` SMS per invoice.

## SMS Reminders settings

Reuse `app_settings`:

```text
twilio_sms_enabled
twilio_account_sid
twilio_auth_token                 encrypted
twilio_messaging_service_sid
twilio_disabled_client_notice
```

Add an **SMS Reminders** Settings tab with the global switch, Account SID, Auth Token password input, Messaging Service SID, editable disable notice, configuration status/test action, and opt-in report. Save the Auth Token using `AppSetting::putEncrypted()` and never redisplay it. Prefer a Messaging Service SID to a hard-coded sender.

Activation requires all credentials and a nonblank notice. Twilio also requires an SMS-capable Messaging Service/sender, applicable A2P/carrier registration, and the inbound webhook.

## Global disable notice

When the global switch changes from enabled to disabled:

1. Preserve preferences but block sending.
2. Snapshot clients with either preference enabled.
3. Create an informational announcement targeted only to them.
4. Send the existing announcement email.
5. Show the existing dismissible portal banner until dismissed.

Use `twilio_disabled_client_notice`. Suggested default:

> SMS messages are currently disabled by the administrator. Please ensure you are receiving email notices from LandPay.

`ClientAnnouncementRecipient` supports this, but `ClientAnnouncementService::activate()` broadcasts. Add a targeted-recipient method; do not use broadcast activation unchanged. Never notify non-opted-in clients.

If a client attempts opt-in while globally disabled, keep it off and show this notice inline. Do not create another announcement.

## Admin report and controls

Show clients with either preference enabled, including client link, primary and consent phones, both preferences, opt-in/out dates and sources, and STOP state. Allow selective disable/override in the report and client record.

Admin changes append audit events. STOP overrides portal/admin settings. Re-enabling after STOP requires confirmation that fresh consent was obtained.

## STOP webhook

Add `POST /webhooks/twilio/messaging` and a dedicated `TwilioMessagingWebhookController`; do not mix it with Square/Stripe webhooks. The existing `webhooks/*` CSRF exclusion applies.

Validate `X-Twilio-Signature` with the SDK, exact URL, request fields, and saved Auth Token. Normalize `From`, process Twilio's opt-out event, disable both preferences, set local STOP state, and append audit events. Retain Twilio's carrier-level STOP enforcement as the compliance backstop.

## Implementation and tests

Install `twilio/sdk`. Add preference/audit/delivery models and migrations, `TwilioConfigurationService`, `InvoiceSmsService`, portal/admin SMS controllers, webhook controller, Settings/client/invoice UI, targeted announcements, workflow integration, and focused tests.

Tests must cover: independent unchecked defaults; immediate portal/admin opt-in/out audit; invalid phones; phone-change invalidation; global-disable targeting and blocked opt-in; automatic invoice/reminder idempotency; explicit manual-invoice sends; repeatable manual reminders; failure isolation; webhook validation/STOP; and admin reporting/history.

Implementation order:

1. SDK and encrypted configuration.
2. Preference, audit, and delivery schema/models.
3. Immediate portal route and admin client controls.
4. Settings tab and report.
5. SMS service, eligibility, content, and idempotency.
6. Automatic invoice/reminder integration.
7. Manual invoice/reminder controls.
8. Targeted disable announcement/email.
9. STOP webhook.
10. Tests and scheduler wording reconciliation.
