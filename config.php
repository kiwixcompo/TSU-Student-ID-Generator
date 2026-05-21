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

    // Suppress errors on production, log them instead
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors',     1);

    // Auto-create logs directory and error log file if missing
    $logDir  = __DIR__ . '/logs';
    $logFile = $logDir . '/php_errors.log';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    if (!file_exists($logFile)) {
        @file_put_contents($logFile, "=== TSU Error Log created " . date('Y-m-d H:i:s') . " ===\n");
        @chmod($logFile, 0644);
    }
    ini_set('error_log', $logFile);

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
if (!defined('AVATAR_SERVER')) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache');
    header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
}

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

// ── Auto-logging error handler (production only) ──────────────────────────────
if ($isProduction) {
    // Catch fatal errors that PHP can't intercept normally
    register_shutdown_function(function () {
        $err = error_get_last();
        if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $logFile = BASE_PATH . '/logs/php_errors.log';
            $logDir  = dirname($logFile);
            if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
            $msg = sprintf(
                "[%s] FATAL %s in %s on line %d\n",
                date('Y-m-d H:i:s'),
                $err['message'],
                $err['file'],
                $err['line']
            );
            @file_put_contents($logFile, $msg, FILE_APPEND | LOCK_EX);
        }
    });
}
