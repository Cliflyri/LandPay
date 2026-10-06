# Closing workflow ? accepted implementation
Branch: closing_workflow. Keep closing state separate from billing and plan status.
Trigger: active/paused + existing contract balance <= 0, independent of extra invoices.
Dashboard: existing edit-plan badge becomes light green Fully Satisfied (old financial conditions).
Yellow Ready to close retains old appearance and links to closing panel; then Closing in progress,
Closing on hold, Closed. Initial review alert dismisses per admin, not workflow badge.
Admin panel always available; early start allowed. Start/resume optionally notifies via email/SMS
using existing preferences. Disable either shows on-hold + optional client note or hides entirely.
Preserve progress. No client writes/download of signing packet while disabled.
Client notice: Your plan xx-xx-xxx may be fulfilled. Please watch for information which will guide
you through the next steps.
Card above monthly payments: Your Closing Process.
Introduction: Please review and complete the steps below so we can prepare your paperwork.
1 Confirm Your Paperwork Details: titling + vesting reference, prefilled legal names/addresses,
optional mailing address, required unselected married yes/no per owner; add another owner.
Marriage explanation emphasizes: even if a spouse is not listed on the paperwork.
Required unselected beneficiary yes/no carried into step 2. Required client confirmation.
2 Request Additional Paperwork: spouse/beneficiary/special requests, explicitly for an additional fee.
No additional paperwork needed or discuss a special request + comments; admin resolves fees.
3 Complete and Sign Required Forms: forms being prepared until admin releases after steps 1/2.
Editable default instructions:
a) Please print the required form.
b) Obtain notarization where indicated (do not sign until in the presence of a notary).
c) Physically mail the original to us at the address indicated in section 2 on the form.
Admin accepts originals; submission does not equal approval. Revisions reopen review and withdraw packet.
4 Recording and Your Deed: visual milestones paperwork accepted / ready for recording /
submitted to county / recording confirmed. Completion means confirmed recording, not client submissions.
Client tax reminder and deed mailing information remain. Billing continues.
Balances and invoice links always shown to admin. Fresh checks at readiness, submission, completion;
outstanding amounts require explicit acknowledgment + reason per action, audit history retained.
Closing secure messages: latest two with earlier-message drawer; existing secure messaging integration.
Tests: triggers, nonmember access, hold/release/review transitions, financial safeguards, notifications,
private files, dashboard/card rendering, regression checks.

## Implementation and verification
- Implemented on closing_workflow over SSH as landpaydev.
- New closing state is independent of financial transactions and payment-plan billing status.
- Private documents use the existing local storage disk; all downloads check plan membership,
  workflow visibility and signing-packet release.
- Per-client closing threads reuse secure messages; their normal routes redirect to closing.
  Alternate reply routes cannot bypass the closing state.
- Optimistic version checks reject stale forms; row locks protect state transitions and financial checks.
- Client submissions produce existing admin notices; initial readiness dismisses per administrator.
- Email uses the existing client email; SMS respects existing opt-in, STOP and global enable settings.
- Signing packet revisions or client detail changes invalidate downstream approvals and milestones.
- Milestone history retains outstanding balance acknowledgments and reasons.
- Tests: 63 tests / 825 assertions passed on the dedicated landpay_testing MySQL database.
  Includes 8 closing tests plus portal, improvements, plan management, message editing, notice history,
  and financial-posting regression tests. Initial SQLite attempt could not run the existing MySQL CONCAT query.
- Browser visual automation was unavailable: its runtime failed to launch due to the environment's
  invalid mapped working directory. Server-rendered admin/client pages are covered by integration tests.

## Operation
Open a plan's Closing panel (always available). Save client-facing instructions, upload vesting/signing
documents, then Start closing (optionally notify). Review steps 1 and 2, release the signing packet,
accept originals, and record each county milestone. Closing never automatically changes plan billing status.
Disabling preserves all data and either shows the hold note or hides the client area.
Email/SMS delivery results are logged in closing history; zero successful deliveries are reported.
Migration: database/migrations/2026_10_05_000000_create_plan_closings.php.


## Shared vesting guide
Settings > Closing defaults accepts one default PDF (maximum 10 MB). Starting a closing assigns
an immutable reference to that version, unless a plan-specific vesting document already exists.
Replacing the default applies to future starts only. Existing closing panels offer Update to latest
guide / Use default vesting guide. Uploading a Vesting reference PDF replaces just that plan's guide.
Shared file versions are retained when a plan removes/replaces its reference. No schema change is needed.
Client step 1 opens a Bootstrap PDF modal with authenticated inline preview, download, and separate-tab
fallback. Previews enforce the same membership and closing visibility checks as downloads.

## Combined client submission
Sections 1 and 2 remain numbered separately but share one Save draft / Submit paperwork details and
requests form. Both sets of required answers and one confirmation are validated together; both statuses
become Awaiting review. Drafts do not create submission notices. Any client revision resets both reviews
and withdraws the signing packet. Admin retains separate reviews, guarded by the combined submission.
Previously started active closings without a vesting guide receive the current default once; existing
assigned versions are preserved. Preview and direct PDF download appear before the titling question.

Combined-submission verification: 13 closing tests / 211 assertions passed; JavaScript syntax and Blade compilation passed. Existing missing active guide assignments repaired; assigned versions preserved.

## Admin document placement
Vesting uploads and information belong to step 1. Step 3 contains signing upload, file review/removal,
signing and mailing instructions, then release. Release stays disabled with unmet prerequisites listed.
Recorded-document uploads belong to step 4. Separate instruction forms preserve other sections' notes.

## Next-action banners and release notification
Yellow banners at the top of both closing cards follow the current submission, review, signing, receipt,
and recording stage. Drafts retain the Not yet submitted warning. The release form offers an optional
Notify client checkbox using the existing email and eligible SMS delivery, with step 3-specific wording.
Other review/recording transitions update portal banners but do not automatically email or text clients.

## Approved-step visibility and progress undo
Approved sections 1–2 are replaced by contact guidance; clients cannot change approved details without
an explicit admin reopen. Accepted step 3 is similarly replaced by contact guidance.
After county submission the client sees only progress beneath the relevant plan in Your Plans, including
after recording confirmation. Undoing county submission restores the full card.
Explicit Reopen controls sit beside completed section badges, with editable client instructions.
Yellow banners identify reopened steps. Reopening sections 1/2 preserves answers and requires combined
resubmission; reopening step 3 preserves its released packet and instructions.
Undo buttons beside completed recording milestones clear that milestone and later milestones.
Undo paperwork acceptance reopens client step 3. All prior values are retained in event history.
Instruction saves and repeated approval saves do not reopen steps or reset progress. Billing is unchanged.

Admin recording controls now sit directly below their corresponding progress segments: date label,
small date field and compact Mark button. Later actions are disabled until prerequisites are complete.
Completed segments show a small Undo control below the marker. Balance safeguards are retained.

## Compact-view trigger revision
Client progress moves beneath the plan as soon as paperwork is accepted (step 3 complete), rather than
waiting for county submission. Undoing readiness or county submission keeps this compact view.
Undoing paperwork acceptance or reopening a client step restores the full card and action instructions.

Client section visibility: show combined steps 1-2 until reviewed, then replace them with a single contact-us sentence above step 3. Hide future recording sections; accepted paperwork continues to use compact progress beneath the plan number. Explicit reopening restores the applicable client phase.
