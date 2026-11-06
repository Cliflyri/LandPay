# LandInvest authenticated CSV fetch

## Setup and deployment
No new migration. Existing manual CSV display/copy remains available.
Deploy normally and rebuild route/view caches. Admin Actions > Export for LandInvest > HTTPS export access: generate a separate key, copy ID and secret into LandInvest, then explicitly enable export access. Generation alone leaves access disabled. Confirm administrator password for key/control changes. Secret appears only once. Manual rotation supports optional 24-hour old-key overlap; revoke disables and invalidates both keys. Repayment credentials are independent and cannot fetch CSV; export credentials cannot upload repayments.

## Exact request
GET https://landpay.mohavedeals.com/webhooks/landinvest/export
No body, no query parameters. HTTPS only, certificate verification enabled, redirects disabled.
Accept: text/csv (errors are JSON regardless of Accept).

Headers:
X-LandInvest-Key: export key ID
X-LandInvest-Timestamp: current Unix seconds, 10 decimal digits
X-LandInvest-Nonce: 16 random bytes encoded as 32 lowercase hex characters, fresh each attempt
X-LandInvest-Signature: lowercase hex HMAC-SHA256

Join these seven fields using newline characters, WITHOUT a final newline:
landinvest-v1
GET
/webhooks/landinvest/export
<key ID>
<timestamp header>
<nonce header>
<SHA256 hex digest of the empty string>

Empty body digest: e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855
Signature: hash_hmac('sha256', canonical_string, displayed_secret)
Use the displayed secret literally; do not hex-decode it.

Five-minute request timestamp window; nonce retained eleven minutes. Rate limit 30 requests/minute/IP.
Use fresh signing headers on retries, bounded backoff for timeout/429/503. Do not automatically retry invalid credentials.

## Response
200: complete CSV, Content-Type: text/csv; charset=UTF-8, Cache-Control: no-store.
Content-Disposition: attachment; filename="landpay-landinvest.csv"
X-LandPay-Generated-At: UTC ISO timestamp
X-LandPay-Plan-Count: row count excluding header

CSV header:
apn,county,contract_status,plan_reference,primary_client_name,property_name

Uses the identical query and status calculations as manual Export for LandInvest. Includes historical/deleted non-test plans, excludes test plans/clients, preserves duplicate APNs, missing APNs and CSV quoting. Parse with a CSV parser, not by splitting lines. Treat property identifiers as strings. Validate entire response before applying existing LandInvest import rules. Never interpret transport failures as an empty export. Successful download does not mean LandInvest has imported it; LandPay records when the authenticated export was served.

Errors (JSON with error field):
400: HTTP or GET with body/query (HEAD also rejected)
401: invalid signature, timestamp or credential
409: replayed nonce
429: rate limit
503: disabled, cache unavailable, or export unavailable
Unsupported methods are rejected by routing (405).

No data or secrets logged; only key ID and row count on success. No admin notice is generated for downloads. Production setup/sender activation remains manual.
