# Previous Principal Payment Adjustment Option

## Purpose

Add an optional amount to a payment plan for clients who entered LandPay with an existing contract and had already paid down part of the purchase principal before the plan was created in LandPay.

This amount should reduce the plan's opening contract balance and count toward the administrator-visible paid-in value. It is historical principal, not a new payment collected through LandPay, an invoice payment, a documentation-fee payment, or client credit.

## Create Plan Form

Place the option inside section 3, **Opening contract balance**, directly below the existing initial contract balance calculation.

Add a small subsection with the heading:

> Principal previously paid in

Supporting explanation:

> Optional. Enter any principal paid before creating this plan in LandPay.

### Layout

- Left side: a currency input named `previous_principal_paid`, defaulting to `0.00`.
- Right side: a smaller green calculation bar labeled **Adjusted initial contract balance**.
- On narrow screens, the calculation bar may stack below the input.

The green calculation bar should update immediately when the purchase price, documentation fee, waived documentation fee, or previous-principal amount changes.

### Calculation

```text
Documentation fee charged
    = documentation fee - documentation fee waived

Contract balance before prior payments
    = purchase price + documentation fee charged

Adjusted initial contract balance
    = contract balance before prior payments - principal previously paid in
```

When the previous-principal amount is zero, the adjusted balance will equal the same balance before prior payments.

## Validation

- The field is optional and should be treated as zero when blank.
- Accept a currency value with no more than two decimal places.
- The amount must be zero or greater.
- The amount must not exceed the purchase price.
- It reduces purchase-price principal only; it does not reduce the documentation-fee portion of the balance.
- The resulting contract balance must never be negative.
- Validation and calculations must use integer cents on the server.

## Financial Recording

Do not implement this solely as a mutable value that is subtracted from balances throughout the application. Record it through the financial ledger so the contract balance remains reproducible from transaction effects.

When a plan is created:

1. Create the normal opening purchase and documentation-fee balance.
2. If `previous_principal_paid` is greater than zero, post a separate opening-principal-credit transaction effective on the contract start date.
3. Give the transaction a negative purchase-balance effect for the entered amount.
4. Attribute the effect to a distinct opening-principal-credit component.
5. Use an idempotency key tied to the plan so a repeated request cannot post the opening credit twice.
6. Record the administrator as the actor and use a description such as `Principal previously paid before LandPay`.

The existing `OpeningPrincipalCreditService` provides a starting point for this behavior. Its referenced transaction and component enum values must exist, and the create-plan workflow must call the service after establishing the opening contract balance.  Review this service for suitability, and keep as lean as practical.

## Paid-In Value

The previous-principal amount should count toward the administrator-visible paid-in value because it represents client-funded purchase-price principal paid before LandPay began tracking the contract.

Update the paid-in-value calculation to include net opening-principal-credit effects, including any later corrections. Continue excluding documentation principal, service fees, late fees, administrative fees, write-offs, and non-client-funded balance adjustments.

Do not create a LandPay `Payment` record for this amount. Doing so would incorrectly imply that LandPay received and processed the historical payment and could distort payment reports and receipts.

## Plan Details

On the administrator plan details page, show:

- Purchase price
- Documentation fee charged
- Principal paid before LandPay
- Opening contract balance after prior principal
- Current contract balance
- Paid-in value

The previous-principal amount remains administrator-only unless a separate decision is made to expose it in the client portal or client documents.

## Edit Plan Form

Add the same **Principal previously paid in** currency field and explanation to the contract-amount area of the Edit Plan form.

Populate the field from the net opening-principal-credit ledger activity, rather than relying on a separate mutable plan column.

Editing the amount must preserve the financial audit trail:

- Increasing the value posts an additional negative purchase-balance effect for the difference.
- Decreasing the value posts a positive purchase-balance restoration for the difference.
- No transaction should be posted when the amount is unchanged.
- Use the amendment effective date and required amendment reason already collected by the Edit Plan form.
- Record the administrator who made the change.
- Do not rewrite or delete the original opening transaction.

Example:

```text
Recorded previous principal: $5,000
Edited value:               $5,500
New ledger effect:            -$500 purchase balance

Recorded previous principal: $5,000
Edited value:               $4,500
New ledger effect:            +$500 purchase balance
```

An edit must still satisfy the validation rules, including the rule that net previous principal cannot exceed the applicable purchase price.

## Suggested Implementation Areas

The addition is expected to touch these areas:

- Create Plan Blade view and its live calculation script
- Edit Plan Blade view
- `PaymentPlanController` create and update validation/workflows
- Opening-principal-credit transaction and effect enums
- `OpeningPrincipalCreditService`, plus differential correction behavior for edits
- `FinancialBalanceService::administratorPaidInValue()`
- Administrator plan details view
- Feature and service tests

A new database column is not required if the displayed amount is derived from the ledger. If a cached projection is added later for reporting performance, it should remain rebuildable from ledger activity and must not become the financial source of truth.

## Acceptance Criteria

1. The optional field appears in Create Plan section 3 beneath the opening-balance calculation.
2. The field is on the left and a smaller green adjusted-balance bar is on the right.
3. The adjusted balance updates immediately as relevant amounts change.
4. A blank or zero value produces the same opening balance as today.
5. A positive value reduces purchase-price principal and the opening contract balance by the exact amount.
6. The value counts toward administrator-visible paid-in value without appearing as a payment received by LandPay.
7. The value is visible and editable in the Edit Plan form.
8. Edits create differential, auditable ledger entries rather than rewriting financial history.
9. The amount cannot be negative, exceed purchase price, or create a negative contract balance.
10. Duplicate form submission cannot post the opening credit twice.

## Minimum Tests

- Create a plan with the field blank.
- Create a plan with the field set to zero.
- Create a plan with a valid previous-principal amount.
- Confirm the adjusted-balance preview and server-calculated balance agree.
- Reject negative values, excess precision, and an amount greater than purchase price.
- Confirm the opening credit reduces contract balance but creates no `Payment` record.
- Confirm the opening credit is included in administrator paid-in value.
- Increase the value through Edit Plan and verify the differential effect.
- Decrease the value through Edit Plan and verify the balance restoration.
- Submit an unchanged value and verify that no financial transaction is added.
- Verify idempotency prevents a duplicated opening credit.
- Verify amendment actor, effective date, and reason are retained in the audit trail.
