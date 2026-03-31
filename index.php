<?php
// Simple landing page for PBX custom tools
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PBX Tools</title>
    <style>
        body {
            background: #0f172a;
            color: #e2e8f0;
            font-family: system-ui, sans-serif;
            padding: 40px;
        }
        h1 {
            font-size: 28px;
            margin-bottom: 20px;
        }
        a {
            display: inline-block;
            margin: 10px 0;
            padding: 12px 18px;
            background: #1e293b;
            border-radius: 8px;
            color: #93c5fd;
            text-decoration: none;
            border: 1px solid #334155;
            transition: 0.2s;
        }
        a:hover {
            background: #334155;
            color: #bfdbfe;
        }
    </style>
</head>
<body>
    <h1>GIXO Custom Pages</h1>

    <a href="voice_agent/" title="Open voice agent latency report">⚡ Voice Agent Latency</a>
    <a href="call_analytics/" title="Open call analytics page">📈 Call Analytics</a>
    <a href="call_surveys/" title="Open call surveys dashboard">📊 Call Surveys Dashboard</a>
    <a href="queue_alert/" title="Configure queue and alert number settings">🚨 Queue Alert</a>
    <a href="call_transfer/" title="Open call transfer report page">🔁 Call Transfer Report</a>
    <a href="voicemails/" title="Open voicemails report page">📬 Voicemails Report</a>
    <a href="clean_cdr/" title="Open clean CDR settings page">🧹 Clean CDR Settings</a>
    <a href="clean_recording/" title="Open clean call recording page">🗑️ Clean Call Recording</a>
    <a href="ai_agent/" title="Open AI voice agent page">🤖 AI Voice Agent</a>

</body>
</html>
