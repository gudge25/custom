<?php
$pageName = 'Call Analytics';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageName); ?></title>
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            background: #0f172a;
            color: #e2e8f0;
            font-family: system-ui, sans-serif;
        }
        .card {
            width: min(720px, calc(100% - 40px));
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 12px;
            padding: 28px;
            text-align: center;
        }
        h1 {
            margin: 0;
            font-size: 28px;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #60a5fa;
            text-decoration: none;
            font-size: 14px;
        }
        .back-link:hover {
            color: #93c5fd;
        }
    </style>
</head>
<body>
    <div class="card">
        <h1><?php echo htmlspecialchars($pageName); ?></h1>
        <a href="../index.php" class="back-link">← Back to Home</a>
    </div>
</body>
</html>
