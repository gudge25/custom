<?php
require_once dirname(__DIR__) . '/bootstrap.php';

/**
 * Dominant sentiment across a call's turns. Ties resolve toward the more
 * cautious label (negative over neutral over positive) since this feeds a
 * "needs review" signal, not a vanity metric.
 */
function callSentiment(array $turns): string
{
    $counts = ['positive' => 0, 'neutral' => 0, 'negative' => 0];
    foreach ($turns as $turn) {
        $counts[$turn['sentiment']]++;
    }
    if ($counts['negative'] > 0 && $counts['negative'] >= $counts['neutral'] && $counts['negative'] >= $counts['positive']) {
        return 'negative';
    }
    if ($counts['positive'] > $counts['neutral'] && $counts['positive'] > $counts['negative']) {
        return 'positive';
    }
    return 'neutral';
}

function sentimentBadgeClass(string $sentiment): string
{
    if ($sentiment === 'positive') {
        return 'badge-positive';
    }
    if ($sentiment === 'negative') {
        return 'badge-negative';
    }
    return 'badge-neutral';
}

function sentimentLabel(string $sentiment): string
{
    return ucfirst($sentiment);
}

function formatDuration(int $totalSeconds): string
{
    return sprintf('%d:%02d', intdiv($totalSeconds, 60), $totalSeconds % 60);
}

function decorateCall(array $call): array
{
    $sentiment = callSentiment($call['turns']);
    $call['sentiment'] = $sentiment;
    $call['sentiment_badge_class'] = sentimentBadgeClass($sentiment);
    $call['sentiment_label'] = sentimentLabel($sentiment);
    $call['flagged'] = in_array('negative', array_column($call['turns'], 'sentiment'), true);
    $call['has_action_items'] = count($call['action_items']) > 0;
    $call['duration_label'] = formatDuration($call['duration_seconds']);
    foreach ($call['action_items'] as &$item) {
        $item['timestamp_label'] = formatDuration($item['timestamp_seconds']);
    }
    unset($item);
    return $call;
}

$pageName = 'Call Analytics';
$isDemo = false;
$error = '';
$calls = [];

if (envEnabled('FEATURE_CALL_ANALYTICS')) {
    // NOTE: this table and its AssemblyAI ingestion job don't exist yet -
    // this is the intended real-data schema (see agent_latency/README.md's
    // collect_rtt.php for the closest existing example of a similar
    // ingestion cron). Until that's built, turning this flag on will show
    // the error below rather than data.
    try {
        $pdo = db();
        $stmt = $pdo->query("
            SELECT id, calldate, agent, extension, caller, duration_seconds,
                   snippet, turns, action_items, has_redacted_pii
            FROM call_transcripts
            ORDER BY calldate DESC
        ");
        foreach ($stmt->fetchAll() as $row) {
            $calls[] = decorateCall([
                'id' => $row['id'],
                'date' => $row['calldate'],
                'agent' => $row['agent'],
                'ext' => $row['extension'],
                'caller' => $row['caller'],
                'duration_seconds' => (int) $row['duration_seconds'],
                'snippet' => $row['snippet'],
                'turns' => json_decode($row['turns'], true) ?: [],
                'action_items' => json_decode($row['action_items'], true) ?: [],
                'has_redacted_pii' => (bool) $row['has_redacted_pii'],
            ]);
        }
    } catch (PDOException $e) {
        $error = env('APP_ENV') !== 'production'
            ? 'Query error: ' . $e->getMessage()
            : 'Something went wrong loading call analytics. Please try again or contact support.';
    }
} else {
    $demoFile = __DIR__ . '/demo_data.php';

    if (!file_exists($demoFile)) {
        echo "<div style='padding:40px;text-align:center'>
                <h2>🚫 Call Analytics Disabled</h2>
                <p>Contact <b>Gixo</b></p>
              </div>";
        exit;
    }

    foreach (require $demoFile as $call) {
        $calls[] = decorateCall($call);
    }
    $isDemo = true;
}

$totalCalls = count($calls);
$totalSeconds = array_sum(array_column($calls, 'duration_seconds'));
$avgDuration = $totalCalls ? formatDuration((int) round($totalSeconds / $totalCalls)) : '0:00';
$flaggedCount = count(array_filter($calls, fn($c) => $c['flagged']));
$actionItemsCount = array_sum(array_map(fn($c) => count($c['action_items']), $calls));

$callsJson = json_encode($calls);
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
            background: radial-gradient(circle at top, #283c86 0, #0a0f1f 45%, #050814 100%);
            color: #e5e7eb;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "SF Pro Text", sans-serif;
        }
        a { color: #7dd3fc; text-decoration: none; }
        a:hover { color: #bae6fd; }
        .page { max-width: 1300px; margin: 0 auto; padding: 40px 32px 64px; display: flex; flex-direction: column; gap: 20px; }
        .card {
            border-radius: 1rem;
            background: rgba(15,23,42,0.9);
            border: 1px solid rgba(148,163,184,0.1);
            box-shadow: 0 18px 40px rgba(0,0,0,0.55);
        }
        .chip {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            border-radius: 999px;
            padding: 3px 10px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            white-space: nowrap;
        }
        .input {
            width: 100%;
            border-radius: 0.75rem;
            border: 1px solid rgba(148,163,184,0.25);
            background: rgba(15,23,42,0.8);
            color: #e2e8f0;
            padding: 0.7rem 0.9rem;
            outline: none;
            font-size: 14px;
        }
        .input:focus { border-color: rgba(14,165,233,0.8); }
        .badge { display: inline-flex; align-items: center; border-radius: 999px; padding: 2px 9px; font-size: 11px; font-weight: 600; }
        .badge-positive { color: #4ade80; background: rgba(34,197,94,0.12); border: 1px solid rgba(34,197,94,0.3); }
        .badge-neutral { color: #94a3b8; background: rgba(148,163,184,0.12); border: 1px solid rgba(148,163,184,0.3); }
        .badge-negative { color: #fda4af; background: rgba(244,63,94,0.12); border: 1px solid rgba(244,63,94,0.3); }
        .badge-flagged { color: #fb7185; background: rgba(244,63,94,0.1); border: 1px solid rgba(244,63,94,0.35); }
        .badge-info { color: #7dd3fc; background: rgba(56,189,248,0.1); border: 1px solid rgba(56,189,248,0.3); }
        .kpi-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 1rem; }
        .kpi-label { font-size: 11px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.06em; }
        .kpi-value { font-size: 26px; font-weight: 600; color: #f1f5f9; margin-top: 6px; }
        .call-row { display: flex; flex-direction: column; gap: 5px; padding: 12px 14px; border-radius: 10px; border-left: 2px solid transparent; cursor: pointer; }
        .call-row:hover { background: rgba(56,189,248,0.06); }
        .call-row-active { background: rgba(56,189,248,0.1); border-left-color: #38bdf8; }
        .call-list { max-height: 560px; overflow-y: auto; display: flex; flex-direction: column; gap: 4px; }
        .call-list::-webkit-scrollbar { width: 6px; }
        .call-list::-webkit-scrollbar-thumb { background: rgba(148,163,184,0.25); border-radius: 999px; }
        .snippet { font-size: 12px; color: #94a3b8; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .transcript-scroll { max-height: 360px; overflow-y: auto; padding-right: 4px; }
        .turn { display: flex; margin-bottom: 12px; }
        .turn-agent { justify-content: flex-start; }
        .turn-caller { justify-content: flex-end; }
        .bubble { max-width: 78%; padding: 10px 14px; border-radius: 14px; font-size: 13px; line-height: 1.5; }
        .bubble-agent { background: rgba(56,189,248,0.12); border: 1px solid rgba(56,189,248,0.25); color: #e0f2fe; border-bottom-left-radius: 4px; }
        .bubble-caller { background: rgba(148,163,184,0.12); border: 1px solid rgba(148,163,184,0.2); color: #e2e8f0; border-bottom-right-radius: 4px; }
        .bubble-label { display: flex; align-items: center; gap: 5px; font-size: 10px; text-transform: uppercase; letter-spacing: 0.06em; opacity: 0.7; margin-bottom: 4px; }
        .sentiment-dot { width: 6px; height: 6px; border-radius: 999px; flex-shrink: 0; }
        .action-items { display: flex; flex-direction: column; gap: 10px; }
        .action-item { padding: 10px 12px; border-radius: 10px; background: rgba(56,189,248,0.06); border: 1px solid rgba(56,189,248,0.18); }
        .action-item-quote { font-size: 12px; color: #94a3b8; font-style: italic; margin-top: 4px; }
        .modal-overlay { position: fixed; inset: 0; background: rgba(2,6,16,0.72); display: flex; align-items: center; justify-content: center; padding: 24px; z-index: 50; }
        .modal-overlay[hidden] { display: none; }
        .modal-card { width: 100%; max-width: 640px; max-height: 85vh; display: flex; flex-direction: column; gap: 14px; padding: 22px; overflow-y: auto; }
        .modal-close { width: 28px; height: 28px; border-radius: 999px; border: 1px solid rgba(148,163,184,0.25); background: rgba(15,23,42,0.8); color: #cbd5e1; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 14px; flex-shrink: 0; }
        .modal-close:hover { color: #f1f5f9; border-color: rgba(148,163,184,0.5); }
        .section-label { font-size: 11px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 8px; }
    </style>
</head>
<body>
<div class="page">

    <div style="display: flex; align-items: center; justify-content: space-between;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div style="width: 36px; height: 36px; border-radius: 0.75rem; background: rgba(14,165,233,0.8); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <span style="color: #fff; font-size: 18px; font-weight: 600;">📈</span>
            </div>
            <div>
                <h1 style="font-size: 20px; font-weight: 600; color: #f8fafc; margin: 0;">Call Analytics</h1>
                <p style="font-size: 12px; color: #94a3b8; margin: 2px 0 0;">Call transcription &amp; insights</p>
            </div>
            <?php if ($isDemo): ?>
                <span class="chip" style="background: rgba(245,158,11,0.12); color: #fbbf24; border: 1px solid rgba(245,158,11,0.3); margin-left: 6px;">Demo Data</span>
            <?php endif; ?>
        </div>
        <div style="text-align: right;">
            <div style="font-size: 11px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.08em;">Today</div>
            <div style="font-size: 13px; color: #e2e8f0;"><?php echo date('Y-m-d'); ?></div>
        </div>
    </div>

    <?php if ($error !== ''): ?>
        <div class="card" style="padding: 14px 18px; border-color: rgba(244,63,94,0.4); color: #fda4af; font-size: 13px;">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="kpi-grid">
        <div class="card" style="padding: 18px;">
            <div class="kpi-label">Calls Transcribed</div>
            <div class="kpi-value"><?php echo $totalCalls; ?></div>
        </div>
        <div class="card" style="padding: 18px;">
            <div class="kpi-label">Avg Duration</div>
            <div class="kpi-value"><?php echo htmlspecialchars($avgDuration); ?></div>
        </div>
        <div class="card" style="padding: 18px;">
            <div class="kpi-label">Flagged for Review</div>
            <div class="kpi-value" style="color: #fb7185;"><?php echo $flaggedCount; ?></div>
        </div>
        <div class="card" style="padding: 18px;">
            <div class="kpi-label">Open Action Items</div>
            <div class="kpi-value" style="color: #7dd3fc;"><?php echo $actionItemsCount; ?></div>
        </div>
    </div>

    <div class="card" style="padding: 16px 18px; display: flex; flex-direction: column; gap: 8px;">
        <input id="searchInput" class="input" type="text" placeholder="Search transcripts... (e.g. refund, cancel, upgrade)">
        <div id="resultLabel" style="font-size: 12px; color: #64748b;"><?php echo $totalCalls; ?> calls transcribed</div>
    </div>

    <div class="card" style="padding: 16px;">
        <div id="callList" class="call-list"></div>
    </div>

    <div style="text-align: center; padding-top: 4px;">
        <a href="../index.php">← Back to Home</a>
    </div>
</div>

<div id="transcriptModal" class="modal-overlay" hidden>
    <div class="card modal-card">
        <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 8px;">
            <div>
                <div id="modalTitle" style="font-size: 16px; font-weight: 600; color: #f1f5f9;"></div>
                <div id="modalMeta" style="font-size: 12px; color: #94a3b8; margin-top: 2px;"></div>
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <span id="modalSentiment" class="badge"></span>
                <span id="modalAction" class="badge badge-info" hidden>Action</span>
                <span id="modalFlagged" class="badge badge-flagged" hidden>Flagged</span>
                <span id="modalPii" class="badge badge-info" hidden>PII Redacted</span>
                <span class="modal-close" id="modalClose">✕</span>
            </div>
        </div>
        <div id="modalTranscript" class="transcript-scroll"></div>
        <div id="modalActionItemsWrap">
            <div class="section-label">Action Items</div>
            <div id="modalActionItems" class="action-items"></div>
        </div>
    </div>
</div>

<script>
const CALLS = <?php echo $callsJson; ?>;
const SENTIMENT_COLOR = { positive: '#4ade80', neutral: '#94a3b8', negative: '#fb7185' };

const callList = document.getElementById('callList');
const searchInput = document.getElementById('searchInput');
const resultLabel = document.getElementById('resultLabel');
const modal = document.getElementById('transcriptModal');

function text(tag, className, content) {
    const el = document.createElement(tag);
    if (className) el.className = className;
    if (content !== undefined) el.textContent = content;
    return el;
}

function matchesQuery(call, q) {
    if (!q) return true;
    if (call.agent.toLowerCase().includes(q)) return true;
    if (call.caller.toLowerCase().includes(q)) return true;
    return call.turns.some((t) => t.text.toLowerCase().includes(q));
}

function renderList(filter) {
    const q = (filter || '').trim().toLowerCase();
    const filtered = CALLS.filter((c) => matchesQuery(c, q));

    resultLabel.textContent = q
        ? `${filtered.length} of ${CALLS.length} calls matching "${filter}"`
        : `${CALLS.length} calls transcribed`;

    callList.innerHTML = '';

    if (filtered.length === 0) {
        callList.appendChild(text('div', 'snippet', 'No transcripts match your search.'));
        return;
    }

    filtered.forEach((call) => {
        const row = document.createElement('div');
        row.className = 'call-row';
        row.addEventListener('click', () => openModal(call.id));

        const top = text('div');
        top.style.display = 'flex';
        top.style.alignItems = 'center';
        top.style.justifyContent = 'space-between';
        top.appendChild(text('span', '', `${call.agent} · ext ${call.ext}`)).style.cssText = 'font-size:13px;font-weight:600;color:#f1f5f9;';

        const badges = text('span');
        badges.style.cssText = 'display:flex;align-items:center;gap:6px;';
        if (call.has_action_items) badges.appendChild(text('span', 'badge badge-info', 'Action'));
        badges.appendChild(text('span', `badge ${call.sentiment_badge_class}`, call.sentiment_label));
        top.appendChild(badges);
        row.appendChild(top);

        row.appendChild(text('div', 'snippet', call.snippet));

        const bottom = text('div');
        bottom.style.cssText = 'display:flex;align-items:center;justify-content:space-between;';
        bottom.appendChild(text('span', '', `${call.date} · ${call.caller}`)).style.cssText = 'font-size:11px;color:#64748b;';
        bottom.appendChild(text('span', '', call.duration_label)).style.cssText = 'font-size:11px;color:#64748b;';
        row.appendChild(bottom);

        callList.appendChild(row);
    });
}

function openModal(id) {
    const call = CALLS.find((c) => c.id === id);
    if (!call) return;

    document.getElementById('modalTitle').textContent = `${call.agent} · ext ${call.ext}`;
    document.getElementById('modalMeta').textContent = `${call.date} · ${call.caller} · ${call.duration_label}`;

    const sentimentEl = document.getElementById('modalSentiment');
    sentimentEl.className = `badge ${call.sentiment_badge_class}`;
    sentimentEl.textContent = call.sentiment_label;

    document.getElementById('modalAction').hidden = !call.has_action_items;
    document.getElementById('modalFlagged').hidden = !call.flagged;
    document.getElementById('modalPii').hidden = !call.has_redacted_pii;

    const transcript = document.getElementById('modalTranscript');
    transcript.innerHTML = '';
    call.turns.forEach((turn) => {
        const row = text('div', turn.speaker === 'Agent' ? 'turn turn-agent' : 'turn turn-caller');
        const bubble = text('div', turn.speaker === 'Agent' ? 'bubble bubble-agent' : 'bubble bubble-caller');
        const label = text('span', 'bubble-label');
        const dot = text('span', 'sentiment-dot');
        dot.style.background = SENTIMENT_COLOR[turn.sentiment];
        label.appendChild(dot);
        label.appendChild(document.createTextNode(turn.speaker));
        bubble.appendChild(label);
        bubble.appendChild(document.createTextNode(turn.text));
        row.appendChild(bubble);
        transcript.appendChild(row);
    });

    const actionWrap = document.getElementById('modalActionItemsWrap');
    const actionList = document.getElementById('modalActionItems');
    actionList.innerHTML = '';
    if (call.action_items.length > 0) {
        actionWrap.hidden = false;
        call.action_items.forEach((item) => {
            const box = text('div', 'action-item');
            const topRow = text('div');
            topRow.style.cssText = 'display:flex;align-items:flex-start;justify-content:space-between;gap:8px;';
            topRow.appendChild(text('span', '', item.text)).style.cssText = 'font-size:13px;color:#e0f2fe;';
            topRow.appendChild(text('span', 'badge badge-info', item.timestamp_label));
            box.appendChild(topRow);
            const quote = text('div', 'action-item-quote', `"${item.quote}"`);
            box.appendChild(quote);
            actionList.appendChild(box);
        });
    } else {
        actionWrap.hidden = true;
    }

    modal.hidden = false;
}

document.getElementById('modalClose').addEventListener('click', () => { modal.hidden = true; });
searchInput.addEventListener('input', (e) => renderList(e.target.value));

renderList('');
</script>
</body>
</html>
