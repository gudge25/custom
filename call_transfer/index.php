<?php
require_once dirname(__DIR__) . '/bootstrap.php';

requireFeature('FEATURE_CALL_TRANSFER', 'Call Transfer Report');

function formatDuration(int $totalSeconds): string
{
    $hours = intdiv($totalSeconds, 3600);
    $minutes = intdiv($totalSeconds % 3600, 60);
    $seconds = $totalSeconds % 60;

    return sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
}

$pageName = 'Call Transfer Report';
$summaryData = [];
$mainData = [];
$error = '';
$debug = '';
$showDebug = env('APP_ENV') !== 'production';

$fromDate = trim((string) ($_POST['from'] ?? date('Y-m-d', strtotime('-6 days'))));
$toDate = trim((string) ($_POST['to'] ?? date('Y-m-d')));

$debug .= "Requested range: From = $fromDate, To = $toDate\n";

$datePattern = '/^\d{4}-\d{2}-\d{2}$/';

if (!preg_match($datePattern, $fromDate) || !preg_match($datePattern, $toDate)) {
    $error = 'Invalid date format. Please use the date picker.';
    $debug .= "Date validation failed\n";
} else {
    $summaryQuery = "
        select
            substring(cdr.clid, 3, 4) as acct,
            sec_to_time(sum(cdr.duration)) as total_patchtime,
            sum(cdr.duration) as total_seconds
        from cdr cdr
        where cast(cdr.calldate as date) between :from_date and :to_date
          and cdr.accountcode = 'Outbound'
          and (cdr.dst not in ('*80','*89','*50') and char_length(cdr.dst) = 3)
          and char_length(cdr.src) != 3
          and left(cdr.dstchannel, 3) <> 'PJSIP'
        group by acct
        order by acct
    ";

    $mainQuery = "
        select
            cdr.calldate,
            substring(cdr.clid, 3, 4) as acct,
            cdr.src as caller,
            cdr.dst as ext,
            sec_to_time(cdr.duration) as patchtime,
            cdr.duration as duration_seconds,
            cdr.uniqueid,
            cdr.lastapp,
            cdr.linkedid
        from cdr cdr
        where cast(cdr.calldate as date) between :from_date and :to_date
          and cdr.accountcode = 'Outbound'
          and (cdr.dst not in ('*80','*89','*50') and char_length(cdr.dst) = 3)
          and char_length(cdr.src) != 3
          and left(cdr.dstchannel, 3) <> 'PJSIP'
        order by cdr.calldate
    ";

    $debug .= "Using database: " . env('DB_NAME', 'not set') . "\n";
    $debug .= "Running prepared queries with from_date = $fromDate and to_date = $toDate\n";

    try {
        $pdo = db();
        $debug .= "Database connection successful\n";

        $summaryStmt = $pdo->prepare($summaryQuery);
        $summaryStmt->execute([
            'from_date' => $fromDate,
            'to_date' => $toDate,
        ]);
        $summaryData = $summaryStmt->fetchAll();
        $debug .= 'Summary query executed successfully. Rows found: ' . count($summaryData) . "\n";

        $mainStmt = $pdo->prepare($mainQuery);
        $mainStmt->execute([
            'from_date' => $fromDate,
            'to_date' => $toDate,
        ]);
        $mainData = $mainStmt->fetchAll();
        $debug .= 'Main query executed successfully. Rows found: ' . count($mainData) . "\n";
    } catch (PDOException $e) {
        $error = $showDebug
            ? 'Query error: ' . $e->getMessage()
            : 'Something went wrong loading the report. Please try again or contact support.';
        $debug .= 'Query failed: ' . $e->getMessage() . "\n";
    }
}

$totalTransfers = count($mainData);
$totalSeconds = array_sum(array_column($mainData, 'duration_seconds'));
$maxAccountSeconds = $summaryData ? max(array_column($summaryData, 'total_seconds')) : 0;

$isTodayPreset = $fromDate === date('Y-m-d') && $toDate === date('Y-m-d');
$isLast7Preset = $fromDate === date('Y-m-d', strtotime('-6 days')) && $toDate === date('Y-m-d');
$isMonthPreset = $fromDate === date('Y-m-01') && $toDate === date('Y-m-d');
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
        .input {
            width: 100%;
            border-radius: 0.75rem;
            border: 1px solid rgba(148,163,184,0.25);
            background: rgba(15,23,42,0.8);
            color: #e2e8f0;
            padding: 0.65rem 0.8rem;
            outline: none;
        }
        .input:focus {
            border-color: rgba(14,165,233,0.8);
            box-shadow: 0 0 0 2px rgba(14,165,233,0.25);
        }
        .btn {
            border-radius: 0.75rem;
            border: 1px solid rgba(56,189,248,0.4);
            background: rgba(2,132,199,0.25);
            color: #e0f2fe;
            padding: 0.65rem 1rem;
            font-weight: 600;
            transition: 0.2s;
        }
        .btn:hover {
            background: rgba(3,105,161,0.45);
        }
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .preset {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            border: 1px solid rgba(148,163,184,0.2);
            background: rgba(15,23,42,0.6);
            color: #cbd5e1;
            padding: 0.3rem 0.75rem;
            font-size: 0.75rem;
            text-decoration: none;
        }
        .preset:hover {
            color: #e0f2fe;
        }
        .preset-active {
            border-color: rgba(56,189,248,0.5);
            background: rgba(2,132,199,0.25);
            color: #e0f2fe;
        }
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 1rem;
        }
        .kpi-label {
            font-size: 0.68rem;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }
        .kpi-value {
            font-size: 1.5rem;
            font-weight: 600;
            color: #f1f5f9;
            margin-top: 0.35rem;
        }
        .bar-track {
            width: 100%;
            height: 6px;
            border-radius: 999px;
            background: rgba(71,85,105,0.3);
            overflow: hidden;
        }
        .bar-fill {
            height: 100%;
            border-radius: 999px;
            background: #38bdf8;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        th, td {
            padding: 0.6rem 0.5rem;
            border: 1px solid rgba(71,85,105,0.45);
            text-align: left;
        }
        th {
            color: #93c5fd;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            background: #0c1424;
            position: sticky;
            top: 0;
        }
        td {
            color: #e2e8f0;
        }
        tbody tr:hover td {
            background: rgba(56,189,248,0.06);
        }
        .mono {
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            color: #94a3b8;
            font-size: 0.75rem;
        }
        .table-container {
            max-height: 400px;
            overflow-y: auto;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
<div class="min-h-screen px-6 py-5">
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center space-x-3">
            <div class="w-9 h-9 rounded-xl bg-sky-500/80 flex items-center justify-center">
                <span class="text-white text-xl font-semibold">🔁</span>
            </div>
            <div>
                <h1 class="text-xl font-semibold text-slate-50">Call Transfer Report</h1>
                <p class="text-xs text-slate-400">Outbound call transfer analysis</p>
            </div>
        </div>
        <div class="text-right">
            <div class="text-xs text-slate-400 uppercase tracking-widest">Today</div>
            <div class="text-sm text-slate-100"><?php echo date('Y-m-d'); ?></div>
        </div>
    </div>

    <div class="max-w-6xl mx-auto">
        <div class="card p-6 mb-6">
            <div class="flex gap-2 mb-4">
                <button type="button" class="preset<?php echo $isTodayPreset ? ' preset-active' : ''; ?>" onclick="return applyPreset(event, 0)">Today</button>
                <button type="button" class="preset<?php echo $isLast7Preset ? ' preset-active' : ''; ?>" onclick="return applyPreset(event, 6)">Last 7 Days</button>
                <button type="button" class="preset<?php echo $isMonthPreset ? ' preset-active' : ''; ?>" onclick="return applyMonthPreset(event)">This Month</button>
            </div>

            <form method="post" id="dateForm" class="grid grid-cols-1 md:grid-cols-3 gap-4" onsubmit="return onQuerySubmit()">
                <div>
                    <label for="from" class="block text-sm text-slate-300 mb-1.5">From Date</label>
                    <input
                        type="date"
                        name="from"
                        id="from"
                        min="2021-01-01"
                        max="2031-01-01"
                        value="<?php echo htmlspecialchars($fromDate); ?>"
                        class="input"
                        required
                    >
                </div>

                <div>
                    <label for="to" class="block text-sm text-slate-300 mb-1.5">To Date</label>
                    <input
                        type="date"
                        name="to"
                        id="to"
                        min="2021-01-01"
                        max="2031-01-01"
                        value="<?php echo htmlspecialchars($toDate); ?>"
                        class="input"
                        required
                    >
                </div>

                <div class="flex items-end">
                    <button type="submit" id="queryBtn" class="btn w-full">Query Data</button>
                </div>
            </form>
        </div>

        <?php if ($error !== ''): ?>
            <div class="mb-4 max-w-6xl rounded-lg border border-rose-500/40 bg-rose-500/10 px-3 py-2 text-sm text-rose-200">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($showDebug && $debug !== ''): ?>
            <div class="mb-4 max-w-6xl rounded-lg border border-amber-500/40 bg-amber-500/10 px-3 py-2 text-xs text-amber-200">
                <h4 class="font-semibold mb-2">Debug Information:</h4>
                <pre class="whitespace-pre-wrap"><?php echo htmlspecialchars($debug); ?></pre>
            </div>
        <?php endif; ?>

        <?php if ($error === '' && empty($mainData)): ?>
            <div class="card p-6 mb-6 text-sm text-slate-400">
                No transfers found for the selected date range.
            </div>
        <?php endif; ?>

        <?php if (!empty($mainData)): ?>
            <div class="kpi-grid mb-6">
                <div class="card p-4">
                    <div class="kpi-label">Transfers</div>
                    <div class="kpi-value"><?php echo $totalTransfers; ?></div>
                </div>
                <div class="card p-4">
                    <div class="kpi-label">Total Patch Time</div>
                    <div class="kpi-value"><?php echo formatDuration($totalSeconds); ?></div>
                </div>
                <div class="card p-4">
                    <div class="kpi-label">Accounts</div>
                    <div class="kpi-value"><?php echo count($summaryData); ?></div>
                </div>
                <div class="card p-4">
                    <div class="kpi-label">Range</div>
                    <div class="kpi-value" style="font-size: 1.05rem;">
                        <?php echo date('M j', strtotime($fromDate)) . ' – ' . date('M j', strtotime($toDate)); ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($summaryData)): ?>
            <div class="card p-6 mb-6">
                <h3 class="text-lg font-semibold text-slate-100 mb-4">Total Duration per Account</h3>
                <div class="overflow-x-auto">
                    <table>
                        <thead>
                            <tr>
                                <th>Account</th>
                                <th>Relative</th>
                                <th>Total Patch Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($summaryData as $row): ?>
                                <?php $barWidth = $maxAccountSeconds > 0 ? round(($row['total_seconds'] / $maxAccountSeconds) * 100) : 0; ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['acct']); ?></td>
                                    <td><div class="bar-track"><div class="bar-fill" style="width: <?php echo $barWidth; ?>%;"></div></div></td>
                                    <td><?php echo htmlspecialchars($row['total_patchtime']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($mainData)): ?>
            <div class="card p-6">
                <h3 class="text-lg font-semibold text-slate-100 mb-4">Call Transfer Details</h3>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Extension</th>
                                <th>Date</th>
                                <th>Account</th>
                                <th>Caller</th>
                                <th>Patch Time</th>
                                <th>Unique ID</th>
                                <th>Last App</th>
                                <th>Linked ID</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($mainData as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['ext']); ?></td>
                                    <td><?php echo htmlspecialchars($row['calldate']); ?></td>
                                    <td><?php echo htmlspecialchars($row['acct']); ?></td>
                                    <td><?php echo htmlspecialchars($row['caller']); ?></td>
                                    <td><?php echo htmlspecialchars($row['patchtime']); ?></td>
                                    <td class="mono"><?php echo htmlspecialchars($row['uniqueid']); ?></td>
                                    <td><?php echo htmlspecialchars($row['lastapp']); ?></td>
                                    <td class="mono"><?php echo htmlspecialchars($row['linkedid']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <div class="mt-6 text-center">
            <a href="../index.php" class="text-sm text-sky-300 hover:text-sky-200">← Back to Home</a>
        </div>
    </div>
</div>

<script>
function pad(n) { return n < 10 ? '0' + n : '' + n; }
function toDateStr(d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); }

function applyPreset(event, daysBack) {
    event.preventDefault();
    var to = new Date();
    var from = new Date();
    from.setDate(to.getDate() - daysBack);
    document.getElementById('from').value = toDateStr(from);
    document.getElementById('to').value = toDateStr(to);
    document.getElementById('dateForm').submit();
    return false;
}

function applyMonthPreset(event) {
    event.preventDefault();
    var to = new Date();
    var from = new Date(to.getFullYear(), to.getMonth(), 1);
    document.getElementById('from').value = toDateStr(from);
    document.getElementById('to').value = toDateStr(to);
    document.getElementById('dateForm').submit();
    return false;
}

function onQuerySubmit() {
    var btn = document.getElementById('queryBtn');
    btn.disabled = true;
    btn.textContent = 'Querying…';
    return true;
}
</script>
</body>
</html>
