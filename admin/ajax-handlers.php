<?php
require_once '../config.php';
require_once '../includes/session.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

initSession();

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    jsonResponse(['success' => false, 'error' => 'Unauthorized'], 401);
}

$action = post('action');

switch ($action) {
    case 'update_note':
        handleUpdateNote();
        break;
    
    case 'change_password':
        handleChangePassword();
        break;
    
    case 'delete_student':
        handleDeleteStudent();
        break;
    
    case 'mark_generated':
        handleMarkGenerated();
        break;

    case 'mark_printed':
        handleMarkPrinted();
        break;
    
    default:
        jsonResponse(['success' => false, 'error' => 'Invalid action'], 400);
}

function handleUpdateNote() {
    $studentId = post('student_id');
    $note = post('note', '');
    
    if (!$studentId) {
        jsonResponse(['success' => false, 'error' => 'Student ID is required']);
    }
    
    try {
        updateStudentNote($studentId, $note);
        jsonResponse(['success' => true, 'message' => 'Note updated successfully']);
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'error' => $e->getMessage()]);
    }
}

function handleChangePassword() {
    $studentId = post('student_id');
    $password = post('password');
    
    if (!$studentId || !$password) {
        jsonResponse(['success' => false, 'error' => 'Student ID and password are required']);
    }
    
    if (strlen($password) < 4) {
        jsonResponse(['success' => false, 'error' => 'Password must be at least 4 characters']);
    }
    
    try {
        updateStudentPassword($studentId, $password);
        jsonResponse(['success' => true, 'message' => 'Password updated successfully']);
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'error' => $e->getMessage()]);
    }
}

function handleDeleteStudent() {
    $studentId = post('student_id');
    
    if (!$studentId) {
        jsonResponse(['success' => false, 'error' => 'Student ID is required']);
    }
    
    try {
        deleteStudent($studentId);
        jsonResponse(['success' => true, 'message' => 'Student deleted successfully']);
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'error' => $e->getMessage()]);
    }
}

function handleMarkGenerated() {
    $regNumber = post('reg_number');
    
    if (!$regNumber) {
        jsonResponse(['success' => false, 'error' => 'Registration number is required']);
    }
    
    try {
        markStudentIdGenerated($regNumber);
        jsonResponse(['success' => true, 'message' => 'ID card marked as generated']);
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'error' => $e->getMessage()]);
    }
}

function handleMarkPrinted() {
    $studentId = post('student_id');
    $printed   = (int) post('printed'); // 1 = printed, 0 = unprinted

    if (!$studentId) {
        jsonResponse(['success' => false, 'error' => 'Student ID is required']);
    }

    try {
        markStudentPrinted($studentId, $printed);
        jsonResponse(['success' => true, 'printed' => $printed]);
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'error' => $e->getMessage()]);
    }
}
