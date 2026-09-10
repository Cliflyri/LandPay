# Invoice SMS Notifications

## Implemented scope

LandPay sends short Twilio SMS notices only to clients who explicitly opt in through one combined preference:

> Send me invoice, reminder, and account-related text messages.

The preference is unchecked by default. Clients may turn it off in the portal, administrators may change it on the client record, and Twilio STOP remains enforced through the signed inbound webhook. Changing the primary phone keeps consent active and updates the normalized delivery number; consent events retain the phone snapshot that was in use when each decision was recorded.

## Messages and workflow

- Automatically generated invoices attempt one `invoice_created` SMS immediately after invoice creation.
- The unique delivery key prevents more than one successful/pending `invoice_created` send per invoice.
- Automated reminder SMS uses the existing reminder workflow and schedule; no second scheduler was added.
- Manually issued invoices send no SMS unless the administrator checks **Send invoice text notification**.
- Eligible invoice pages provide **Send SMS reminder** beside the existing email reminder.
- SMS failure is logged and does not roll back an invoice or prevent its email workflow.
- Messages identify the configured company, show invoice/balance and due date, and link to portal login.

Direct free-form client texting is intentionally not exposed in this release. The shared delivery service, client association, message type, audit fields, and report allow it to be added later without a second SMS subsystem.

## Client consent and administration

Consent is stored separately from the client phone so the existing primary phone remains the single source of contact data. LandPay stores enabled state, normalized E.164 destination, opt-in/out timestamps and sources, STOP time, actor/source, IP/user agent where available, and immutable consent-event history.

The administrator can:

- enable or disable SMS globally in **Settings → SMS Reminders**;
- configure Twilio and send a real test SMS to an entered phone with a custom message;
- view/change consent on each client record;
- view/export **Reports → Client SMS opt-in** with phone, consent state, timestamps, source, STOP state, and delivery totals.

When global SMS is turned off, currently opted-in clients receive a targeted dismissible portal announcement and its normal announcement email notification. Clients who are not opted in receive nothing. An opted-in client attempting to enable SMS while it is globally disabled sees the administrator's configured notice.

## Required Twilio settings

All values live in the existing encrypted/application settings structure:

- Global **Enable SMS reminders** toggle
- Twilio Account SID
- Twilio Auth Token (encrypted at rest and never redisplayed)
- Twilio Messaging Service SID
- Client notice shown when SMS is disabled

Configure the Twilio Messaging Service inbound webhook to:

`POST {APP_URL}/webhooks/twilio/messaging`

Twilio request signatures are validated before STOP is recorded. Sender registration, toll-free/A2P approval, and Twilio Messaging Service opt-out behavior remain Twilio operational prerequisites.

## Schedule

Invoice generation remains at `06:00`. Reminder delivery reads `LANDPAY_REMINDERS_SEND_TIME` through `config/landpay.php` (default `07:00`), and the Settings UI displays that same value. The host cron still only needs to run Laravel's scheduler every minute.

## Deployment

1. Deploy code and run `php artisan migrate --force`.
2. Clear/rebuild application caches using the normal deployment process.
3. Enter Twilio credentials, save while globally disabled, and use **Send configuration test**.
4. Configure the signed Twilio inbound webhook.
5. Enable SMS reminders when the sender is approved and the test succeeds.
