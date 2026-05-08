<?php
/**
 * TSU Student ID Generator – PRODUCTION Configuration
 * Live server: sig.tsuniversity.ng
 *
 * The deploy script (git_pull.php) copies this file to config.php
 * on the live server during first-time setup.
 * After that, config.php is excluded from rsync so it is never overwritten.
 */

// Database Configuration
define('DB_HOST',    'localhost');
define('DB_NAME',    'tsuniver_tsu_id_generator');
define('DB_USER',    'tsuniver_tsu_id_generator');
define('DB_PASS',    'JW?T(7!S1hqUG1sP');
define('DB_CHARSET', 'utf8mb4');

// Application Configuration
define('APP_NAME',  'TSU Student ID Generator');
define('APP_URL',   'https://sig.tsuniversity.ng');
define('BASE_PATH', __DIR__);

// Session Configuration
define('SESSION_LIFETIME', 3600 * 24); // 24 hours
ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
ini_set('session.cookie_lifetime', SESSION_LIFETIME);

// File Upload Configuration
define('MAX_UPLOAD_SIZE',    2 * 1024 * 1024); // 2 MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/jpg']);

// Timezone
date_default_timezone_set('Africa/Lagos');

// Production: suppress error display, enable logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors',     1);
ini_set('error_log',      __DIR__ . '/logs/php_errors.log');

// Security Headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');

// Cache Control – force fresh content always
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');

/**
 * Returns a versioned asset URL using the file's last modified time.
 * Forces the browser to fetch a fresh copy whenever the file changes.
 */
function asset(string $path): string {
    $path     = ltrim($path, '/');
    $fullPath = BASE_PATH . '/' . $path;
    $version  = file_exists($fullPath) ? filemtime($fullPath) : time();
    return APP_URL . '/' . $path . '?v=' . $version;
}
