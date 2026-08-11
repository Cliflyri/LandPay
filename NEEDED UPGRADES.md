
Needed upgrades:

DONE payoff balance adjustment - change contract balance in client account section to "Current Payoff" and add base fee amount to balance if no fee collected this month.  

DONE - ADMIN NOTICES - whenever a payment is made by webhook, place a notice on the admin dashboard It should note the client, date, amount and stay until dismissed by admin.DONE The actual dashboard display message is generated in [dashboard.blade.php (line 26)](/F:/Nas Web Root/LandPay/resources/views/admin/dashboard.blade.php:26).
The stored fallback message is created in [ProviderWebhookController.php (line 23)](/F:/Nas Web Root/LandPay/app/Http/Controllers/ProviderWebhookController.php:23).

DONE - both create and edit screens done previous principal - readme exists

DONE ZELLE - have custom click to copy so when the logo or email is clicked it copies the zelle handle to the users clipboard.  Have some brief instructions notifying the user of such.

DONE  multiple payment notice fix

DONE schedule adjustment 1 hr earlier

DONE added favicon

within 2 payments of payoff - client dash notice, email reminder adjustment (your payoff is xx.xx if you would like to make a larger payoff month prior)

sms reminder flow

auto cron update

TODO dashboard horizontal drag panning - Click-and-drag horizontal scrolling on dashboard tables is still not working. Both pointer-event and mouse-event implementations were attempted in public/assets/js/landpay.js. Revisit later; verify asset loading/cache and test the actual table scroll container in-browser before revising the handler.
Git Deployment Admin Feature

DONE - Documented the lean proposal in GIT_DEPLOYMENT_ADMIN_FEATURE.md; no application code implemented.

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
