# Admin Payment Checkout

## Purpose

Add an administrator-only **Take Payment** workflow for clients who call and provide a credit card for payment. The feature should reuse LandPay's existing client checkout, payment-intent, webhook, allocation, posting, receipt, and reconciliation infrastructure. It must not introduce a separate accounting path or allow card details to pass through LandPay.

## Lean Scope

Add a **Take Payment** link to the admin sidebar. The link opens one admin entry page that:

1. Selects a client and one of that client's active payment plans.
2. Accepts the amount, payment type, and overpayment handling.
3. Creates an admin-originated client payment intent through the existing checkout service.
4. Presents the configured processor's secure hosted checkout using its supported embedded mode, or redirects when embedding is not supported.
5. Waits for the existing verified webhook to confirm and post the payment.
6. Shows the completed payment and receipt after confirmation.

LandPay must never receive, store, or log the card number, expiration date, security code, or other sensitive authentication data.

## Admin Entry Page

The initial form should contain:

- **Client:** required typed autocomplete.
- **Payment plan:** required and limited to the selected client's active plans.
- **Amount:** required, positive currency amount.
- **Payment type:** regular payment or principal-only payment.
- **Overpayment handling:** shown when applicable and governed by the existing allocation rules.
- **Internal reference:** optional, short, and explicitly labeled not to contain card information.
- **Continue to Secure Checkout:** creates the intent and starts hosted checkout.

The selected client, plan, amount, payment type, and overpayment decision become fixed when checkout is created. To change them, the administrator must cancel that attempt and create a new intent.

## Typed Client Search and Selection

Client selection must use the typed autocomplete interaction already established elsewhere in the admin portal, not a long static select menu.

Reuse the current behavior from the payment-plan, message, and document screens:

- The administrator begins typing a client name, organization, email address, or phone number.
- Matching clients appear as autocomplete suggestions.
- Selecting a suggestion stores the client ID in a hidden field while displaying the readable client label.
- Free text that does not match a real client is invalid and cannot be submitted.
- After selection, the payment-plan control is enabled and filtered to plans associated with that client.
- If there is exactly one eligible active plan, it may be selected automatically.
- Changing or clearing the client clears the selected payment plan.
- Results should be keyboard accessible and follow the current admin styling.

For a lean first implementation, reuse the existing in-page client data and picker pattern. Do not add a new search dependency or JavaScript framework. If the client collection later becomes too large, the same UI can be backed by a small authenticated search endpoint without changing the workflow.

## Checkout Presentation

Checkout presentation is processor-specific but must use the existing configured card processor:

- Use the processor's supported embedded checkout when available.
- If the processor does not permit its normal checkout URL in an iframe, use its official embedded checkout mode or redirect to the hosted page.
- Keep all payment fields inside processor-hosted elements.
- Return the administrator to the same intent status page after redirect checkout.

The page may poll a lightweight intent-status endpoint every few seconds. Polling only updates the display; it must never post financial activity. The verified webhook remains the authority that completes the intent and invokes the existing posting flow.

## Reuse of Existing Payment Flow

The admin controller should call the same application service used by the client portal. Reuse:

- `ClientPaymentIntent`
- Configured processor selection
- Checkout-session creation
- Webhook signature verification
- Processor transaction ID and idempotency protection
- Payment preview and allocation rules
- Payment posting service
- Ledger and balance effects
- Receipt generation and delivery behavior
- Failed, expired, and cancelled intent handling

Do not duplicate webhook controllers, payment allocation logic, or financial posting logic for admin checkout.

## Minimal Data and Audit Additions

If equivalent fields do not already exist, add:

- `origin`: `client_portal` or `admin_phone`
- `initiated_by_user_id`: nullable administrator user ID

Record the initiating administrator and origin in the audit trail. Use the processor transaction ID for completion idempotency. No ledger effect occurs until the verified webhook reports a successful payment.

## Routes

A minimal route set is:

```text
GET /admin/take-payment
POST /admin/take-payment
GET /admin/take-payment/{intent}/status
```

All routes require admin authentication. The status route must confirm that the intent is an authorized admin-originated intent and return only non-sensitive state required by the page.

## Validation and Safety

- Confirm the selected client is associated with the selected active plan.
- Apply the current amount and principal-only limits.
- Apply the current overpayment rules.
- Refuse disabled payment methods or an unconfigured card processor.
- Prevent checkout creation for completed, cancelled, or expired intents.
- Never accept card data in a LandPay request.
- Warn administrators not to place card information in references or notes.
- Do not treat the browser poll or return URL as proof of payment.
- Do not create duplicate payments when a webhook is delivered more than once.
- Confirm with the configured processor whether telephone-order transactions require MOTO enablement or classification before operational use.

## Failure and Cancellation Behavior

- A processor failure leaves the intent failed with no ledger effect.
- Closing or abandoning checkout leaves an incomplete intent that can expire with no ledger effect.
- A cancelled attempt cannot later be reused with changed payment details.
- If checkout succeeds while the admin page is closed, the webhook still completes normal posting.
- A delayed webhook leaves the page in a clear **Waiting for confirmation** state and provides a safe refresh action.

## Focused Test Coverage

Add tests proving:

1. Guests and client-portal users cannot access admin checkout.
2. An administrator can create an intent for a client and associated active plan.
3. Typed selection submits a real client ID, and unmatched text is rejected.
4. The plan selector is limited to the selected client's eligible plans.
5. Invalid client/plan combinations are rejected server-side.
6. Checkout uses the configured card processor and records `admin_phone` plus the initiating administrator.
7. The verified webhook posts the payment exactly once.
8. Regular, principal-only, and overpayment allocations use existing rules.
9. Failed, cancelled, and abandoned checkout attempts do not change balances.
10. The page never accepts or renders card-number or security-code fields owned by LandPay.

## Acceptance Criteria

The feature is complete when an authenticated administrator can find a client through the established typed autocomplete, select an eligible plan, enter payment instructions, open secure processor-hosted checkout, and see the resulting payment after the existing webhook posts it. The resulting payment must be indistinguishable in the ledger and accounting system from an equivalent client-portal payment except for its `admin_phone` origin and initiating-administrator audit information.
