<?php
require_once '../config.php';
require_once '../includes/session.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

initSession();
requireAdminAuth();

$session = getAdminSession();
if ($session['programme_managed'] !== 'SuperAdmin') {
    jsonResponse(['success' => false, 'error' => 'SuperAdmin only'], 403);
}

$action = post('action') ?: get('action');
$db = getDB();

switch ($action) {

    // ── FACULTIES ─────────────────────────────────────────────────────────────
    case 'list_faculties':
        $rows = $db->query("SELECT id, name, sort_order FROM faculties ORDER BY sort_order, name")->fetchAll();
        jsonResponse(['success' => true, 'data' => $rows]);

    case 'seed_defaults':
        require_once '../includes/tsu-data.php';
        $static = _getTsuDataStatic();
        $fCount = $dCount = $cCount = 0;
        $insF = $db->prepare("INSERT IGNORE INTO faculties (name, sort_order) VALUES (?, ?)");
        $insD = $db->prepare("INSERT IGNORE INTO departments (faculty_id, name, sort_order) VALUES (?, ?, ?)");
        $insC = $db->prepare("INSERT IGNORE INTO courses (department_id, name, sort_order) VALUES (?, ?, ?)");
        $getF = $db->prepare("SELECT id FROM faculties WHERE name = ?");
        $getD = $db->prepare("SELECT id FROM departments WHERE faculty_id = ? AND name = ?");
        foreach ($static as $fi => $fac) {
            $insF->execute([$fac['faculty'], $fi]);
            $getF->execute([$fac['faculty']]);
            $facId = $getF->fetchColumn();
            if (!$facId) continue;
            $fCount++;
            foreach ($fac['departments'] as $di => $dept) {
                $insD->execute([$facId, $dept['name'], $di]);
                $getD->execute([$facId, $dept['name']]);
                $deptId = $getD->fetchColumn();
                if (!$deptId) continue;
                $dCount++;
                foreach ($dept['programmes'] as $ci => $course) {
                    $insC->execute([$deptId, $course, $ci]);
                    $cCount++;
                }
            }
        }
        jsonResponse(['success' => true, 'faculties' => $fCount, 'departments' => $dCount, 'courses' => $cCount]);

    case 'add_faculty':
        $name = trim(post('name'));
        if (!$name) jsonResponse(['success' => false, 'error' => 'Faculty name is required']);
        try {
            $db->prepare("INSERT INTO faculties (name) VALUES (?)")->execute([$name]);
            jsonResponse(['success' => true, 'id' => $db->lastInsertId(), 'name' => $name]);
        } catch (PDOException $e) {
            jsonResponse(['success' => false, 'error' => 'Faculty already exists']);
        }

    case 'rename_faculty':
        $id   = (int) post('id');
        $name = trim(post('name'));
        if (!$id || !$name) jsonResponse(['success' => false, 'error' => 'ID and name required']);
        try {
            $db->prepare("UPDATE faculties SET name = ? WHERE id = ?")->execute([$name, $id]);
            jsonResponse(['success' => true]);
        } catch (PDOException $e) {
            jsonResponse(['success' => false, 'error' => 'Name already in use']);
        }

    case 'delete_faculty':
        $id = (int) post('id');
        if (!$id) jsonResponse(['success' => false, 'error' => 'ID required']);
        $db->prepare("DELETE FROM faculties WHERE id = ?")->execute([$id]);
        jsonResponse(['success' => true]);

    // ── DEPARTMENTS ───────────────────────────────────────────────────────────
    case 'list_departments':
        $facId = (int) (post('faculty_id') ?: get('faculty_id'));
        if (!$facId) jsonResponse(['success' => false, 'error' => 'faculty_id required']);
        $rows = $db->prepare("SELECT id, name, sort_order FROM departments WHERE faculty_id = ? ORDER BY sort_order, name");
        $rows->execute([$facId]);
        jsonResponse(['success' => true, 'data' => $rows->fetchAll()]);

    case 'add_department':
        $facId = (int) post('faculty_id');
        $name  = trim(post('name'));
        if (!$facId || !$name) jsonResponse(['success' => false, 'error' => 'faculty_id and name required']);
        try {
            $db->prepare("INSERT INTO departments (faculty_id, name) VALUES (?, ?)")->execute([$facId, $name]);
            jsonResponse(['success' => true, 'id' => $db->lastInsertId(), 'name' => $name]);
        } catch (PDOException $e) {
            jsonResponse(['success' => false, 'error' => 'Department already exists in this faculty']);
        }

    case 'rename_department':
        $id   = (int) post('id');
        $name = trim(post('name'));
        if (!$id || !$name) jsonResponse(['success' => false, 'error' => 'ID and name required']);
        try {
            $db->prepare("UPDATE departments SET name = ? WHERE id = ?")->execute([$name, $id]);
            jsonResponse(['success' => true]);
        } catch (PDOException $e) {
            jsonResponse(['success' => false, 'error' => 'Name already in use']);
        }

    case 'delete_department':
        $id = (int) post('id');
        if (!$id) jsonResponse(['success' => false, 'error' => 'ID required']);
        $db->prepare("DELETE FROM departments WHERE id = ?")->execute([$id]);
        jsonResponse(['success' => true]);

    // ── COURSES ───────────────────────────────────────────────────────────────
    case 'list_courses':
        $deptId = (int) (post('department_id') ?: get('department_id'));
        if (!$deptId) jsonResponse(['success' => false, 'error' => 'department_id required']);
        $rows = $db->prepare("SELECT id, name FROM courses WHERE department_id = ? ORDER BY sort_order, name");
        $rows->execute([$deptId]);
        jsonResponse(['success' => true, 'data' => $rows->fetchAll()]);

    case 'add_course':
        $deptId = (int) post('department_id');
        $name   = trim(post('name'));
        if (!$deptId || !$name) jsonResponse(['success' => false, 'error' => 'department_id and name required']);
        try {
            $db->prepare("INSERT INTO courses (department_id, name) VALUES (?, ?)")->execute([$deptId, $name]);
            jsonResponse(['success' => true, 'id' => $db->lastInsertId(), 'name' => $name]);
        } catch (PDOException $e) {
            jsonResponse(['success' => false, 'error' => 'Course already exists in this department']);
        }

    case 'rename_course':
        $id   = (int) post('id');
        $name = trim(post('name'));
        if (!$id || !$name) jsonResponse(['success' => false, 'error' => 'ID and name required']);
        try {
            $db->prepare("UPDATE courses SET name = ? WHERE id = ?")->execute([$name, $id]);
            jsonResponse(['success' => true]);
        } catch (PDOException $e) {
            jsonResponse(['success' => false, 'error' => 'Name already in use']);
        }

    case 'delete_course':
        $id = (int) post('id');
        if (!$id) jsonResponse(['success' => false, 'error' => 'ID required']);
        $db->prepare("DELETE FROM courses WHERE id = ?")->execute([$id]);
        jsonResponse(['success' => true]);

    default:
        jsonResponse(['success' => false, 'error' => 'Unknown action'], 400);
}
