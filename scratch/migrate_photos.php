<?php
/**
 * One-time migration script to:
 * 1. Alter table `students` to add column `photo_orientation` and index it.
 * 2. Scan all existing student passport photos in memory-safe batches of 50.
 * 3. Detect their orientation.
 * 4. Automatically rotate any landscape (flipped/sideways) photos to portrait.
 * 5. Update the DB record with the correct orientation.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

echo "Starting photo orientation migration...\n";

$db = getDB();

// 1. Add column and index if they don't exist
try {
    // Check if column already exists first to be clean
    $chk = $db->query("SHOW COLUMNS FROM students LIKE 'photo_orientation'")->fetch();
    if (!$chk) {
        echo "Adding 'photo_orientation' column to students table...\n";
        $db->exec("ALTER TABLE students ADD COLUMN photo_orientation VARCHAR(10) DEFAULT 'portrait' AFTER passport_photo");
        $db->exec("ALTER TABLE students ADD INDEX idx_photo_orientation (photo_orientation)");
        echo "Column and index added successfully.\n";
    } else {
        echo "'photo_orientation' column already exists.\n";
    }
} catch (PDOException $e) {
    die("Database migration error (adding column): " . $e->getMessage() . "\n");
}

// 2. Paginate over all students with photos to update their orientation
$batchSize = 50;
$offset = 0;
$totalProcessed = 0;
$totalLandscape = 0;
$totalRotated = 0;

while (true) {
    // Fetch a batch of student metadata + photo blob
    $stmt = $db->prepare("SELECT id, reg_number, first_name, last_name, passport_photo FROM students WHERE passport_photo IS NOT NULL AND passport_photo != '' LIMIT ? OFFSET ?");
    $stmt->bindValue(1, $batchSize, PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll();
    
    if (empty($rows)) {
        break;
    }
    
    foreach ($rows as $student) {
        $totalProcessed++;
        $studentId = $student['id'];
        $regNumber = $student['reg_number'];
        $fullName = $student['first_name'] . ' ' . $student['last_name'];
        $photo = $student['passport_photo'];
        
        $orientation = getBase64ImageOrientation($photo);
        
        if ($orientation === 'landscape') {
            $totalLandscape++;
            echo "Student ID $studentId ($fullName, $regNumber) has a Landscape photo. Automatically rotating to Portrait...\n";
            try {
                // Rotate clockwise (CW) by 90 degrees using the built-in helper
                rotateStudentPhoto($studentId, 'cw');
                
                // Fetch the updated orientation
                $stmtNew = $db->prepare("SELECT photo_orientation FROM students WHERE id = ?");
                $stmtNew->execute([$studentId]);
                $newOrientation = $stmtNew->fetchColumn();
                
                echo "-> Successfully rotated. New orientation: $newOrientation\n";
                $totalRotated++;
            } catch (Exception $ex) {
                echo "-> Error rotating image: " . $ex->getMessage() . "\n";
                // Fallback to update orientation to landscape if rotation failed
                $stmtUpdate = $db->prepare("UPDATE students SET photo_orientation = 'landscape' WHERE id = ?");
                $stmtUpdate->execute([$studentId]);
            }
        } else {
            // Save detected orientation (portrait/square/unknown)
            $stmtUpdate = $db->prepare("UPDATE students SET photo_orientation = ? WHERE id = ?");
            $stmtUpdate->execute([$orientation, $studentId]);
        }
    }
    
    $offset += $batchSize;
    // Clear variables to free memory
    unset($rows);
    gc_collect_cycles();
}

echo "--------------------------------------------------------\n";
echo "Migration Completed:\n";
echo "- Total student photos processed: $totalProcessed\n";
echo "- Flipped landscape photos located: $totalLandscape\n";
echo "- Flipped landscape photos auto-corrected: $totalRotated\n";
echo "--------------------------------------------------------\n";
