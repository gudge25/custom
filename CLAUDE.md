# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

A lightweight PHP dashboard suite for PBX/call-center operations (Gixo). Each module is a standalone folder with a single `index.php` that renders its own HTML/CSS (Tailwind via CDN) and, for DB-backed pages, runs its own SQL against a MySQL/MariaDB Asterisk CDR database. There is no framework, router, build step, package manager, or test suite — pages are plain PHP scripts served directly.

## Running Locally

```bash
php -S localhost:8000
```

Then open `http://localhost:8000/`. There are no build, lint, or test commands in this repo (no composer.json/package.json).

## Configuration

Copy `.env.example` to `.env`. `bootstrap.php` calls `die()` if `.env` is missing, so any DB-backed page that requires `bootstrap.php` will hard-fail without it.

- `loadEnv($path)` — parses `.env` (`KEY=VALUE`, `#` comments, quotes stripped)
- `env($key, $default)` — read a config value
- `envEnabled($key)` — `true` iff the value is exactly `'1'`
- `db()` — singleton PDO connection built from `DB_HOST`/`DB_NAME`/`DB_USER`/`DB_PASS`
- `renderFeatureDisabled($name)` — echoes the shared "🚫 disabled" message and `exit`s; used by the demo-fallback modules when their `demo_data.php` fixture is missing

## Architecture: bootstrap-integrated modules + placeholders

Every DB-backed module now follows the same base pattern — this used to be split between a "bootstrap-integrated" and a "legacy inline-config" group, but the last legacy module (`queue_alert/`) was migrated too:

1. **Bootstrap-integrated** (`call_transfer/`, `call_surveys/`, `call_analytics/`, `agent_latency/`, `voicemails/`, `queue_alert/`): `require_once dirname(__DIR__) . '/bootstrap.php';`, then uses `db()`/`env()`/`envEnabled()`. This is the pattern for all DB-backed pages.
2. **Placeholders** (`clean_cdr/`, `clean_recording/`, `ai_agent/`): a title card and a "Back to Home" link only, no logic, no DB access.

The landing page (`index.php` at repo root) just links to all module folders and is not itself gated by feature flags, but it does require an authenticated FreePBX session via `freepbx_auth.php`'s `requireFreepbxAuth()` (also called automatically inside `bootstrap.php`, and directly by non-bootstrap pages).

### Demo-data fallback pattern

`call_surveys/`, `call_analytics/`, `agent_latency/`, `call_transfer/`, and `voicemails/` each check `envEnabled('FEATURE_X')`: when on, they query the live DB; when off, they `require` a sibling `demo_data.php` fixture instead and set an `$isDemo` flag that renders an amber "Demo Data" chip next to the page title. `demo_data.php` files return a flat array of rows shaped exactly like the real query's rows (or raw rows a shared compute function can aggregate), and use relative timestamps (`strtotime('-N hours')`) rather than absolute dates so the fixture never looks stale. `call_analytics/` is the one exception: its real query targets `call_transcripts`, a table that doesn't exist yet, so `FEATURE_CALL_ANALYTICS=1` currently surfaces a query error — demo mode is the only working path there today.

`queue_alert/` is bootstrap-integrated but has no demo fallback — it's a write-only settings form (saves to `queue_alert_settings.json`, see below), not a data display, so there's nothing meaningful to fake. It just calls `renderFeatureDisabled('Queue Alert')` directly when `FEATURE_QUEUE_ALERT` is off.

## Key DB tables (asteriskcdrdb)

- `cdr` — Asterisk call detail records; used by `call_transfer/` (`clid`, `duration`, `accountcode`, `dst`, `src`, `dstchannel`, `calldate`, `uniqueid`, `lastapp`, `linkedid`) and by `voicemails/` (rows where `lastapp = 'VoiceMail'`; mailbox is `dst` with its `vmu` prefix stripped, caller is `src`, `duration`, `recordingfile` — empty string when no recording was captured). There's no listened/unheard flag in CDR; that lives in Asterisk's voicemail spool, not this DB.
- `survey` — post-call survey results; used by `call_surveys/` and `queue_alert/` (`num`, `operator`, `queue`, `valuation`, `date`)
- `registrations` — voice-agent SIP registration latency; used by `agent_latency/` (`name`, `roundtrip_usec`, `registration_datetime`)
- `call_transcripts` — **not implemented yet**; `call_analytics/index.php` queries it (`id`, `calldate`, `agent`, `extension`, `caller`, `duration_seconds`, `snippet`, `turns`, `action_items`, `has_redacted_pii`) but the table and its ingestion job don't exist, so `FEATURE_CALL_ANALYTICS=1` currently surfaces a query error — demo mode is the only working path today.

None of these schemas are formally documented elsewhere — infer columns from the queries in each module when making changes.

## Conventions observed in existing modules

- User-supplied output is escaped with `htmlspecialchars()` before rendering server-side; client-side table rendering should build DOM nodes and set `textContent` rather than interpolating into `innerHTML` (see `voicemails/index.php`, `call_analytics/index.php` for the pattern) — `call_surveys/index.php`'s table renderer predates this and still uses template-literal `innerHTML`, don't copy that.
- SQL parameters are passed via PDO prepared statements with named placeholders.
- Each module's `<style>` block is self-contained and duplicated across files (no shared CSS) — dark gradient background (`#0f172a`/`radial-gradient(...#283c86...)`), `.card`/`.input`/`.btn`/`.chip` utility classes, Tailwind loaded via `<script src="https://cdn.tailwindcss.com">` on the fuller dashboards.
- `call_transfer/index.php` shows a `Debug Information` panel driven by `env('APP_ENV') !== 'production'` — follow this pattern if adding debug output to a bootstrap-integrated page rather than `var_dump`/`echo`ing directly.
- `queue_alert/index.php` persists settings to `queue_alert_settings.json` (repo root, git-ignored, created at runtime) rather than the database. Nothing in this repo reads that file back yet — there's no cron/worker that actually fires an alert when a queue threshold is hit, so this page currently only saves the config, not the alerting itself.

## CI

`.github/workflows/build.yml` runs a SonarQube scan (`SonarSource/sonarqube-scan-action`) on every push to `main`. No test or lint step currently runs in CI.
