
Needed upgrades:

DONE payoff balance adjustment - change contract balance in client account section to "Current Payoff" and add base fee amount to balance if no fee collected this month.  

DONE - ADMIN NOTICES - whenever a payment is made by webhook, place a notice on the admin dashboard It should note the client, date, amount and stay until dismissed by admin.DONE The actual dashboard display message is generated in [dashboard.blade.php (line 26)](/F:/Nas Web Root/LandPay/resources/views/admin/dashboard.blade.php:26).
The stored fallback message is created in [ProviderWebhookController.php (line 23)](/F:/Nas Web Root/LandPay/app/Http/Controllers/ProviderWebhookController.php:23).

DONE - both create and edit screens done previous principal - readme exists

DONE ZELLE - have custom click to copy so when the logo or email is clicked it copies the zelle handle to the users clipboard.  Have some brief instructions notifying the user of such.

DONE  multiple payment notice fix

DONE schedule adjustment 1 hr earlier

within 2 payments of payoff - client dash notice, email reminder adjustment (your payoff is xx.xx if you would like to make a larger payoff month prior)

sms reminder flow

auto cron update

TODO dashboard horizontal drag panning - Click-and-drag horizontal scrolling on dashboard tables is still not working. Both pointer-event and mouse-event implementations were attempted in public/assets/js/landpay.js. Revisit later; verify asset loading/cache and test the actual table scroll container in-browser before revising the handler.