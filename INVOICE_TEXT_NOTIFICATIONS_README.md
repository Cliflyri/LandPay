# Invoice Text Notifications — Future Implementation

Status: planned only; not implemented.

## Goal

Add Twilio SMS notifications for clients who explicitly opt in. Automatically generated invoices may send one short `invoice_created` message. Manually created invoices must not send automatically, but their invoice page should offer a manual **Send SMS reminder** action.

## Required behavior

- Portal checkbox, unchecked by default: **Send me a text when a new invoice is created.**
- Client may opt out by unchecking it; Twilio STOP remains supported.
- Use `clients.primary_phone`; do not add a duplicate phone field.
- Changing the opted-in phone should disable SMS until the client consents again.
- Admin can view and change consent on the client record and view opted-in clients in Settings.
- Consent changes must record enabled state, time, source, phone snapshot, and actor where applicable.
- Never send more than one `invoice_created` SMS for one invoice.
- SMS should identify LandPay and contain only invoice number/amount, due date, and a portal-login link or instruction.
- Manual reminders must honor active consent and Twilio STOP status.

## Existing LandPay integration points

### Client and portal

- Phone fields: `clients.primary_phone`, `clients.secondary_phone`.
- Portal account UI: `resources/views/portal/account/show.blade.php`.
- Portal controller: `app/Http/Controllers/Portal/AccountController.php`.
- Admin client UI: `resources/views/admin/clients/show.blade.php` and `edit.blade.php`.
- Admin client controller: `app/Http/Controllers/Admin/ClientController.php`.

Consent should use its own direct portal route instead of the contact-change approval workflow. Portal impersonation is already read-only.

### Automatic invoices

Current flow:

```text
invoices:generate
  -> GenerateAutomaticInvoices
  -> AutomaticInvoiceService
  -> MonthlyInvoiceService::issue()
  -> optional InvoiceEmailService
```

Trigger automatic SMS in `AutomaticInvoiceService` immediately after `MonthlyInvoiceService::issue()` returns. At that point the invoice transaction is complete. Use a separate try/catch so SMS failure creates an admin notice without failing or rolling back the invoice.

Do not trigger from `MonthlyInvoiceService`, because it is also used by administrator-created monthly invoices.

### Reminders

Current email flow:

```text
reminders:send
  -> ReminderAutomationService
  -> InvoiceReminderService
  -> InvoiceReminderMail
```

Do not add a second SMS schedule. Invoice-created SMS belongs to automatic invoice generation. A manual SMS reminder should be exposed beside the existing email reminder on `resources/views/admin/invoices/show.blade.php`.

The actual reminder schedule in `routes/console.php` is currently 07:00. Settings-page schedule wording should be reviewed separately because it is inconsistent.

## Recommended database changes

Add current consent state to `clients`:

```text
invoice_sms_enabled boolean default false
invoice_sms_opted_in_at timestamp nullable
invoice_sms_opt_in_source varchar nullable
invoice_sms_opted_out_at timestamp nullable
invoice_sms_opt_out_source varchar nullable
```

Add append-only `client_sms_consent_events`:

```text
id
client_id
enabled
source                 portal, admin, twilio_stop
phone_snapshot
changed_by_user_id nullable
portal_account_id nullable
created_at
```

Add `sms_deliveries`:

```text
id
invoice_id
payment_plan_id
recipient_client_id
message_type           invoice_created, manual_reminder
recipient_phone
message_snapshot
idempotency_key unique
twilio_message_sid nullable unique
status                 pending, sent, failed, delivered, undelivered
sent_by_user_id nullable
sent_at nullable
failed_at nullable
failure_message nullable
timestamps
```

## Idempotency

Use this unique key for automatic delivery:

```text
invoice-created:{invoice UUID}
```

`InvoiceSmsService` should create or retrieve that delivery row before contacting Twilio. It must not send when the row is already `pending` or `sent`. A failed delivery may retry by updating the same row, never by inserting another automatic-delivery record.

Manual reminders use a different type/key and may be sent more than once.

## Twilio settings

Reuse `app_settings` and `AppSetting`:

```text
twilio_sms_enabled
twilio_account_sid
twilio_auth_token              encrypted
twilio_messaging_service_sid
```

Add an **SMS** tab to `resources/views/admin/settings/index.blade.php`. Store the auth token with `AppSetting::putEncrypted()` and never display it after saving. Prefer a Twilio Messaging Service SID over a hard-coded sender number.

The Settings tab should contain connection settings, a global enable/disable switch, and an opted-in client table linking to client records.

## Twilio webhook

Add a dedicated public endpoint:

```text
POST /webhooks/twilio/messaging
```

Create `TwilioMessagingWebhookController`; do not add Twilio to the Square/Stripe `ProviderWebhookController`.

The controller must:

- Validate `X-Twilio-Signature` using the Twilio SDK, the exact webhook URL, request fields, and saved auth token.
- Read `From` and `OptOutType`.
- Normalize the sender to E.164 and match `clients.primary_phone`.
- Disable consent and append a `twilio_stop` consent event for STOP.
- Return a successful response for valid events it does not otherwise process.

`bootstrap/app.php` already excludes `webhooks/*` from CSRF protection.

## Expected code changes

Modify:

- `composer.json`
- `routes/web.php`
- `app/Models/Client.php`
- `app/Models/Invoice.php`
- `app/Http/Controllers/Portal/AccountController.php`
- `app/Http/Controllers/Admin/ClientController.php`
- `app/Http/Controllers/Admin/SettingsController.php`
- `app/Services/AutomaticInvoiceService.php`
- `resources/views/portal/account/show.blade.php`
- `resources/views/admin/clients/show.blade.php`
- `resources/views/admin/clients/edit.blade.php`
- `resources/views/admin/settings/index.blade.php`
- `resources/views/admin/invoices/show.blade.php`

Create:

- `app/Models/SmsDelivery.php`
- `app/Models/ClientSmsConsentEvent.php`
- `app/Services/TwilioConfigurationService.php`
- `app/Services/InvoiceSmsService.php`
- `app/Http/Controllers/TwilioMessagingWebhookController.php`
- `app/Http/Controllers/Admin/InvoiceSmsController.php`
- A small portal consent controller, or focused consent methods in `Portal/AccountController`
- Migrations for client consent state, consent events, and SMS deliveries
- Focused feature tests for consent, STOP, manual reminders, automatic delivery, and idempotency

## Implementation cautions

- Normalize and validate the primary phone as E.164 before enabling consent.
- STOP must override both portal and admin settings. Admin re-enabling should require confirmation that fresh consent was obtained.
- A phone-number change invalidates prior consent.
- Keep message content minimal; do not include sensitive property details.
- Existing portal invoice routes require authentication, which is preferred over a public invoice link.
- Automatic SMS must only run for system-generated invoices. Manual invoices only receive the manual reminder button.
- Queue tables exist, but production queue-worker availability is not established. The lean first version should send synchronously inside `invoices:generate`, consistent with current automatic email behavior. Queue later only after a reliable worker is deployed.
- Twilio sender/A2P registration and Messaging Service configuration are operational prerequisites.

## Suggested implementation order

1. Add Twilio SDK and encrypted settings UI.
2. Add consent state/history and portal/admin controls.
3. Add delivery records and `InvoiceSmsService` with idempotency.
4. Trigger it from `AutomaticInvoiceService` only.
5. Add the manual invoice SMS-reminder action.
6. Add and validate the Twilio STOP webhook.
7. Test duplicate command runs, failed retries, phone changes, portal opt-out, admin override, STOP, and manual invoices.
