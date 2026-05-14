-- TSU Student ID Generator Database Schema
-- MySQL Database Setup
-- Import this into the existing database: tsuniver_tsu_id_generator

-- Students Table
CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    programme ENUM('Sandwich', 'IDELL') NOT NULL DEFAULT 'Sandwich',
    first_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100) DEFAULT NULL,
    last_name VARCHAR(100) NOT NULL,
    reg_number VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    blood_group ENUM('A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-') DEFAULT NULL,
    passport_photo LONGTEXT DEFAULT NULL COMMENT 'Base64 encoded image',
    faculty VARCHAR(255) DEFAULT NULL,
    department VARCHAR(255) DEFAULT NULL,
    course_of_study VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'id_generated') DEFAULT 'pending',
    admin_note TEXT DEFAULT NULL,
    printed TINYINT(1) DEFAULT 0,
    printed_at TIMESTAMP NULL DEFAULT NULL,
    INDEX idx_reg_number (reg_number),
    INDEX idx_programme (programme),
    INDEX idx_status (status),
    INDEX idx_faculty (faculty),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admins Table
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    programme_managed ENUM('Sandwich', 'IDELL', 'SuperAdmin') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert Default Admin Accounts
-- Password: 'password' (hashed with PASSWORD_DEFAULT)
INSERT INTO admins (username, password_hash, programme_managed) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'SuperAdmin'),
('sandwich_admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sandwich'),
('idell_admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'IDELL');

-- Add printed columns to existing installations (safe to run multiple times)
ALTER TABLE students
    ADD COLUMN IF NOT EXISTS printed TINYINT(1) DEFAULT 0,
    ADD COLUMN IF NOT EXISTS printed_at TIMESTAMP NULL DEFAULT NULL;

-- Relax NOT NULL constraints for bulk-import compatibility
ALTER TABLE students
    MODIFY COLUMN blood_group ENUM('A+','A-','B+','B-','O+','O-','AB+','AB-') DEFAULT NULL,
    MODIFY COLUMN passport_photo LONGTEXT DEFAULT NULL,
    MODIFY COLUMN faculty VARCHAR(255) DEFAULT NULL,
    MODIFY COLUMN department VARCHAR(255) DEFAULT NULL;

-- Sessions Table (for better session management)
CREATE TABLE IF NOT EXISTS sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_type ENUM('student', 'admin') NOT NULL,
    user_id INT NOT NULL,
    data TEXT,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_last_activity (last_activity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Faculty / Department / Course tables (dynamic management) ─────────────────
CREATE TABLE IF NOT EXISTS faculties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    faculty_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_dept (faculty_id, name),
    FOREIGN KEY (faculty_id) REFERENCES faculties(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_course (department_id, name),
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
