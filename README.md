# PBX Custom Pages

A lightweight PHP dashboard suite for PBX and call-center operations. The project exposes several internal web pages from a single landing page, with a shared bootstrap for environment loading, feature flags, and database access.

## What This Project Includes

The application currently provides a small set of live tools plus several placeholder pages for future work.

- `voice_agent/` - latency dashboard for voice-agent registrations using the `registrations` table.
- `call_surveys/` - survey dashboard with KPI cards, charts, search, and a full table sourced from the `survey` table.
- `call_transfer/` - date-range transfer report built on top of the PBX `cdr` table.
- `queue_alert/` - form for saving queue-to-alert-number settings in `queue_alert_settings.json`.
- `voicemails/`, `clean_cdr/`, `clean_recording/`, `call_analytics/`, `ai_agent/` - currently simple placeholder pages linked from the home screen.

## Project Structure

```text
.
├── index.php                # Landing page with links to all modules
├── bootstrap.php            # Env loader, feature flags, PDO helper, shared utilities
├── .env.example             # Example configuration
├── voice_agent/
├── call_surveys/
├── call_transfer/
├── queue_alert/
├── voicemails/
├── clean_cdr/
├── clean_recording/
├── call_analytics/
├── ai_agent/
└── old/                     # Legacy experiments / archived files
```

## Requirements

- PHP with PDO MySQL enabled
- MySQL or MariaDB access to the PBX database
- Web server capable of serving PHP, or PHP built-in server for local testing
- Network access from the PHP host to the database server

## Configuration

Copy `.env.example` to `.env` and adjust the values for your environment.

```env
FEATURE_VOICE_AGENT=1
FEATURE_CALL_ANALYTICS=1
FEATURE_CALL_SURVEYS=1
FEATURE_QUEUE_ALERT=1
FEATURE_CALL_TRANSFER=1
FEATURE_VOICEMAILS=1
FEATURE_CLEAN_CDR=1
FEATURE_CLEAN_RECORDINGS=1
FEATURE_AI_AGENT=1

DB_HOST=localhost
DB_NAME=asteriskcdrdb
DB_USER=root
DB_PASS=

APP_NAME="Voice Agent Latency Report"
APP_ENV=production

MAIL_FROM=operator@example.com
VM_ALERT_THRESHOLD=75
VM_MAX_MESSAGES=100
```

### Feature Flags

`bootstrap.php` exposes `requireFeature()` so pages can be disabled from config without removing links or code. When a feature is off, the page stops rendering and shows a disabled message.

### Environment Helpers

The shared bootstrap provides:

- `loadEnv($path)` to parse `.env`
- `env($key, $default)` to read config values
- `envEnabled($key)` for `1`/`0` feature flags
- `db()` for a reusable PDO connection

## Local Development

For quick local testing, from the project root run:

```bash
php -S localhost:8000
```

Then open:

```text
http://localhost:8000/
```

## Module Notes

### Voice Agent Latency

`voice_agent/index.php` reads the `registrations` table and shows:

- the highest average roundtrip times
- a selectable list of agent names
- a 7-day latency trend chart using Chart.js

### Call Surveys Dashboard

`call_surveys/index.php` reads the `survey` table and displays:

- average rating
- surveys submitted today
- top-performing agent
- satisfaction percentage for ratings `>= 4`
- charts for rating distribution, daily trend, and top agents
- searchable survey history table rendered in the browser

### Call Transfer Report

`call_transfer/index.php` uses `bootstrap.php` and queries the `cdr` table for a selected date range. It returns:

- total patch time per account
- detailed transfer rows including caller, extension, unique ID, last app, and linked ID
- debug output when `APP_ENV` is not `production`

### Queue Alert

`queue_alert/index.php` attempts to load queue options from the `survey` table, then stores the selected queue and alert number in:

```text
queue_alert_settings.json
```

If the database is unavailable, the page still allows manual queue entry.

## Known Codebase Notes

- `call_transfer/` is the only module currently wired into `bootstrap.php` feature gating.
- Several modules still use hard-coded DB credentials instead of the shared `env()` / `db()` helpers.
- The landing page links to placeholder modules that currently contain only a title card and back link.
- `old/` contains archived or experimental files and does not appear to be part of the active app flow.
- `queue_alert_settings.json` is created at runtime and is not committed by default.

## Suggested Next Improvements

- migrate all DB-backed pages to `bootstrap.php`
- move hard-coded credentials into `.env` everywhere
- add auth if these tools are exposed outside an internal network
- add input validation and error handling consistency across modules
- document the expected database schema for `cdr`, `survey`, and `registrations`

## Entry Point

The main landing page is:

- `index.php`

From there, users can navigate to each custom PBX page.
