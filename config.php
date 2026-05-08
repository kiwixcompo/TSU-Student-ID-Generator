<?php
/**
 * TSU Student ID Generator - Configuration File
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'tsu_id_generator');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Application Configuration
define('APP_NAME', 'TSU Student ID Generator');
define('APP_URL', 'http://localhost/TSU-Student-ID-Generator');
define('BASE_PATH', __DIR__);

// Session Configuration
define('SESSION_LIFETIME', 3600 * 24); // 24 hours
ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
ini_set('session.cookie_lifetime', SESSION_LIFETIME);

// File Upload Configuration
define('MAX_UPLOAD_SIZE', 2 * 1024 * 1024); // 2MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/jpg']);

// Timezone
date_default_timezone_set('Africa/Lagos');

// Error Reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Security Headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');

// Cache Control - force fresh content always
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');

/**
 * Returns a versioned asset URL using the file's last modified time.
 * Forces browser to fetch fresh copy whenever the file changes.
 */
function asset($path) {
    $path = ltrim($path, '/');
    $fullPath = BASE_PATH . '/' . $path;
    $version = file_exists($fullPath) ? filemtime($fullPath) : time();
    return APP_URL . '/' . $path . '?v=' . $version;
}
