# LandPay Identity Schema Proposal

Status: approved 2026-07-26. Identity migrations may now be created and run; immutable financial migrations remain pending separate review.

Target database: MariaDB 10.6. Money fields use integer cents (`BIGINT UNSIGNED`). Public identifiers use UUID strings (`CHAR(36)`) while internal relationships use unsigned big integers.

## Design boundaries

- `users` contains LandPay administrators and optional read-only auditors only.
- Clients are not stored in `users` and cannot use the administrator guard.
- Future client authentication will use a separate `client_portal_accounts` design and guard after identity ownership rules are approved.
- Every spouse or jointly responsible person is a first-class `client` connected through `payment_plan_clients`.
- General, emergency, and continuity contacts are optional, non-liable `client_contacts`; listing a contact grants no ownership, account access, disclosure permission, or payment obligation.
- A client who chooses not to provide an alternate contact must acknowledge per payment-plan membership that loss of communication may result in contract default.
- Identity and contact records referenced by plan or financial history are never hard-deleted.

## `users`

Administrator authentication accounts. This proposal replaces the unexecuted default users migration before migrations run.

| Column | Type | Null | Default | Notes |
|---|---|---:|---|---|
| `id` | BIGINT UNSIGNED | no | auto | Primary key |
| `uuid` | CHAR(36) | no | — | Public identifier |
| `name` | VARCHAR(120) | no | — | Administrator display name |
| `email` | VARCHAR(254) | no | — | Login identifier; normalized to lowercase |
| `email_verified_at` | TIMESTAMP | yes | null | Set for CLI-created administrators |
| `password` | VARCHAR(255) | no | — | Laravel password hash only |
| `role` | VARCHAR(32) | no | `administrator` | `administrator` or `auditor` |
| `status` | VARCHAR(32) | no | `active` | `active` or `disabled` |
| `last_login_at` | TIMESTAMP | yes | null | Security visibility |
| `last_login_ip` | VARCHAR(45) | yes | null | IPv4/IPv6 |
| `remember_token` | VARCHAR(100) | yes | null | Framework compatibility; remember-me UI remains disabled |
| `created_at`, `updated_at` | TIMESTAMP | yes | — | Laravel timestamps |

Indexes and constraints:

- Primary key on `id`.
- Unique index on `uuid`.
- Unique index on `email`; application lowercases email before storage.
- Check `role IN ('administrator', 'auditor')`.
- Check `status IN ('active', 'disabled')`.
- Disabled users cannot authenticate, even with a valid password.
- Public registration routes remain absent.

Supporting Laravel tables `password_reset_tokens` and `sessions` remain, subject to later approval of the password-reset workflow. The current login UI does not expose password reset or remember-me.

## `clients`

Every legally responsible individual or organization.

| Column | Type | Null | Default | Notes |
|---|---|---:|---|---|
| `id` | BIGINT UNSIGNED | no | auto | Primary key |
| `uuid` | CHAR(36) | no | — | Public identifier |
| `client_type` | VARCHAR(24) | no | `individual` | `individual` or `organization` |
| `first_name` | VARCHAR(100) | yes | null | Required for individuals |
| `middle_name` | VARCHAR(100) | yes | null | Optional |
| `last_name` | VARCHAR(100) | yes | null | Required for individuals |
| `preferred_name` | VARCHAR(100) | yes | null | Display preference |
| `organization_name` | VARCHAR(180) | yes | null | Required for organizations |
| `email` | VARCHAR(254) | yes | null | Not unique; households may share email |
| `primary_phone` | VARCHAR(32) | yes | null | Store normalized/display-safe value |
| `secondary_phone` | VARCHAR(32) | yes | null | Optional |
| `address_line_1` | VARCHAR(150) | yes | null | Mailing address |
| `address_line_2` | VARCHAR(150) | yes | null | Optional |
| `city` | VARCHAR(100) | yes | null | — |
| `state_region` | VARCHAR(100) | yes | null | — |
| `postal_code` | VARCHAR(24) | yes | null | — |
| `country_code` | CHAR(2) | no | `US` | ISO country code |
| `status` | VARCHAR(24) | no | `active` | `active`, `inactive`, or `deceased` |
| `notes` | TEXT | yes | null | Administrator-only initially |
| `created_by_user_id` | BIGINT UNSIGNED | no | — | Administrator creator |
| `updated_by_user_id` | BIGINT UNSIGNED | no | — | Last administrator editor |
| `archived_at` | TIMESTAMP | yes | null | Replaces deletion |
| `created_at`, `updated_at` | TIMESTAMP | yes | — | Laravel timestamps |

Indexes and constraints:

- Primary key on `id`; unique `uuid`.
- Index on `(last_name, first_name)` and separate indexes on `email`, `primary_phone`, and `status`.
- Foreign keys from creator/editor to `users.id` with `RESTRICT` deletion.
- Individual clients require first and last name; organization clients require organization name. Enforce with service validation and a MariaDB check where practical.
- Email is not unique because spouses and organizations may share addresses.
- No hard deletes after creation; use status and `archived_at`.

## `payment_plans`

One financed purchase account.

| Column | Type | Null | Default | Notes |
|---|---|---:|---|---|
| `id` | BIGINT UNSIGNED | no | auto | Primary key |
| `uuid` | CHAR(36) | no | — | Public identifier |
| `plan_number` | VARCHAR(40) | no | — | Human-facing internal account number |
| `title` | VARCHAR(180) | no | — | Plan/property label |
| `asset_description` | TEXT | yes | null | Financed property or asset |
| `original_purchase_balance` | BIGINT UNSIGNED | no | — | Integer cents; immutable after activation |
| `customary_monthly_payment` | BIGINT UNSIGNED | no | — | Integer cents |
| `monthly_service_fee` | BIGINT UNSIGNED | no | `0` | Integer cents |
| `monthly_due_day` | TINYINT UNSIGNED | no | — | 1–31; shorter months use their final day |
| `first_due_date` | DATE | no | — | — |
| `plan_start_date` | DATE | no | — | — |
| `maturity_date` | DATE | yes | null | Optional |
| `grace_period_days` | SMALLINT UNSIGNED | no | `0` | 0–60 initially |
| `status` | VARCHAR(24) | no | `draft` | See values below |
| `activated_at` | TIMESTAMP | yes | null | Set once activated |
| `closed_at` | TIMESTAMP | yes | null | Historical closure |
| `notes` | TEXT | yes | null | Administrator-only initially |
| `created_by_user_id` | BIGINT UNSIGNED | no | — | Creator |
| `updated_by_user_id` | BIGINT UNSIGNED | no | — | Last editor |
| `created_at`, `updated_at` | TIMESTAMP | yes | — | Laravel timestamps |

Indexes and constraints:

- Primary key on `id`; unique indexes on `uuid` and `plan_number`.
- Indexes on `status`, `first_due_date`, and `monthly_due_day`.
- Check due day 1–31, grace days 0–60, and nonzero original/monthly payment.
- Status values: `draft`, `active`, `paused`, `paid_off`, `defaulted`, `closed`.
- Creator/editor foreign keys use `RESTRICT` deletion.
- No primary-client column; all ownership comes from `payment_plan_clients`.
- Activation service requires at least one active primary client and freezes original purchase balance/start terms.
- No hard delete after activation.

## `payment_plan_clients`

Historical many-to-many membership supporting spouses and other co-clients.

| Column | Type | Null | Default | Notes |
|---|---|---:|---|---|
| `id` | BIGINT UNSIGNED | no | auto | Primary key |
| `payment_plan_id` | BIGINT UNSIGNED | no | — | Plan FK |
| `client_id` | BIGINT UNSIGNED | no | — | Client FK |
| `role` | VARCHAR(24) | no | — | `primary` or `co_client` |
| `responsibility` | VARCHAR(24) | no | `joint` | Initially `joint`; extensible only after review |
| `receives_invoices` | BOOLEAN | no | true | Communication control |
| `effective_from` | DATE | no | — | Membership start |
| `effective_to` | DATE | yes | null | Null while active |
| `end_reason` | VARCHAR(255) | yes | null | Required when ended |
| `contact_risk_acknowledged_at` | TIMESTAMP | yes | null | Per-contract acknowledgment when no alternate contact is supplied |
| `contact_risk_acknowledgment_method` | VARCHAR(32) | yes | null | How acknowledgment was captured |
| `created_by_user_id` | BIGINT UNSIGNED | no | — | Creator |
| `ended_by_user_id` | BIGINT UNSIGNED | yes | null | Administrator ending membership |
| `created_at`, `updated_at` | TIMESTAMP | yes | — | Laravel timestamps |

Indexes and constraints:

- Foreign keys to payment plans, clients, and users use `RESTRICT` deletion.
- Check role and responsibility values; check `effective_to >= effective_from` when present.
- Index `(payment_plan_id, effective_to)`, `(client_id, effective_to)`, and `(payment_plan_id, role, effective_to)`.
- Prevent duplicate active membership for the same plan/client.
- Permit historical re-association after a prior membership ends.
- Permit at most one active primary client per plan. MariaDB 10.6 can enforce this through nullable generated keys plus unique indexes; the proposed migration will show the exact expression for review.
- “At least one primary client” is enforced transactionally when activating or changing an active plan because a normal row constraint cannot enforce existence across rows.
- Ending a membership preserves the row; it is never deleted.

## `client_contacts`

General table for a client's non-liable contacts, including emergency and continuity candidates. It is intentionally not a fully versioned subsystem initially.

| Column | Type | Null | Default | Notes |
|---|---|---:|---|---|
| `id` | BIGINT UNSIGNED | no | auto | Primary key |
| `uuid` | CHAR(36) | no | — | Public identifier |
| `client_id` | BIGINT UNSIGNED | no | — | Owning client |
| `payment_plan_id` | BIGINT UNSIGNED | yes | null | Optional plan-specific scope; null means all plans for that client |
| `is_general_contact` | BOOLEAN | no | false | Routine alternate communication role |
| `is_emergency_contact` | BOOLEAN | no | false | Urgent-contact role |
| `is_continuity_contact` | BOOLEAN | no | false | Prolonged loss, incapacity, or succession-contact role |
| `first_name` | VARCHAR(100) | no | — | — |
| `last_name` | VARCHAR(100) | no | — | — |
| `relationship` | VARCHAR(100) | yes | null | Spouse, sibling, attorney, etc.; does not imply liability |
| `email` | VARCHAR(254) | yes | null | At least one contact method required |
| `primary_phone` | VARCHAR(32) | yes | null | — |
| `secondary_phone` | VARCHAR(32) | yes | null | — |
| `address_line_1`, `address_line_2` | VARCHAR(150) | yes | null | Optional |
| `city`, `state_region` | VARCHAR(100) | yes | null | Optional |
| `postal_code` | VARCHAR(24) | yes | null | Optional |
| `country_code` | CHAR(2) | no | `US` | — |
| `preferred_contact_method` | VARCHAR(24) | yes | null | `email`, `phone`, `mail`, or null |
| `priority` | SMALLINT UNSIGNED | no | `1` | Contact order |
| `permission_scope` | VARCHAR(24) | no | `contact_only` | No financial disclosure initially |
| `status` | VARCHAR(24) | no | `active` | `active`, `replaced`, `withdrawn`, or `deceased` |
| `effective_from` | DATE | no | — | — |
| `ended_at` | TIMESTAMP | yes | null | Retains past contacts |
| `end_reason` | VARCHAR(255) | yes | null | — |
| `replaced_by_contact_id` | BIGINT UNSIGNED | yes | null | Self-reference to replacement row |
| `notes` | TEXT | yes | null | Restricted display |
| `created_by_user_id`, `updated_by_user_id` | BIGINT UNSIGNED | yes | null | Null later when client portal is actor |
| `created_at`, `updated_at` | TIMESTAMP | yes | — | Laravel timestamps |

Indexes and constraints:

- Primary key on `id`; unique `uuid`.
- Foreign keys to client, optional payment plan, replacement contact, and users use `RESTRICT` deletion.
- Index `(client_id, status, priority)` and `(payment_plan_id, status, priority)`.
- Require at least one of the three non-exclusive contact-role flags to be true; one person may serve multiple roles.
- Check enum-like values and nonnegative priority.
- Service validation requires at least one of email, primary phone, secondary phone, or mailing address.
- If plan-scoped, service validation confirms the owner client is or was associated with that plan.
- A contact never gains liability, client status, portal access, or financial disclosure rights.
- Replace contacts by ending the old row and creating a new row linked through `replaced_by_contact_id`; do not delete the old row.
- Ordinary corrections may update the current row, but every before/after change is captured in `audit_logs`.
- Future client portal policies may allow a client to create, edit, replace, reorder, or withdraw only contacts owned by that client.

## `audit_logs`

Append-only record of identity and contact changes. This supports initial contact history without a separate version table.

| Column | Type | Null | Default | Notes |
|---|---|---:|---|---|
| `id` | BIGINT UNSIGNED | no | auto | Primary key |
| `uuid` | CHAR(36) | no | — | Public identifier |
| `actor_type` | VARCHAR(24) | no | — | `administrator`, `client`, or `system` |
| `actor_user_id` | BIGINT UNSIGNED | yes | null | Administrator actor |
| `actor_client_id` | BIGINT UNSIGNED | yes | null | Future client actor |
| `event` | VARCHAR(100) | no | — | Example: `client_contact.updated` |
| `auditable_type` | VARCHAR(100) | no | — | Controlled morph type |
| `auditable_id` | BIGINT UNSIGNED | no | — | Subject ID |
| `before_values` | JSON | yes | null | Changed fields before operation |
| `after_values` | JSON | yes | null | Changed fields after operation |
| `ip_address` | VARCHAR(45) | yes | null | — |
| `user_agent` | VARCHAR(500) | yes | null | Truncated safely |
| `created_at` | TIMESTAMP | no | current | Append timestamp; no `updated_at` |

Indexes and constraints:

- Primary key on `id`; unique `uuid`.
- Index `(auditable_type, auditable_id, created_at)`, `(actor_user_id, created_at)`, `(actor_client_id, created_at)`, and `(event, created_at)`.
- Actor foreign keys use `SET NULL` only if an actor account is later removed; the log itself remains.
- Application uses a controlled morph map rather than arbitrary class names.
- Audit rows are never updated or deleted through application workflows.
- Sensitive values such as password hashes, reset tokens, session payloads, and secrets are never placed in before/after JSON.

## Relationship summary

- `users` 1→many created/updated identity records.
- `clients` many↔many `payment_plans` through historical `payment_plan_clients`.
- `clients` 1→many `client_contacts`.
- `payment_plans` 1→many optional plan-scoped `client_contacts`.
- `client_contacts` may self-reference a replacement.
- `audit_logs` records changes to all identity tables.

## Deletion and retention rules

- Administrators may be disabled, not deleted after activity exists.
- Clients are archived, not deleted.
- Activated payment plans are closed, not deleted.
- Plan memberships are ended, not deleted.
- Contacts are withdrawn/replaced, not deleted.
- Audit logs are append-only.
- Foreign keys default to `RESTRICT`; cascades are avoided for business history.

## Approved decisions

Approved 2026-07-26:

1. Administrator roles: `administrator` and optional `auditor`.
2. Individual and organization clients in the first migration.
3. Due-day behavior: 1–31 with final-day fallback for shorter months.
4. Optional, non-exclusive contact roles: general, emergency, and continuity. Clients may decline all contacts after accepting the per-contract communication-loss/default warning.
5. Contact history approach: retain replaced rows plus audit before/after values; no version table initially.
6. Application transaction and payment-plan row locking enforce one active primary and no duplicate active membership. Generated nullable-key uniqueness is deferred until the exact development and production MariaDB versions are both compatibility-tested.
7. Separate future client authentication table and guard.