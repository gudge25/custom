<?php
/***********************
 * DB CONFIG (same as new.php)
 ***********************/
$dbHost = 'localhost';
$dbName = 'asteriskcdrdb';
$dbUser = 'root';
$dbPass = '';

$queueOptions = [];
$dbError = '';
$successMessage = '';
$errorMessage = '';

$settingsFile = __DIR__ . '/../queue_alert_settings.json';

try {
    $dsn = "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];
    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);

    $stmt = $pdo->query("SELECT DISTINCT queue FROM survey WHERE queue IS NOT NULL AND queue <> '' ORDER BY queue ASC");
    $queueOptions = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Throwable $e) {
    $dbError = 'Could not load queues from database. You can still enter queue number manually.';
}

$selectedQueue = '';
$alertNumber = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedQueue = trim($_POST['queue_number'] ?? '');
    $alertNumber = trim($_POST['alert_number'] ?? '');

    if ($selectedQueue === '') {
        $errorMessage = 'Queue number is required.';
    } elseif ($alertNumber === '') {
        $errorMessage = 'Alert number is required.';
    } elseif (!preg_match('/^[0-9+]{5,20}$/', $alertNumber)) {
        $errorMessage = 'Alert number must contain only digits (and optional +), 5-20 characters.';
    } else {
        $payload = [
            'queue_number' => $selectedQueue,
            'alert_number' => $alertNumber,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $ok = file_put_contents($settingsFile, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        if ($ok === false) {
            $errorMessage = 'Failed to save settings.';
        } else {
            $successMessage = 'Queue alert settings saved successfully.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Queue Alert Settings</title>

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
    </style>
</head>
<body>
<div class="min-h-screen px-6 py-5">
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center space-x-3">
            <div class="w-9 h-9 rounded-xl bg-sky-500/80 flex items-center justify-center">
                <span class="text-white text-xl font-semibold">Q</span>
            </div>
            <div>
                <h1 class="text-xl font-semibold text-slate-50">Queue Alert</h1>
                <p class="text-xs text-slate-400">Configure queue notification target</p>
            </div>
        </div>
        <div class="text-right">
            <div class="text-xs text-slate-400 uppercase tracking-widest">Today</div>
            <div class="text-sm text-slate-100"><?php echo date('Y-m-d'); ?></div>
        </div>
    </div>

    <div class="max-w-2xl">
        <div class="card p-5">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-slate-100 text-lg font-semibold">Queue Alert Settings</h2>
                    <p class="text-xs text-slate-400 mt-1">Choose a queue and set the alert number.</p>
                </div>
                <div class="chip bg-sky-900/60 text-sky-300 border border-sky-500/40">Form</div>
            </div>

            <?php if ($dbError !== ''): ?>
                <div class="mb-4 rounded-lg border border-amber-500/40 bg-amber-500/10 px-3 py-2 text-sm text-amber-200">
                    <?php echo htmlspecialchars($dbError); ?>
                </div>
            <?php endif; ?>

            <?php if ($successMessage !== ''): ?>
                <div class="mb-4 rounded-lg border border-emerald-500/40 bg-emerald-500/10 px-3 py-2 text-sm text-emerald-200">
                    <?php echo htmlspecialchars($successMessage); ?>
                </div>
            <?php endif; ?>

            <?php if ($errorMessage !== ''): ?>
                <div class="mb-4 rounded-lg border border-rose-500/40 bg-rose-500/10 px-3 py-2 text-sm text-rose-200">
                    <?php echo htmlspecialchars($errorMessage); ?>
                </div>
            <?php endif; ?>

            <form method="post" class="space-y-4">
                <div>
                    <label for="queue_number" class="block text-sm text-slate-300 mb-1.5">Set Queue number</label>
                    <?php if (count($queueOptions) > 0): ?>
                        <select id="queue_number" name="queue_number" class="input" required>
                            <option value="">Select queue</option>
                            <?php foreach ($queueOptions as $queue): ?>
                                <option value="<?php echo htmlspecialchars((string)$queue); ?>" <?php echo ($selectedQueue === (string)$queue) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars((string)$queue); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <input id="queue_number" name="queue_number" type="text" class="input" placeholder="Enter queue number" value="<?php echo htmlspecialchars($selectedQueue); ?>" required>
                    <?php endif; ?>
                </div>

                <div>
                    <label for="alert_number" class="block text-sm text-slate-300 mb-1.5">Alert number</label>
                    <input
                        id="alert_number"
                        name="alert_number"
                        type="text"
                        class="input"
                        placeholder="e.g. +15551234567"
                        value="<?php echo htmlspecialchars($alertNumber); ?>"
                        required
                    >
                </div>

                <div class="pt-1 flex items-center gap-3">
                    <button type="submit" class="btn">Save Alert Settings</button>
                    <a href="../index.php" class="text-sm text-sky-300 hover:text-sky-200">Back to Home</a>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>
