<?php

/**
 * Load environment variables from .env file
 * Supports simple KEY=VALUE format
 */
function loadEnv($path) {
    // Stop execution if .env file is missing
    if (!file_exists($path)) {
        die("ENV not found. Copy .env.example to .env and configure it.");
    }

    // Read file into array (ignore empty lines)
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $env = [];

    foreach ($lines as $line) {
        $line = trim($line);

        // Skip empty lines and comments (# ...)
        if ($line === '' || strpos($line, '#') === 0) continue;

        // Skip invalid lines (must contain "=")
        if (strpos($line, '=') === false) continue;

        // Split key=value
        list($key, $value) = explode('=', $line, 2);

        // Store cleaned value (remove quotes)
        $env[trim($key)] = trim($value, "\"'");
    }

    return $env;
}

/**
 * Load .env once and store globally
 * So all helper functions can access it
 */
$GLOBALS['env'] = loadEnv(__DIR__ . '/.env');

/**
 * Require a logged-in FreePBX admin session for every bootstrap-integrated
 * page automatically, so a new page can't forget to add this check.
 */
require_once __DIR__ . '/freepbx_auth.php';
requireFreepbxAuth();

/**
 * Get environment variable by key
 * Example: env('DB_HOST')
 */
function env($key, $default = null) {
    return isset($GLOBALS['env'][$key]) ? $GLOBALS['env'][$key] : $default;
}

/**
 * Check if feature flag is enabled (1 = enabled)
 */
function envEnabled($key) {
    return env($key) == '1';
}

/**
 * Enable PHP error display in non-production environments
 * Helps debugging during development
 */
if (env('APP_ENV') !== 'production') {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
}

/**
 * Create (or reuse) a PDO database connection
 * Uses singleton pattern (one connection per request)
 *
 * Connection failures are left to propagate as a PDOException so callers can
 * catch them and decide what to show (e.g. gate the raw message behind
 * APP_ENV) - this function itself must never die()/echo, or every caller's
 * own error handling becomes unreachable dead code.
 */
function db() {
    static $pdo;

    // Return existing connection if already created
    if ($pdo) return $pdo;

    // Build DSN from env config
    $dsn = "mysql:host=" . env('DB_HOST') .
           ";dbname=" . env('DB_NAME') .
           ";charset=utf8mb4";

    $pdo = new PDO($dsn, env('DB_USER'), env('DB_PASS'), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    return $pdo;
}

/**
 * Render the shared "feature disabled" message and stop execution. Used
 * when a module's FEATURE_* flag is off and it has no demo fallback to show
 * instead (see call_surveys/, call_analytics/, agent_latency/,
 * call_transfer/, voicemails/ for the demo-fallback pattern).
 */
function renderFeatureDisabled(string $name): void {
    echo "<div style='padding:40px;text-align:center'>
            <h2>🚫 $name Disabled</h2>
            <p>Contact <b>Gixo</b></p>
          </div>";
    exit;
}

/**
 * TODO:
 * Auto-detect feature based on folder name
 * Example:
 *   /queue_alert/ -> FEATURE_QUEUE_ALERT
 */