# Near-Payoff Client Experience

Status: Deferred. This document is a proposal only; no application code has been implemented.

## Objective

Help clients recognize a practical payoff opportunity when a plan is within two normal scheduled payments of completion and the projected final payment would be less than half of the normal payment.

The feature should improve communication without changing invoices, schedules, allocations, or accounting behavior.

## Recommended lean implementation

Create one reusable payoff-opportunity calculator and use its result in:

- The client dashboard
- Payment reminder emails
- The existing Make a Payment page through a prefilled payoff link

No database migration is expected.

## Eligibility

Show the payoff opportunity only when all conditions apply:

- The plan is active.
- The current payoff is greater than zero.
- The current payoff is no more than two normal scheduled payments.
- After one normal payment, the projected final payment is greater than zero.
- The projected final payment is less than half of the normal scheduled payment.
- Accelerated daily testing mode is not enabled.

The comparison should use the full normal scheduled payment, including scheduled principal and the recurring service fee. The actual payoff amount must come from the existing `CurrentPayoffService`.

If the current payoff is already less than or equal to one normal payment, use the simpler message: **Pay $X to complete your plan.**

## Reusable result

The calculator should return a small result containing:

- Whether the opportunity applies
- Normal scheduled payment
- Current payoff
- Projected final payment
- Effective or "as of" date
- Prefilled payoff URL

All consumers should use this same result so amounts and eligibility remain consistent.

## Client dashboard

Display a concise informational callout without replacing overdue or account-status messages.

Suggested copy:

> **You're close to paying off your plan**
>
> Continue with your regular scheduled payment of $150.00, or pay the current payoff amount of $205.00 to complete your plan. Payoff calculated as of August 11.

Show one emphasized action:

**Pay off plan - $205.00**

The existing Make a Payment action remains the regular-payment path, avoiding a duplicate button.

If an online payoff payment is already pending, hide or replace the callout with the existing processing status.

## Payment page

The payoff button should open the existing Make a Payment page with:

- The correct plan selected
- The current payoff amount prefilled
- The client's enabled payment methods unchanged

The server must recalculate and validate the payoff at submission time. The prefilled amount is a convenience, not an authoritative stored quote.

## Payment reminder email

Keep the configured payment-reminder template unchanged, then conditionally append a standard payoff callout after the rendered template.

Suggested copy:

> You're nearing the end of your payment plan. You may make your regular scheduled payment of $150.00 or pay the current payoff amount of $205.00. This payoff was calculated as of August 11 and may change if additional fees or account activity occur.

Include:

- The existing direct invoice link
- A prefilled Pay Current Payoff link
- The existing generic client portal link

Appending a programmatic conditional callout is preferred for the initial version because it:

- Preserves customized saved templates
- Requires no template restoration
- Avoids blank conditional template variables
- Keeps eligibility and wording consistent

A later enhancement could make the payoff callout editable in Admin Settings.

## Accounting and behavior boundaries

- Do not change the invoice amount.
- Do not alter the billing schedule.
- Do not automatically create, cancel, or modify the projected final invoice.
- Do not mark the plan paid off until funds are posted.
- Do not treat the displayed payoff as a guaranteed future quote.
- Continue showing overdue status when applicable; place the payoff opportunity beneath it.
- Recalculate after payments, credits, fees, reversals, amendments, or other account activity.
- Use "regular scheduled payment," not "monthly fee."

## Reused LandPay components

- `CurrentPayoffService`
- Current billing terms
- Client dashboard status area
- Existing payment reminder delivery
- Existing invoice and client portal links
- Existing Make a Payment form and query-prefill behavior
- Existing pending online-payment state

## Suggested implementation order

1. Add the reusable payoff-opportunity calculator.
2. Add focused calculation tests around the threshold.
3. Add the client dashboard callout and payoff link.
4. Append the conditional reminder-email callout.
5. Verify server-side payoff recalculation during payment submission.

## Required test cases

1. More than two payments remain: no callout.
2. Exactly two payments remain and projected final payment equals half: no callout.
3. Projected final payment is just below half: callout appears.
4. Current payoff is less than one normal payment: simplified message.
5. Plan is paused, closed, terminated, or draft: no callout.
6. Accelerated testing mode is enabled: no callout.
7. Plan is overdue: overdue status remains and payoff callout appears beneath it when eligible.
8. Pending online payoff exists: duplicate payoff action is hidden.
9. Fees, credits, or payments change the balance: amount recalculates.
10. Prefilled amount is stale at submission: server uses the newly calculated valid payoff.
11. Customized reminder template remains unchanged and receives the conditional appended callout.
12. Payment posting and final plan-closing behavior remain unchanged.

## Future options

- Editable payoff-callout wording in Admin Settings
- A dedicated near-payoff email template
- Optional one-time near-payoff notification separate from reminders
- Admin reporting for plans approaching payoff
