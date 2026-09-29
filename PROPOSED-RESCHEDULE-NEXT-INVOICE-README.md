# Proposed Reschedule Next Invoice

Status: Proposal only. Do not implement until explicitly authorized.

## Purpose

Allow an administrator to postpone the next unissued monthly invoice to a specific date, optionally adopting that day for subsequent monthly invoices. This supports skipped months and avoids invoices occurring too close together. Preserve all existing invoice, payment, and billing behavior for plans without a reschedule.

## Agreed interface

The admin plan details page already displays `Next scheduled invoice`, using the existing scheduler calculation. Add a compact `Reschedule` button beside that display. Expand an inline form only when needed:

- Current next invoice date (read-only).
- New invoice date.
- Unchecked checkbox: `Use this day for future monthly invoices`.
- Required reason.
- Preview of the next three scheduled invoice dates.
- Save reschedule and Cancel (close the unsaved form).

After saving, show the new next date, a Rescheduled indicator, the original date, and Change reschedule / Cancel reschedule actions. Canceling a saved reschedule must preview the resulting schedule before confirmation; distinguish this from closing an unsaved form.

Example: original next invoice October 3, 2026; replacement November 10, 2026:

| Checkbox | Schedule |
| --- | --- |
| Unchecked | November 10, December 3, January 3 |
| Checked | November 10, December 10, January 10 |

Unchecked means resume the original monthly day in the following month, not another invoice later in the replacement month. The preview must expose short intervals such as November 10 to December 3.

The existing Edit Plan invoice-day control remains available. Checked means update actual effective-dated billing terms through the existing mechanism, not maintain a second recurring schedule in the reschedule record.

## Scheduling behavior

1. Apply only to invoices not yet created. Existing issued invoices, dates, charges, balances, payments, and delivery history remain unchanged.
2. Replace the next scheduled occurrence and intentionally skip intervening scheduled dates. October 3 -> November 10 suppresses October 3 and November 3. Neither is later back-billed.
3. Do not create the rescheduled invoice before its replacement date. If the scheduled job misses that date, its next run must generate it with the selected issue date, without duplicating it.
4. Calculate its due date using the applicable plan billing terms (`due_days_after_issue`). Do not change payment terms implicitly.
5. After successful issuance, mark the reschedule fulfilled. Retain the skipped-period history so ordinary catch-up logic never mistakes intentionally skipped dates for missed runs.
6. Plans without reschedules use the existing scheduling behavior.
7. No placeholder invoices, zero-dollar invoices, or fictitious payments for skipped periods.
8. Save changes and reasons to the existing audit log; no new tracking dashboard.

## Existing code and integration points

Confirm these against current code before implementation:

- `app/Services/AutomaticInvoiceService.php`: `nextDate()`, `missingDates()`, `dateForMonth()`, `termsFor()`, and invoice generation in `run()`.
- The scheduler calculates monthly dates from billing terms and scans prior months for missing invoices. It does not currently use a mutable next-invoice pointer. Both generation and date previews must honor reschedules.
- Monthly invoice numbers currently follow `INV-{plan_id}-{YYYYMM}`. Investigate how a replacement invoice maps to its billing period and invoice number before implementation. Prevent conflicts with any invoice already issued for the destination month, including manual issuance. Do not simply change dates while ignoring monthly uniqueness.
- `app/Services/MonthlyInvoiceService.php`: reuse existing invoice issuance, balance checks, and financial posting behavior.
- Admin manual next-invoice review/issuance: identify current routes/controllers and ensure they agree with the scheduler about the replacement date and skipped periods.
- `app/Http/Controllers/Admin/PaymentPlanController.php`: `show()` already calculates/passes `nextInvoiceDate`; `update()` maintains effective-dated billing terms.
- `resources/views/admin/plans/show.blade.php`: Plan details card and current next-date display; pause/resume controls are nearby.
- `app/Http/Controllers/Admin/PaymentPlanPauseController.php`: paused dates are skipped, payments remain available, and planned resume is not automatic. Avoid conflating a reschedule with a plan pause.
- Existing invoice editing accepts due dates on/after issue date for non-voided invoices. It does not reschedule future monthly invoices.

## Lean persistence proposal

One small table linked to the payment plan, with a migration and model. Suggested fields (final schema to be reviewed):

- Payment plan ID.
- Original scheduled date / beginning of intentionally skipped interval.
- Replacement issue date.
- Status: pending, fulfilled, canceled.
- Fulfilled invoice reference and timestamps as appropriate for reliable idempotency.

Store actor, reason, and before/after changes in the existing audit log. The skip interval must remain identifiable after fulfillment. Monthly recurrence remains in billing terms; the record must not become an alternate recurring calendar. Any checkbox intent stored for editing/audit purposes is metadata only.

Recommended initial scope: active monthly plans, one pending reschedule per plan. Explicitly decide handling of paused, draft, terminated, closed, paid-off, and accelerated-testing plans before implementation; do not silently apply monthly rules to daily testing mode.

## Implementation details to settle before coding

- Forward-only validation, including today's date, overdue unissued invoices, and multiple outstanding missing months. Do not silently suppress earlier arrears outside the approved interval.
- Destination-month conflicts: reject or explicitly resolve an existing invoice; never overwrite it or silently duplicate it.
- Canceling after the original date has passed could expose dates to catch-up. Preview and explicitly confirm the restoration policy rather than accidentally back-billing.
- When the checkbox changes billing terms, define cancellation/change semantics: whether and how those terms are restored. Preserve later independent amendments; do not blindly revert current terms.
- If Edit Plan changes the invoice day while a reschedule is pending, establish which dates prevail and keep previews consistent.
- If a plan is paused, closed, or paid off while pending, respect existing eligibility and principal limits. Define what happens to the pending record on resumption.
- Handle month-end dates (29/30/31) consistently with the existing last-day-of-month clamping.
- Use existing application timezone/date-only conventions.
- Manual issuance and scheduled issuance must share reschedule fulfillment and duplicate protection.
- Lock/revalidate the plan, pending record, and relevant invoice state during changes and issuance. Tie invoice creation and fulfillment together transactionally; prevent concurrent cron/manual runs from creating duplicate invoices or losing skipped history.
- Load relevant reschedules efficiently for batch generation; avoid adding per-date queries.

These are implementation questions, not authorization to expand scope. Resolve using existing architecture and surface business-rule choices that require admin preference.

## Suggested implementation sequence

1. Inspect existing schedule/manual issuance paths and tests; settle billing-period identity and cancellation behavior.
2. Add minimal persistence and a shared date-resolution approach used by preview and issuance.
3. Integrate transactional/idempotent issuance and retained skip history.
4. Add admin validation, existing audit logging, and optional billing-term update.
5. Add the compact inline UI and schedule preview.
6. Run focused regression tests and verify the UI before deployment. Supply the migration command for cPanel.

## Required regression coverage

- Unchanged schedule and catch-up when no reschedule exists.
- Same-month and multi-month postponements; no invoices before the replacement date.
- Checkbox off/on schedules match the agreed examples.
- Intervening dates remain skipped after fulfillment and later scheduler runs.
- Missed replacement-day run catches up once with the selected issue date.
- Repeated and concurrent runs/manual issuance cannot duplicate invoices.
- Existing invoices and payments remain untouched; destination-month conflicts handled.
- Correct due-date calculation and applicable terms, including later amendments.
- Pending reschedule changes/cancellation and explicit handling of past dates.
- Month-end/leap-year dates, paused/closed/paid-off plans, and daily testing eligibility.
- Preview, dashboard/plan next-date displays, manual review, and cron agree.
- Authorized admin access, input validation, and audit entries.

## Current implementation status

Only the next scheduled invoice display exists. No reschedule controls, records, migrations, or scheduler changes have been implemented for this proposal.

This README is intended to remain untracked: do not stage or commit it unless requested.