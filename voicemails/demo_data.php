<?php
// Demo fixture for voicemails/index.php, shown when FEATURE_VOICEMAILS is
// off. Flat list of voicemail rows (mailbox, caller, duration, status);
// timestamps are relative to "now" so the report never looks stale.

$samples = [
    ['mailbox' => '201', 'caller' => '447911223344', 'hoursAgo' => 1,   'duration_seconds' => 38,  'status' => 'new'],
    ['mailbox' => '204', 'caller' => '447700900123', 'hoursAgo' => 4,   'duration_seconds' => 52,  'status' => 'new'],
    ['mailbox' => '201', 'caller' => '447811556677', 'hoursAgo' => 9,   'duration_seconds' => 21,  'status' => 'old'],
    ['mailbox' => '305', 'caller' => '447911998877', 'hoursAgo' => 14,  'duration_seconds' => 67,  'status' => 'new'],
    ['mailbox' => '308', 'caller' => '447700112233', 'hoursAgo' => 20,  'duration_seconds' => 15,  'status' => 'old'],
    ['mailbox' => '201', 'caller' => '447811334455', 'hoursAgo' => 28,  'duration_seconds' => 44,  'status' => 'old'],
    ['mailbox' => '412', 'caller' => '447911223344', 'hoursAgo' => 33,  'duration_seconds' => 29,  'status' => 'new'],
    ['mailbox' => '415', 'caller' => '447700900456', 'hoursAgo' => 45,  'duration_seconds' => 81,  'status' => 'old'],
    ['mailbox' => '305', 'caller' => '447811556699', 'hoursAgo' => 58,  'duration_seconds' => 36,  'status' => 'old'],
    ['mailbox' => '204', 'caller' => '447911998877', 'hoursAgo' => 70,  'duration_seconds' => 19,  'status' => 'old'],
    ['mailbox' => '201', 'caller' => '447700112233', 'hoursAgo' => 96,  'duration_seconds' => 58,  'status' => 'old'],
    ['mailbox' => '412', 'caller' => '447811334455', 'hoursAgo' => 120, 'duration_seconds' => 24,  'status' => 'old'],
];

return array_map(function (array $s): array {
    return [
        'mailbox' => $s['mailbox'],
        'caller' => $s['caller'],
        'date' => date('Y-m-d H:i:s', strtotime("-{$s['hoursAgo']} hours")),
        'duration_seconds' => $s['duration_seconds'],
        'status' => $s['status'],
    ];
}, $samples);
