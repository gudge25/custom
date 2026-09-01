<?php
/**
 * Cron job: parse Asterisk's full log for PJSIP OPTIONS "Reachable" RTT
 * lines from the last N minutes and store them in `registrations`.
 *
 * Run every 15 minutes, e.g.:
 *   /15 * * * * php /path/to/custom/voice_agent/collect_rtt.php >> /path/to/custom/voice_agent/collect_rtt.log 2>&1
 *
 * Example source line (/var/log/asterisk/full):
 *   [2026-09-01 07:25:13] VERBOSE[16735] res_pjsip/pjsip_options.c: Contact 225/sip:225@79.117.161.109:56656;... is now Reachable.  RTT: 102.764 msec
 */

require_once dirname(__DIR__) . '/bootstrap.php';

$logPath = env('ASTERISK_LOG_PATH', '/var/log/asterisk/full');
$windowMinutes = (int) env('RTT_COLLECT_WINDOW_MINUTES', 20);

$pattern = '/^\[(?<ts>\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\].*Contact (?<name>[^\/\s]+)\/sip:.*is now Reachable\.\s+RTT:\s+(?<rtt>[\d.]+)\s*msec/';

$handle = @fopen($logPath, 'r');
if ($handle === false) {
    fwrite(STDERR, "Cannot open log file: $logPath\n");
    exit(1);
}

$cutoff = (new DateTime())->modify("-{$windowMinutes} minutes");
$pdo = db();
$insertStmt = $pdo->prepare(
    'INSERT IGNORE INTO registrations (name, roundtrip_usec, registration_datetime)
     VALUES (:name, :roundtrip_usec, :registration_datetime)'
);

$matched = 0;
$inserted = 0;

while (($line = fgets($handle)) !== false) {
    if (!preg_match($pattern, $line, $m)) {
        continue;
    }

    $ts = DateTime::createFromFormat('Y-m-d H:i:s', $m['ts']);
    if ($ts === false || $ts < $cutoff) {
        continue;
    }

    $matched++;

    $insertStmt->execute([
        'name' => $m['name'],
        'roundtrip_usec' => (int) round(((float) $m['rtt']) * 1000),
        'registration_datetime' => $ts->format('Y-m-d H:i:s'),
    ]);

    if ($insertStmt->rowCount() > 0) {
        $inserted++;
    }
}

fclose($handle);

echo date('Y-m-d H:i:s') . " collect_rtt: matched=$matched inserted=$inserted window={$windowMinutes}m\n";
