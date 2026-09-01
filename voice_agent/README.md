# Voice Agent Latency Report

Tracks SIP registration round-trip time (RTT) per agent/extension and
shows it as a dropdown-driven latency graph in `index.php`.

Data flows: Asterisk logs a "Reachable... RTT: x msec" line for each
PJSIP contact in `/var/log/asterisk/full` -> `collect_rtt.php` (cron,
every 15 min) parses recent lines into the `registrations` table ->
`index.php` reads from `registrations` for the top-average table and
the per-name trend chart.

## 1. Create the database table

Check the event scheduler is on (it resets to `OFF` on some installs
after a MySQL/MariaDB restart):

```bash
mysql -u root -e "SHOW VARIABLES LIKE 'event_scheduler';"
```

If it shows `OFF`, enable it for the current session and persist it:

```bash
mysql -u root -e "SET GLOBAL event_scheduler = ON;"
```

Then add `event_scheduler=ON` under `[mysqld]` in the server's MySQL
config (e.g. `/etc/my.cnf` or `/etc/my.cnf.d/server.cnf`) so it
survives a restart.

Now run the schema once against the Asterisk CDR database:

```bash
mysql -u root asteriskcdrdb < voice_agent/schema.sql
```

This creates `registrations` (`name`, `roundtrip_usec`,
`registration_datetime`) if it doesn't already exist, and a daily
event (`registrations_prune_daily`, runs at 02:00) that deletes rows
older than 10 days. `CREATE TABLE IF NOT EXISTS` is a no-op if the
table is already present, so it's safe to re-run.

> If `registrations` already exists with extra columns (e.g. `aor`,
> `uri`, `user_agent`, `status`) from a previous setup, this script
> will not alter it automatically — check `SHOW CREATE TABLE
> registrations;` first and adapt `schema.sql` to match before
> running it. The retention event is added independently of the
> table's exact columns, so it's safe to apply either way.

## 2. Configure `.env`

Copy `.env.example` to `.env` at the repo root if you haven't already,
and set:

```
DB_HOST=localhost
DB_NAME=asteriskcdrdb
DB_USER=root
DB_PASS=

ASTERISK_LOG_PATH=/var/log/asterisk/full
RTT_COLLECT_WINDOW_MINUTES=20
```

`ASTERISK_LOG_PATH` is where Asterisk's full log lives (check with
`ls -la /var/log/asterisk/full` — the cron user needs read access, or
run the cron as `root`/`asterisk`). `RTT_COLLECT_WINDOW_MINUTES`
should stay comfortably above the cron interval (15 min) so a slow or
delayed run doesn't skip lines.

## 3. Add the cron job

```bash
crontab -e
```

Add:

```
*/15 * * * * php /var/www/html/custom/voice_agent/collect_rtt.php >> /var/www/html/custom/voice_agent/collect_rtt.log 2>&1
```

Adjust the path to wherever this repo is checked out on the server.

## 4. Verify it's working

```bash
tail -f /var/www/html/custom/voice_agent/collect_rtt.log
mysql -u root asteriskcdrdb -e "SELECT * FROM registrations ORDER BY id DESC LIMIT 5;"
```

Each cron run logs a line like:

```
2026-09-01 12:15:00 collect_rtt: matched=6 inserted=6 window=20m
```

If `matched` stays 0, double-check `ASTERISK_LOG_PATH` and that the
log actually contains "is now Reachable... RTT:" lines
(`grep Reachable /var/log/asterisk/full | grep RTT`).

## Known limitations

- `name` is taken as the raw extension number parsed from the log
  line (e.g. `225`), not a resolved friendly name (e.g. `Leo`). Some
  earlier version of this pipeline on this server resolved a display
  name instead — that mapping hasn't been ported into `collect_rtt.php`
  yet.
- The 10-day retention event runs once daily at 02:00 regardless of
  whether new data is coming in — but it depends on the MySQL/MariaDB
  event scheduler being `ON`; if that's ever disabled (e.g. after a
  DB restart without `event_scheduler=ON` persisted in config),
  pruning silently stops. Check with `SHOW VARIABLES LIKE
  'event_scheduler'` and `SHOW EVENTS;`.
- Log rotation isn't tracked explicitly; the script re-scans whatever
  is currently at `ASTERISK_LOG_PATH` within the configured window on
  every run.
