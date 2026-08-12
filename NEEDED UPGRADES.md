
Needed upgrades:

DONE payoff balance adjustment - change contract balance in client account section to "Current Payoff" and add base fee amount to balance if no fee collected this month.  

DONE - ADMIN NOTICES - whenever a payment is made by webhook, place a notice on the admin dashboard It should note the client, date, amount and stay until dismissed by admin.DONE The actual dashboard display message is generated in [dashboard.blade.php (line 26)](/F:/Nas Web Root/LandPay/resources/views/admin/dashboard.blade.php:26).
The stored fallback message is created in [ProviderWebhookController.php (line 23)](/F:/Nas Web Root/LandPay/app/Http/Controllers/ProviderWebhookController.php:23).

DONE - both create and edit screens done previous principal - readme exists

DONE ZELLE - have custom click to copy so when the logo or email is clicked it copies the zelle handle to the users clipboard.  Have some brief instructions notifying the user of such.

DONE  multiple payment notice fix

DONE schedule adjustment 1 hr earlier

DONE added favicon

DONE dashboard horizontal drag panning - Click-and-drag horizontal scrolling on dashboard tables is still not working. Both pointer-event and mouse-event implementations were attempted in public/assets/js/landpay.js. Revisit later; verify asset loading/cache and test the actual table scroll container in-browser before revising the handler.

DONE - Added reusable active/draft/terminated/closed/all plan filtering and client/APN/plan/email/phone search to the Admin Dashboard and Payment Plans list.

DONE - Added debounced automatic plan search and tokenized full/preferred/organization name matching.

DONE - Preserved the plan-filter layout while adding anchored search results and cache-busting for reliable automatic submission.

DONE - Added a reusable in-field X that cancels pending plan search, clears filters, and reloads the full Active list.

DONE - Added lean invoice editing for invoice/due dates and add, edit, or delete line items with clean current output.

DONE - Added reusable revoke and reset client portal access controls with replacement invitations and disabled-session enforcement.

DONE - Fixed the client portal access Blade conditional parse error.

DONE - Added a conditional priority Notices sidebar link with bell icon, open-count badge, dashboard anchor, and locally switchable background.

DONE - Added generic client portal and payment-detail email variables and updated invoice, reminder, receipt, and reversal defaults with appropriate portal links.

DONE - Highlighted daily test-billing plans in pale yellow and sorted them below ready-to-close plans on admin listings.

DONE - Reused the debounced list filter on Clients with full-name, preferred name, organization, contact, APN, and plan-number search.

DONE - Added sanitized Square checkout diagnostics, failed-intent handling, and retained Credit Card form state after checkout errors.

DONE - Removed duplicate payment actions by hiding the initial button while checkout details are open and restoring the expanded card form after errors.

DONE - Simplified card checkout to an always-visible optional note followed by one Pay Now button.

DONE - Consolidated the admin payment receipt into one natural-height summary, compact email history, and subdued cancellation panel.

DONE - Added a clear Square or Stripe provider row to online-card payment receipts.

DONE - Added reusable newest-first payment ordering for portal recent/history lists and the admin plan payment list.

Stripe ACH hosted checkout, payment amount normalization, and provider-label cleanup -- deferred; see STRIPE_ACH_README.md.

  Stripe ACH, amount validation, provider labels, webhook lifecycle, and testing proposal in STRIPE_ACH_README.md; no application code implemented.

within 2 payments of payoff - client dash notice, email reminder adjustment (your payoff is xx.xx if you would like to make a larger payoff month prior)

sms reminder flow

auto cron update

Git Deployment Admin Feature GIT_DEPLOYMENT_ADMIN_FEATURE.md; no application code implemented.

At plan closure a document (pdf) and instructions need to be shared with the client.  I would like an interface between the admin and client portals, sort of a message bridge between the portals with the ability to attach a file.  This also may be helpful for storing the clients contracts at plan implementation, though that use case is not the primary goal.  I would like ideas for implementing this keeping it lean and very usable from a client and admin perspective.

Near-payoff client dashboard and payment-reminder experience -- deferred; see PAYOFF_OPPORTUNITY_README.md.

Reusable payoff-opportunity calculation, dashboard callout, prefilled payoff link, conditional reminder callout, and testing proposal in PAYOFF_OPPORTUNITY_README.md; no application code implemented.

DONE - Created feature/secure-client-messages as the isolated development branch for the secure messaging feature.

DONE - Implemented secure client messaging with optional plan references, private PDF attachments, client replies, unread notices, admin follow-up stars, viewed/downloaded timestamps, and generic email reminders.

DONE - Fixed the secure-message sidebar badge Blade parse error.

DONE - Added a right-aligned secure-message actions menu with a larger passive follow-up star indicator.

DONE - Corrected secure-message actions placement, star rendering, and clipped admin dropdown behavior.

DONE - Expanded secure messages to private PDF/JPG/PNG attachments with automatic image resizing, admin attachment deletion, and permanent conversation deletion with storage cleanup.

DONE - Added authenticated image thumbnails and modal previews plus shared admin/client conversation bubbles for secure-message threads.

DONE - Added low-impact admin polling to refresh sidebar notice/message badges and only the dashboard notices area without reloading pages or disturbing form input.

DONE - Added client-initiated secure messages with processed JPG/PNG uploads and optional generic administrator email notifications sent to the configured reply-to address.

DONE - Disabled secure-message admin email notifications when no valid reply-to address is configured and added clear guidance with a Settings link.

Dedicated administrator Notices page -- proposed; see NOTICES_PAGE_README.md. Move open notices off the dashboard, retain the live sidebar badge, reuse existing notice actions, and refresh details only on the Notices page; no application code implemented.

DONE - Reused the latest-two secure-message collapse in both portals, moved its behavior to shared JavaScript, simplified the client subject heading, preserved failed replies, and made the history control full width.

DONE - Changed generic administrator secure-message notification emails to link directly to the relevant authenticated conversation.
