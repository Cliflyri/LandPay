# Current Payoff and Monthly Fee Tracking

## Goal

Display an accurate **Current Payoff** amount in the client portal without changing the stored contract balance, financial ledger, invoice balances, payment limits, or any other global financial value.

The displayed payoff should include the current billing month's remaining base fee when that fee has not been fully collected.

This work also corrects the admin payment page so it reports fees applied to the selected billing month rather than fees received during that calendar month.

## Business Rules

### Current Payoff

The client-facing value is calculated for display only:

```text
Current Payoff = Contract Balance + Remaining Current-Month Base Fee
```

```text
Remaining Current-Month Base Fee =
    Current-Month Fee Assessed - Valid Allocations Applied to That Fee
```

The remaining fee must never be less than zero.

### Billing-Month Attribution

A fee payment belongs to the billing month of the invoice containing the fee item. The payment's received date does not determine the fee's billing month.

Use these invoice fields to identify the applicable billing period:

- `invoices.period_start`
- `invoices.period_end`

Count allocations only when:

- The invoice belongs to the applicable payment plan.
- The invoice period contains the selected month/date.
- The allocated invoice item is a monthly service or administrative fee.
- The associated payment has not been reversed.

This produces the following behavior:

- An August payment applied to a July fee does not satisfy the August fee.
- A July payment applied early to the August fee does satisfy the August fee.
- A partial payment against the August fee reduces only the remaining August fee.
- A reversed payment no longer counts toward the fee.
- A waived fee uses the actual assessed invoice-item amount after the waiver.

### No Current-Month Invoice

If no monthly invoice exists for the current billing period, use the active billing term's monthly service fee as the display adjustment.

This adjustment remains informational. It must not create an invoice, transaction, assessment, allocation, or ledger effect.

### Manual Invoices

Manual invoices currently have null `period_start` and `period_end` values. A manually added fee therefore cannot be reliably attributed to a billing month.

For this calculation, count only fees attached to invoices with a defined billing period. Do not infer a billing month from a manual invoice's issue date.

## Admin Payment Page Correction

The existing monthly fee summary currently filters allocations by `payments.received_date`. That answers how much fee money was received during a calendar month, but it does not reliably answer whether that billing month's fee was collected.

Update the shared fee-history logic to report:

- Billing month
- Fee assessed for that billing month
- Amount allocated to that fee
- Remaining fee
- Relevant payment and invoice allocation details

The payment page's selected received date may determine which billing month is displayed, but allocations must be selected using the invoice billing period.

Suggested wording:

```text
August fee assessed: $25.00
Applied to August fee: $10.00
August fee remaining: $15.00
```

## Client Portal Display

In the client account contract table:

- Rename **Contract balance** to **Current Payoff**.
- Display contract balance plus the remaining current-month base fee.
- Consider including a short explanation that Current Payoff includes any remaining base fee for the current billing month.
- Do not expose internal allocation or ledger terminology to the customer.

## Expected Files

### Primary implementation

- `app/Services/MonthlyServiceFeeHistoryService.php`
  - Change fee attribution from payment received date to invoice billing period.
  - Return assessed, applied, and remaining fee amounts.

- `app/Http/Controllers/Admin/PaymentController.php`
  - Continue selecting the relevant month from the payment date.
  - Pass the corrected billing-month summary to the view.

- `resources/views/admin/shared/monthly-service-fees-collected.blade.php`
  - Update labels and details to describe the selected billing month's fee status.

- `app/Http/Controllers/Portal/AccountController.php`
  - Calculate a separate display-only `current_payoff` for each active plan.
  - Do not alter the value returned by `FinancialBalanceService::contractBalance()`.

- `resources/views/portal/account/show.blade.php`
  - Rename the column and display the calculated Current Payoff value.

### Tests

- `tests/Feature/Portal/ClientPortalTest.php`
- The existing admin payment and financial test files covering monthly service-fee history and allocation.

## Required Test Scenarios

1. No current-month fee has been paid: add the full assessed fee to Current Payoff.
2. Current-month fee is fully paid: add nothing.
3. Current-month fee is partially paid: add only the unpaid remainder.
4. An older fee is paid during the current month: do not treat the current fee as paid.
5. The current fee is paid early: treat it as paid based on its invoice period.
6. The fee payment is reversed: restore the fee to the remaining amount.
7. The current-month fee is partially or fully waived: use the actual assessed amount.
8. The configured monthly fee is zero: Current Payoff equals contract balance.
9. No current-month invoice exists: use the active billing term's fee for display only.
10. A manual invoice has no billing period: do not infer that it represents the current-month fee.

## Out of Scope

Do not change:

- The financial ledger or transaction effects
- `FinancialBalanceService::contractBalance()`
- Stored contract balances
- Invoice balances
- Payment allocation priority
- Principal-payment validation
- Automatic invoice generation
- Payment receipt balances
- Admin dashboard contract-balance values

## Acceptance Criteria

- The client portal shows **Current Payoff** instead of **Contract balance** in the account section.
- Current Payoff includes only the unpaid portion of the applicable current billing month's base fee.
- Payments are attributed to a fee using the invoice billing period, not the payment received month.
- The admin payment page and client portal use the same billing-month attribution rule.
- Reversals and waivers are handled correctly.
- No persistent financial value or global balance calculation changes.
- Automated tests cover all required scenarios.
