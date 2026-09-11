<?php
// Demo fixture for call_transfer/index.php, shown when FEATURE_CALL_TRANSFER
// is off. Raw rows shaped like the real `cdr`-derived main query (calldate,
// acct, caller, ext, duration_seconds, uniqueid, lastapp, linkedid) - patchtime
// strings and the per-account summary are derived from these the same way
// the live path derives them from SQL rows. Timestamps are relative to "now"
// so the report never looks stale.

$samples = [
    ['acct' => '1001', 'caller' => '447911223344', 'ext' => '201', 'hoursAgo' => 2,   'duration_seconds' => 184, 'lastapp' => 'Dial'],
    ['acct' => '1001', 'caller' => '447700900123', 'ext' => '204', 'hoursAgo' => 5,   'duration_seconds' => 96,  'lastapp' => 'Dial'],
    ['acct' => '1001', 'caller' => '447811556677', 'ext' => '201', 'hoursAgo' => 27,  'duration_seconds' => 302, 'lastapp' => 'Dial'],
    ['acct' => '1002', 'caller' => '447911998877', 'ext' => '305', 'hoursAgo' => 8,   'duration_seconds' => 145, 'lastapp' => 'Dial'],
    ['acct' => '1002', 'caller' => '447700112233', 'ext' => '308', 'hoursAgo' => 30,  'duration_seconds' => 211, 'lastapp' => 'Dial'],
    ['acct' => '1002', 'caller' => '447811334455', 'ext' => '305', 'hoursAgo' => 52,  'duration_seconds' => 78,  'lastapp' => 'Dial'],
    ['acct' => '2001', 'caller' => '447911223344', 'ext' => '412', 'hoursAgo' => 12,  'duration_seconds' => 420, 'lastapp' => 'Dial'],
    ['acct' => '2001', 'caller' => '447700900456', 'ext' => '415', 'hoursAgo' => 34,  'duration_seconds' => 63,  'lastapp' => 'Dial'],
    ['acct' => '2001', 'caller' => '447811556699', 'ext' => '412', 'hoursAgo' => 76,  'duration_seconds' => 189, 'lastapp' => 'Dial'],
    ['acct' => '1001', 'caller' => '447911998877', 'ext' => '204', 'hoursAgo' => 100, 'duration_seconds' => 267, 'lastapp' => 'Dial'],
    ['acct' => '1002', 'caller' => '447700112233', 'ext' => '308', 'hoursAgo' => 124, 'duration_seconds' => 133, 'lastapp' => 'Dial'],
    ['acct' => '2001', 'caller' => '447811334455', 'ext' => '415', 'hoursAgo' => 140, 'duration_seconds' => 355, 'lastapp' => 'Dial'],
];

return array_map(function (array $s): array {
    $calldate = strtotime("-{$s['hoursAgo']} hours");
    $uniqueid = sprintf('%d.%03d', $calldate, $s['hoursAgo']);

    return [
        'calldate' => date('Y-m-d H:i:s', $calldate),
        'acct' => $s['acct'],
        'caller' => $s['caller'],
        'ext' => $s['ext'],
        'duration_seconds' => $s['duration_seconds'],
        'uniqueid' => $uniqueid,
        'lastapp' => $s['lastapp'],
        'linkedid' => $uniqueid,
    ];
}, $samples);
