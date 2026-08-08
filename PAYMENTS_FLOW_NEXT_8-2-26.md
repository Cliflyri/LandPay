# Payments Flow Next — 8-2-26

This document is the implementation handoff for LandPay's next payment stage. Follow it if the original planning session is unavailable.

## Confirmed product decisions

- Clients see **Credit card**, never a Stripe-versus-Square choice.
- An administrator selects Stripe or Square as the active credit-card processor.
- Processor changes affect only new attempts. Historical attempts retain their provider.
- Stripe and Square use hosted checkout.
- Only a verified, idempotent webhook may post an automatic payment. A browser return page never posts money.
- Zelle, Venmo, Cash App, Chime, bank transfer, checks, money orders, and custom methods use explicit instructions plus a client-reported-payment workflow.
- A reported manual payment does not change balances until an administrator verifies and posts it.
- Credit-card and other automatic payments automatically email an HTML/PDF receipt.
- Administrator-recorded payments offer **Record payment** and **Record and email receipt**.
- Every automatically posted payment creates a reconciliation notice that remains open until an administrator explicitly marks it recorded in the backup system.
- Receipt-email failure never rolls back a valid posted payment.
- Extra payment amounts apply to principal under the existing allocation rules, with no prepayment penalty.

## Client payment flow

1. Authenticated client chooses a plan when more than one active plan exists.
2. LandPay displays current amount due and suggests that amount.
3. Client may enter a larger amount.
4. Review clearly separates:
   - Open invoice allocation
   - Additional principal
   - Total payment
5. Client confirms the amount and sees enabled payment-method tabs/cards.
6. Available methods are generated from admin settings and display order.
7. Credit card silently uses the admin-selected processor.
8. Manual methods show explicit instructions and an **I am paying by [method] now** action.

Use integer cents throughout. Never use floating-point values for stored or provider amounts.

## Payment-method settings

Add **Settings → Payment Methods**. Each manual method needs structured fields:

- Enabled
- Client-facing display name
- Display order
- Short description
- Recipient name
- Email, phone, handle, account identifier, or mailing address
- Payment link
- QR code or approved image
- Memo format, supporting safe variables
- Instructions shown before reporting
- Instructions shown after reporting
- Require sender name
- Require sender email/phone
- Require confirmation/reference number
- Expected verification time
- Report expiration period
- Optional safe rich-text instructions
- Administrator-only notes

Recommended safe variables:

- `{{ client_name }}`
- `{{ plan_number }}`
- `{{ amount }}`
- `{{ amount_due }}`
- `{{ due_date }}`
- `{{ payment_reference }}`
- `{{ company_name }}`

Do not allow arbitrary scripts or unrestricted HTML. Use structured fields plus sanitized rich text.

### Credit-card settings

- Enable credit cards
- Active processor: Stripe or Square
- Test/live mode
- Connection/test status
- Minimum/maximum payment if needed
- Client-facing instructions
- Separate encrypted credentials for each provider
- Stripe publishable key, secret key, and webhook secret
- Square application ID, access token, location ID, and webhook signature key
- Never redisplay saved secrets
- Do not allow credentials needed by unresolved historical events, refunds, or disputes to be deleted

Do not implement a generic convenience-fee formula initially. Processor/card surcharge rules require separate policy review.

## Processor-neutral records

Create a processor-neutral payment-attempt model before integrating providers.

Suggested fields:

- UUID/internal public reference
- Portal account/client/payment plan
- Optional invoice context
- Requested amount
- Invoice allocation snapshot
- Additional-principal snapshot
- Client-facing method
- Provider: Stripe, Square, or manual
- Provider checkout/session/order/payment identifiers
- Unique idempotency key
- Status: draft, checkout_created, pending, completed, failed, expired, refunded, partially_refunded, disputed, canceled
- Provider status
- Currency
- Client IP/user agent where appropriate
- Completed/failed/expired timestamps
- Metadata
- Linked posted payment ID
- Created/updated timestamps

Create a provider-event table:

- Provider
- Unique provider event ID
- Event type
- Signature verification result
- Payload or appropriately redacted payload
- Received/processed timestamps
- Processing status/error
- Linked attempt/payment

Enforce database uniqueness for provider event IDs, provider payment IDs, attempt idempotency keys, and one posted payment per completed attempt.

## Stripe and Square

Use hosted checkout:

- Stripe Checkout Sessions
- Square Checkout API payment links

Create checkout server-side and attach the internal payment-attempt reference using the provider's supported reference/metadata fields.

Required sequence:

1. Validate authenticated client, active membership, plan, amount, and allocation preview.
2. Create internal attempt and stable idempotency key.
3. Create hosted checkout with that exact amount/reference.
4. Redirect client to provider.
5. Return page displays **processing/received**, but does not post payment.
6. Verify webhook signature.
7. Confirm provider payment status, currency, amount, and merchant/location.
8. Reject duplicates and mismatches.
9. Lock the attempt and post through the existing LandPay PaymentService/ledger.
10. Link provider attempt to the posted payment.
11. Create mandatory backup-system reconciliation record and notice.
12. Commit the financial posting.
13. Send receipt and update delivery history separately.

Never automatically fail over from Stripe to Square. A client may explicitly retry, producing a new attempt. Provider switching affects only new attempts.

## Manual payment reporting

For Zelle, Venmo, Cash App, Chime, bank transfer, check, money order, and custom methods:

### Instructions

Example:

> Send $135.00 to payments@example.com by Zelle. Include LP-1234 in the memo. Send from an account belonging to John Doe when possible.

Button:

> I am paying by Zelle now

Final report form:

- Plan
- Method
- Amount
- Sender name
- Sender email/phone if configured
- Confirmation/reference number if configured
- Date sent
- Optional client note
- Required acknowledgment that balances update only after receipt and verification

After submission:

> Zelle payment reported. You reported a $135.00 payment from John Doe. Your balance will update as soon as the administrator receives and verifies it.

Official ledger balances stay unchanged. The portal may show:

- Current amount due
- Reported payment
- Expected balance after verification

Label the state **Reported — awaiting receipt**, not merely **Pending**.

Suggested statuses:

- draft
- reported
- received_and_credited
- not_received
- canceled_by_client
- rejected
- expired
- amount_mismatch
- duplicate

Clients may cancel unresolved reports using **I did not send this payment**. Preserve all audit records.

Before creating a report, warn about unresolved reports matching client, plan, method, amount, and recent date. A report linked to a posted payment can never be posted again.

## Admin manual-payment workflow

Create an admin notice:

> Zelle payment reported — John Doe reports sending $135.00 for LP-1234 on 08/02/2026.

Review page shows:

- Client and plan
- Method and instructions/account used
- Reported amount/date
- Sender identity/contact
- Confirmation/reference
- Client note
- Existing amount due
- Similar unresolved reports
- Possible duplicates

Actions:

- Review
- Mark received and record payment
- Not received
- Reject
- Dismiss only when appropriate

**Mark received and record payment** opens the existing payment allocation workflow prefilled with report data. It must use the normal payment service and ledger.

Posting options:

- Record payment
- Record and email receipt
- Checkbox: **I recorded this in the backup system**

After posting:

- Link the report to the posted payment
- Resolve the awaiting-receipt notice
- Update balances
- Show payment in portal history
- Send receipt when selected
- If backup-system acknowledgment was not checked, create a persistent reconciliation notice

## Receipts

### Automatic payments

Automatically send the existing template-driven HTML/PDF receipt after successful posting.

- Payment remains valid if email fails.
- Record email delivery status.
- Create an admin email-failure notice with a resend action.
- Receipt identifies client-facing method as **Credit card**.
- Admin detail records **Credit card via Stripe/Square** and provider reference.

### Administrator-recorded payments

Keep optional receipt behavior:

- Record payment
- Record and email receipt

Defaulting receipt email on for a client-reported payment is reasonable, but the administrator retains control.

## Backup-system reconciliation

Create a durable payment reconciliation record rather than relying only on a generic notice.

Suggested fields:

- Payment ID, unique
- Required boolean
- Status: required, acknowledged
- Acknowledged by user
- Acknowledged timestamp
- Optional note
- Timestamps

Every automatically posted payment sets reconciliation to required and creates a persistent admin notice:

> Automatic payment received — John Doe paid $135.00 by credit card on 08/02/2026 for LP-1234.

Notice details:

- Client
- Plan/APN
- Amount and date
- Client-facing method
- Internal processor
- Provider payment ID
- LandPay payment/transaction link
- Allocation
- Receipt email status

Actions:

- View payment
- View client
- Mark recorded in backup system

Use **Mark recorded in backup system**, not generic **Dismiss**. Record the administrator and timestamp. Viewing a notice does not clear it. These notices never auto-expire or auto-dismiss.

Repeated webhooks must not create duplicate payment, reconciliation, notice, or receipt records.

If an automatic payment is refunded, reversed, or disputed, create a new persistent action-required notice even if the original reconciliation was acknowledged.

## Admin notices categories

- Manual payments awaiting receipt
- Automatic payments requiring backup-system entry
- Receipt/email failures
- Refunds and disputes
- Contact changes
- Portal/account notices

Make payment reconciliation notices visually prominent. Preserve notice/audit history after resolution.

## Accounting rules

- All confirmed payments post through the existing financial posting/payment service.
- Do not create a parallel payment ledger.
- Allocation remains fee/invoice/principal aware according to current LandPay rules.
- A reported manual payment has no financial effects.
- Webhook return/redirect pages have no financial effects.
- Provider processing fees should be recorded separately for reconciliation and should not silently reduce the client's credited amount.
- Refunds, chargebacks, and disputes require append-only reversing transactions and audit notices.

## Security requirements

- Encrypt processor secrets.
- Verify every webhook signature using the raw request body.
- Use idempotency at provider, application, and database levels.
- Rate-limit checkout creation and manual reports.
- Authorize every client request through active plan membership.
- Validate server-side amount, currency, plan, and allocation; never trust browser fields.
- Use CSRF protection for portal/admin actions; webhook endpoints instead use signature verification.
- Redact secrets and sensitive payment data from logs.
- Never store full card data.
- Hosted checkout should minimize PCI scope.
- Use generic client error messages and detailed internal logs.
- Keep test and live credentials/events clearly separated.

## Failure behavior

- Checkout creation failure: keep attempt failed/retryable; do not post.
- Client abandons checkout: expire attempt; do not post.
- Browser returns before webhook: show processing; poll internal status only.
- Webhook repeats: acknowledge safely without duplicate effects.
- Webhook amount/currency mismatch: do not post; create urgent admin notice.
- Receipt email failure: retain payment and reconciliation notice; offer resend.
- Provider timeout: retrieve status by provider ID/idempotency key before retrying.
- Manual report not received: admin marks not received; notify client; balances remain unchanged.

## Implementation order

1. Payment-method settings and safe generated instruction tabs
2. Client amount entry and allocation-review page
3. Processor-neutral attempts, provider events, and reconciliation records
4. Manual payment reporting, duplicate checks, cancellation, and admin notices
5. Admin verification to existing payment allocation/posting workflow
6. Receipt options and backup-system acknowledgment
7. Stripe hosted Checkout and signed webhook handling
8. Square hosted Checkout and signed webhook handling
9. Automatic receipts and persistent reconciliation notices
10. Refund, reversal, dispute, email-failure, and reconciliation reporting
11. Sandbox end-to-end tests, then controlled live rollout

## Required testing

- Client can access only active member plans
- Default amount equals aggregate current due
- Larger amount allocation shows extra principal
- Multiple plans require explicit choice
- Disabled methods never appear
- Settings fields render correct sanitized instructions
- Manual report does not alter balances
- Duplicate manual report warning
- Client cancellation and admin rejection/not-received flows
- One report cannot post twice
- Stripe and Square checkout creation uses integer cents and stable idempotency
- Invalid webhook signature rejected
- Duplicate webhook produces one payment
- Wrong amount/currency/merchant does not post
- Browser success route cannot post
- Automatic payment posts through existing allocation logic
- Automatic receipt sent exactly once
- SMTP failure does not roll back payment
- Automatic payment always creates one unresolved reconciliation notice
- Notice remains until explicit backup-system acknowledgment
- Acknowledgment records administrator and timestamp
- Refund/dispute/reversal creates new action-required notice
- Processor switch affects only new attempts
- Existing provider records remain usable after switching
- Full financial, admin, portal, mail, and authorization regression suite

## Existing LandPay capabilities to reuse

At the time this document was written, LandPay already has:

- Append-only financial posting and balances
- Admin payment preview/allocation/posting
- Payment reversal
- Template-driven HTML/PDF payment receipts
- Optional admin receipt email
- Portal authentication and active-membership authorization
- Portal invoice/payment history and PDFs
- Admin notices
- SMTP/template settings
- Client contact-change review workflow

Extend these services; do not duplicate them.
