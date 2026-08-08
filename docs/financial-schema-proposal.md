# LandPay Immutable Financial Schema Proposal

Status: approved 2026-07-26. All twelve financial design decisions are approved. Financial migrations and posting code may now be prepared in the approved implementation order.

Target: MariaDB 10.6.23 in development. Production MariaDB compatibility must be rechecked before cPanel migration. Money uses integer cents: unsigned `BIGINT` for stored magnitudes and signed `BIGINT` for balance effects.

## Boundaries and sign convention

LandPay tracks purchase payment plans; it is not a general accounting package. There is no chart of accounts, journal, or double-entry bookkeeping.

The immutable ledger changes only three business balances:

- `invoice_due`: positive adds an amount due on one invoice; negative reduces it.
- `client_credit`: positive creates unapplied plan-level client credit; negative applies or refunds it.
- `purchase_balance`: positive establishes or increases remaining financed principal; negative reduces it.

Transactions and effects are append-only after posting. Corrections create new transactions. No posted transaction, effect, payment detail, allocation, issued invoice item, or fee assessment is edited or deleted.

## Transaction types and permitted effects

| Type | Required effect behavior | Required supporting data |
|---|---|---|
| `opening_purchase_balance` | One positive `purchase_balance` effect | Activated plan; idempotency key |
| `invoice_charge` | Positive `invoice_due`; no purchase increase for scheduled purchase installments | Invoice and invoice item |
| `recurring_fee` | Positive `invoice_due`; no purchase-balance effect | Invoice item and fee assessment |
| `payment` | Negative `invoice_due`, negative `purchase_balance`, and/or positive `client_credit` according to allocations | Payment and allocations |
| `credit_application` | Negative `client_credit` plus negative `invoice_due`; purchase reduction only for purchase-payment items | Target invoice/items; idempotency key |
| `adjustment` | Authorized signed correction to approved balances | Reason; authorization for principal reduction |
| `reversal` | Exact opposite of every effect on one original transaction | Unique original transaction reference and reason |
| `refund` | Negative `client_credit`; cannot silently undo applied allocations | Reason, disbursement method/reference |
| `write_off` | Authorized negative `invoice_due`, `purchase_balance`, or both | Reason and administrator authorization |

There are no primary `credits`, `credit_applications`, `adjustments`, `reversals`, `refunds`, or `write_offs` tables. These are values of `financial_transactions.type`.

## Tables

### `invoices`

Presentation and collection container; not the purchase-balance source of truth.

| Column | Type | Null | Notes |
|---|---|---:|---|
| `id` | BIGINT UNSIGNED | no | Primary key |
| `uuid` | CHAR(36) | no | Public identifier; unique |
| `payment_plan_id` | BIGINT UNSIGNED | no | FK to `payment_plans`; restrict delete |
| `invoice_number` | VARCHAR(50) | no | Unique human-facing number |
| `period_start`, `period_end` | DATE | yes | Both null or a valid inclusive range |
| `issue_date`, `due_date` | DATE | no | Due date must not precede issue date |
| `status` | VARCHAR(32) | no | `draft`, `issued`, `partially_paid`, `paid`, `voided`, `closed_terminated` |
| `issued_at` | TIMESTAMP | yes | Required when issued |
| `operationally_closed_at` | TIMESTAMP | yes | Set by confirmed termination; financial effects remain |
| `reopened_at` | TIMESTAMP | yes | Latest administrative reinstatement time |
| `created_by_user_id` | BIGINT UNSIGNED | no | FK to `users`; restrict delete |
| `created_at`, `updated_at` | TIMESTAMP | yes | Metadata timestamps |

Indexes: unique `uuid`; unique `invoice_number`; `(payment_plan_id, due_date)`; `(payment_plan_id, status, due_date)`.

Rules: draft presentation metadata may change. Once issued, financial contents do not change. Status may be a transactionally maintained projection but must reconcile to transaction effects. Voiding an issued invoice does not delete effects; it requires appropriate reversal/adjustment transactions.

### `financial_transactions`

Immutable transaction header.

| Column | Type | Null | Notes |
|---|---|---:|---|
| `id` | BIGINT UNSIGNED | no | Primary key |
| `uuid` | CHAR(36) | no | Public identifier; unique |
| `payment_plan_id` | BIGINT UNSIGNED | no | FK to plan; restrict delete |
| `invoice_id` | BIGINT UNSIGNED | yes | Primary related invoice; restrict delete |
| `type` | VARCHAR(40) | no | Approved type listed above |
| `gross_amount` | BIGINT UNSIGNED | no | Display/tender magnitude; never signed |
| `effective_date` | DATE | no | Business date |
| `posted_at` | TIMESTAMP | no | Immutable posting time |
| `description` | VARCHAR(500) | yes | Human-readable explanation |
| `reason` | VARCHAR(500) | yes | Required for adjustment, reversal, refund, write-off |
| `actor_type` | VARCHAR(24) | no | `administrator`, `client`, or `system` |
| `posted_by_user_id` | BIGINT UNSIGNED | yes | Administrator FK; restrict delete |
| `posted_by_client_id` | BIGINT UNSIGNED | yes | Future client FK; restrict delete |
| `authorized_by_user_id` | BIGINT UNSIGNED | yes | Required for write-off and principal-reducing adjustment |
| `authorized_at` | TIMESTAMP | yes | Paired with authorizer |
| `reversal_of_transaction_id` | BIGINT UNSIGNED | yes | Self-FK; unique; restrict delete |
| `idempotency_key` | VARCHAR(100) | yes | Unique when supplied |
| `source_reference` | VARCHAR(150) | yes | External processor/check/import reference |
| `metadata` | JSON | yes | Non-critical, allowlisted metadata only |
| `created_at` | TIMESTAMP | no | No `updated_at` |

Indexes: unique `uuid`; unique `reversal_of_transaction_id`; unique `idempotency_key`; `(payment_plan_id, effective_date, id)`; `(invoice_id, effective_date, id)`; `(type, posted_at)`; `(source_reference)`.

Rules: reversal target must be posted, on the same plan, not itself a reversal, and not already reversed. Actor columns must match `actor_type`. Metadata cannot contain credentials, full payment-card data, bank credentials, or secrets.

### `invoice_items`

Immutable line items after invoice issuance.

| Column | Type | Null | Notes |
|---|---|---:|---|
| `id` | BIGINT UNSIGNED | no | Primary key |
| `invoice_id` | BIGINT UNSIGNED | no | FK; restrict delete |
| `source_transaction_id` | BIGINT UNSIGNED | no | FK to posting transaction; restrict delete |
| `item_type` | VARCHAR(32) | no | `purchase_payment`, `recurring_fee`, `late_fee`, `administrative_fee`, `other` |
| `description` | VARCHAR(500) | no | Display text |
| `standard_amount` | BIGINT UNSIGNED | no | Normal charge before waiver |
| `amount` | BIGINT UNSIGNED | no | Actual line amount; may be zero when waived |
| `waived_amount` | BIGINT UNSIGNED | no | Default zero; standard minus actual amount |
| `waiver_reason` | VARCHAR(500) | yes | Required when waived |
| `waived_by_user_id` | BIGINT UNSIGNED | yes | Administrator FK required when waived |
| `waived_at` | TIMESTAMP | yes | Required when waived |
| `display_order` | SMALLINT UNSIGNED | no | Default 1 |
| `created_at` | TIMESTAMP | no | No `updated_at` |

Indexes: `(invoice_id, display_order)`; `(invoice_id, item_type)`; index `source_transaction_id`.

### `transaction_effects`

Immutable signed balance changes and the sole financial source of truth.

| Column | Type | Null | Notes |
|---|---|---:|---|
| `id` | BIGINT UNSIGNED | no | Primary key |
| `financial_transaction_id` | BIGINT UNSIGNED | no | FK; restrict delete |
| `effect_type` | VARCHAR(32) | no | `invoice_due`, `client_credit`, `purchase_balance` |
| `invoice_id` | BIGINT UNSIGNED | yes | Required only for `invoice_due`; restrict delete |
| `amount_delta` | BIGINT SIGNED | no | Nonzero signed cents |
| `component` | VARCHAR(40) | no | See approved component set below |
| `invoice_item_id` | BIGINT UNSIGNED | yes | Related item; restrict delete |
| `fee_assessment_id` | BIGINT UNSIGNED | yes | Related assessment; FK added after assessment table exists |
| `description` | VARCHAR(500) | yes | Effect-level explanation |
| `created_at` | TIMESTAMP | no | No `updated_at` |

Initial components: `purchase_price_principal`, `documentation_fee_principal`, `scheduled_purchase_payment`, `monthly_service_fee`, `late_fee_stage_1`, `late_fee_stage_2`, `administrative_fee`, `unapplied_credit`, `refund`, `write_off`, `other`.

Purchase-price and documentation-fee principal sum to one contract balance, but remain separately attributable. Documentation principal is paid first. Only net purchase-price-principal reductions funded by payments or applied client credit count toward the administrator-only paid-in value.

Indexes: `(financial_transaction_id, id)`; `(invoice_id, effect_type, id)`; `(effect_type, financial_transaction_id)`; `invoice_item_id`; `fee_assessment_id`.

Rules: effects must belong to the header plan. `invoice_due` requires an invoice on that plan. Other effect types must have null `invoice_id`. Component/effect combinations are validated by the posting service.

### `payments`

Immutable supporting data for a `payment` transaction only.

| Column | Type | Null | Notes |
|---|---|---:|---|
| `id` | BIGINT UNSIGNED | no | Primary key |
| `financial_transaction_id` | BIGINT UNSIGNED | no | Unique FK to a `payment` transaction |
| `payer_client_id` | BIGINT UNSIGNED | yes | FK to client; restrict delete |
| `received_date` | DATE | no | Tender receipt date |
| `payment_method` | VARCHAR(32) | no | `cash`, `check`, `ach`, `card`, `money_order`, `other` |
| `external_reference` | VARCHAR(150) | yes | Processor/check reference; no sensitive instrument data |
| `gross_amount` | BIGINT UNSIGNED | no | Must equal transaction gross amount and allocations total |
| `current_invoice_amount` | BIGINT UNSIGNED | no | Snapshot used in approved preview |
| `overpayment_amount` | BIGINT UNSIGNED | no | Default zero |
| `overpayment_disposition` | VARCHAR(32) | yes | `principal` or `next_invoice_credit` |
| `decision_source` | VARCHAR(32) | yes | `client_portal` or `admin_recorded_instruction` |
| `decision_selected_at` | TIMESTAMP | yes | Required with overpayment |
| `instruction_recorded_by_user_id` | BIGINT UNSIGNED | yes | Required for admin-recorded instruction |
| `created_at` | TIMESTAMP | no | No `updated_at` |

Indexes: unique `financial_transaction_id`; `(payer_client_id, received_date)`; `(payment_method, external_reference)` non-unique initially because check references may repeat across sources.

Rules: when overpayment is zero, all decision fields are null. When positive, disposition/source/time are required. No default disposition. Amount sent to principal cannot exceed remaining purchase balance; any excess requires a new client choice to carry forward or refund.

### `payment_allocations`

Immutable explanation of how one payment was used.

| Column | Type | Null | Notes |
|---|---|---:|---|
| `id` | BIGINT UNSIGNED | no | Primary key |
| `payment_id` | BIGINT UNSIGNED | no | FK; restrict delete |
| `allocation_type` | VARCHAR(32) | no | `invoice_item`, `purchase_balance`, `client_credit` |
| `invoice_id` | BIGINT UNSIGNED | yes | Required for invoice-item allocation |
| `invoice_item_id` | BIGINT UNSIGNED | yes | Required for invoice-item allocation |
| `fee_assessment_id` | BIGINT UNSIGNED | yes | Optional related fee |
| `amount` | BIGINT UNSIGNED | no | Greater than zero |
| `display_order` | SMALLINT UNSIGNED | no | Default 1 |
| `created_at` | TIMESTAMP | no | No `updated_at` |

Indexes: `(payment_id, display_order)`; `invoice_id`; `invoice_item_id`; `fee_assessment_id`.

Rules: allocation amount totals must exactly equal payment gross amount. Referenced invoice/items must belong to the payment plan. Allocations and matching effects post atomically.

### `recurring_fee_rules`

Effective-dated fee definitions; used rules are ended and replaced rather than historically rewritten.

Columns: `id`, `uuid`, `payment_plan_id`, `name`, `amount` (unsigned cents), `frequency` (`monthly` initially), `effective_from`, nullable `effective_to`, `due_day`, `status` (`active`, `ended`), creator/updater user FKs, and timestamps.

Indexes: unique `uuid`; `(payment_plan_id, status, effective_from)`.

Rules: amount is positive; due day is 1-31 with final-day fallback; effective end cannot precede start. Once an assessment exists, amount/frequency/effective start are immutable; changing terms ends the rule and creates a replacement.

### `fee_assessments`

Idempotent record that a rule was assessed for one plan period.

Columns: `id`, `payment_plan_id`, `recurring_fee_rule_id`, `invoice_id`, `invoice_item_id`, unique `financial_transaction_id`, `period_key` (`YYYY-MM` initially), `effective_date`, `amount` (unsigned cents), and `created_at` only.

Indexes and constraints: unique `(payment_plan_id, recurring_fee_rule_id, period_key)`; unique `financial_transaction_id`; index `(invoice_id, effective_date)`. All foreign keys restrict deletion.

Paid and reversed state is derived from immutable transactions, effects, and allocations; it is not stored as mutable flags.

## Contract billing, delinquency, termination, and reinstatement

### Contract balance presentation

The contract begins with separately visible purchase-price principal and documentation-fee principal. Documentation fees may be waived. The two components sum to one contract balance, but their attribution is retained permanently because documentation payments do not count toward paid-in value.

The client portal focuses on current invoices, upcoming scheduled payments, payment history, and an up-to-date message. Contract balance is available only through a secondary plan-details view. Paid-in value is never displayed to clients; it is restricted to authorized administrators.

### `billing_defaults`

Administrator-managed defaults used when creating new plans. Changing defaults does not retroactively change existing contracts.

Columns: `id`, billing frequency, invoice day, due-days-after-issue, grace days, scheduled payment amount, monthly service fee, stage-one late-fee enabled/type/value/minimum-amount/days-late, stage-two late-fee enabled/type/value/minimum-amount/days-late, default-eligibility days, reminder settings, updater user FK, and timestamps.

Late-fee type is `fixed` or `percentage`. Percentage values use a fixed decimal rate representation, never floating point. Each percentage stage may define a minimum late-fee amount; the assessed fee is the greater of the calculated percentage or that configured minimum. A zero minimum disables the floor.

### `payment_plan_billing_terms`

One effective set of plan-specific terms copied from defaults and then independently managed. Columns include the same billing and delinquency values as defaults, `payment_plan_id`, `effective_from`, nullable `effective_to`, creator user FK, reason, and timestamps.

Terms are effective-dated. A contractual change ends the current row and creates a replacement so prior invoices remain explainable. Initial frequency is monthly, while the schema permits later approved frequencies.

For a $500 scheduled payment and $25 monthly service fee, the new invoice total is $525. The service fee is a separate line and is present on every invoice unless the plan term is zero or an administrator records a waiver.

### Invoice Generation, Delinquency, and Late Fee Assessment

Every billing period creates a separate invoice even when earlier invoices remain unpaid. More than one open invoice is therefore a clear delinquency indicator.

Each invoice receives only its own late charges when it reaches its configured thresholds. Stage one and optional stage two are cumulative, separately dated invoice lines. A typical invoice may show:

- Scheduled purchase payment: $500.
- Monthly service fee: $25.
- Late fee assessed on the configured first date: $25.
- Additional Late Fee added on the configured second date: $50.

For percentage late fees, the calculation base is only the remaining unpaid scheduled purchase-payment amount on that invoice at assessment time. Service fees, late fees, administrative fees, documentation fees, and other charges are excluded. The assessed amount is the greater of the calculated percentage or the configured minimum late fee. A maximum of one Stage One and one Stage Two late-fee assessment may be posted per invoice; unique invoice/stage constraints and idempotency keys enforce this.

Payments apply to the oldest unpaid fees first, then later fees chronologically, then the oldest unpaid scheduled purchase-payment item and later scheduled items. Contract balance decreases only by amounts allocated beyond fees to principal components.

### `contract_status_events`

Immutable lifecycle history separate from financial transactions.

Columns: `id`, `uuid`, `payment_plan_id`, event type (`default_eligible`, `termination_confirmed`, `reinstated`), effective timestamp, reason, administrator user ID for manual events, system eligibility details JSON, contract-balance snapshot, open-invoice count, paid-in-value snapshot visible only to administrators, related prior event ID when applicable, and `created_at` only.

Indexes: unique `uuid`; `(payment_plan_id, effective_at, id)`; `(event_type, effective_at)`. Rows are never updated or deleted.

LandPay may automatically identify a plan as default eligible and send configured client warnings, administrator email, and dashboard prompts. It never terminates a contract automatically in the initial release. Moving a plan to the terminated/default category requires explicit administrator confirmation.

Confirmed termination:

- Stops future invoice generation and client payment acceptance.
- Operationally closes open invoices while preserving their items, effects, allocations, and calculated historical balances.
- Freezes the remaining contract balance as the balance at termination; it does not create a write-off because LandPay is not treating the balance as a collection receivable.
- Preserves historical administrator-only paid-in value but ends the client's current contractual entitlement.
- Removes the plan from normal active dashboard views while retaining it in a filterable terminated/default category.
- Allows the client to sign in but replaces payment activity with instructions to contact LandPay.

Administrator reinstatement is supported in the initial release. By default it restores the client's contractual rights and reopens the invoices operationally closed by that termination without creating new financial effects or duplicate charges. Future invoices and payment acceptance resume. If different reinstatement terms are negotiated, the administrator posts explicit adjustments or waivers with reasons and authorization; historical termination and reinstatement events remain visible.
## Derived balances and projections

- Invoice amount: sum `transaction_effects.amount_delta` where `effect_type = invoice_due` and `invoice_id` matches.
- Unapplied client credit: sum effects where `effect_type = client_credit` and transaction plan matches.
- Remaining purchase balance: sum effects where `effect_type = purchase_balance` and transaction plan matches.
- Administrator-only historical paid-in value: net purchase-price-principal reduction funded by payments or applied client credit, including exact reversals. Documentation principal, service/late/administrative fees, write-offs, and non-client-funded adjustments are excluded. Termination does not erase this historical value, but ends current client entitlement until reinstatement.

Optional cached balances may be introduced only as rebuildable projections updated in the same transaction as posting. A reconciliation command must compare projections to effect sums.

## Atomic posting and immutability enforcement

Every post runs in one database transaction and locks the payment plan plus affected invoice rows. It creates the transaction header, effects, supporting payment/allocation/item/assessment records, and audit event together.

The posting service must:

1. Validate the allowed effects for the transaction type.
2. Validate plan ownership of every related invoice, item, assessment, and client.
3. Reject negative client credit.
4. Reject invoice or purchase balances below zero except through an explicitly authorized, validated payoff/correction workflow that still ends at zero or above.
5. Validate payment allocation totals and effect correspondence.
6. Require and enforce idempotency keys for automated invoice, recurring-fee, and credit-application jobs.
7. Enforce unique reversal reference and exact opposite effects.
8. Write an audit event without copying sensitive tender information.

Application models expose no update/delete workflow for immutable rows. Database users used by the web application should ultimately receive only the privileges required by the application; append-only database triggers are deferred because they can complicate migrations and operational recovery, but production privilege hardening is required before launch.

## Required overpayment workflow

`overpayment = payment gross amount - amount applied to the current invoice`

When positive, the preview shows the exact amount and requires one unselected choice:

- `principal`: 100% of the permitted overpayment creates a negative purchase-balance effect immediately.
- `next_invoice_credit`: 100% creates positive client credit. A later, separate idempotent `credit_application` reduces the next invoice, fees first and then its purchase-payment item. Purchase balance decreases only for the amount applied to purchase-payment items.

An administrator must record that the choice came from the client. The system never silently chooses. If principal application would exceed payoff, the payment applies only to zero and the client explicitly chooses credit or refund for the remainder.

## Example posting flows

Starting state after `opening_purchase_balance`: invoice $0, credit $0, purchase balance $20,000.

| Flow | Effects | Result: invoice / credit / purchase balance |
|---|---|---|
| Issue $500 purchase item + $25 fee | `invoice_due +500`, `invoice_due +25` | $525 / $0 / $20,000 |
| Receive $500 | `invoice_due -500`, `purchase_balance -475` | $25 / $0 / $19,525 |
| Receive $650 against $525; choose principal | `invoice_due -525`, `purchase_balance -625` | $0 / $0 / $19,375 |
| Receive $650 against $525; choose next-invoice credit | `invoice_due -525`, `purchase_balance -500`, `client_credit +125` | $0 / $125 / $19,500 |
| Apply $100 credit to purchase-payment item | `client_credit -100`, `invoice_due -100`, `purchase_balance -100` | Invoice -$100 from prior state / credit -$100 / purchase -$100 |
| Apply $25 credit to fee item | `client_credit -25`, `invoice_due -25` | Invoice -$25 / credit -$25 / purchase unchanged |
| Add authorized $20 administrative adjustment | `invoice_due +20` | Invoice +$20 / credit unchanged / purchase unchanged |
| Reverse $500 payment | Exact opposites: `invoice_due +500`, `purchase_balance +475` | Restores the original payment effects |
| Refund $75 unapplied credit | `client_credit -75` | Invoice unchanged / credit -$75 / purchase unchanged |
| Write off $300 principal, $100 currently invoiced | `purchase_balance -300`, `invoice_due -100` | Invoice -$100 / credit unchanged / purchase -$300 |

## Proposed migration and implementation order

1. Define PHP enums/value objects for transaction types, effects/components, invoice/item statuses, payment methods, allocations, overpayment choices, late-fee types, and contract-status events.
2. Create `billing_defaults`, effective-dated `payment_plan_billing_terms`, and immutable `contract_status_events`.
3. Create `invoices`.
4. Create `financial_transactions` with the self-reversal FK and idempotency constraints.
5. Create `invoice_items`.
6. Create `recurring_fee_rules` and `fee_assessments`.
7. Create `transaction_effects`, including the fee-assessment FK.
8. Create `payments` and `payment_allocations`.
9. Implement append-only model protections and the atomic posting service.
10. Implement balance queries and reconciliation tests before cached projections.
11. Implement opening balances and invoice charges.
12. Implement idempotent recurring fees.
13. Implement payment preview, allocation, and required overpayment choice.
14. Implement credit application, adjustments, reversals, refunds, and write-offs in that order.
15. Implement delinquency eligibility, manual termination, restricted terminated portal behavior, and reinstatement.
16. Build admin review screens only after posting behavior passes tests.

## Approved decisions

1. Approve the nine financial transaction types and allowed-effect matrix; contract termination/reinstatement remain lifecycle events, not financial transaction types.
2. Approve the eight core financial tables plus `billing_defaults`, effective-dated `payment_plan_billing_terms`, and immutable `contract_status_events` supporting tables.
3. Approve one combined contract balance with separately attributable purchase-price and documentation-fee principal.
4. Approve integer cents, signed effect deltas, unique reversal references, and idempotency constraints.
5. Approve the revised component, payment-method, invoice-status, and item-type values.
6. Approved: effective-dated billing and fee rules, separate monthly invoices, a maximum of one Stage One and one Stage Two late-fee assessment per invoice, and percentage late fees calculated only from the remaining unpaid scheduled purchase-payment amount for that invoice, subject to an optional configured minimum fee.
7. Approve oldest-fee-first then oldest-scheduled-payment allocation priority.
8. Approve administrator-only paid-in value as net client-funded purchase-price-principal reduction, excluding documentation principal and all fees.
9. Approve manual termination, operational invoice closure, frozen balance-at-termination, blocked payments, continued restricted portal access, and immutable status history.
10. Approve default reinstatement behavior: restore rights, reopen previously closed invoices, and require explicit immutable adjustments/waivers for negotiated changes.
11. Approve service-level append-only enforcement initially, with production database privilege hardening before launch.
12. Approve the revised migration and implementation order after the supporting tables are placed into it.