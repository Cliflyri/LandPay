# Accounting Reconciliation Work Plan

This plan tracks the accounting review and reconciliation work for LandPay. Update this same file as each numbered step is completed by adding `DONE` after the number.

## Recommended sequence

1 DONE - Establish safe NAS-side test execution over SSH.
2 DONE - Create a dedicated NAS test database so existing NAS test fixtures are preserved.
3 DONE - Audit and consolidate duplicated accounting routines.
4 DONE - Build invariant-focused accounting tests, including late fees and credits.
5 DONE - Add Credit available to the admin dashboard.
6 DONE - Build the plan Account ledger tab from the reconciled transaction model.
7 - Use that ledger to investigate and correct any remaining historical test records.

## Step 3 result

- Account-credit allocation now has one authoritative implementation in `AccountCreditApplicationService`.
- Monthly invoices, manual invoices, and the administrator action use that shared routine.
- Duplicate credit-allocation methods were removed from the invoice services.
- Late-fee assessment was confirmed to already use one dedicated `LateFeeAssessmentService`.
- PHP syntax checks pass. Automated tests await step 4 because NAS deploy dependencies do not currently include PHPUnit or Pest.

## Step 4 result

- PHPUnit is configured to use only `landpay_testing`.
- Foundational checks cover account-credit application, manual-invoice credit, late-fee assessment/idempotency, fee-first payment allocation, and payment reversal.
- Focused NAS MySQL result: 5 tests passed with 72 assertions.
- No broad suite, coverage scan, or parallel test databases were run.

## Step 5 result

- The admin dashboard now shows a dedicated `Credit available` column immediately after `Current Due`.
- Positive plan credit is shown as money; zero credit is shown as a dash.
- Focused result: 1 dashboard test passed with 20 assertions.

## Step 6 result

- Added a read-only Account ledger tab to each admin plan.
- The compact statement emphasizes payment, fee, principal applied, and contract balance after each entry; payments without invoices leave the invoice cell blank.
- Current payoff and unused credit appear only in the summary.
- Invoice and payment references link to their existing admin detail pages, and reversals remain visible.
- The ledger includes every post-opening transaction affecting payments, paid fees, principal, or customer credit.
- Credit creation and later credit applications appear chronologically in a dedicated Credit change column.
- The ledger footer repeats payment, fee, principal, credit, and ending contract-balance totals for quick reconciliation.

## Safety boundaries

- Automated tests must use the dedicated `landpay_testing` database, never the current NAS `landpay` database.
- Database connection overrides should be supplied only when running tests; do not change the application `.env` or deployable configuration.
- Do not commit credentials or copy NAS-only database settings into the cPanel codebase.
- Commands that rebuild or truncate tables must verify the active database name is exactly `landpay_testing` first.

## Test execution

- NAS project: `/media/F-4tb-WD-Red-NAS/Nas Web Root/LandPay`
- NAS PHP CLI: PHP 8.4.24
- SSH user: `landpaydev`
- Test database: `landpay_testing`
- `phpunit.xml` is fixed to MySQL database `landpay_testing`; application runtime configuration remains unchanged.

