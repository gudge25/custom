<?php
require_once dirname(__DIR__) . '/bootstrap.php';

/**
 * Aggregate a flat list of voicemail rows into the KPI values this page
 * shows - used for both real DB rows and the demo fixture, so the two can
 * never drift apart.
 */
function computeVoicemailMetrics(array $rows): array
{
    $total = count($rows);
    $withRecording = count(array_filter($rows, fn($r) => $r['recordingfile'] !== ''));
    $totalSeconds = array_sum(array_column($rows, 'duration_seconds'));
    $avgSeconds = $total ? (int) round($totalSeconds / $total) : 0;

    $byMailbox = [];
    foreach ($rows as $r) {
        $byMailbox[$r['mailbox']] = ($byMailbox[$r['mailbox']] ?? 0) + 1;
    }
    $busiestMailbox = null;
    $busiestCount = 0;
    foreach ($byMailbox as $mailbox => $count) {
        if ($count > $busiestCount) {
            $busiestMailbox = $mailbox;
            $busiestCount = $count;
        }
    }

    return [
        'total' => $total,
        'withRecording' => $withRecording,
        'avgDuration' => sprintf('%d:%02d', intdiv($avgSeconds, 60), $avgSeconds % 60),
        'busiestMailbox' => $busiestMailbox,
        'busiestCount' => $busiestCount,
    ];
}

$pageName = 'Voicemails Report';
$isDemo = false;
$error = '';
$rows = [];

if (envEnabled('FEATURE_VOICEMAILS')) {
    // Voicemails aren't a separate table - FreePBX/Asterisk logs each one as
    // a `cdr` row with lastapp = 'VoiceMail' and dst = "vmu<mailbox>". CDR
    // has no listened/unheard flag (that lives in Asterisk's voicemail
    // spool, not this DB), so "has a recording file" stands in as the one
    // real signal available here.
    try {
        $pdo = db();
        $stmt = $pdo->query("
            SELECT calldate, dst, src AS caller, duration AS duration_seconds, recordingfile
            FROM cdr
            WHERE lastapp = 'VoiceMail'
            ORDER BY calldate DESC
            LIMIT 500
        ");
        foreach ($stmt->fetchAll() as $row) {
            $rows[] = [
                'date' => $row['calldate'],
                'mailbox' => preg_replace('/^vmu/', '', (string) $row['dst']),
                'caller' => (string) $row['caller'],
                'duration_seconds' => (int) $row['duration_seconds'],
                'recordingfile' => (string) $row['recordingfile'],
            ];
        }
    } catch (PDOException $e) {
        $error = env('APP_ENV') !== 'production'
            ? 'Query error: ' . $e->getMessage()
            : 'Something went wrong loading voicemails. Please try again or contact support.';
    }
} else {
    $demoFile = __DIR__ . '/demo_data.php';

    if (!file_exists($demoFile)) {
        echo "<div style='padding:40px;text-align:center'>
                <h2>🚫 Voicemails Report Disabled</h2>
                <p>Contact <b>Gixo</b></p>
              </div>";
        exit;
    }

    $rows = require $demoFile;
    $isDemo = true;
}

$metrics = computeVoicemailMetrics($rows);
$rowsJson = json_encode($rows);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageName); ?></title>

    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        body {
            background: radial-gradient(circle at top, #283c86 0, #0a0f1f 45%, #050814 100%);
            color: #e5e7eb;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "SF Pro Text", sans-serif;
        }
        .card {
            border-radius: 1rem;
            background: rgba(15,23,42,0.9);
            border: 1px solid rgba(148,163,184,0.1);
            box-shadow: 0 18px 40px rgba(0,0,0,0.55);
        }
        .chip {
            border-radius: 999px;
            padding: 0.1rem 0.55rem;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }
        .status-badge {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 2px 9px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-yes { color: #4ade80; background: rgba(34,197,94,0.12); border: 1px solid rgba(34,197,94,0.3); }
        .status-no { color: #94a3b8; background: rgba(148,163,184,0.12); border: 1px solid rgba(148,163,184,0.3); }
        .table-container {
            max-height: 480px;
            overflow: auto;
        }
        ::-webkit-scrollbar { width: 7px; height: 7px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #4b5563; border-radius: 999px; }
    </style>
</head>
<body>
<div class="min-h-screen px-6 py-5">
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center space-x-3">
            <div class="w-9 h-9 rounded-xl bg-sky-500/80 flex items-center justify-center">
                <span class="text-white text-xl font-semibold">📬</span>
            </div>
            <div>
                <h1 class="text-xl font-semibold text-slate-50">Voicemails Report</h1>
                <p class="text-xs text-slate-400">Voicemail activity overview</p>
            </div>
            <?php if ($isDemo): ?>
                <div class="chip bg-amber-900/60 text-amber-300 border border-amber-500/40">Demo Data</div>
            <?php endif; ?>
        </div>
        <a href="../index.php" class="text-sm text-sky-300 hover:text-sky-200">← Back to Home</a>
    </div>

    <?php if ($error !== ''): ?>
        <div class="mb-4 max-w-5xl rounded-lg border border-rose-500/40 bg-rose-500/10 px-3 py-2 text-sm text-rose-200">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($rows)): ?>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="card p-4">
                <div class="text-xs text-slate-400 uppercase tracking-widest mb-2">Total Voicemails</div>
                <div class="text-3xl font-semibold text-slate-50"><?php echo $metrics['total']; ?></div>
            </div>
            <div class="card p-4">
                <div class="text-xs text-slate-400 uppercase tracking-widest mb-2">With Recording</div>
                <div class="text-3xl font-semibold text-emerald-400"><?php echo $metrics['withRecording']; ?></div>
            </div>
            <div class="card p-4">
                <div class="text-xs text-slate-400 uppercase tracking-widest mb-2">Avg Duration</div>
                <div class="text-3xl font-semibold text-slate-50"><?php echo htmlspecialchars($metrics['avgDuration']); ?></div>
            </div>
            <div class="card p-4">
                <div class="text-xs text-slate-400 uppercase tracking-widest mb-2">Busiest Mailbox</div>
                <div class="text-3xl font-semibold text-slate-50">
                    <?php echo $metrics['busiestMailbox'] !== null ? htmlspecialchars($metrics['busiestMailbox']) : '—'; ?>
                </div>
                <div class="text-xs text-slate-400 mt-1"><?php echo $metrics['busiestCount']; ?> voicemails</div>
            </div>
        </div>

        <div class="card p-4">
            <div class="flex items-center justify-between mb-3">
                <div class="text-sm font-medium text-slate-100">All Voicemails</div>
                <input id="searchInput" type="text"
                       class="bg-slate-900/70 border border-slate-600/60 rounded-lg px-3 py-1.5 text-xs text-slate-100 focus:outline-none focus:ring-1 focus:ring-sky-500"
                       placeholder="Search mailbox / caller...">
            </div>
            <div class="table-container">
                <table class="w-full text-xs text-left">
                    <thead class="sticky top-0 bg-slate-900/90 backdrop-blur border-b border-slate-700/60">
                    <tr>
                        <th class="py-2 px-3 text-slate-400 font-medium">Date/Time</th>
                        <th class="py-2 px-3 text-slate-400 font-medium">Mailbox</th>
                        <th class="py-2 px-3 text-slate-400 font-medium">Caller</th>
                        <th class="py-2 px-3 text-slate-400 font-medium">Duration</th>
                        <th class="py-2 px-3 text-slate-400 font-medium">Recording</th>
                    </tr>
                    </thead>
                    <tbody id="tableBody"></tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php if (!empty($rows)): ?>
<script>
    const rows = <?php echo $rowsJson; ?>;

    function formatDuration(seconds) {
        const m = Math.floor(seconds / 60);
        const s = seconds % 60;
        return m + ':' + String(s).padStart(2, '0');
    }

    function cell(tag, text, className) {
        const el = document.createElement(tag);
        if (className) el.className = className;
        el.textContent = text;
        return el;
    }

    const tbody = document.getElementById('tableBody');
    const searchInput = document.getElementById('searchInput');

    function renderTable(filter = '') {
        tbody.innerHTML = '';
        const f = filter.toLowerCase();

        rows
            .filter(r => {
                if (!f) return true;
                return String(r.mailbox).toLowerCase().includes(f) || String(r.caller).toLowerCase().includes(f);
            })
            .forEach(r => {
                const tr = document.createElement('tr');
                tr.className = 'border-b border-slate-800/60 hover:bg-slate-800/60';
                tr.appendChild(cell('td', r.date, 'py-1.5 px-3 text-slate-200'));
                tr.appendChild(cell('td', r.mailbox, 'py-1.5 px-3 text-slate-300'));
                tr.appendChild(cell('td', r.caller, 'py-1.5 px-3 text-slate-300'));
                tr.appendChild(cell('td', formatDuration(r.duration_seconds), 'py-1.5 px-3 text-slate-300'));

                const hasRecording = r.recordingfile !== '';
                const statusTd = document.createElement('td');
                statusTd.className = 'py-1.5 px-3';
                statusTd.appendChild(cell('span', hasRecording ? 'Yes' : 'No', `status-badge status-${hasRecording ? 'yes' : 'no'}`));
                tr.appendChild(statusTd);

                tbody.appendChild(tr);
            });
    }

    renderTable();
    searchInput.addEventListener('input', (e) => renderTable(e.target.value));
</script>
<?php endif; ?>
</body>
</html>
