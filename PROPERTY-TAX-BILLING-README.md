# Property Tax Billing

## Purpose

LandPay should provide a simple annual workflow for invoicing clients for property taxes associated with their payment plans. The workflow should be fast for the administrator while retaining structured records, audit history, duplicate protection, and normal LandPay payment functionality.

## Recommended Approach

Use a hybrid workflow:

- bulk input by pasted text or CSV for the annual tax list;
- structured database records after import;
- automatic matching to payment plans by APN;
- a review screen before anything financial is posted; and
- per-plan correction and manual entry for exceptions.

A flat file or settings textarea may be used as the input mechanism, but it should not be the permanent record.

## Administrator Workflow

1. Open **Property tax billing** in the administrator area.
2. Select:
   - tax year;
   - invoice date;
   - due date;
   - whether invoices should be emailed immediately.
3. Paste or upload records in a simple format:

   ```text
   APN | Amount
   123-45-678 | 84.27
   123-45-679 | 112.06
   ```

4. LandPay validates and matches each APN to a payment plan.
5. Review a summary showing:
   - matched plans;
   - client and plan;
   - original and normalized APN;
   - imported amount;
   - existing property-tax invoice, if any;
   - unmatched APNs;
   - duplicate APNs;
   - plans with missing APNs;
   - invalid or zero amounts; and
   - total amount to be invoiced.
6. Correct or exclude individual rows as needed.
7. Confirm issuance.
8. LandPay creates one property-tax invoice for each approved plan.
9. If selected, LandPay emails each invoice using the existing invoice email and secure magic invoice link.

Nothing financial should be recorded until the administrator confirms the reviewed batch.

## Matching Rules

- APN is the primary matching key.
- Preserve the original APN for display and audit purposes.
- Use a normalized APN for matching, accounting for harmless spacing and hyphen differences.
- Never guess when a normalized APN matches more than one plan.
- Do not silently match an APN to a plan number.
- Unmatched and ambiguous records must remain unresolved until reviewed by the administrator.

## Invoice Treatment

Create a separate invoice for each plan with a line such as:

```text
2026 Property Tax    $84.27
```

The property-tax amount:

- is an invoice obligation;
- is not principal;
- does not change the contract balance;
- is not a monthly service fee;
- can be partially or fully paid through normal LandPay payment methods;
- appears on receipts and reports; and
- uses existing invoice email, magic-link, reminder, credit, payment-allocation, and voiding behavior.

A dedicated internal `property_tax` invoice-item/component type is preferred for reporting and reliable accounting. A generic fee should only be used for an intentionally lean first version if the tax year and source remain explicitly recorded.

## Duplicate Protection

LandPay should prevent more than one active property-tax invoice for the same:

- payment plan; and
- tax year.

Voided invoices should remain in history. Reissuing after a void should require an explicit administrator action and retain the relationship to the earlier invoice.

Submitting or refreshing the same import must not create duplicate invoices.

## Stored Batch Record

Each annual import should retain:

- tax year;
- import timestamp;
- invoice and due dates;
- original input;
- original APN;
- normalized APN;
- imported amount;
- matched payment plan;
- resolution status;
- created invoice;
- administrator who imported and confirmed it; and
- any correction or exclusion note.

Suggested batch statuses include `draft`, `reviewed`, `issued`, and `partially_issued`.

Suggested row statuses include `matched`, `unmatched`, `ambiguous`, `invalid`, `excluded`, and `issued`.

## Per-Plan Entry

The plan page may provide a small property-tax action for exceptions:

- tax year;
- amount;
- invoice date;
- due date;
- description; and
- issue/email controls.

This should use the same validation, duplicate protection, invoice type, and audit records as the bulk workflow.

## Settings

Application settings should contain defaults only:

- default invoice description;
- default due date or days until due;
- default email-immediately choice;
- whether normal invoice reminders apply; and
- optional default client wording.

Annual APNs, amounts, matches, and issuance results should not be stored as general application settings.

## Safety and Usability

- Preview all matches and totals before issuance.
- Require explicit confirmation before posting invoices.
- Display unmatched and ambiguous records prominently.
- Validate amounts as positive currency values.
- Use database transactions for batch issuance.
- Continue safely if one row fails and clearly identify partial completion, or roll back the entire batch; the selected behavior must be explicit.
- Record administrator actions in the audit history.
- Permit export of the reviewed batch and results for reconciliation.
- Keep the workflow usable on mobile, while optimizing the bulk review table for desktop.
- Require a verified database backup before the first production batch.

## Lean Implementation Sequence

1. Add property-tax batch and row tables.
2. Add a dedicated property-tax invoice item/effect component.
3. Build paste/CSV parsing and APN normalization.
4. Build the read-only match and validation preview.
5. Add corrections, exclusions, and duplicate detection.
6. Add confirmed transactional invoice issuance.
7. Reuse existing invoice emails, secure links, reminders, receipts, payments, credits, and voiding.
8. Add per-plan exception entry.
9. Add batch history, reconciliation export, and focused tests.

## Acceptance Criteria

- Admin can paste an annual APN/amount list and see matches before posting.
- Unmatched, ambiguous, invalid, and duplicate rows cannot be silently issued.
- Confirmed rows create clearly labeled property-tax invoices.
- Property taxes never affect contract principal or monthly service-fee satisfaction.
- Duplicate active invoices for a plan and tax year are prevented.
- Clients can view and pay through every currently enabled payment method.
- Invoice emails and reminders use the secure magic invoice link.
- Admin can review the original import, corrections, invoices, payments, and unresolved rows later.

