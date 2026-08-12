# Stripe ACH and Payment Checkout Improvements

Status: Deferred. This document is a proposal only; no application code has been implemented.

## Objective

Add Stripe-hosted ACH Direct Debit as a separate client payment option while improving payment amount handling and clarifying Square and Stripe configuration labels.

## Lean implementation

### Bank account (ACH)

- Add `ach` to the configurable client payment methods. The existing `PaymentMethod::Ach` enum can be reused.
- Display a separate **Bank account (ACH)** tab in the client portal.
- Reuse the existing Stripe secret key and webhook endpoint.
- ACH remains available independently of whether Square or Stripe is selected as the card provider.
- Show only relevant ACH settings:
  - Show this method to clients
  - Display name
  - Client instructions
  - Checkout button label
- Do not show recipient, handle, external-link, or logo fields for ACH.

### Client checkout

- Show the optional note and applicable overpayment choice above one **Pay Now (Bank Account)** button.
- Create a Stripe Checkout Session restricted to `us_bank_account`.
- Prefill the LandPay client email when one is available.
- After Checkout, show that the bank payment is processing; do not immediately mark the account paid.

### Asynchronous confirmation

ACH is not an instant payment method.

- `checkout.session.completed`: retain the checkout intent as processing.
- `checkout.session.async_payment_succeeded`: post the LandPay ACH payment and allocations, mark the intent received, send the normal receipt when applicable, and create a linked admin notice.
- `checkout.session.async_payment_failed`: mark the intent failed, do not post a LandPay payment, and create an admin failure/review notice.
- Continue enforcing webhook signature verification, amount/currency matching, and duplicate-event protection.
- Store successful payments as:
  - Method: ACH
  - Provider: Stripe
  - Reference: `stripe:<payment-intent-id>`

The Stripe webhook endpoint must subscribe to all three events above.

## Payment amount improvements

- Accept entries such as `.65` and normalize them to `0.65`.
- Normalize the visible amount when the field loses focus.
- Reject more than two decimal places with an inline validation message.
- Convert the amount to cents once during validation and reuse that value.
- Convert parser failures into form validation errors rather than server errors.
- Keep the field as text with `inputmode="decimal"` for predictable browser and mobile behavior.
- Apply the same handling to card, ACH, and payment-notification methods.

## Provider setting labels

Existing storage keys can remain unchanged.

### Square

- Environment -> **Mode**
- Sandbox / test -> **Sandbox**
- Live -> **Production**
- API secret -> **Access token**
- Webhook signing secret -> **Webhook signature key**
- Keep **Location ID**

### Stripe

- Environment -> **Mode**
- Sandbox / test -> **Test mode**
- Live -> **Live mode**
- API secret -> **Secret key**
- Webhook signing secret -> **Webhook endpoint signing secret**
- Remove the unused **Account / publishable identifier** field from the interface. Hosted Checkout does not require a publishable key.

Secrets remain encrypted and blank submissions retain existing saved secrets.

## Stripe diagnostics

Mirror the existing Square diagnostics:

- Log only sanitized Stripe error type, code, parameter, message, HTTP status, and LandPay intent UUID.
- Display a safe Stripe error message on the selected payment tab.
- Mark rejected checkout intents failed.
- Never log secret keys, webhook secrets, bank details, or full webhook payloads.

## Reused LandPay components

- `ClientPaymentIntent`
- Stripe connection settings
- Hosted checkout service
- Provider webhook endpoint and signature validation
- Payment posting and allocation services
- Admin notices and sidebar count
- Payment receipts and provider display
- Existing settings key/value storage

No database migration is expected.

## Required testing

1. Confirm ACH is hidden when disabled and visible when enabled.
2. Confirm Stripe test mode uses an `sk_test_...` secret.
3. Start ACH Checkout with valid and malformed amounts, including `.65`.
4. Confirm Checkout completion leaves the intent processing.
5. Simulate `checkout.session.async_payment_succeeded`; verify one payment, correct allocations, ACH/Stripe receipt details, and one admin notice.
6. Replay the success webhook; verify no duplicate payment.
7. Simulate `checkout.session.async_payment_failed`; verify no payment is posted and an admin notice appears.
8. Verify amount or currency mismatches enter review without posting.
9. Verify card and Square checkout behavior remains unchanged.
10. Repeat the flow with live credentials using a controlled low-value payment only after sandbox testing passes.

## Operational cautions

ACH settlement can take several days and can subsequently fail or be disputed. LandPay must not reduce balances or treat invoices as paid until Stripe confirms asynchronous success.
