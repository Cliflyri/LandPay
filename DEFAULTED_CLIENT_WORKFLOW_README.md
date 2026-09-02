# Defaulted Client Workflow

## Purpose

LandPay needs a reversible way to move a client and payment plan out of active servicing after default. Administrators must retain the complete historical record, while balances LandPay will not pursue must be excluded from operational receivables.

LandPay is not an accounting system. This workflow must not create write-offs, journal entries, or other accounting transactions. To avoid a broad rewrite, the payment plan status will be the operational control; invoices retain their existing statuses and history.

## Goals

- Preserve all client, plan, invoice, payment, document, message, and ledger records.
- Stop future servicing activity for defaulted plans.
- Exclude defaulted balances from active receivables, delinquency totals, and projections.
- Keep defaulted records searchable and viewable by administrators.
- Display historical unpaid amounts without presenting them as collectible.
- Support controlled reinstatement without reconstructing invoices.
- Avoid adding another operational state to every invoice.

## Non-Goals

- Creating accounting or write-off entries.
- Deleting, voiding, or rewriting invoices because a plan defaulted.
- Pursuing collections after default.
- Automatically invoicing every missed month after reinstatement.
- Copying complete client records into snapshot tables.

## Status Model

Add `defaulted` to the payment plan statuses: `draft`, `active`, `paused`, `defaulted`, `terminated`, and `closed`.

- **Closed:** Completed normally.
- **Terminated:** Ended administratively for a reason other than default.
- **Defaulted:** Ended with unmet obligations and an unpaid historical balance LandPay will not pursue.

The admin interface should present the client as defaulted/inactive when no other active or paused plans remain. The payment plan status remains the authoritative processing switch.

## Default Metadata

Store on the payment plan:

- `defaulted_at`
- `defaulted_by_user_id`
- `default_reason`
- `status_before_default`

The reason is required. Record the actor, effective date, previous status, reason, open invoice count, and relevant balances in the existing audit log.

## Invoice Treatment

Do not add `defaulted` as an invoice status. Existing statuses remain accurate: issued remains `issued`, partially paid remains `partially_paid`, paid remains `paid`, and voided remains `voided`.

Default describes the plan lifecycle, not an invoice's payment state. Defaulting must not delete, void, mark paid, or otherwise alter open invoices. Their balances remain historical information on the defaulted plan.

## Default Workflow

Add a dedicated **Mark as defaulted** action instead of relying on the general plan-edit form.

Before confirmation, show the client, affected plan, current status, open invoice count and unpaid total, remaining contract principal, unapplied credit, pending payment attempts, and servicing actions that will stop. Require an effective date, reason, and administrator confirmation.

In one database transaction:

1. Change the selected active or paused plan to `defaulted`.
2. Save the default metadata.
3. Make the client defaulted/inactive when no other active or paused plans remain.
4. Cancel pending automated servicing activity where applicable.
5. Record an audit event and the historical balance at default.
6. Preserve all invoices and related history unchanged.

Draft plans must not silently become defaulted. They should be canceled, terminated, or removed through the appropriate workflow. For clients with multiple plans, default each plan independently.

## Processing Rules

Defaulted plans must be excluded from:

- Automatic and first-payment invoice generation
- Automated invoice email delivery
- Payment reminders and collection messages
- Late-fee assessment
- Scheduled payment requests
- New hosted-payment or card-payment attempts
- Expected-income and cash-flow projections
- Delinquency work queues

Most current automated functions already require `payment_plans.status = 'active'`. Preserve that rule and add regression tests. Paused plans retain their current behavior until explicitly defaulted.

## Receivables and Reporting

Defaulted balances must not be included in operational accounts receivable.

```text
Active receivables =
unpaid invoice balances belonging to eligible, non-defaulted plans
```

Exclude defaulted plans from accounts-receivable totals, dashboard receivable balances, expected-payment reports, overdue and delinquency totals, cash-flow projections, reminder queues, and late-fee queues.

Apply payment-plan status filters in reporting queries; do not alter invoice balances. Historical views may include defaulted plans but must clearly state:

> Historical unpaid balance at default: $X  
> Excluded from active receivables

An optional defaulted-accounts report may show the client, plan, default date and reason, unpaid invoice count, historical unpaid balance, and last payment date. It is informational and must not feed receivable totals.

## Admin Visibility

Add `Defaulted` filters and badges to client and plan screens. Defaulted records should be absent from normal active lists unless `Defaulted` or `All` is selected.

The defaulted plan page remains fully viewable and shows default metadata, historical balances, contract principal for reference, all invoices, payments, reversals, documents, messages, ledger activity, and audit history.

## Portal and Payment Access

Because LandPay does not pursue collections, a defaulted plan must not offer normal payments, create hosted-payment sessions, send reminders or payment links, or generate and email invoices.

Choose during implementation whether the portal becomes read-only or is disabled. At minimum, block payment actions. Existing secure invoice links must be revoked or made read-only and must not remain payment-capable.

## Reinstatement

Add a dedicated **Reinstate plan** action requiring an effective date, reason, target status (`active` or `paused`), billing-term review, invoice review, and confirmation.

In one transaction:

1. Change the plan to the selected status.
2. Preserve the original default metadata and audit event.
3. Record a reinstatement audit event.
4. Return existing unpaid invoices to operational receivable reporting.
5. Re-enable only the servicing options selected by the administrator.
6. Calculate the next future invoice date without automatically generating missed-month invoices.

Recommended metadata: `reinstated_at`, `reinstated_by_user_id`, and `reinstatement_reason`. Do not erase prior defaults. If repeat defaults are supported, use a lifecycle-event history table rather than overwriting old metadata.

## Required Code Review

This design avoids revising every invoice function, but all operational balance queries require review. Check automatic and first-payment invoicing, late fees, reminders, invoice emails, dashboards, reports, exports, list totals, portal and secure-invoice payments, account-credit application, and scheduled commands.

Historical admin views must continue loading defaulted plans and invoices.

## Testing Requirements

Tests must prove that:

1. An administrator can default an active or paused plan with a required reason.
2. Defaulting preserves all records, invoice statuses, and balances.
3. Defaulted plans generate no invoices, fees, reminders, emails, or payment sessions.
4. Defaulted balances are excluded from dashboards and active receivables.
5. Historical balances remain visible to administrators.
6. Admin filters find defaulted clients and plans.
7. Reinstatement restores an explicitly selected operational status.
8. Reinstatement returns existing unpaid invoices to active receivables.
9. Reinstatement does not generate missed-month invoices automatically.
10. Audit history retains default and reinstatement events.
11. Defaulting one plan does not inactivate a client with another active plan.
