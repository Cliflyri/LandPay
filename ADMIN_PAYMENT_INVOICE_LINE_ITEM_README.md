# Add an Invoice Line Item While Recording a Payment

## Status

Future enhancement. This document records the agreed behavior for later implementation. No application behavior is changed by this document.

## Purpose

Allow an administrator to add one line item to an existing invoice directly from the **Record payment** page before posting the payment. This avoids leaving the page, editing and saving the invoice, and then returning to finish the payment.

The added item becomes a normal line item on the selected original invoice. It is not payment metadata, a receipt-only adjustment, or an override of the payment amount.

## Form Placement and Fields

Add an optional section after the existing **Payment details** fields and before the overpayment controls. It may initially be hidden behind this checkbox:

> Add a line item to an invoice before posting

When selected, show four fields in this order:

1. **Invoice**
2. **Type**
3. **Description**
4. **Amount**

Suggested desktop layout:

```text
Invoice                              Type
[ INV-1024 - $500.00 open       v ]  [ Fee                         v ]

Description                          Amount
[ Late payment fee                 ] [ $25.00                        ]
```

On smaller screens, stack the fields in the same order.

## Invoice Field

Record payment operates at the payment-plan level and can allocate one payment across multiple open invoices. The administrator must therefore identify which invoice receives the item.

- With one eligible open invoice, display it as the fixed target.
- With multiple eligible open invoices, provide a required dropdown.
- The first invoice in normal allocation order may be the default, but the target must remain obvious.
- With no eligible open invoice, do not allow the option.
- Do not infer the target from the payment amount.

Choices should show the invoice number, due date, and current balance.

## Type Field

Use the same stored types and labels as **Edit invoice**, but expose only types appropriate for a manually added charge during payment entry:

- **Documentation fee** (`documentation_fee`)
- **Monthly service fee** (`monthly_service_fee`)
- **Fee** (`administrative_fee`)
- **Other / adjustment** (`other`)

Default to **Fee**.

Do not expose or accept these types here:

- **Plan payment** (`scheduled_purchase_payment`)
- **Stage-one late fee** (`late_fee_stage_1`)
- **Stage-two late fee** (`late_fee_stage_2`)

Plan-payment items affect principal behavior. Stage-one and stage-two late fees belong to the automated delinquency process.

## Description and Amount

- Description is required when the option is enabled.
- Use the same 500-character limit as invoice editing.
- Amount is required when the option is enabled.
- Apply the existing invoice-edit validation rules for the selected type.
- The amount should normally be greater than zero.
- If **Other / adjustment** retains zero or negative adjustment support, use the existing invoice-edit rules rather than creating different payment-page behavior.

## Relationship to Existing Payment Fields

The new line-item amount is distinct from both existing amount fields:

- **Amount** is the money actually received.
- **Service fee from this payment** is a non-principal allocation from the received payment for the indicated billing month.
- **Line-item amount** changes the selected invoice itself.

Adding a line item must not silently increase the payment amount. For example:

- Existing invoice balance: $500.00
- New invoice line item: $25.00
- Amount received: $500.00
- Remaining invoice balance after posting: $25.00

If $525.00 was received, the administrator enters $525.00 as the payment amount. The option should be available only for a regular payment, not a principal-only payment.

## Preview

Selecting **Preview allocation** must not save anything. Show the proposed invoice change before the normal allocation preview:

```text
Invoice change
INV-1024

Current invoice balance              $500.00
Fee: Late payment fee                  $25.00
Revised invoice balance              $525.00
```

Then allocate the entered payment against the proposed revised balances. The Contract summary's **Open invoices** total should reflect the proposed item. Overpayment detection must use the proposed revised amount currently payable, so the new charge is not mistaken for an overpayment or principal payment.

## Posting Behavior

When the administrator selects **Confirm and post payment** or **Post and email receipt**, perform the following as one database transaction:

1. Lock and revalidate the selected invoice and payment plan.
2. Confirm that the invoice is still eligible to receive the item.
3. Add the item with the selected type, description, and amount.
4. Recalculate the invoice and plan-level open balance.
5. Allocate and post the payment against the revised balances.
6. Generate or email the normal receipt when requested.

Either the invoice item and payment must both be posted, or neither must be posted. Existing payment idempotency protection must cover the combined operation so a retry cannot duplicate the invoice item.

## Resulting Invoice and Receipt

After posting, the entry is an ordinary line item on the original invoice. Invoice screens, PDFs, and receipt details should use the normal presentation:

```text
Plan payment                         $500.00
Late payment fee                      $25.00
                                    -------
Invoice total                        $525.00
```

No customer-facing "payment adjustment" label is needed. Internally, normal audit and financial records should identify the administrator, time, source workflow, selected invoice, and resulting item.

## Validation and Concurrency

- All four fields are required when the option is enabled.
- Reject partial or ambiguous line-item input rather than silently ignoring it.
- The invoice must belong to the payment plan being paid.
- A voided, replaced, or otherwise non-editable invoice cannot receive the item.
- Re-run allocation using current locked balances when posting; do not trust previewed totals.
- If the invoice changes after preview and the action cannot safely post, show a clear error and require a refreshed preview.
- Apply the same item-type accounting and validation rules used by invoice editing.

## Suggested Tests

- No eligible invoices disables the option.
- One eligible invoice is shown as the fixed target.
- Multiple eligible invoices produce the correct selector.
- Only Documentation fee, Monthly service fee, Fee, and Other / adjustment are offered and accepted.
- Plan payment and automated late-fee types are rejected server-side.
- Preview changes no records and includes the proposed item in all balance calculations.
- Posting creates one invoice item and one payment in the same transaction.
- A failed payment post leaves no new invoice item.
- An idempotent retry duplicates neither record.
- Partial, exact, and excess payments use the revised invoice balance correctly.
- Principal-only payments cannot add an invoice item.
- Invoice screens, PDFs, and emailed receipts show the item normally.

## Out of Scope

- Adding multiple new items in one payment submission.
- Editing or deleting existing invoice items from the Record payment page.
- Manually adding plan-payment items from this page.
- Manually invoking automated late-fee classifications.
- Automatically changing the received payment amount to match the revised invoice.
