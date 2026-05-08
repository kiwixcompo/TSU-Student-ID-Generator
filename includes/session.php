<?php
/**
 * Session Management Functions
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Start or resume session
function initSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// Check if user is logged in as student
function isStudentLoggedIn() {
    return isset($_SESSION['student_id']) && isset($_SESSION['student_reg']);
}

// Check if user is logged in as admin
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']) && isset($_SESSION['admin_username']);
}

// Get current student session data
function getStudentSession() {
    if (isStudentLoggedIn()) {
        return [
            'id' => $_SESSION['student_id'],
            'reg_number' => $_SESSION['student_reg']
        ];
    }
    return null;
}

// Get current admin session data
function getAdminSession() {
    if (isAdminLoggedIn()) {
        return [
            'id' => $_SESSION['admin_id'],
            'username' => $_SESSION['admin_username'],
            'programme_managed' => $_SESSION['admin_programme']
        ];
    }
    return null;
}

// Set student session
function setStudentSession($student) {
    $_SESSION['student_id'] = $student['id'];
    $_SESSION['student_reg'] = $student['reg_number'];
    $_SESSION['user_type'] = 'student';
}

// Set admin session
function setAdminSession($admin) {
    $_SESSION['admin_id'] = $admin['id'];
    $_SESSION['admin_username'] = $admin['username'];
    $_SESSION['admin_programme'] = $admin['programme_managed'];
    $_SESSION['user_type'] = 'admin';
}

// Destroy student session
function logoutStudent() {
    unset($_SESSION['student_id']);
    unset($_SESSION['student_reg']);
    unset($_SESSION['user_type']);
    session_destroy();
}

// Destroy admin session
function logoutAdmin() {
    unset($_SESSION['admin_id']);
    unset($_SESSION['admin_username']);
    unset($_SESSION['admin_programme']);
    unset($_SESSION['user_type']);
    session_destroy();
}

// Require student authentication
function requireStudentAuth() {
    if (!isStudentLoggedIn()) {
        header('Location: ' . APP_URL . '/student/login.php');
        exit;
    }
}

// Require admin authentication
function requireAdminAuth() {
    if (!isAdminLoggedIn()) {
        header('Location: ' . APP_URL . '/admin/login.php');
        exit;
    }
}

// Check if admin can access student (programme-based authorization)
function canAdminAccessStudent($admin_programme, $student_programme) {
    return $admin_programme === 'SuperAdmin' || $admin_programme === $student_programme;
}

// CSRF Token Functions
function generateCsrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
