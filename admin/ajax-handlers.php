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

    case 'upload_photo':
        handleUploadPhoto();
        break;

    case 'rotate_photo':
        handleRotatePhoto();
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

function handleUploadPhoto() {
    $studentId = post('student_id');
    
    if (!$studentId) {
        jsonResponse(['success' => false, 'error' => 'Student ID is required']);
    }
    
    if (!isset($_FILES['passport_photo']) || $_FILES['passport_photo']['error'] !== UPLOAD_ERR_OK) {
        jsonResponse(['success' => false, 'error' => 'No file uploaded or file upload error occurred.']);
    }
    
    try {
        $imgErrors = validateImage($_FILES['passport_photo']);
        if (!empty($imgErrors)) {
            jsonResponse(['success' => false, 'error' => implode(' ', $imgErrors)]);
        }
        
        $passport_photo = imageToBase64($_FILES['passport_photo']);
        updateStudent($studentId, ['passport_photo' => $passport_photo]);
        
        jsonResponse([
            'success' => true, 
            'message' => 'Passport photo uploaded successfully', 
            'photo' => $passport_photo
        ]);
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'error' => $e->getMessage()]);
    }
}

function handleRotatePhoto() {
    $studentId = post('student_id');
    $direction = post('direction', 'cw');
    
    if (!$studentId) {
        jsonResponse(['success' => false, 'error' => 'Student ID is required']);
    }
    
    if (!in_array($direction, ['cw', 'ccw'])) {
        jsonResponse(['success' => false, 'error' => 'Invalid direction']);
    }
    
    try {
        rotateStudentPhoto($studentId, $direction);
        
        $db = getDB();
        $stmt = $db->prepare("SELECT passport_photo FROM students WHERE id = ?");
        $stmt->execute([$studentId]);
        $row = $stmt->fetch();
        
        jsonResponse([
            'success' => true,
            'message' => 'Image rotated successfully',
            'photo' => $row['passport_photo']
        ]);
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'error' => $e->getMessage()]);
    }
}
