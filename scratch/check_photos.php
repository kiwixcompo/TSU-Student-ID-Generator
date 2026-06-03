<?php
/**
 * Scratch script to analyze student passport photo orientations.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';

try {
    $db = getDB();
    $stmt = $db->query("SELECT id, reg_number, first_name, last_name, passport_photo FROM students WHERE passport_photo IS NOT NULL AND passport_photo != ''");
    $students = $stmt->fetchAll();

    echo "Analyzing " . count($students) . " student photos...\n";
    echo str_repeat("-", 80) . "\n";
    echo sprintf("%-5s | %-25s | %-20s | %-12s | %-12s | %-10s\n", "ID", "Name", "Reg Number", "Width", "Height", "Orientation");
    echo str_repeat("-", 80) . "\n";

    $landscapeCount = 0;

    foreach ($students as $student) {
        $photo = $student['passport_photo'];
        if (strpos($photo, 'data:') === 0) {
            $parts = explode(',', $photo, 2);
            if (count($parts) === 2) {
                $base64Data = $parts[1];
                $binaryData = base64_decode($base64Data);
                
                $img = @imagecreatefromstring($binaryData);
                if ($img) {
                    $width = imagesx($img);
                    $height = imagesy($img);
                    imagedestroy($img);

                    $orientation = ($width > $height) ? "Landscape" : (($width < $height) ? "Portrait" : "Square");
                    if ($width > $height) {
                        $landscapeCount++;
                    }

                    echo sprintf("%-5d | %-25s | %-20s | %-12d | %-12d | %-10s\n", 
                        $student['id'], 
                        $student['first_name'] . " " . $student['last_name'],
                        $student['reg_number'],
                        $width,
                        $height,
                        $orientation
                    );
                } else {
                    echo sprintf("%-5d | %-25s | %-20s | %-12s | %-12s | %-10s\n", 
                        $student['id'], 
                        $student['first_name'] . " " . $student['last_name'],
                        $student['reg_number'],
                        "ERR", "ERR", "Invalid Img"
                    );
                }
            }
        }
    }

    echo str_repeat("-", 80) . "\n";
    echo "Total Landscape (potentially flipped) photos: $landscapeCount\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
