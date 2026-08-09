
Needed upgrades:

payoff balance adjustment - change contract balance in client account section to "Current Payoff" amd add base fee amount to balance if no fee collected this month.

DONE - ADMIN NOTICES - whenever a payment is made by webhook, place a notice on the admin dashboard It should note the client, date, amount and stay until dismissed by admin.DONE The actual dashboard display message is generated in [dashboard.blade.php (line 26)](/F:/Nas Web Root/LandPay/resources/views/admin/dashboard.blade.php:26).
The stored fallback message is created in [ProviderWebhookController.php (line 23)](/F:/Nas Web Root/LandPay/app/Http/Controllers/ProviderWebhookController.php:23).

previous principal - readme exists

schedule adjustment 1 hr earlier

sms reminder flow

auto cron update