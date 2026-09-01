<?php
// Simple landing page for PBX custom tools
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PBX Tools</title>
    <style>
        body {
            margin: 0;
            background: radial-gradient(circle at top, #283c86 0, #0a0f1f 45%, #050814 100%);
            color: #e5e7eb;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "SF Pro Text", sans-serif;
        }
        a {
            color: #93c5fd;
            text-decoration: none;
        }
        a:hover {
            color: #bfdbfe;
        }
        .page {
            max-width: 1100px;
            margin: 0 auto;
            padding: 48px 32px 64px;
        }
        h1 {
            font-size: 28px;
            font-weight: 600;
            color: #f8fafc;
            margin: 0 0 6px;
        }
        .subtitle {
            font-size: 14px;
            color: #94a3b8;
            margin: 0 0 40px;
        }
        .module-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
        }
        @media (max-width: 900px) {
            .module-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
        @media (max-width: 600px) {
            .module-grid {
                grid-template-columns: minmax(0, 1fr);
            }
        }
        .module-card {
            display: flex;
            flex-direction: column;
            gap: 14px;
            padding: 22px;
            border-radius: 1rem;
            background: rgba(15, 23, 42, 0.9);
            border: 1px solid rgba(148, 163, 184, 0.1);
            box-shadow: 0 18px 40px rgba(0, 0, 0, 0.55);
            text-decoration: none;
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        }
        .module-card:hover {
            transform: translateY(-4px);
            border-color: rgba(56, 189, 248, 0.4);
            box-shadow: 0 20px 45px rgba(14, 165, 233, 0.25);
        }
        .module-card-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
        }
        .icon-badge {
            width: 40px;
            height: 40px;
            border-radius: 0.75rem;
            background: rgba(14, 165, 233, 0.18);
            border: 1px solid rgba(56, 189, 248, 0.35);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 19px;
            flex-shrink: 0;
        }
        .chip {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 9px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            white-space: nowrap;
        }
        .chip-live {
            color: #4ade80;
            background: rgba(34, 197, 94, 0.12);
            border: 1px solid rgba(34, 197, 94, 0.3);
        }
        .chip-soon {
            color: #fbbf24;
            background: rgba(245, 158, 11, 0.12);
            border: 1px solid rgba(245, 158, 11, 0.3);
        }
        .chip-dot {
            width: 6px;
            height: 6px;
            border-radius: 999px;
            background: currentColor;
        }
        .module-title {
            font-size: 16px;
            font-weight: 600;
            color: #f1f5f9;
        }
        .module-desc {
            font-size: 13px;
            color: #94a3b8;
            margin-top: 4px;
        }
    </style>
</head>
<body>
<div class="page">
    <h1>GIXO Custom Pages</h1>
    <p class="subtitle">PBX &amp; call-center operations dashboards</p>

    <div class="module-grid">

        <a class="module-card" href="agent_latency/" title="Open agent latency report">
            <div class="module-card-header">
                <div class="icon-badge">⚡</div>
                <span class="chip chip-live"><span class="chip-dot"></span>Live</span>
            </div>
            <div>
                <div class="module-title">Agent Latency</div>
                <div class="module-desc">SIP registration round-trip report</div>
            </div>
        </a>

        <a class="module-card" href="call_analytics/" title="Open call analytics page">
            <div class="module-card-header">
                <div class="icon-badge">📈</div>
                <span class="chip chip-soon"><span class="chip-dot"></span>Coming soon</span>
            </div>
            <div>
                <div class="module-title">Call Analytics</div>
                <div class="module-desc">Aggregate call volume &amp; trends</div>
            </div>
        </a>

        <a class="module-card" href="call_surveys/" title="Open call surveys dashboard">
            <div class="module-card-header">
                <div class="icon-badge">📊</div>
                <span class="chip chip-live"><span class="chip-dot"></span>Live</span>
            </div>
            <div>
                <div class="module-title">Call Surveys Dashboard</div>
                <div class="module-desc">Post-call survey results by operator</div>
            </div>
        </a>

        <a class="module-card" href="queue_alert/" title="Configure queue and alert number settings">
            <div class="module-card-header">
                <div class="icon-badge">🚨</div>
                <span class="chip chip-live"><span class="chip-dot"></span>Live</span>
            </div>
            <div>
                <div class="module-title">Queue Alert</div>
                <div class="module-desc">Queue &amp; alert number settings</div>
            </div>
        </a>

        <a class="module-card" href="call_transfer/" title="Open call transfer report page">
            <div class="module-card-header">
                <div class="icon-badge">🔁</div>
                <span class="chip chip-live"><span class="chip-dot"></span>Live</span>
            </div>
            <div>
                <div class="module-title">Call Transfer Report</div>
                <div class="module-desc">Outbound call transfer analysis</div>
            </div>
        </a>

        <a class="module-card" href="voicemails/" title="Open voicemails report page">
            <div class="module-card-header">
                <div class="icon-badge">📬</div>
                <span class="chip chip-soon"><span class="chip-dot"></span>Coming soon</span>
            </div>
            <div>
                <div class="module-title">Voicemails Report</div>
                <div class="module-desc">Voicemail activity overview</div>
            </div>
        </a>

        <a class="module-card" href="clean_cdr/" title="Open clean CDR settings page">
            <div class="module-card-header">
                <div class="icon-badge">🧹</div>
                <span class="chip chip-soon"><span class="chip-dot"></span>Coming soon</span>
            </div>
            <div>
                <div class="module-title">Clean CDR Settings</div>
                <div class="module-desc">CDR retention &amp; cleanup rules</div>
            </div>
        </a>

        <a class="module-card" href="clean_recording/" title="Open clean call recording page">
            <div class="module-card-header">
                <div class="icon-badge">🗑️</div>
                <span class="chip chip-soon"><span class="chip-dot"></span>Coming soon</span>
            </div>
            <div>
                <div class="module-title">Clean Call Recording</div>
                <div class="module-desc">Recording retention &amp; cleanup rules</div>
            </div>
        </a>

        <a class="module-card" href="ai_agent/" title="Open AI voice agent page">
            <div class="module-card-header">
                <div class="icon-badge">🤖</div>
                <span class="chip chip-soon"><span class="chip-dot"></span>Coming soon</span>
            </div>
            <div>
                <div class="module-title">AI Voice Agent</div>
                <div class="module-desc">AI-driven voice agent controls</div>
            </div>
        </a>

    </div>
</div>
</body>
</html>
