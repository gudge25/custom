<?php
// Demo fixture for agent_latency/index.php, shown when FEATURE_AGENT_LATENCY
// is off. Shape matches a real `registrations` row exactly (name,
// roundtrip_usec, registration_datetime); timestamps are computed relative
// to "now" so the "last 7 days" window this page queries never goes stale.

$samples = [
    ['name' => 'Anton', 'hoursAgo' => 1,   'roundtrip_usec' => 41200],
    ['name' => 'Anton', 'hoursAgo' => 13,  'roundtrip_usec' => 43800],
    ['name' => 'Anton', 'hoursAgo' => 25,  'roundtrip_usec' => 39500],
    ['name' => 'Anton', 'hoursAgo' => 49,  'roundtrip_usec' => 46100],
    ['name' => 'Anton', 'hoursAgo' => 97,  'roundtrip_usec' => 44300],
    ['name' => 'Anton', 'hoursAgo' => 145, 'roundtrip_usec' => 40800],
    ['name' => 'Leo',   'hoursAgo' => 2,   'roundtrip_usec' => 63500],
    ['name' => 'Leo',   'hoursAgo' => 14,  'roundtrip_usec' => 59800],
    ['name' => 'Leo',   'hoursAgo' => 26,  'roundtrip_usec' => 66200],
    ['name' => 'Leo',   'hoursAgo' => 50,  'roundtrip_usec' => 61400],
    ['name' => 'Leo',   'hoursAgo' => 98,  'roundtrip_usec' => 64700],
    ['name' => 'Leo',   'hoursAgo' => 146, 'roundtrip_usec' => 60200],
    ['name' => 'Maria', 'hoursAgo' => 3,   'roundtrip_usec' => 35100],
    ['name' => 'Maria', 'hoursAgo' => 15,  'roundtrip_usec' => 37600],
    ['name' => 'Maria', 'hoursAgo' => 27,  'roundtrip_usec' => 33900],
    ['name' => 'Maria', 'hoursAgo' => 51,  'roundtrip_usec' => 38400],
    ['name' => 'Maria', 'hoursAgo' => 99,  'roundtrip_usec' => 36200],
    ['name' => 'Maria', 'hoursAgo' => 147, 'roundtrip_usec' => 34700],
];

return array_map(function (array $s): array {
    return [
        'name' => $s['name'],
        'roundtrip_usec' => $s['roundtrip_usec'],
        'registration_datetime' => date('Y-m-d H:i:s', strtotime("-{$s['hoursAgo']} hours")),
    ];
}, $samples);
