# LandInvest HTTPS receiver and sender contract

No new migrations. Manual import remains available. New installations are disabled until an administrator generates a key and enables automatic imports.

## Production deployment

Deploy code using the existing process. Set APP_URL=https://landpay.mohavedeals.com and SESSION_SECURE_COOKIE=true in the production environment, then rebuild configuration/routes/views using the normal deployment process. Do not copy the NAS .env. Keep production debug disabled and cPanel HTTPS redirect enabled. Database cache and cache_locks already exist; use persistent file/database/Redis cache, never array/null/failover for this integration.

Admin Actions > Import from LandInvest > Automatic updates: confirm administrator password, generate key, copy key ID and secret into LandInvest private configuration. Secret is shown only in the direct generation response (never flashed into session). Current key metadata is visible; the secret cannot be redisplayed. Enable only after configuring the sender. Keys are encrypted using the existing AppSetting facility and APP_KEY. Preserve APP_KEY securely across deployments and backups. Database backups contain encrypted keys; do not clone production credentials into test environments.

Manual rotation is available, with optional 24-hour old-key overlap. No scheduled rotation or expiry of the current key. Revoke invalidates both keys and disables the receiver. Key operations use existing audit_logs without secrets. Successful uploads log only key ID and property count.

## Sender contract (LandInvest)

POST https://landpay.mohavedeals.com/webhooks/landinvest/repayments
Content-Type: application/json
Accept: application/json

No query string. TLS certificate verification must remain enabled; do not follow redirects. Limit 2 MiB and 30 requests/minute per source IP. Keep system time synchronized.

Send these headers:
- X-LandInvest-Key: key ID
- X-LandInvest-Timestamp: current Unix seconds (10 decimal digits)
- X-LandInvest-Nonce: 16 random bytes encoded as 32 lowercase hex characters; fresh for every attempt
- X-LandInvest-Signature: lowercase hex HMAC-SHA256 described below

Use the displayed 64-character secret as the literal HMAC key; DO NOT hex-decode it.

Canonical signed string: join these seven fields with newline characters, with NO final newline:
1. landinvest-v1
2. POST
3. /webhooks/landinvest/repayments
4. key ID
5. timestamp header exactly
6. nonce header exactly
7. lowercase hex SHA256 of the exact transmitted JSON body bytes

signature = hash_hmac('sha256', canonical_string, secret)
Serialize JSON once; sign and send those identical bytes.

Body is the existing complete schema_version 1 snapshot. All statuses remain paid/partial/unpaid/not_funded. Automated empty snapshots, future exports beyond five minutes, older exports, and conflicting exports with an equal generated_at are rejected. Identical repeated snapshots succeed without rewriting source/time metadata. Requests must be within five minutes; nonce records remain for eleven minutes. Manual imports can deliberately replace or clear data. All writes use a shared filesystem lock and atomic replacement.

Success: 200 with ok:true, generated_at, property_count. No property data returned.
400: insecure request or wrong request format
401: invalid/expired signature or key
409: replayed request (use fresh nonce and timestamp)
413: body too large
422: invalid/empty/stale/conflicting snapshot
429: rate limited
503: disabled or temporarily unavailable

On timeout/429/503, retry with bounded backoff and fresh signing headers; preserve the export timestamp. Do not retry validation errors automatically. Schedule daily and offer Send now. The sender remains a separate LandInvest implementation task.

The import page reports last actual snapshot replacement and source separately from last accepted HTTPS request (which may be an idempotent retry). Older snapshots without source metadata are treated as manual imports.
