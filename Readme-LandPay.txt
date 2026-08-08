You are helping me build a private, single-lender loan management web application inspired by the core loan-tracking features of GeekPay.io.

This is not a public SaaS product. It will initially be used by one lender and a relatively small number of clients.

## Primary objective

Create a secure loan-management application that allows an administrator to:

* Manage clients and co-clients.
* Create and manage payment plans.
* Track original and current principal balances.
* Define a customary monthly payment amount.
* Define a recurring monthly service or administrative fee.
* Define a monthly due day.
* Manually record payments at any time and for any amount.
* Automatically allocate payments to fees and principal.
* Preserve every financial event in an immutable ledger.
* Reverse or adjust incorrect transactions without editing or deleting the original transaction.
* View account status, payment history, fees, principal reductions, and current balances.

The application will later include:

* Client portal.
* Automated email reminders.
* Configurable reminder rules similar to Invoice Ninja.
* Groq-assisted email drafting.
* Twilio SMS reminders.
* Square, Stripe, or another established payment-processor integration.
* Statements, receipts, reports, and document storage.

Do not implement those later features during this initial phase unless a minimal placeholder or interface is useful to the architecture.

Do not implement Zelle, Venmo, Cash App, email parsing, n8n payment detection, or bank transaction imports at this time.

## Required technology

Use:

* PHP 8.2 or newer.
* Laravel, using the current stable version compatible with PHP 8.2.
* MySQL or MariaDB.
* Bootstrap 5 for the interface.
* Laravel Blade templates.
* PHPUnit or Pest for automated testing.
* Laravel migrations, models, controllers, form requests, policies, services, and database transactions.

Prefer server-rendered Laravel pages over a JavaScript-heavy single-page application.

Use JavaScript only where it materially improves the interface.

The project must be maintainable by someone working in Visual Studio Code with Codex.

## Important development behavior

Before changing anything:

1. Inspect the current project directory.
2. Determine whether it is empty, an existing Laravel installation, or another project.
3. Report what you found.
4. Do not overwrite an existing unrelated application.
5. Do not delete existing files without explaining why.
6. Do not modify files outside the project directory.
7. Do not install Docker.
8. Do not add unnecessary dependencies.
9. Do not use experimental packages when Laravel’s native features are sufficient.
10. Keep secrets out of source control.

If the directory is empty, initialize the Laravel application.

If Laravel is already installed, inspect its version, PHP requirements, database configuration, and existing structure before proceeding.

## Core accounting rules

This application does not use a conventional amortization schedule.

Do not generate a required month-by-month payment schedule.

Do not require payments to correspond to predefined installment records.

The loan has recurring payment expectations, but actual payments are independent financial events manually entered whenever they are received.

The administrator may enter:

* Early payments.
* Late payments.
* Partial payments.
* Multiple payments in one month.
* Payments covering multiple months.
* Irregular payment amounts.
* Overpayments.
* Backdated payments.
* Principal-only payments.
* Cash payments.
* Checks.
* ACH payments.
* Card payments.
* Zelle payments entered manually.
* Venmo payments entered manually.
* Cash App payments entered manually.
* Money orders.
* Other custom payment methods.

The customary monthly payment amount is used to determine whether the account is current or overdue. It is not a fixed installment record that must exist before a payment can be posted.

## Default payment allocation

For a regular payment, allocate funds in this order:

1. Outstanding recurring monthly service fees.
2. Outstanding late or administrative fees.
3. Principal.

Any amount remaining after all applicable fees are paid must be applied entirely to principal.

Example:

* Current principal: $20,000.
* Payment received: $700.
* Outstanding monthly service fee: $25.
* Outstanding late fee: $0.
* Fee allocation: $25.
* Principal allocation: $675.
* New principal: $19,325.

The payment entry screen must show an allocation preview before the administrator confirms the transaction.

## Principal-only payment

Support a separate principal-only payment type.

For a principal-only payment:

* Apply 100% of the payment to principal.
* Do not automatically satisfy monthly service fees.
* Do not automatically satisfy late fees.
* Do not automatically mark the client’s regular monthly obligation as paid.
* Require the administrator to explicitly select principal-only treatment.

## Partial and multiple payments

The application must support multiple payments during the same monthly period.

Example:

* Monthly expected payment: $500.
* Monthly service fee: $25.
* First payment: $200.
* Second payment: $300.

The ledger and account-status calculations must correctly determine:

* Total received for the period.
* Fees satisfied.
* Principal reduced.
* Remaining expected amount.
* Whether the account is current, partially paid, or overdue.

Do not assume one payment equals one month.

## Monthly fee assessments

A loan may have a recurring monthly service or administrative fee.

The system may create a fee-assessment ledger transaction when the fee becomes due.

That fee assessment is not a scheduled installment.

Fee assessments must be:

* Idempotent.
* Created no more than once for a given loan and fee period.
* Traceable to the applicable month.
* Reversible through a separate transaction.
* Never silently edited or deleted.

The initial version may provide a console command and service class for assessing currently due monthly fees.

Do not build automated scheduling until the fee assessment logic and idempotency tests are complete.

## Immutable financial ledger

Every financial event must be represented by immutable ledger entries.

After a financial transaction has been posted:

* It cannot be edited.
* It cannot be deleted through the normal application.
* Its amount cannot be silently changed.
* Its allocation cannot be silently changed.

Corrections must use:

* Reversal transactions.
* Adjustment transactions.
* New replacement transactions, when appropriate.

Each reversal must:

* Reference the original transaction.
* Fully offset the original financial effects.
* Restore principal and unpaid fee balances correctly.
* Include a required reason.
* Record the administrator who performed it.
* Record the date and time.
* Prevent the same transaction from being reversed more than once unless a documented reversal-of-reversal workflow is intentionally implemented.

Never recalculate historical ledger entries by modifying stored amounts.

## Source of truth

The ledger is the financial source of truth.

Current principal, outstanding fees, payment totals, and account status must be derived from ledger activity or maintained through transactionally safe projections that can be rebuilt from the ledger.

Do not make the `loans.current_principal_balance` field the only source of truth.

It is acceptable to maintain a cached current balance for performance, but:

* It must be updated in the same database transaction as the ledger.
* It must be reconcilable against ledger entries.
* Include a service or command that can verify the cached balance against the ledger.

## Proposed domain structure

Use clear domain terminology.

Suggested entities include:

### Users

Administrator users who can access the lender dashboard.

Initial roles:

* Administrator.
* Read-only auditor, if easy to include cleanly.

Do not build a complex permission system during this phase.

### Clients and payment-plan parties

Use `clients` for every person or organization that is a legally responsible party on a payment plan. A spouse or other jointly responsible person is another client, not a contact record.

The `clients` table should include:

* UUID or non-sequential public identifier.
* Client type: individual or organization, with individual as the initial default.
* First name.
* Middle name, optional.
* Last name.
* Organization name, nullable.
* Email.
* Primary phone.
* Secondary phone, optional.
* Mailing address.
* Notes.
* Active status.
* Created and updated timestamps.

Use a `payment_plan_clients` junction table to support one or more clients on a plan and allow one client to participate in more than one plan. Fields should include:

* Payment plan ID.
* Client ID.
* Role: primary or co-client.
* Responsibility status, initially jointly responsible unless a later legal requirement adds another option.
* Effective date, optional.
* End date, nullable.
* Communication preference or receives-statements flag, if needed.
* Created and updated timestamps.

Constraints should include:

* A unique pair of payment plan ID and client ID.
* Exactly one active primary client per payment plan, enforced transactionally and with the strongest practical database constraint.
* Any number of active co-clients.
* A payment plan must have at least one active client before activation.

### General client contacts

Use one `client_contacts` table for general, emergency, and continuity contacts. Contacts are owned by a client and may optionally be scoped to one payment plan. They are not co-clients and gain no liability, ownership, portal access, or financial disclosure rights.

Do not implement a fully versioned contact subsystem initially. Retain replaced or withdrawn contacts by ending their active period and linking to a replacement row. Allow corrections to the current row, with every before/after change recorded in append-only `audit_logs`. Clients may later manage only their own contacts through separately authorized client-portal workflows.

A continuity contact remains a candidate only. Any future transfer of obligations or rights requires verification, administrator/legal approval, appropriate documentation, and creation/linkage of a first-class client relationship.

The complete proposed identity schema, indexes, constraints, and retention rules are documented in `docs/identity-schema-proposal.md` and must be approved before migrations are created.

### Payment plans

Use a payment_plans table for each financed account. Fields should include:

* UUID or non-sequential public identifier.
* Internal payment-plan/account number.
* Plan title or description.
* Financed property or asset description.
* Original principal.
* Opening principal balance.
* Current cached principal balance, if used only as a rebuildable projection.
* Customary monthly payment amount.
* Monthly service fee amount.
* Monthly due day.
* First due date.
* Grace period in days, optional.
* Late fee configuration, optional or deferred.
* Balloon or maturity date, optional.
* Plan start date.
* Status.
* Notes.
* Created and updated timestamps.

Client ownership and primary/co-client roles must be stored only through `payment_plan_clients`; do not duplicate a primary client foreign key on `payment_plans`.

Payment-plan statuses should initially include:

* Draft.
* Active.
* Paused.
* Paid off.
* Defaulted.
* Closed.

Do not automatically mark a payment plan paid off solely because the cached principal reaches zero without verifying all outstanding fees.

### Minimal immutable financial transaction model

LandPay is a purchase payment-plan tracker, not a general ledger or accounting package. Do not create a chart of accounts, journal debits and credits, or full double-entry bookkeeping.

The immutable model tracks effects on only three business balances:

* `invoice_due`: positive deltas increase a specific invoice amount; negative deltas reduce it.
* `client_credit`: positive deltas create unapplied client credit; negative deltas apply or refund it.
* `purchase_balance`: positive deltas establish or increase the remaining financed purchase amount; negative deltas reduce it.

All money values use integer minor units. These effect categories do not have to balance against one another because they are business-state changes, not accounting accounts.

#### Transaction types

Use an enum-backed `financial_transactions.type`:

* `opening_purchase_balance`: establishes the original purchase balance.
* `invoice_charge`: adds a purchase-payment or one-time charge to an invoice without increasing an already established purchase balance.
* `recurring_fee`: adds a recurring service or administrative fee to an invoice without changing the purchase balance.
* `payment`: records money received and its allocations.
* `credit_application`: applies existing client credit.
* `adjustment`: applies an authorized correction to one or more balances and requires a reason.
* `reversal`: exactly negates one original posted transaction and references it.
* `refund`: returns unapplied credit or other refundable money without rewriting a prior payment.
* `write_off`: intentionally reduces an invoice charge, purchase balance, or both and requires authorization and a reason.

One payment transaction may allocate among fees, purchase balance, and client credit; separate transaction types are not needed for each allocation component.

#### Tables and relationships

##### `invoices`

* ID and UUID.
* Payment plan ID.
* Invoice number.
* Period dates, issue date, and due date.
* Status: draft, issued, partially paid, paid, voided, or closed. Financial status is derived from effects even if cached.
* Created and issued timestamps.

Invoices are presentation and collection containers, not the source of the remaining purchase balance.

##### `invoice_items`

* Invoice ID.
* Source financial transaction ID.
* Item type: purchase payment, recurring fee, late fee, administrative fee, or other approved charge.
* Description and original amount in integer minor units.
* Created timestamp.

Once issued and linked to a posted transaction, an invoice item is immutable. Corrections use adjustments, reversals, or write-offs.

##### `financial_transactions`

* ID and UUID.
* Payment plan ID.
* Invoice ID, nullable.
* Type and gross amount in integer minor units.
* Effective date and posted timestamp.
* Description.
* Required reason for adjustments, reversals, refunds, and write-offs.
* Posted-by administrator user ID, or authenticated client actor where later allowed.
* `reversal_of_transaction_id`, nullable and unique when present.
* Optional source reference and non-critical metadata.
* Created timestamp.

Posted transactions cannot be edited or deleted. Reversal state is derived from a transaction referencing the original; the original never receives a mutable reversed flag.

##### `transaction_effects`

* Financial transaction ID.
* Effect type: `invoice_due`, `client_credit`, or `purchase_balance`.
* Invoice ID, required for `invoice_due` and otherwise nullable.
* Signed `amount_delta` in integer minor units.
* Component: purchase payment, recurring fee, late fee, administrative fee, principal, unapplied credit, refund, write-off, or another approved component.
* Related invoice item ID or fee assessment ID, nullable.
* Description and created timestamp.

The posting service validates allowed effects for each transaction type. Cached balances must be rebuildable by summing these immutable effects.

##### `payments`

* Financial transaction ID, unique.
* Payer client ID, nullable when unknown.
* Received date, payment method, and optional external reference.
* Gross amount in integer minor units.
* Current invoice amount at posting time in integer minor units.
* Calculated overpayment amount in integer minor units, zero when there is no overpayment.
* Overpayment disposition, nullable unless overpayment exists: `principal` or `next_invoice_credit`.
* Decision source: client portal or administrator-recorded client instruction, nullable when there is no overpayment.
* Decision-selected timestamp, nullable when there is no overpayment.
* Administrator user ID that recorded the instruction, nullable.
* Created timestamp.

The payment and posted transaction are immutable. Reversal state is derived through the transaction relationship. When overpayment exists, the disposition and selection metadata are required and immutable.

##### `payment_allocations`

* Payment ID.
* Allocation type: invoice item, purchase balance, or client credit.
* Invoice ID and invoice item ID, nullable when not applicable.
* Related fee assessment ID, nullable.
* Amount in integer minor units.
* Created timestamp.

Allocations are immutable, must total the payment gross amount, and must correspond to transaction effects created atomically with the payment.

#### Required overpayment choice

`overpayment amount = payment gross amount - amount applied to the current invoice`

When this amount is greater than zero, the payment preview must show the exact overpayment and require the client to choose one option before confirmation. Neither option is preselected:

1. **Apply overpayment to principal.** Allocate 100% of the overpayment directly to the remaining purchase balance. Create a `purchase_balance` negative effect for the entire allowed overpayment and no `client_credit` effect.
2. **Carry forward to the next invoice.** Create a positive `client_credit` effect for 100% of the overpayment. When the next invoice is issued, automatically post a separate immutable `credit_application` transaction, up to the invoice amount, so the invoice shows the reduced amount owed. Apply the credit using the normal allocation priority: fees first, then the purchase-payment portion. Any credit exceeding the next invoice continues forward.

The interface must explain that principal application reduces the purchase balance immediately, while carry-forward credit reduces a future invoice and reduces purchase balance only when that credit is later applied to a purchase-payment item.

An administrator-entered payment must record that the administrator captured the client's instruction; administrators must not silently choose a disposition. If the requested principal application exceeds the remaining purchase balance, apply only the amount needed for payoff and require an explicit choice to carry the remainder as credit or refund it. Never allow purchase balance to become negative.

##### `recurring_fee_rules` and `fee_assessments`

`recurring_fee_rules` stores amount, frequency, effective dates, and active status. Each generated `fee_assessments` row stores:

* Payment plan ID and recurring fee rule ID.
* Stable period key.
* Effective date and amount in integer minor units.
* Financial transaction ID, unique.
* Created timestamp.

A unique plan/rule/period constraint makes assessment idempotent. Paid and reversal state are derived from allocations and financial transactions.

No primary `credits`, `credit_applications`, `adjustments`, `reversals`, `refunds`, or `write_offs` tables are created. Those are immutable transaction types.

#### Derived balances

* Current invoice amount: sum `amount_delta` for `invoice_due` effects on that invoice.
* Unapplied client credit: sum `amount_delta` for `client_credit` effects on the payment plan.
* Remaining purchase balance: sum `amount_delta` for `purchase_balance` effects on the payment plan.

Reject postings that would create negative client credit, reduce an invoice below zero without an approved adjustment/write-off workflow, or reduce purchase balance below zero without a reviewed payoff/overpayment workflow.

#### Example posting flows

##### 1. Open a $20,000 purchase balance

Post `opening_purchase_balance` with `purchase_balance +20,000`. Result: invoice $0; credit $0; purchase balance $20,000.

##### 2. Issue a $500 purchase-payment invoice plus a $25 recurring fee

Post `invoice_charge` with `invoice_due +500`; purchase balance is unchanged because this is part of the existing balance. Post `recurring_fee` with `invoice_due +25`; purchase balance is unchanged. Result: invoice $525; credit $0; purchase balance $20,000.

##### 3. Receive $500 against that invoice

Allocate $25 to the fee and $475 to principal. The `payment` effects are `invoice_due -500` and `purchase_balance -475`. Result: invoice $25; credit $0; purchase balance $19,525.

##### 4. Receive $650 when $525 is due and ask how to use the $125 overpayment

The preview first allocates $25 to the fee and $500 to the purchase-payment item, then identifies a $125 overpayment. The client must choose:

**A. Apply overpayment to principal**

Effects are `invoice_due -525` and `purchase_balance -625`. No client credit is created. Result: invoice $0; credit $0; purchase balance $19,375.

**B. Carry forward to the next invoice**

Effects are `invoice_due -525`, `purchase_balance -500`, and `client_credit +125`. Result: invoice $0; credit $125; purchase balance $19,500. When the next invoice is issued, LandPay posts a separate `credit_application` for up to $125, reducing that invoice according to the normal fee-then-purchase allocation priority.

##### 5. Apply $100 of credit to a purchase-payment invoice

Post `credit_application` with `client_credit -100`, `invoice_due -100`, and `purchase_balance -100`. Applying credit to a fee reduces credit and invoice due but does not change purchase balance.

##### 6. Add or remove a $20 administrative charge

Post `adjustment` with a required reason. Adding uses `invoice_due +20`; removing uses `invoice_due -20`. Neither changes purchase balance. A principal correction requires an explicit purchase-balance effect and reason.

##### 7. Reverse the $500 payment from example 3

Post `reversal` referencing the original payment. Copy each original effect with the exact opposite delta: `invoice_due +500` and `purchase_balance +475`. Preserve the original payment and allocations. A unique reversal reference prevents a second reversal.

##### 8. Refund $75 of unapplied client credit

Post `refund` with `client_credit -75`. Invoice and purchase balance remain unchanged. Refunding an applied payment first requires an explicit reversal or adjustment; a refund cannot silently undo prior allocations.

##### 9. Write off $300 of purchase balance

Post `write_off` with required authorization and reason using `purchase_balance -300`. If $100 is also currently invoiced, include `invoice_due -100`. All prior records remain unchanged.

Every posting creates its transaction, effects, tender/allocation/supporting records, and audit event atomically in one database transaction.

### Audit log

Track non-financial administrative actions, including:

* Client created or updated.
* Loan created or updated.
* Payment posted.
* Payment reversed.
* Adjustment posted.
* User login.
* Settings changed.

Financial details should remain primarily in the immutable ledger, while the audit log records who performed the surrounding action.

## Account-status calculation

Do not create future installment rows.

Create a service that calculates an account’s current servicing status using:

* Loan start date.
* First due date.
* Monthly due day.
* Customary monthly payment.
* Payments received.
* Payment type.
* Monthly fee assessments.
* Pauses or adjustments.
* Current date.

Return a structured result such as:

* Current expected period.
* Current amount expected.
* Amount received toward expected regular payments.
* Current unpaid expected amount.
* Outstanding fees.
* Days overdue.
* Next due date.
* Last payment date.
* Last payment amount.
* Status label.

Suggested status labels:

* Current.
* Due soon.
* Due today.
* Partially paid.
* Overdue.
* Paused.
* Paid off.
* Closed.

Clearly document the calculation assumptions.

If requirements are ambiguous, implement the simplest well-tested rule and isolate it in a service class so it can be revised later.

Do not bury account-status rules inside controllers or Blade templates.

## Initial user interface

Build a clean Bootstrap 5 administrative interface.

Required screens:

### Login

Use Laravel’s secure authentication scaffolding or a minimal native Laravel authentication implementation.

Do not allow public registration.

The first administrator should be created by seeder, command, or documented setup process.

### Dashboard

Display summary cards for:

* Active loans.
* Total outstanding principal.
* Total outstanding fees.
* Payments received this month.
* Loans currently overdue.
* Loans due within the next seven days.

Display a recent-payments table.

Display an overdue-loans table.

### Client list

Include:

* Search.
* Client name.
* Email.
* Phone.
* Number of active loans.
* Current combined principal.
* Status.
* View action.

### Client detail

Include:

* Contact information.
* Notes.
* Associated loans.
* Recent payments.
* Communication placeholder section for future email and SMS history.

### Create and edit client

Use Laravel form requests for validation.

### Loan list

Include:

* Loan number.
* Client.
* Loan title.
* Current principal.
* Monthly expected payment.
* Monthly fee.
* Next due date.
* Account status.
* View action.

### Loan detail

Include clear summary cards for:

* Original principal.
* Current principal.
* Monthly expected payment.
* Monthly fee.
* Outstanding fees.
* Current expected amount.
* Next due date.
* Days overdue.
* Total principal paid.

Include tabs or sections for:

* Overview.
* Payments.
* Ledger.
* Fees.
* Clients.
* Notes.
* Documents placeholder.

Include prominent actions:

* Record payment.
* Record principal-only payment.
* Assess fee manually.
* Create adjustment.
* Reverse eligible transaction.

### Record payment

The payment form must:

* Default to the current date but allow a different payment date.
* Accept any positive amount.
* Allow regular or principal-only payment type.
* Allow payment method.
* Allow reference number.
* Allow notes.
* Show current principal and outstanding fees.
* Generate an allocation preview.
* Require confirmation before posting.
* Use a database transaction when posting.
* Prevent double submission.
* Return a clear success or error message.
* Show the newly created ledger transaction after posting.

### Payment detail

Display:

* Gross amount.
* Payment date.
* Payment method.
* Reference.
* Payment type.
* Allocation breakdown.
* Principal before payment.
* Principal after payment.
* Related ledger transaction.
* Posted by.
* Reversal state derived from the linked ledger reversal transaction.
* Reverse-payment action when eligible.

### Ledger view

Display a chronological ledger table containing:

* Effective date.
* Posted date.
* Transaction type.
* Description.
* Amount or financial effect.
* Principal effect.
* Fee effect.
* Running principal balance if calculated safely.
* Source.
* Posted by.
* Reversal state derived from the linked ledger reversal transaction.
* View action.

Visually distinguish reversed transactions without hiding them.

## Security requirements

Implement:

* CSRF protection.
* Server-side validation.
* Authorization policies.
* Secure password hashing.
* Session security.
* Rate limiting on login.
* Protection against mass-assignment vulnerabilities.
* Escaped output in Blade.
* Database transactions for all financial postings.
* Idempotency protection for fee assessments and future webhook-ready transaction creation.
* Non-sequential public identifiers where records may later appear in client-facing URLs.
* No secrets committed to Git.
* Example environment configuration only.
* Clear setup instructions for HTTPS in production.

Do not store card numbers, bank account numbers, or other sensitive payment credentials.

Future payment-provider integrations must use processor tokens and hosted or processor-approved payment collection.

## Service-layer requirements

Financial logic must live in dedicated services, not controllers.

Create services such as:

* PaymentAllocationService.
* PaymentPostingService.
* LedgerService.
* TransactionReversalService.
* FeeAssessmentService.
* LoanBalanceService.
* AccountStatusService.
* LedgerReconciliationService.

Controllers should coordinate requests and responses but should not contain core accounting calculations.

Use database transactions and row locking where appropriate to prevent two simultaneous payments from corrupting balances.

## Testing requirements

Automated tests are mandatory for the financial logic.

Create tests covering at least:

1. Opening principal ledger transaction.
2. Regular payment with one monthly fee.
3. Regular payment with no outstanding fees.
4. Overpayment where all excess goes to principal.
5. Partial payment.
6. Multiple payments during the same month.
7. Principal-only payment.
8. Payment greater than remaining principal.
9. Payment that pays principal to zero.
10. Outstanding fee remaining after principal reaches zero.
11. Reversal of a regular payment.
12. Reversal restores principal correctly.
13. Reversal restores fee balances correctly.
14. Prevention of duplicate reversal.
15. Prevention of editing posted payment financial fields.
16. Duplicate monthly fee assessment prevention.
17. Ledger-to-cached-balance reconciliation.
18. Backdated payment behavior.
19. Transaction rollback if any allocation or ledger entry fails.
20. Authorization preventing unauthorized users from posting or reversing payments.

Use integer minor units for money wherever practical, such as cents stored as integers.

Do not use floating-point arithmetic for financial values.

Include model factories and seeders sufficient to demonstrate the application.

## Documentation requirements

Create or update a README containing:

* Application purpose.
* Technology requirements.
* Local installation instructions.
* Database setup.
* Environment variables.
* Migration instructions.
* Initial administrator creation.
* How to run tests.
* How to run the development server.
* How to build frontend assets, if applicable.
* How financial allocation works.
* Ledger sign convention.
* How reversals work.
* Why posted transactions cannot be edited.
* How monthly fees are assessed.
* How to run the ledger reconciliation command.
* Features intentionally deferred.

Also create a short architecture document under `docs/architecture.md`.

## Deferred features

Do not implement these during the initial foundation unless specifically instructed later:

* Client portal.
* Public client registration.
* Online payment checkout.
* Stripe integration.
* Square integration.
* ACH integration.
* Payment-provider webhooks.
* Automated email reminders.
* Groq AI.
* Twilio SMS.
* Email inbox scanning.
* n8n workflows.
* Zelle API integration.
* Venmo API integration.
* Cash App API integration.
* Bank-feed imports.
* Automatic matching of external payments.
* Document uploads.
* PDF statements.
* Multi-lender or multi-tenant support.
* Subscription billing.
* Complex interest amortization.
* Escrow accounting.
* Tax reporting.
* Credit bureau reporting.

Leave clean interfaces and extension points for future Stripe or Square integrations, but do not create fake implementations.

## Revised implementation sequence

No migrations or domain code should be generated until this schema is reviewed and approved.

1. Approve the transaction types, three effect categories, component labels, posting rules, and example balance behavior.
2. Approve the relational schema and its foreign-key, uniqueness, temporal-history, privacy, and immutability constraints.
3. Create the initial Git baseline commit for the approved Laravel and design foundation.
4. Add secure administrator authentication with public registration disabled.
5. Review and approve the documented identity, plan-ownership, contact, and audit schema before generating migrations:

   * users
   * clients
   * payment_plans
   * payment_plan_clients
   * client_contacts
   * audit_logs

6. After schema approval, generate the identity migrations and add authorization policies for administrator access and future client-owned contact updates.
7. Generate the minimal immutable financial model:

   * invoices
   * invoice_items
   * financial_transactions
   * transaction_effects
   * payments
   * payment_allocations
   * recurring_fee_rules
   * fee_assessments
   * audit_logs

8. Add enums for financial transaction types, effect types, effect components, payment allocation types, payment methods, client roles, contact designations, permission scopes, and plan statuses.
9. Add database and service protections: integer-money checks, allowed-effect validation by transaction type, allocation-total validation, idempotent fee-period uniqueness, unique reversal references, append-only posted records, and atomic posting.
10. Create factories and test helpers without generating production financial history or real contact data.
11. Implement administrator client CRUD, co-client assignment, and contact-history review.
12. Implement payment-plan CRUD and activation prerequisites.
13. Implement and test opening purchase-balance posting.
14. Implement and test invoice charges and idempotent recurring fees.
15. Implement and test payments, required overpayment previews, client-selected principal application, and carry-forward credit creation.
16. Implement and test principal-only payments.
17. Implement and test idempotent automatic client-credit application when the next invoice is issued.
18. Implement and test adjustments with required reasons and authorization.
19. Implement and test exact-effect reversals referencing original transactions.
20. Implement and test refunds and write-offs.
21. Implement rebuildable invoice, client-credit, purchase-balance, paid-in-value, and account-status calculations.
22. Build dashboard, client, payment-plan, invoice, payment, allocation-preview, transaction-history, and audit views.
23. Implement secure client authentication and portal read access.
24. Implement client-owned general contact management with retained replaced rows and complete audit-log tests.
25. Implement an administrator-only continuity workflow for loss of contact without automatic disclosure or obligation transfer.
26. Run migrations only after explicit schema approval, then run all financial posting, reversal, authorization, temporal-history, and rebuild tests.
27. Document cPanel environment, database, queue, scheduler, mail, backup, privacy, retention, and deployment requirements before production migration.

## Coding standards

Use:

* Strict typing where practical.
* Laravel conventions.
* PHP type declarations.
* Enums for stable transaction, payment, allocation, loan-status, and payment-method values where appropriate.
* Form request classes for validation.
* Policies for authorization.
* Service classes for domain logic.
* Database constraints in addition to application validation.
* Descriptive names.
* Small, testable methods.
* Comments explaining non-obvious financial logic.
* PHPDoc only where it adds value.

Avoid:

* Giant controllers.
* Financial calculations in views.
* Raw SQL unless justified.
* Duplicate business logic.
* Magic numbers.
* Floating-point money calculations.
* Silent error handling.
* Deleting posted financial records.
* Editing posted financial amounts.
* Premature complexity.
* Multi-tenancy.
* Unnecessary APIs.
* Unnecessary frontend frameworks.

## Working process

Work in controlled stages.

Before implementing, provide:

1. A brief summary of the existing project state.
2. The proposed database and service structure.
3. Any assumptions you must make.

Then begin implementation.

After each major stage:

* Run relevant tests.
* Fix failures before proceeding.
* Summarize what changed.
* Do not claim a feature works unless its tests pass or it has been manually verified.

Do not ask me to approve every individual file.

Make reasonable decisions that follow the requirements above.

If a requirement conflicts with sound ledger or security design, explain the conflict and choose the safer implementation.

At the end, provide:

* Implementation summary.
* Database schema summary.
* Routes and screens added.
* Test results.
* Setup commands.
* Default administrator setup procedure.
* Known limitations.
* Recommended next milestone.

Begin by inspecting the current project directory. Do not write code until you have reported what is already present and stated your proposed first implementation steps.
