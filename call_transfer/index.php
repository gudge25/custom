<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

$pageName = 'Call Transfer Report';
$summaryData = [];
$mainData = [];
$error = '';
$debug = '';
$fromDate = '';
$toDate = '';

if (isset($_POST['from']) && isset($_POST['to'])) {
    $fromDate = $_POST['from'];
    $toDate = $_POST['to'];
    
    $debug .= "POST data received: From = $fromDate, To = $toDate\n";
    
    $link = mysqli_connect('localhost', 'root', '');
    if (!$link) {
        $error = 'Connection error: ' . mysqli_error($link);
        $debug .= "Connection failed: " . mysqli_error($link) . "\n";
    } else {
        $debug .= "Database connection successful\n";
        
        if (!mysqli_select_db($link, 'asteriskcdrdb')) {
            $error = 'Can\'t select database: ' . mysqli_error($link);
            $debug .= "Database selection failed: " . mysqli_error($link) . "\n";
        } else {
            $debug .= "Database asteriskcdrdb selected successfully\n";
            
            $from = mysqli_real_escape_string($link, $fromDate);
            $to = mysqli_real_escape_string($link, $toDate);
            
            $debug .= "Escaped dates: From = $from, To = $to\n";

            // Summary table query
            $summary_query = "
                select
                    substring(cdr.clid, 3, 4) as acct,
                    sec_to_time(sum(cdr.duration)) as total_patchtime
                from asteriskcdrdb.cdr cdr
                where cast(cdr.calldate as date) between '" . $from . "' and '" . $to . "'
                  and cdr.accountcode = 'Outbound'
                  and (cdr.dst not in ('*80','*89','*50') and char_length(cdr.dst) = 3)
                  and char_length(cdr.src) != 3
                  and left(cdr.dstchannel, 3) <> 'PJSIP'
                group by acct
                order by acct
            ";
            
            $debug .= "Summary Query: " . $summary_query . "\n";

            $summary_result = mysqli_query($link, $summary_query);
            if ($summary_result) {
                $summary_count = mysqli_num_rows($summary_result);
                $debug .= "Summary query executed successfully. Rows found: $summary_count\n";
                
                while ($row = mysqli_fetch_assoc($summary_result)) {
                    $summaryData[] = $row;
                }
                mysqli_free_result($summary_result);
            } else {
                $error = 'Summary query error: ' . mysqli_error($link);
                $debug .= "Summary query failed: " . mysqli_error($link) . "\n";
            }

            // Main query
            $query = "
                select
                    cdr.calldate,
                    substring(cdr.clid, 3, 4) as acct,
                    cdr.src as caller,
                    cdr.dst as ext,
                    sec_to_time(cdr.duration) as patchtime,
                    cdr.uniqueid,
                    cdr.lastapp,
                    cdr.linkedid
                from asteriskcdrdb.cdr cdr
                where cast(cdr.calldate as date) between '" . $from . "' and '" . $to . "'
                  and cdr.accountcode = 'Outbound'
                  and (cdr.dst not in ('*80','*89','*50') and char_length(cdr.dst) = 3)
                  and char_length(cdr.src) != 3
                  and left(cdr.dstchannel, 3) <> 'PJSIP'
                order by cdr.calldate
            ";
            
            $debug .= "Main Query: " . $query . "\n";

            $result = mysqli_query($link, $query);
            if ($result) {
                $main_count = mysqli_num_rows($result);
                $debug .= "Main query executed successfully. Rows found: $main_count\n";
                
                while ($row = mysqli_fetch_assoc($result)) {
                    $mainData[] = $row;
                }
                mysqli_free_result($result);
            } else {
                $error = 'Main query error: ' . mysqli_error($link);
                $debug .= "Main query failed: " . mysqli_error($link) . "\n";
            }

            mysqli_close($link);
            $debug .= "Database connection closed\n";
        }
    }
} else {
    $debug .= "No POST data received. Waiting for form submission.\n";
}
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
            background: rgba(15,23,42,0.8);
        }
        td {
            color: #e2e8f0;
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
            <h2 class="text-lg font-semibold text-slate-100 mb-4">Date Range Selection</h2>
            
            <form method="post" class="grid grid-cols-1 md:grid-cols-3 gap-4">
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
                    <button type="submit" class="btn w-full">Query Data</button>
                </div>
            </form>
        </div>

        <?php if ($error !== ''): ?>
            <div class="mb-4 max-w-6xl rounded-lg border border-rose-500/40 bg-rose-500/10 px-3 py-2 text-sm text-rose-200">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($debug !== ''): ?>
            <div class="mb-4 max-w-6xl rounded-lg border border-amber-500/40 bg-amber-500/10 px-3 py-2 text-xs text-amber-200">
                <h4 class="font-semibold mb-2">Debug Information:</h4>
                <pre class="whitespace-pre-wrap"><?php echo htmlspecialchars($debug); ?></pre>
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
                                <th>Total Patch Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($summaryData as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['acct']); ?></td>
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
                                <th>Extension</th>
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
                                    <td><?php echo htmlspecialchars($row['ext']); ?></td>
                                    <td><?php echo htmlspecialchars($row['patchtime']); ?></td>
                                    <td><?php echo htmlspecialchars($row['uniqueid']); ?></td>
                                    <td><?php echo htmlspecialchars($row['lastapp']); ?></td>
                                    <td><?php echo htmlspecialchars($row['linkedid']); ?></td>
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
</body>
</html>
