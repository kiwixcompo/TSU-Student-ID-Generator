<?php
/**
 * Database Connection and Helper Functions
 */

require_once __DIR__ . '/../config.php';

class Database {
    private static $instance = null;
    private $conn;
    
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $this->conn = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->conn;
    }
    
    // Prevent cloning
    private function __clone() {}
    
    // Prevent unserialization
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

// Helper function to get database connection
function getDB() {
    return Database::getInstance()->getConnection();
}

// Columns safe for list queries — excludes the huge passport_photo BLOB
define('STUDENT_LIST_COLS',
    'id, programme, first_name, middle_name, last_name, reg_number,
     blood_group, faculty, department, course_of_study,
     created_at, status, admin_note, printed, printed_at'
);

// Student Functions
function registerStudent($data) {
    $db = getDB();
    
    // Check if registration number already exists
    $stmt = $db->prepare("SELECT id FROM students WHERE reg_number = ?");
    $stmt->execute([$data['reg_number']]);
    if ($stmt->fetch()) {
        throw new Exception('Student with this registration number already exists.');
    }
    
    // Hash password (default to reg_number)
    $password = password_hash($data['reg_number'], PASSWORD_DEFAULT);
    
    $stmt = $db->prepare("
        INSERT INTO students (
            programme, first_name, middle_name, last_name, reg_number, 
            password, blood_group, passport_photo, faculty, department, course_of_study
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    return $stmt->execute([
        $data['programme'],
        $data['first_name'],
        $data['middle_name'] ?? null,
        $data['last_name'],
        $data['reg_number'],
        $password,
        $data['blood_group'],
        $data['passport_photo'],
        $data['faculty'],
        $data['department'],
        $data['course_of_study'] ?? null
    ]);
}

function getStudents($programme = null) {
    $db = getDB();
    $cols = STUDENT_LIST_COLS;

    if ($programme && $programme !== 'SuperAdmin') {
        $stmt = $db->prepare("SELECT $cols FROM students WHERE programme = ? ORDER BY created_at DESC");
        $stmt->execute([$programme]);
    } else {
        $stmt = $db->query("SELECT $cols FROM students ORDER BY created_at DESC");
    }

    return $stmt->fetchAll();
}

function getStudentById($id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getStudentByRegNumber($reg_number) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM students WHERE reg_number = ?");
    $stmt->execute([$reg_number]);
    return $stmt->fetch();
}

function updateStudentStatus($reg_number, $status) {
    $db = getDB();
    $stmt = $db->prepare("UPDATE students SET status = ? WHERE reg_number = ?");
    return $stmt->execute([$status, $reg_number]);
}

function deleteStudent($id) {
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM students WHERE id = ?");
    return $stmt->execute([$id]);
}

function updateStudentPassword($id, $new_password) {
    $db = getDB();
    $hashed = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $db->prepare("UPDATE students SET password = ? WHERE id = ?");
    return $stmt->execute([$hashed, $id]);
}

function updateStudentNote($id, $note) {
    $db = getDB();
    $stmt = $db->prepare("UPDATE students SET admin_note = ? WHERE id = ?");
    return $stmt->execute([$note, $id]);
}

function markStudentIdGenerated($reg_number) {
    $db = getDB();
    $stmt = $db->prepare("UPDATE students SET status = 'id_generated' WHERE reg_number = ?");
    return $stmt->execute([$reg_number]);
}

function markStudentPrinted($id, $printed) {
    $db = getDB();
    if ($printed) {
        $stmt = $db->prepare("UPDATE students SET printed = 1, printed_at = NOW() WHERE id = ?");
    } else {
        $stmt = $db->prepare("UPDATE students SET printed = 0, printed_at = NULL WHERE id = ?");
    }
    return $stmt->execute([$id]);
}

// Update full student profile (used by edit-student page)
function updateStudent($id, $data) {
    $db = getDB();

    $fields = [];
    $values = [];

    $allowed = [
        'programme', 'first_name', 'middle_name', 'last_name', 'reg_number',
        'blood_group', 'faculty', 'department', 'course_of_study',
        'admin_note', 'status'
    ];

    foreach ($allowed as $field) {
        if (array_key_exists($field, $data)) {
            $fields[] = "$field = ?";
            $values[] = $data[$field] === '' ? null : $data[$field];
        }
    }

    // Handle passport photo separately (only update if provided)
    if (!empty($data['passport_photo'])) {
        $fields[] = "passport_photo = ?";
        $values[] = $data['passport_photo'];
    }

    if (empty($fields)) return false;

    $values[] = $id;
    $sql = "UPDATE students SET " . implode(', ', $fields) . " WHERE id = ?";
    $stmt = $db->prepare($sql);
    return $stmt->execute($values);
}

function searchStudents($query, $programme = null) {
    $db   = getDB();
    $cols = STUDENT_LIST_COLS;
    $like = '%' . $query . '%';

    if ($programme && $programme !== 'SuperAdmin') {
        $stmt = $db->prepare("
            SELECT $cols FROM students
            WHERE programme = ?
              AND (first_name LIKE ? OR last_name LIKE ? OR middle_name LIKE ? OR reg_number LIKE ?)
            ORDER BY last_name, first_name
            LIMIT 50
        ");
        $stmt->execute([$programme, $like, $like, $like, $like]);
    } else {
        $stmt = $db->prepare("
            SELECT $cols FROM students
            WHERE first_name LIKE ? OR last_name LIKE ? OR middle_name LIKE ? OR reg_number LIKE ?
            ORDER BY last_name, first_name
            LIMIT 50
        ");
        $stmt->execute([$like, $like, $like, $like]);
    }

    return $stmt->fetchAll();
}

// Bulk import students from parsed CSV/Excel rows
// Returns ['inserted'=>N, 'skipped'=>N, 'errors'=>[...]]
function bulkImportStudents(array $rows) {
    $db  = getDB();
    $inserted = 0;
    $skipped  = 0;
    $errors   = [];

    foreach ($rows as $i => $row) {
        $line = $i + 2; // human-readable row number (1-indexed + header)

        $reg = trim($row['reg_number'] ?? '');
        if ($reg === '') {
            $errors[] = "Row $line: Registration number is required — skipped.";
            $skipped++;
            continue;
        }

        $firstName = trim($row['first_name'] ?? '');
        $lastName  = trim($row['last_name']  ?? '');
        if ($firstName === '' || $lastName === '') {
            $errors[] = "Row $line ($reg): First name and last name are required — skipped.";
            $skipped++;
            continue;
        }

        // Check duplicate
        $chk = $db->prepare("SELECT id FROM students WHERE reg_number = ?");
        $chk->execute([$reg]);
        if ($chk->fetch()) {
            $errors[] = "Row $line ($reg): Already exists — skipped.";
            $skipped++;
            continue;
        }

        $programme = trim($row['programme'] ?? 'Sandwich');
        if (!in_array($programme, ['Sandwich', 'IDELL'])) $programme = 'Sandwich';

        $bloodGroup = strtoupper(trim($row['blood_group'] ?? ''));
        $validBG    = ['A+','A-','B+','B-','O+','O-','AB+','AB-'];
        if (!in_array($bloodGroup, $validBG)) $bloodGroup = null;

        $stmt = $db->prepare("
            INSERT INTO students
                (programme, first_name, middle_name, last_name, reg_number,
                 password, blood_group, faculty, department, course_of_study)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $programme,
            $firstName,
            trim($row['middle_name'] ?? '') ?: null,
            $lastName,
            $reg,
            password_hash($reg, PASSWORD_DEFAULT),
            $bloodGroup,
            trim($row['faculty']        ?? '') ?: null,
            trim($row['department']     ?? '') ?: null,
            trim($row['course_of_study']?? '') ?: null,
        ]);

        $inserted++;
    }

    return compact('inserted', 'skipped', 'errors');
}

// Admin Functions
function getAdmin($username) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    return $stmt->fetch();
}

function verifyAdminPassword($username, $password) {
    $admin = getAdmin($username);
    if ($admin && password_verify($password, $admin['password_hash'])) {
        return $admin;
    }
    return false;
}

function verifyStudentPassword($reg_number, $password) {
    $db   = getDB();
    // Only fetch the fields needed for auth — avoids loading the passport_photo BLOB
    $stmt = $db->prepare("SELECT id, reg_number, password, programme FROM students WHERE reg_number = ?");
    $stmt->execute([$reg_number]);
    $student = $stmt->fetch();
    if ($student && password_verify($password, $student['password'])) {
        return $student;
    }
    return false;
}
