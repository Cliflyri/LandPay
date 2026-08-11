# Git Deployment Admin Feature

Status: Proposal only. No application code has been implemented.

## Goal

Add a small environment-aware Git / Deployment section to Admin Settings without allowing arbitrary shell commands.

## Environment selection

Use an explicit environment variable: `LANDPAY_DEPLOYMENT_MODE=nas`.

Allowed values are `nas`, `production`, and `disabled`. Do not rely on `APP_ENV` alone.

## NAS Development

Display the environment, current branch, current commit hash and message, working-tree status with changed files, and relationship to `origin/main`.

Provide two actions:

1. **Commit current changes** requires a message, previews all changed and untracked files, then runs only the fixed equivalent of `git add -A` and `git commit`, passing the message as a safe process argument.
2. **Push to GitHub** is enabled only on `main` with a clean tree when the local branch is ahead and not behind `origin/main`.

Production controls must not appear on NAS.

## Production

Display the environment, current branch, current commit, and whether `origin/main` has a newer commit. If fetch fails, show remote status as unavailable.

Provide one action: **Deploy latest**. Enable it only on `main` with a clean tree when `origin/main` is ahead. Prevent concurrent runs with a deployment lock.

Use this fixed sequence:

```text
git fetch origin main
verify branch is main and working tree is clean
git merge --ff-only origin/main
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Build frontend assets on NAS and commit them so production does not need Node/npm.

## Minimum safeguards

- Admin authorization, CSRF protection, confirmation, and rate limiting
- Fixed commands and arguments only; no command input or branch selector
- Timeouts, sanitized logs, and a concise success or failure result
- Refuse deployment for a dirty tree, a branch other than `main`, or a non-fast-forward update
- Confirm cPanel lets the web-server account run Git, Composer, and Artisan and read the private GitHub repository

## Acceptance criteria

- NAS can inspect, commit, and push eligible changes.
- Production can inspect and deploy only a newer fast-forward `origin/main` revision.
- Each environment shows only its relevant controls.
- Failures do not expose secrets or allow arbitrary shell execution.
