<?php
/**
 * TSU Student ID Generator - Configuration File
 * Auto-detects environment and loads the correct settings.
 */

// ── Environment detection ─────────────────────────────────────────────────────
$host = $_SERVER['HTTP_HOST'] ?? '';
$isProduction = (strpos($host, 'tsuniversity.ng') !== false);

if ($isProduction) {
    // ── PRODUCTION (sig.tsuniversity.ng) ──────────────────────────────────────
    define('DB_HOST',    'localhost');
    define('DB_NAME',    'tsuniver_tsu_id_generator');
    define('DB_USER',    'tsuniver_tsu_id_generator');
    define('DB_PASS',    'JW?T(7!S1hqUG1sP');
    define('DB_CHARSET', 'utf8mb4');

    define('APP_NAME',  'TSU Student ID Generator');
    define('APP_URL',   'https://sig.tsuniversity.ng');
    define('BASE_PATH', __DIR__);

    // Suppress errors on production
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors',     1);
    ini_set('error_log',      __DIR__ . '/logs/php_errors.log');

} else {
    // ── LOCAL DEVELOPMENT (localhost) ─────────────────────────────────────────
    define('DB_HOST',    'localhost');
    define('DB_NAME',    'tsu_id_generator');
    define('DB_USER',    'root');
    define('DB_PASS',    '');
    define('DB_CHARSET', 'utf8mb4');

    define('APP_NAME',  'TSU Student ID Generator');
    define('APP_URL',   'http://localhost/TSU-Student-ID-Generator');
    define('BASE_PATH', __DIR__);

    // Show errors locally
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// ── Shared settings (same for all environments) ───────────────────────────────

// Session
define('SESSION_LIFETIME', 3600 * 24); // 24 hours
ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
ini_set('session.cookie_lifetime', SESSION_LIFETIME);

// File uploads
define('MAX_UPLOAD_SIZE',     2 * 1024 * 1024); // 2 MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/jpg']);

// Timezone
date_default_timezone_set('Africa/Lagos');

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');

// Cache control – always serve fresh content
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');

/**
 * Returns a versioned asset URL based on the file's last-modified time,
 * forcing the browser to fetch a fresh copy whenever the file changes.
 */
function asset(string $path): string {
    $path     = ltrim($path, '/');
    $fullPath = BASE_PATH . '/' . $path;
    $version  = file_exists($fullPath) ? filemtime($fullPath) : time();
    return APP_URL . '/' . $path . '?v=' . $version;
}
