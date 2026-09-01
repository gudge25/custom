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
- `requireFeature($feature, $name)` — call at the top of a page; if the flag is off, prints a disabled message and `exit`s

## Architecture: two module patterns coexist

This is the most important thing to know before editing a module — **not all modules follow `bootstrap.php`**:

1. **Bootstrap-integrated** (currently only `call_transfer/`): `require_once dirname(__DIR__) . '/bootstrap.php';`, then `requireFeature(...)`, then uses `db()`/`env()`. This is the intended pattern for all DB-backed pages going forward.
2. **Legacy inline-config** (`agent_latency/`, `call_surveys/`, `queue_alert/`): each file hard-codes its own `$dbHost/$dbName/$dbUser/$dbPass` (`localhost` / `asteriskcdrdb` / `root` / empty password) and opens its own `PDO` connection at the top of the file. These do **not** check any `FEATURE_*` flag and are not wired into `bootstrap.php` at all. If you touch one of these, prefer migrating it to `bootstrap.php`'s `db()`/`env()`/`requireFeature()` rather than perpetuating the inline pattern — but match the surrounding module's existing style if only making a small fix.
3. **Placeholders** (`voicemails/`, `clean_cdr/`, `clean_recording/`, `call_analytics/`, `ai_agent/`): a title card and a "Back to Home" link only, no logic, no DB access.

The landing page (`index.php` at repo root) just links to all module folders and is not itself gated by feature flags.

## Key DB tables (asteriskcdrdb)

- `cdr` — Asterisk call detail records; used by `call_transfer/` (`clid`, `duration`, `accountcode`, `dst`, `src`, `dstchannel`, `calldate`, `uniqueid`, `lastapp`, `linkedid`)
- `survey` — post-call survey results; used by `call_surveys/` and `queue_alert/` (`num`, `operator`, `queue`, `valuation`, `date`)
- `registrations` — voice-agent SIP registration latency; used by `agent_latency/` (`name`, `roundtrip_usec`, `registration_datetime`)

None of these schemas are formally documented elsewhere — infer columns from the queries in each module when making changes.

## Conventions observed in existing modules

- User-supplied output is escaped with `htmlspecialchars()` before rendering.
- SQL parameters are passed via PDO prepared statements with named placeholders (`call_transfer/`, `agent_latency/`) — except `call_surveys/index.php`, which interpolates a PHP-generated date string directly into a query (`WHERE DATE(date) = '$today'`); don't copy that pattern for user-controlled input.
- Each module's `<style>` block is self-contained and duplicated across files (no shared CSS) — dark gradient background (`#0f172a`/`radial-gradient(...#283c86...)`), `.card`/`.input`/`.btn`/`.chip` utility classes, Tailwind loaded via `<script src="https://cdn.tailwindcss.com">` on the fuller dashboards.
- `call_transfer/index.php` shows a `Debug Information` panel driven by `env('APP_ENV') !== 'production'` — follow this pattern if adding debug output to a bootstrap-integrated page rather than `var_dump`/`echo`ing directly.
- `queue_alert/index.php` persists settings to `queue_alert_settings.json` (repo root, git-ignored, created at runtime) rather than the database.

## CI

`.github/workflows/build.yml` runs a SonarQube scan (`SonarSource/sonarqube-scan-action`) on every push to `main`. No test or lint step currently runs in CI.

/design can U please add something like "demo data"

on call_surveys/index.php

cause it is a demo
