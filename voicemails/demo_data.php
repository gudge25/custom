<?php
// Demo fixture for voicemails/index.php, shown when FEATURE_VOICEMAILS is
// off. Shape matches a real voicemail row derived from `cdr` (lastapp =
// 'VoiceMail'): mailbox is `dst` with its "vmu" prefix already stripped,
// caller is `src`, and recordingfile mirrors the real
// in-<did>-<caller>-<timestamp>-<uniqueid>.WAV naming (empty string when no
// recording was captured, same as older real rows). Timestamps are relative
// to "now" so the report never looks stale.

$samples = [
    ['mailbox' => '8015', 'caller' => '447443519885', 'hoursAgo' => 1,   'duration_seconds' => 14, 'hasRecording' => true],
    ['mailbox' => '8034', 'caller' => '441138730530', 'hoursAgo' => 4,   'duration_seconds' => 10, 'hasRecording' => true],
    ['mailbox' => '8015', 'caller' => '447823859130', 'hoursAgo' => 9,   'duration_seconds' => 8,  'hasRecording' => false],
    ['mailbox' => '5972', 'caller' => '447443519885', 'hoursAgo' => 14,  'duration_seconds' => 17, 'hasRecording' => true],
    ['mailbox' => '8039', 'caller' => '447786311244', 'hoursAgo' => 20,  'duration_seconds' => 6,  'hasRecording' => true],
    ['mailbox' => '8034', 'caller' => '441138730530', 'hoursAgo' => 28,  'duration_seconds' => 21, 'hasRecording' => true],
    ['mailbox' => '8015', 'caller' => '442071832364', 'hoursAgo' => 33,  'duration_seconds' => 4,  'hasRecording' => false],
    ['mailbox' => '8039', 'caller' => '441473208498', 'hoursAgo' => 45,  'duration_seconds' => 30, 'hasRecording' => true],
    ['mailbox' => '5972', 'caller' => '447443519885', 'hoursAgo' => 58,  'duration_seconds' => 2,  'hasRecording' => false],
    ['mailbox' => '8034', 'caller' => '441943725540', 'hoursAgo' => 70,  'duration_seconds' => 7,  'hasRecording' => true],
    ['mailbox' => '8039', 'caller' => '447387671950', 'hoursAgo' => 96,  'duration_seconds' => 6,  'hasRecording' => true],
    ['mailbox' => '8034', 'caller' => '441138730530', 'hoursAgo' => 120, 'duration_seconds' => 26, 'hasRecording' => true],
];

return array_map(function (array $s): array {
    $calldate = strtotime("-{$s['hoursAgo']} hours");
    $did = '44203191' . $s['mailbox'];

    return [
        'date' => date('Y-m-d H:i:s', $calldate),
        'mailbox' => $s['mailbox'],
        'caller' => $s['caller'],
        'duration_seconds' => $s['duration_seconds'],
        'recordingfile' => $s['hasRecording']
            ? sprintf('in-%s-%s-%s-%d.%03d.WAV', $did, $s['caller'], date('Ymd-His', $calldate), $calldate, $s['hoursAgo'])
            : '',
    ];
}, $samples);
