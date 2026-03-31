<?php

/**
 * Load environment variables from .env file
 * Supports simple KEY=VALUE format
 */
function loadEnv($path) {
    // Stop execution if .env file is missing
    if (!file_exists($path)) {
        die("ENV not found: $path");
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
 */
function db() {
    static $pdo;

    // Return existing connection if already created
    if ($pdo) return $pdo;

    // Build DSN from env config
    $dsn = "mysql:host=" . env('DB_HOST') .
           ";dbname=" . env('DB_NAME') .
           ";charset=utf8mb4";

    try {
        $pdo = new PDO($dsn, env('DB_USER'), env('DB_PASS'), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    } catch (Exception $e) {
        // Stop execution on DB error
        die("DB error: " . $e->getMessage());
    }

    return $pdo;
}

/**
 * Block access to a page if feature is disabled
 * Example: requireFeature('FEATURE_QUEUE_ALERT', 'Queue Alert');
 */
function requireFeature($feature, $name) {
    if (!envEnabled($feature)) {
        echo "<div style='padding:40px;text-align:center'>
                <h2>🚫 $name Disabled</h2>
                <p>Contact <b>Gixo</b></p>
              </div>";
        exit;
    }
}

/**
 * TODO:
 * Auto-detect feature based on folder name
 * Example:
 *   /queue_alert/ -> FEATURE_QUEUE_ALERT
 */