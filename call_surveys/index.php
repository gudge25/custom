<?php
// Set timezone to avoid warnings
date_default_timezone_set('UTC');

/***********************
 * DB CONFIG
 ***********************/
$dbHost = 'localhost';
$dbName = 'asteriskcdrdb';
$dbUser = 'root';
$dbPass = '';

$dsn = "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];
$pdo = new PDO($dsn, $dbUser, $dbPass, $options);

/***********************
 * RAW DATA
 ***********************/
$rows = $pdo->query("
    SELECT num, operator, queue, valuation, date
    FROM survey
    ORDER BY date DESC
")->fetchAll();

/***********************
 * SUMMARY METRICS
 ***********************/
$totalSurveys = count($rows);

$avgRating = $totalSurveys
    ? $pdo->query("SELECT AVG(valuation) FROM survey")->fetchColumn()
    : 0;

$today = date('Y-m-d');
$totalToday = $pdo->query("
    SELECT COUNT(*) FROM survey WHERE DATE(date) = '$today'
")->fetchColumn();

$topAgent = $pdo->query("
    SELECT operator, AVG(valuation) AS avgscore, COUNT(*) AS total
    FROM survey
    GROUP BY operator
    ORDER BY avgscore DESC, total DESC
    LIMIT 1
")->fetch();

$highScore = 4; // 1–5 шкала, “задоволений” = 4 або 5
$highCount = $pdo->query("
    SELECT COUNT(*) FROM survey WHERE valuation >= $highScore
")->fetchColumn();
$highPercent = $totalSurveys ? round($highCount / $totalSurveys * 100) : 0;

/***********************
 * CHART DATA
 ***********************/

// Rating distribution
$ratingDist = [];
$stmt = $pdo->query("
    SELECT valuation AS rating, COUNT(*) AS cnt
    FROM survey
    GROUP BY valuation
    ORDER BY valuation
");
while ($r = $stmt->fetch()) {
    $ratingDist[] = $r;
}

// Trend over time (by day)
$trend = [];
$stmt = $pdo->query("
    SELECT DATE(date) AS day, AVG(valuation) AS avgscore, COUNT(*) AS total
    FROM survey
    GROUP BY day
    ORDER BY day
");
while ($r = $stmt->fetch()) {
    $trend[] = $r;
}

// Top agents
$topAgents = [];
$stmt = $pdo->query("
    SELECT operator, AVG(valuation) AS avgscore, COUNT(*) AS total
    FROM survey
    GROUP BY operator
    ORDER BY avgscore DESC, total DESC
    LIMIT 6
");
while ($r = $stmt->fetch()) {
    $topAgents[] = $r;
}

// Prepare data for JS
$ratingDistJson = json_encode($ratingDist);
$trendJson       = json_encode($trend);
$topAgentsJson   = json_encode($topAgents);
$rowsJson        = json_encode($rows);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Call Surveys Dashboard</title>

    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Chart.js -->
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
        .rating-badge {
            min-width: 2.1rem;
            text-align: center;
            border-radius: 999px;
            font-weight: 600;
            padding: 0.15rem 0.5rem;
        }
        .rating-1 { background:#4b5563; }
        .rating-2 { background:#f97316; }
        .rating-3 { background:#eab308; }
        .rating-4 { background:#22c55e; }
        .rating-5 { background:#16a34a; }
        .table-container {
            max-height: 480px;
            overflow: auto;
        }
        ::-webkit-scrollbar {
            width: 7px;
            height: 7px;
        }
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        ::-webkit-scrollbar-thumb {
            background: #4b5563;
            border-radius: 999px;
        }
    </style>
</head>
<body>
<div class="min-h-screen px-6 py-5">
    <!-- Top bar -->
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center space-x-3">
            <div class="w-9 h-9 rounded-xl bg-sky-500/80 flex items-center justify-center">
                <span class="text-white text-xl font-semibold">☎</span>
            </div>
            <div>
                <h1 class="text-xl font-semibold text-slate-50">Call Surveys</h1>
                <p class="text-xs text-slate-400">Manager dashboard</p>
            </div>
        </div>
        <a href="index.php" class="back-link">← Back to Home</a>
        <div class="text-right">
            <div class="text-xs text-slate-400 uppercase tracking-widest">Today</div>
            <div class="text-sm text-slate-100">
                <?php echo date('Y-m-d'); ?>
            </div>
        </div>
    </div>

    <!-- KPI cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="card p-4 flex flex-col justify-between">
            <div class="flex items-center justify-between mb-3">
                <div class="text-xs text-slate-400 uppercase tracking-widest">Average rating</div>
                <div class="chip bg-emerald-900/60 text-emerald-300 border border-emerald-500/40">
                    1–5 scale
                </div>
            </div>
            <div class="flex items-end justify-between">
                <div class="text-3xl font-semibold text-slate-50">
                    <?php echo number_format($avgRating, 2); ?>
                </div>
                <div class="text-xs text-slate-400">
                    Based on <?php echo $totalSurveys; ?> surveys
                </div>
            </div>
        </div>

        <div class="card p-4 flex flex-col justify-between">
            <div class="flex items-center justify-between mb-3">
                <div class="text-xs text-slate-400 uppercase tracking-widest">Total surveys today</div>
                <div class="chip bg-sky-900/60 text-sky-300 border border-sky-500/40">
                    Updated <?php echo date('H:i'); ?>
                </div>
            </div>
            <div class="flex items-end justify-between">
                <div class="text-3xl font-semibold text-slate-50"><?php echo $totalToday; ?></div>
                <div class="text-xs text-slate-400">Calls rated today</div>
            </div>
        </div>

        <div class="card p-4 flex flex-col justify-between">
            <div class="flex items-center justify-between mb-3">
                <div class="text-xs text-slate-400 uppercase tracking-widest">Top agent</div>
                <div class="chip bg-indigo-900/60 text-indigo-300 border border-indigo-500/40">
                    Best average
                </div>
            </div>
            <div class="flex items-end justify-between">
                <div>
                    <div class="text-lg font-semibold text-slate-50">
                        <?php echo $topAgent ? htmlspecialchars($topAgent['operator']) : '—'; ?>
                    </div>
                    <div class="text-xs text-slate-400">
                        Avg rating:
                        <?php echo $topAgent ? number_format($topAgent['avgscore'], 2) : 'n/a'; ?>
                    </div>
                </div>
                <div class="text-xs text-slate-400">
                    <?php echo $topAgent ? $topAgent['total'] : 0; ?> surveys
                </div>
            </div>
        </div>

        <div class="card p-4 flex flex-col justify-between">
            <div class="flex items-center justify-between mb-3">
                <div class="text-xs text-slate-400 uppercase tracking-widest">Highly satisfied</div>
                <div class="chip bg-emerald-900/60 text-emerald-300 border border-emerald-500/40">
                    ≥ <?php echo $highScore; ?> rating
                </div>
            </div>
            <div class="flex items-end justify-between">
                <div class="text-3xl font-semibold text-emerald-400">
                    <?php echo $highPercent; ?>%
                </div>
                <div class="text-xs text-slate-400">
                    <?php echo $highCount; ?> of <?php echo $totalSurveys ?: 0; ?> callers
                </div>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
        <div class="card p-4">
            <div class="flex justify-between items-center mb-3">
                <div>
                    <div class="text-sm font-medium text-slate-100">Rating Distribution</div>
                    <div class="text-xs text-slate-400">Count by rating (1–5)</div>
                </div>
            </div>
            <canvas id="ratingDistChart" height="140"></canvas>
        </div>

        <div class="card p-4">
            <div class="flex justify-between items-center mb-3">
                <div>
                    <div class="text-sm font-medium text-slate-100">Trend Over Time</div>
                    <div class="text-xs text-slate-400">Average rating per day</div>
                </div>
            </div>
            <canvas id="trendChart" height="140"></canvas>
        </div>

        <div class="card p-4">
            <div class="flex justify-between items-center mb-3">
                <div>
                    <div class="text-sm font-medium text-slate-100">Top Agents</div>
                    <div class="text-xs text-slate-400">Average rating</div>
                </div>
            </div>
            <canvas id="agentsChart" height="140"></canvas>
        </div>
    </div>

    <!-- Table -->
    <div class="card p-4">
        <div class="flex items-center justify-between mb-3">
            <div class="text-sm font-medium text-slate-100">All Call Surveys</div>
            <input id="searchInput" type="text"
                   class="bg-slate-900/70 border border-slate-600/60 rounded-lg px-3 py-1.5 text-xs text-slate-100 focus:outline-none focus:ring-1 focus:ring-sky-500"
                   placeholder="Search number / operator...">
        </div>
        <div class="table-container">
            <table class="w-full text-xs text-left">
                <thead class="sticky top-0 bg-slate-900/90 backdrop-blur border-b border-slate-700/60">
                <tr>
                    <th class="py-2 px-3 text-slate-400 font-medium">Date/Time</th>
                    <th class="py-2 px-3 text-slate-400 font-medium">Number</th>
                    <th class="py-2 px-3 text-slate-400 font-medium">Operator</th>
                    <th class="py-2 px-3 text-slate-400 font-medium">Queue</th>
                    <th class="py-2 px-3 text-slate-400 font-medium">Rating</th>
                </tr>
                </thead>
                <tbody id="tableBody">
                <!-- rows rendered by JS -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    const ratingDist = <?php echo $ratingDistJson; ?>;
    const trendData  = <?php echo $trendJson; ?>;
    const topAgents  = <?php echo $topAgentsJson; ?>;
    const tableRows  = <?php echo $rowsJson; ?>;

    // Rating distribution chart
    const rdCtx = document.getElementById('ratingDistChart').getContext('2d');
    new Chart(rdCtx, {
        type: 'bar',
        data: {
            labels: ratingDist.map(r => r.rating),
            datasets: [{
                label: 'Count',
                data: ratingDist.map(r => r.cnt),
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                x: { ticks: { color: '#9ca3af' } },
                y: { ticks: { color: '#9ca3af' } }
            },
            plugins: {
                legend: { labels: { color: '#e5e7eb' } }
            }
        }
    });

    // Trend chart
    const trCtx = document.getElementById('trendChart').getContext('2d');
    new Chart(trCtx, {
        type: 'line',
        data: {
            labels: trendData.map(r => r.day),
            datasets: [{
                label: 'Average rating',
                data: trendData.map(r => r.avgscore),
                borderWidth: 2,
                tension: 0.35,
                pointRadius: 3
            }]
        },
        options: {
            scales: {
                x: { ticks: { color: '#9ca3af' } },
                y: { ticks: { color: '#9ca3af' }, suggestedMin: 1, suggestedMax: 5 }
            },
            plugins: {
                legend: { labels: { color: '#e5e7eb' } }
            }
        }
    });

    // Top agents chart
    const agCtx = document.getElementById('agentsChart').getContext('2d');
    new Chart(agCtx, {
        type: 'bar',
        data: {
            labels: topAgents.map(r => r.operator),
            datasets: [{
                label: 'Average rating',
                data: topAgents.map(r => r.avgscore),
                borderWidth: 1
            }]
        },
        options: {
            indexAxis: 'y',
            scales: {
                x: { ticks: { color: '#9ca3af' }, suggestedMin: 1, suggestedMax: 5 },
                y: { ticks: { color: '#9ca3af' } }
            },
            plugins: {
                legend: { labels: { color: '#e5e7eb' } }
            }
        }
    });

    // Table rendering + search
    const tbody = document.getElementById('tableBody');
    const searchInput = document.getElementById('searchInput');

    function ratingClass(v) {
        if (v >= 5) return 'rating-5';
        if (v >= 4) return 'rating-4';
        if (v >= 3) return 'rating-3';
        if (v >= 2) return 'rating-2';
        return 'rating-1';
    }

    function renderTable(filter = '') {
        tbody.innerHTML = '';
        const f = filter.toLowerCase();
        tableRows
            .filter(r => {
                if (!f) return true;
                return (
                    String(r.num).toLowerCase().includes(f) ||
                    String(r.operator).toLowerCase().includes(f) ||
                    String(r.queue).toLowerCase().includes(f)
                );
            })
            .forEach(r => {
                const tr = document.createElement('tr');
                tr.className = 'border-b border-slate-800/60 hover:bg-slate-800/60';
                tr.innerHTML = `
                    <td class="py-1.5 px-3 text-slate-200 text-xs">${r.date}</td>
                    <td class="py-1.5 px-3 text-slate-300 text-xs">${r.num}</td>
                    <td class="py-1.5 px-3 text-slate-300 text-xs">${r.operator}</td>
                    <td class="py-1.5 px-3 text-slate-400 text-xs">${r.queue}</td>
                    <td class="py-1.5 px-3">
                        <span class="rating-badge ${ratingClass(r.valuation)}">
                            ${r.valuation}
                        </span>
                    </td>
                `;
                tbody.appendChild(tr);
            });
    }

    renderTable();

    searchInput.addEventListener('input', (e) => {
        renderTable(e.target.value);
    });
</script>
</body>
</html>