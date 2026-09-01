<?php
/***********************
 * DB CONFIG
 ***********************/
$dbHost = 'localhost';
$dbName = 'asteriskcdrdb';
$dbUser = 'root';
$dbPass = '';

$topRows = [];
$nameOptions = [];
$chartPoints = [];
$chartLabels = [];
$errorMessage = '';
$selectedName = '';

try {
    $dsn = "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];
    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);

    $topStmt = $pdo->query(
        "SELECT name, ROUND(AVG(roundtrip_usec)/1000) AS avg_roundtrip
         FROM registrations
         WHERE registration_datetime >= NOW() - INTERVAL 7 DAY
         GROUP BY name
         ORDER BY avg_roundtrip DESC
         LIMIT 5"
    );
    $topRows = $topStmt->fetchAll();

    $nameStmt = $pdo->query("SELECT DISTINCT name FROM registrations ORDER BY name ASC");
    $nameOptions = $nameStmt->fetchAll(PDO::FETCH_COLUMN);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $selectedName = trim($_POST['name'] ?? '');
    }

    if ($selectedName === '' && count($nameOptions) > 0) {
        $selectedName = (string)$nameOptions[0];
    }

    if ($selectedName !== '') {
        $seriesStmt = $pdo->prepare(
            "SELECT roundtrip_usec, registration_datetime
             FROM registrations
             WHERE name = :name
               AND registration_datetime >= DATE_SUB(NOW(), INTERVAL 7 DAY)
             ORDER BY registration_datetime ASC"
        );
        $seriesStmt->execute(['name' => $selectedName]);
        $seriesRows = $seriesStmt->fetchAll();

        foreach ($seriesRows as $row) {
            $chartPoints[] = round(((float)$row['roundtrip_usec']) / 1000, 2);
            $chartLabels[] = $row['registration_datetime'];

        }
    }
} catch (Throwable $e) {
    $errorMessage = 'Failed to load latency data. Please check DB connection/settings.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voice Agent Latency Report</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 0.6rem 0.5rem;
            border-bottom: 1px solid rgba(71,85,105,0.45);
            text-align: left;
        }
        th {
            color: #93c5fd;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
    </style>
</head>
<body>
<div class="min-h-screen px-6 py-5">
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center space-x-3">
            <div class="w-9 h-9 rounded-xl bg-sky-500/80 flex items-center justify-center">
                <span class="text-white text-xl font-semibold">⚡</span>
            </div>
            <div>
                <h1 class="text-xl font-semibold text-slate-50">Voice Agent Latency Report</h1>
                <p class="text-xs text-slate-400">Registration latency monitoring</p>
            </div>
        </div>
        <div class="text-right">
            <div class="text-xs text-slate-400 uppercase tracking-widest">Today</div>
            <div class="text-sm text-slate-100"><?php echo date('Y-m-d'); ?></div>
        </div>
    </div>

    <?php if ($errorMessage !== ''): ?>
        <div class="mb-4 max-w-5xl rounded-lg border border-rose-500/40 bg-rose-500/10 px-3 py-2 text-sm text-rose-200">
            <?php echo htmlspecialchars($errorMessage); ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 xl:grid-cols-5 gap-4 w-full max-w-none">
        <div class="xl:col-span-2 card p-5">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-slate-100 text-lg font-semibold">Top Average (3 Days)</h2>
                <div class="chip bg-sky-900/60 text-sky-300 border border-sky-500/40">ms</div>
            </div>

            <?php if (count($topRows) === 0): ?>
                <p class="text-sm text-slate-400">No latency data found.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Average Roundtrip</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($topRows as $row): ?>
                        <tr>
                            <td class="text-slate-200"><?php echo htmlspecialchars((string)$row['name']); ?></td>
                            <td class="text-slate-300"><?php echo htmlspecialchars((string)$row['avg_roundtrip']); ?> ms</td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <form method="post" class="mt-4 space-y-3">
                <label for="name" class="block text-sm text-slate-300">Select a name</label>
                <select id="name" name="name" class="input">
                    <?php foreach ($nameOptions as $name): ?>
                        <?php $name = (string)$name; ?>
                        <option value="<?php echo htmlspecialchars($name); ?>" <?php echo ($name === $selectedName) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn">Show Last 7 Days</button>
            </form>
        </div>

        <div class="xl:col-span-3 card p-5">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-slate-100 text-lg font-semibold">Latency Trend (7 Days)</h2>
                <div class="chip bg-indigo-900/60 text-indigo-300 border border-indigo-500/40">
                    <?php echo htmlspecialchars($selectedName ?: 'No Selection'); ?>
                </div>
            </div>

            <?php if (count($chartPoints) === 0): ?>
                <p class="text-sm text-slate-400">No chart data for selected name.</p>
            <?php else: ?>
                <canvas id="latencyChart" height="120"></canvas>
            <?php endif; ?>

            <div class="pt-4">
                <a href="../index.php" class="text-sm text-sky-300 hover:text-sky-200">Back to Home</a>
            </div>
        </div>
    </div>
</div>

<?php if (count($chartPoints) > 0): ?>
<script>
const points = <?php echo json_encode($chartPoints); ?>;
const labels = <?php echo json_encode($chartLabels); ?>;
const ctx = document.getElementById('latencyChart').getContext('2d');

new Chart(ctx, {
    type: 'line',
    data: {
        labels,
        datasets: [{
            label: 'Roundtrip (ms)',
            data: points,
            borderColor: 'rgba(56, 189, 248, 1)',
            backgroundColor: 'rgba(56, 189, 248, 0.2)',
            borderWidth: 2,
            tension: 0.25,
            pointRadius: 2
        }]
    },
    options: {
        plugins: {
            legend: { labels: { color: '#cbd5e1' } }
        },
        scales: {
            x: { ticks: { color: '#94a3b8' }, grid: { color: 'rgba(51,65,85,0.35)' } },
            y: { beginAtZero: true, ticks: { color: '#94a3b8' }, grid: { color: 'rgba(51,65,85,0.35)' } }
        }
    }
});
</script>
<?php endif; ?>
</body>
</html>
