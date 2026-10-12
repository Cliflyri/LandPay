# LandPay / LandInvest Parcel Status Integration

Status: proposal only. Updating this README does not implement or authorize application changes, database changes, token creation, scheduling, or deployment.

## Goal and boundaries

Let LandInvest show whether properties are currently under contract and their LandPay status in a separate LandPay Status column of the property list. Administrators can follow a normal LandPay admin link for more detail. Both applications retain their own databases; LandPay remains the source of truth for contract facts.

Keep the integration separate and focused. Do not refactor or change LandPay's existing dashboard, contract rules, payment processing, or closing workflow. Add only the connection and narrowly scoped ability to share approved information. Any existing calculation methods called by the integration must be verified to be read-only; do not invoke workflow actions or methods that update records during a lookup.

LandInvest's existing property status (Acquired, Listed, Sold, etc.) remains independent and is not changed automatically. LandInvest may develop its own badges over time. The initial scope is a minimal status badge and a plan link, not dashboard badge mirroring. Future presentation changes belong in LandInvest.

## Proposed LandPay organization

Keep the new application code together, for example:

```text
app/Integrations/LandInvest/
    Routes.php
    Authenticate.php
    ParcelStatusController.php
    ParcelMatcher.php
    ContractStatusReader.php
```

This is a proposed layout, not files already created. The folder would own authentication for this endpoint, input validation, APN/county matching, contract selection, and the limited status response.

Small supporting pieces may be outside this folder: a route-registration connection, server-side secret/enable configuration, and integration tests under the existing tests directory. A database migration is needed only if the final design introduces persistent data in LandPay; the proposed dedicated-secret authentication does not require a token table. LandInvest will need its own local result storage.

Do not extract, reproduce, or mirror dashboard badge rendering. Read only the facts needed to determine the compact contract status, using existing read-only calculation methods where suitable and leaving those methods unchanged. The previous proposal to reproduce the full dashboard badge set is superseded. The folder is an organizational boundary, not a security sandbox; the code still runs within LandPay.

## Proposed request flow

1. LandInvest's server sends a bounded batch of APNs and available counties to a protected HTTPS endpoint, tentatively `/api/integrations/parcel-status`.
2. LandPay authenticates the request before performing lookups or status calculations.
3. The integration matches parcels, selects qualifying contracts, and returns only approved status facts.
4. LandInvest stores the last successful result locally and renders its status badge and plan link.
5. An administrator can open the corresponding LandPay page through a normal authenticated admin link.

The NAS initiates outbound HTTPS requests; no inbound NAS ports or public NAS access are needed. LandPay must be reachable from the NAS. Here, private means authentication-protected, not necessarily inaccessible from the public internet.

## Minimal information shared

For each requested property, return a correlation value identifying the requested APN/item plus these three fields:

| Field | Values / purpose |
| --- | --- |
| `match_result` | `found`, `not_found`, or `ambiguous` |
| `contract_status` | `under_contract`, `paused`, `paid_off`, `sale_completed`, or `no_active_contract`; null for not-found/ambiguous results |
| `plan_reference` | The selected plan's existing admin-route identifier, or null when no plan can be selected safely |

The correlation value is only for mapping a batch response back to the requested property; it is not an additional source of business information. Final request/response field names can be settled during implementation.

Use a single contract-status field rather than a yes/no Under Contract value so paused contracts and successful outcomes remain distinguishable. No separate current-contract boolean or dashboard badge descriptors are needed in this first version. Active and paused count as current; paid-off and completed-sale labels communicate successful outcomes rather than asserting current-contract eligibility.

Use whichever reference LandPay's existing admin route actually expects (ID or public UUID), not an assumed displayed plan number. LandInvest builds an Open in LandPay link using its configured LandPay base URL and the verified route format. No complete URL needs to be returned by the API. For a found parcel without a qualifying contract, return a null reference unless a safe, explicit plan-selection rule is agreed; do not choose an arbitrary historical plan just to supply a link.

Do not share client names in the initial version. Also exclude contact details, balances, payment histories, documents, invoice details, full model objects, and dashboard HTML or badge arrays. Client names can be reconsidered later if opening LandPay for details proves inconvenient. Their inclusion would associate a person with a property/contract and increase the information exposed by a compromise, without being necessary for this goal.

LandInvest records Last checked locally after a successful response. LandPay does not need to share a separate timestamp for that purpose. Future status fields or badges require an explicit scope decision; do not expand the payload preemptively.

## Contract eligibility and meaning

| Contract state | Treatment |
| --- | --- |
| Active current contract | Counts as current |
| Paused current contract | Counts as current |
| Paid off | Remains visible as a successful outcome |
| Successfully closed | Remains visible and indicates the sale completed |
| Cancelled, defaulted, archived, or other excluded inactive state | Does not count as current |

Successful outcomes remain visible even after the contract is no longer current. Their visibility alone does not mean a current contract exists. Map these business meanings to actual stored states and completion rules before implementation.

The read-only assessment found that LandPay has a separate closing workflow with a completed state. Use that completion information when identifying a successful sale; do not assume every closed plan is a successfully completed sale. Likewise, the dashboard's Default Eligible badge does not itself mean that a contract has actually been defaulted or terminated.

### Multiple contracts

Proposed selection, still to be confirmed against actual records:

1. A current active or paused contract takes precedence over historical successful outcomes.
2. Without a current contract, show the most recent qualifying successful outcome.
3. Without either, a matched parcel has No active contract.

Define the recency field, treatment of multiple current contracts, and whether archiving a previously successful contract removes its successful-outcome display. Do not choose arbitrarily when records remain ambiguous. Contract selection remains necessary even though the response now contains only one compact status and one optional plan reference.

## APN-first matching

APN is the primary match. County is secondary, particularly to distinguish duplicate APNs. State is not required for this proposal; do not add a LandPay state field solely for this integration.

- Ignore APN dashes and compare letters case-insensitively, including trailing suffixes.
- Preserve leading zeros and letter suffixes; keep APNs as text.
- Trim surrounding whitespace and apply consistent normalization.
- If an APN identifies one parcel, use that match; county is not a mandatory component of every match.
- If the APN identifies multiple candidate parcels, use normalized county to disambiguate.
- If county is missing or cannot resolve duplicates, return Needs review rather than guessing or reporting Not in LandPay.
- Multiple contracts belonging to the same parcel still require the contract-selection rules above.

For example, `215-07-203a` matches `21507203A`, and `001-02-003B` matches `00102003b`; `21507203A` does not match `21507203B`.

County aliases, a county conflict on an otherwise unique APN, and punctuation other than dashes should receive explicit handling during implementation. APN plus county may still be ambiguous across jurisdictions; unresolved matches must remain flagged for review.

## LandInvest display

Add a separate LandPay Status column with one locally rendered status badge and an Open in LandPay link when a plan reference is available. This supersedes the earlier summary-above-mirrored-badges layout and the requirement to reproduce multiple dashboard badges.

| Result | Display |
| --- | --- |
| Active current contract | Under contract |
| Paused current contract | Paused |
| Paid off | Paid off |
| Successfully closed | Sale completed |
| Matched parcel without a qualifying contract or successful outcome | No active contract |
| Successful lookup found no parcel | Not in LandPay |
| Ambiguous parcel or contract selection | Needs review |

LandInvest owns badge wording and styling. No payment-due, Fully Satisfied, closing-progress, or other dashboard badge replication is required in this version. Keep LandInvest's existing property-status column unchanged.

Admin links must require normal LandPay authentication and authorization. A plan ID/UUID is a reference, not an access credential. Never embed the integration secret, bypass login, or provide automatic admin login through a link. Administrators view client names and detailed information inside LandPay.

## Authentication and security proposal

Prefer a dedicated, high-entropy integration secret checked by the endpoint's own middleware for this single read-only connection. Sanctum was previously considered, but is not required by this proposal and was not declared in the inspected LandPay dependencies. Do not reuse an admin password, session, or broadly privileged credential.

Required controls for implementation:

- The token grants access only to the approved parcel-status endpoint and intended records; possession grants no admin login or write permission.
- Store the secret only in protected server-side configuration, outside browser code and version control. Never include it in URLs, response bodies, or logs.
- Use HTTPS with certificate verification and an Authorization header. Compare secrets safely with a constant-time comparison.
- Authenticate before expensive work. Validate identifier types/lengths, use parameterized queries, and bound request bodies and batch sizes.
- Rate-limit requests and bound calculation work to reduce the risk of slowing LandPay.
- Return only the approved correlation value, match result, contract status, and optional plan reference. Exclude customer names, contact information, payment details, documents, and full model serialization.
- Provide token rotation/revocation and an integration disable switch. Missing or invalid configuration must fail closed.
- Return controlled errors without stack traces, secrets, or internal data. Escape status labels when rendering in LandInvest.
- Verify that lookup code causes no contract, payment, closing, or other business-record changes. Normal request/security logging may still occur.

A new endpoint adds attack surface. If LandInvest is compromised, its stolen token could expose the permitted status information until revoked. The token must not authorize broader access, but a coding vulnerability could have broader consequences because the integration runs inside LandPay. Folder separation and a read-only design reduce complexity; they do not guarantee immunity from attacks.

Reference: https://cheatsheetseries.owasp.org/cheatsheets/REST_Security_Cheat_Sheet.html

## Synchronization and failure handling

Initial proposal:

- Manual Refresh LandPay status button.
- Automatic server-side checks every 15 minutes, in bounded batches.
- Store the last successful match result, contract status, plan reference, and locally recorded successful-check timestamp.
- Show Last checked and Not checked before the first successful lookup.
- Preserve the previous status and plan reference after a failed refresh, with a separate Outdated indicator.
- Keep Not in LandPay, No active contract, Needs review, and transport/authentication failures distinct.
- Preserve prior results for individual parcels when part of a batch fails.

Only a successful lookup can establish that a parcel is absent. A timeout or rejected token must never become Not in LandPay or No active contract. Define retry limits, concurrency control, and the freshness threshold during implementation. The source assessment found a LandInvest CLI entry point suitable for adding a sync command; no new schedule has been installed or verified.

## Implementation and verification when authorized

LandPay: add the isolated integration folder, minimal route/configuration connections, authentication, matching, contract selection, and a bounded response. Leave existing workflows and dashboard logic untouched.

LandInvest: add the HTTPS client, local result storage, CLI sync command, manual refresh, separate property-list column, a locally rendered status badge, timestamps, error presentation, and normal LandPay admin links built from plan references.

Focused checks should cover APN normalization, unique APN matches, county-disambiguated duplicates, unresolved matches, contract history, active/paused eligibility, successful outcomes, excluded contracts, Default Eligible versus actual default, minimal response fields, absence of client/private data, status rendering, correctly authorized plan links, authentication denial, request limits, stale/partial results, and read-only lookup behavior. Confirm existing LandPay behavior remains intact.

Use isolated test data when implementation is authorized. The read-only assessment did not run application tests or query live business data. Exact scheduling, production connectivity, and deployment configuration remain to be verified within authorized scope.

## Workspace and current authorization

Project folders:

- `/media/F-4tb-WD-Red-NAS/Nas Web Root/LandPay`
- `/media/F-4tb-WD-Red-NAS/Nas Web Root/LandInvest`

Keep each application's repository and database independent. Current authorization is to update this README only. No implementation, dependency installation, token issuance, database migration, scheduler change, or deployment is included.
