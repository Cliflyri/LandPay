# Client payment methods

Administrators configure client payment access at **Settings > Payment methods**.

## Method behavior

Enabled methods appear together in the client portal. Zelle is displayed first and marked Recommended. Zelle, Cash App, Venmo, Chime, check, money order, and other configured methods create a nonfinancial payment announcement. The administrator dashboard receives a notice whose **Receive payment** action opens the existing payment form with the plan, payer, amount, method, reference, and excess-payment instruction prefilled. No balance changes until the administrator reviews and posts the payment.

Card payments use the single active provider selected under General. LandPay creates a persistent checkout record and redirects to Square or Stripe hosted checkout. Only a correctly signed completed-payment webhook with the expected checkout reference, USD currency, and exact amount posts the payment. Provider transaction IDs and financial idempotency keys prevent duplicate posting. Mismatches create an administrator exception notice.

## Provider setup

1. Set the production `APP_URL` to LandPay's public HTTPS URL.
2. Open **Settings > Payment methods**.
3. Save the provider environment, account/location identifier, API secret, and webhook signing secret.
4. Configure the provider webhook endpoint shown on the settings tab:
   - `/webhooks/square`
   - `/webhooks/stripe`
5. Test in Square Sandbox or Stripe test mode before selecting the provider under General and enabling the Card method.
6. Confirm a test checkout posts once, updates the portal, and creates an administrator notice.

Secrets are encrypted in application settings and never displayed after saving. LandPay does not store card or bank credentials.

## Operational review

Provider refunds, disputes, amount mismatches, unknown references, and non-completed events require reconciliation. Do not enable live card checkout until the deployment has a publicly reachable HTTPS webhook URL and backup/restore procedures have been tested.
