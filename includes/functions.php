<?php
/**
 * Utility Functions
 */

// Sanitize input
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Validate email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Validate registration number format (Must not be empty)
function isValidRegNumber($reg_number) {
    return !empty(trim($reg_number));
}

// Format date
function formatDate($date, $format = 'F Y') {
    return date($format, strtotime($date));
}

// Get base URL
function baseUrl($path = '') {
    return APP_URL . ($path ? '/' . ltrim($path, '/') : '');
}

// Redirect helper
function redirect($url) {
    header('Location: ' . $url);
    exit;
}

// Flash message functions
function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type, // success, error, warning, info
        'message' => $message
    ];
}

function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $flash;
    }
    return null;
}

function hasFlashMessage() {
    return isset($_SESSION['flash_message']);
}

// Image validation
function validateImage($file) {
    $errors = [];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'File upload error occurred.';
        return $errors;
    }
    
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        $errors[] = 'Image must be less than 2MB.';
    }
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime, ALLOWED_IMAGE_TYPES)) {
        $errors[] = 'Only JPEG and PNG images are allowed.';
    }
    
    return $errors;
}

// Convert image to base64
function imageToBase64($file) {
    $imageData = file_get_contents($file['tmp_name']);
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    return 'data:' . $mime . ';base64,' . base64_encode($imageData);
}

// Extract year from registration number robustly
function extractYearFromRegNumber($reg_number) {
    if (preg_match('/(19|20)\d{2}/', $reg_number, $matches)) {
        return $matches[0];
    }
    $parts = explode('/', $reg_number);
    return isset($parts[2]) ? $parts[2] : '';
}

// JSON response helper
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Escape output
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

// Check if request is POST
function isPost() {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

// Check if request is GET
function isGet() {
    return $_SERVER['REQUEST_METHOD'] === 'GET';
}

// Get POST data
function post($key, $default = null) {
    return $_POST[$key] ?? $default;
}

// Get GET data
function get($key, $default = null) {
    return $_GET[$key] ?? $default;
}

// Generate QR code data string
function generateQrData($student, $verifyUrl) {
    return sprintf(
        "Name: %s, %s %s\nReg No: %s\nFaculty: %s\nDept: %s\nVerify: %s",
        $student['last_name'],
        $student['first_name'],
        $student['middle_name'] ?? '',
        $student['reg_number'],
        $student['faculty'],
        $student['department'] ?? $student['programme'],
        $verifyUrl
    );
}

// Blood group options
function getBloodGroups() {
    return ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'];
}

// Programme options
function getProgrammes() {
    return ['Sandwich', 'IDELL'];
}

// Status badge HTML
function statusBadge($status) {
    if ($status === 'id_generated') {
        return '<span class="badge badge-success"><i class="icon-check"></i> Generated</span>';
    }
    return '<span class="badge badge-warning">Pending</span>';
}

// Truncate text
function truncate($text, $length = 50, $suffix = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . $suffix;
}
