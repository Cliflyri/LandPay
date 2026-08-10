# Admin Git and Deployment Proposal

Status: design only; no application code implemented.

## Goals

Add a small Git / Deployment area to Admin Settings with controls appropriate to the installation role:

- NAS development: inspect, commit, and push the working repository.
- cPanel production: inspect and deploy a vetted update from `origin/main`.
- Never expose an arbitrary command field or general-purpose shell access.

This feature is operational tooling. It must fail closed when the environment, repository, branch, permissions, or Git state is unexpected.

## Environment identification

Do not infer the role from a hostname, operating system, `APP_ENV` alone, or directory path. Add an explicit environment value, for example:

```dotenv
LANDPAY_DEPLOYMENT_ROLE=nas
```

Production would use:

```dotenv
LANDPAY_DEPLOYMENT_ROLE=production
```

Expose it through a small config entry. An absent or unknown value should produce a read-only `Deployment controls are not configured` state.

`APP_ENV=local|production` should continue to describe Laravel behavior; the new setting describes which Git controls are authorized.

## Shared read-only status

Both roles may display:

- Environment label.
- Current branch: `git branch --show-current`.
- Current commit: `git rev-parse --short HEAD`.
- Latest message: `git log -1 --pretty=%s`.
- Working tree: `git status --porcelain`.
- Ahead/behind counts: `git rev-list --left-right --count HEAD...origin/main`.

Ahead/behind information reflects the last fetched remote state. To determine whether GitHub currently has a newer commit, run a fixed `git fetch origin main` first. Avoid fetching on every normal Settings page load; use a `Refresh Git status` action or short cache.

All output must be HTML-escaped. Commands need short timeouts and bounded output. A failure should show a concise status, with technical details written to the application log.

## NAS Development behavior

Display:

- `NAS Development`.
- Branch, commit, and latest message.
- Clean or changed working tree.
- A concise list/count of modified and untracked files.
- Ahead/behind relationship with `origin/main` after status refresh.

### Commit current changes

Require a nonblank commit message with a reasonable length limit. Show the files that will be included before confirmation.

The proposed fixed operation is equivalent to:

```bash
git add -A
git commit -m "<validated message>"
```

`git add -A` matches the current NAS workflow and is lean, but it stages every changed and untracked nonignored file in the repository. The confirmation should explicitly say this. Git ignored files such as `.env` remain excluded, assuming `.gitignore` is correct.

Implementation requirements:

- Run Git with an argument array; do not interpolate the message into a shell command.
- Permit the action only for the configured NAS role.
- Require an administrator, CSRF protection, and preferably recent password confirmation.
- Acquire a lock so two Git actions cannot overlap.
- Refuse an empty working tree.
- Record actor, time, result, and resulting commit hash in an audit log.

This is moderate coding, mostly because status/error handling must be dependable. The Git operation itself is simple.

### Push to GitHub

Use only the fixed operation:

```bash
git push origin main
```

Enable Push only when all are true:

- Role is NAS.
- Current branch is `main`.
- Working tree is clean.
- Local branch is ahead of `origin/main`.
- Local branch is not behind or diverged.
- Remote named `origin` exists.

If the branch is behind or diverged, block the web action and instruct the administrator to reconcile it through SSH. Do not add a generic pull/merge conflict interface.

Pushing is moderate operational work because GitHub authentication must already work for the web/PHP process, not merely for the interactive SSH user.

## cPanel Production behavior

Display:

- `Production`.
- Current branch and commit.
- Latest deployed commit message.
- Clean/dirty working-tree state.
- Whether fetched `origin/main` is newer.

Do not show Commit, Push, arbitrary Git controls, or NAS controls.

Recommended button label: **Deploy latest**. It is concise and makes clear that this is more than a raw Git pull.

Enable it only when:

- Role is production.
- Current branch is `main`.
- Working tree is clean.
- `origin/main` is ahead of local `main`.
- Local `main` is not ahead or diverged.

A dirty, ahead, detached, or diverged production repository should block deployment and require SSH investigation.

## Fixed production deployment

Do not expose a shell-command text box. The server should execute a fixed, reviewed sequence. A suitable sequence is:

```bash
git fetch origin main
git merge --ff-only origin/main
composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Exact cache commands should be verified against LandPay before implementation. If routes contain closures, `route:cache` may not be usable. Frontend assets should normally be built before pushing and committed/deployed as artifacts; running an npm production build on shared hosting should not be assumed.

Maintenance mode can be considered, but it adds failure-handling obligations. If used, `php artisan up` must run in a guaranteed cleanup/finally path. For a lean first version, prefer a short fast-forward deployment without maintenance mode unless migrations require isolation.

`php artisan schedule:run` is not a deployment step. cPanel cron should continue invoking Laravel's scheduler on its normal schedule.

This portion is moderate-to-extensive operational work. The code can be small, but cPanel must allow PHP process execution, Git, Composer, network access, repository writes, and GitHub credentials. Web-request timeouts may also make a synchronous deploy unreliable. These capabilities must be proven before exposing the button.

## Process execution and security boundary

Use Laravel's Process facade or Symfony Process with explicit executable arguments and an explicit repository working directory. Never use `shell_exec` with concatenated user text.

Required controls:

- Super-administrator authorization.
- CSRF protection.
- Recent password confirmation for mutating actions.
- Per-action lock.
- Fixed repository path from server configuration.
- Fixed remote `origin` and branch `main`.
- Command allowlist; no arbitrary executable or arguments.
- Timeouts and bounded captured output.
- Audit record for actor, action, before hash, after hash, and outcome.
- Redact credentials and remote URLs containing secrets.

The web server user must own or have appropriate access to the repository. Avoid broad permission changes such as making the repository world-writable.

## Lean implementation phases

### Phase 1: read-only status (easy to moderate)

- Explicit deployment role.
- Git status service.
- Environment, branch, commit, message, and working-tree state.
- Manual remote refresh for ahead/behind status.

### Phase 2: NAS commit and push (moderate)

- Confirmed `git add -A` plus commit message.
- Conditional fixed push.
- Locks and audit records.

### Phase 3: production deploy (moderate to extensive)

- Prove cPanel process, Git, Composer, credential, and timeout behavior.
- Add fast-forward-only deployment.
- Add deployment log and robust failure reporting.

Starting with read-only status prevents the settings page from becoming an untested remote shell.

## Admin client-name display proposal

This is separate from Git/deployment. In admin views, organization names currently replace personal names. The lean fix is one reusable display partial at:

```text
resources/views/admin/clients/_display-name.blade.php
```

Example partial:

```blade
@php
    $personName = collect([
        $client->first_name,
        $client->middle_name,
        $client->last_name,
    ])->filter()->join(' ');
@endphp

@if($client->organization_name)
    <span>{{ $client->organization_name }}</span>
    @if($personName)
        <small class="d-block text-muted">{{ $personName }}</small>
    @endif
@else
    <span>{{ $personName ?: 'Unnamed client' }}</span>
@endif
```

Replace visible admin expressions such as:

```blade
{{ $client->organization_name ?: trim($client->first_name.' '.$client->last_name) }}
```

with:

```blade
@include('admin.clients._display-name', ['client' => $client])
```

Inside a link:

```blade
<a class="dashboard-client-link" href="{{ route('admin.clients.show', $client) }}">
    @include('admin.clients._display-name', ['client' => $client])
</a>
```

Keep a plain-text `$name` for HTML attributes, page titles, mail subjects, and other places where a partial cannot be used. Limit the partial to admin-facing views initially; portal greetings and customer emails may require different naming rules.

On the full client record page, explicitly labeled `Organization` and `Client name` or `Primary contact` fields are clearer than relying only on the compact partial.