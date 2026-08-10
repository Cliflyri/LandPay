# Automatic Cron Settings Instructions

## Goal

Update only the cron instructions displayed in the Admin Settings area so they automatically match the server on which LandPay is running.

This feature must not install, edit, or remove cron jobs. It must not require new `.env` values or change any global application settings.

## Required behavior

The application should infer the current hosting environment and display the appropriate instructions:

- On the local NAS, retain the existing command-oriented instructions for the regular user, root/sudo, and status checks.
- On cPanel, show instructions formatted for the cPanel **Cron Jobs** manager.
- If the environment cannot be confidently identified, show safe generic Unix instructions.

All displayed paths, usernames, and PHP executable paths should be derived from the current server where practical rather than being hardcoded.

## Environment detection

Detection should happen at runtime without `.env` configuration. Useful indicators include:

- `base_path()` beginning with `/media/` for the NAS installation.
- `base_path()` beginning with `/home/{account}/` for a typical cPanel installation.
- Known cPanel filesystem locations or executables.
- The owner of the project directory when a username or cPanel account name is needed.

Detection should be conservative. If the evidence is ambiguous, use the generic Unix fallback rather than presenting instructions known to be incorrect.

## PHP executable detection

The displayed command should use an available PHP CLI executable compatible with the application's PHP version. Candidate locations may include:

- `/usr/local/bin/ea-php83`
- `/opt/cpanel/ea-php83/root/usr/bin/php`
- `/usr/local/bin/php`
- `/usr/bin/php`

The exact EasyApache version should be derived from the running PHP major and minor version when possible. Do not rely exclusively on `PHP_BINARY`, because a web request may report an FPM executable that is unsuitable for cron.

## cPanel display

The cPanel version should explain how to add one job in cPanel's **Cron Jobs** interface and present the values as separate fields:

| Field | Value |
| --- | --- |
| Minute | `*` |
| Hour | `*` |
| Day | `*` |
| Month | `*` |
| Weekday | `*` |
| Command | `cd '/home/{account}/{project-path}' && {php-cli} artisan schedule:run >> /dev/null 2>&1` |

The command must use the actual detected project path and PHP CLI executable. The user should not be told to edit a crontab manually on cPanel.

## NAS display

The NAS display may retain the current tabs for:

- Regular user setup
- Root/sudo setup
- Status and troubleshooting

However, the displayed username, project path, and PHP executable should be inferred rather than fixed to one development installation.

## Application schedule shown to administrators

The descriptive text must remain consistent with the Laravel scheduler in `routes/console.php`. At present:

- Invoice emails run at **6:00 AM**.
- Payment reminder emails run at **7:00 AM**.

Only the single `artisan schedule:run` cron entry is needed; Laravel controls the individual task times.

## Suggested implementation

Keep the logic small and presentation-focused:

1. Add a service such as `CronInstructionService` that detects the environment and returns a display model.
2. Have the Admin Settings controller pass that model to the settings view.
3. Update `resources/views/admin/settings/index.blade.php` to render NAS, cPanel, or fallback instructions.
4. Add focused tests for environment classification and generated display values.

The display model should contain only values needed by the view, such as:

- Environment type
- Project path
- Account or user name
- PHP CLI path
- Scheduler command
- cPanel schedule fields

## Out of scope

- Modifying a system or user crontab
- Running `crontab` commands
- Installing or removing scheduled jobs
- Adding environment variables
- Changing `routes/console.php`
- Changing invoice or reminder times
- Performing remote server configuration

## Acceptance criteria

- The Admin Settings cron instructions automatically fit the current NAS or cPanel server.
- A cPanel deployment shows separate schedule fields and the command expected by cPanel's Cron Jobs manager.
- A NAS deployment shows suitable terminal instructions using detected local values.
- No `.env` update is required.
- Viewing the page makes no server or cron configuration changes.
- The displayed invoice and reminder times match the actual Laravel schedule.
