# LandPay delivery roadmap

## Current lean release

- Secure administrator access.
- Client and payment-plan management.
- Immutable invoices and financial postings.
- Manual payment preview, posting, receipt, and reversal.
- Administrator-reviewed monthly invoice generation with deterministic idempotency.
- Audited manual invoice reminders from invoice and dashboard actions.

## Next operational capabilities

1. Automated reminder schedules with editable templates, retry handling, and client opt-out rules.
2. Late-fee assessment and default-eligibility workflows with explicit administrator review.
3. Statements and focused operational reports derived from the immutable ledger.

## Future requests not yet implemented

- Invoice Ninja-style payment drawer from the dashboard three-dot menu. Open the existing payment form in a right-side panel; keep allocation preview, validation, confirmation, receipt options, and idempotency protections; submit without navigating away; refresh the affected row contract balance, current amount due, status, and invoice link (plus applicable dashboard totals); preserve the exact table and scroll position. Estimated implementation and testing time: 4-6 hours, or 6-8 hours for closer Invoice Ninja-style desktop and mobile polish.

- Custom invoice creation from the dashboard three-dot menu, payment-plan detail, and client detail where applicable. Support optional purchase-payment and service-fee amounts plus any number of manually described custom lines; require at least one positive line; provide preview before issuance; preserve normal email, reminder, PDF, payment-allocation, status, and voiding workflows. Purchase-payment lines reduce contract principal when paid; service-fee and custom lines are invoice-only by default and do not alter contract balance. Estimated implementation and testing time: 5-8 hours.
- Administrator-authored client portal banners. Allow a notice to target an entire client account or one payment plan, with title, message, informational/important/urgent style, draft/active state, optional start and expiration dates, preview, and an audit trail. Notices must be intentionally published and must not be triggered automatically by internal plan status.

- Global client portal announcements. Allow administrators to publish and disable a company-wide banner for events such as office closures or payment-processing delays, with optional scheduling, preview, and audit history. Keep this independent from paused plans and other internal administrative states.

- Draggable dashboard column ordering similar to Invoice Ninja, with the chosen order saved per administrator and a reset-to-default option. Include keyboard-accessible reordering and responsive behavior so mobile layouts remain usable.

## Later integrations

- Hosted payment checkout through an established processor such as Square or Stripe.
- Signed, idempotent payment-provider webhooks with reconciliation and exception queues.
- A separately authorized client portal for balances, statements, payment history, and overpayment choices.
- Carefully scoped workflow automation after the underlying administrator workflows are stable.

Do not store card or bank credentials in LandPay. Do not add Zelle, Venmo, Cash App, email parsing, or bank-import automation unless the product scope is explicitly revised.
