<?php
/**
 * TSU Student ID Generator - Dynamic Avatar Server
 * Serves cached student passport photos from the database.
 */

// Disable global cache-control headers set in config.php so we can handle caching ourselves
define('AVATAR_SERVER', true);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';

// Clean out any cache-preventing headers that config.php might have set
if (function_exists('header_remove')) {
    header_remove('Cache-Control');
    header_remove('Pragma');
    header_remove('Expires');
}

$studentId = (int) ($_GET['id'] ?? 0);
if ($studentId <= 0) {
    header("HTTP/1.1 404 Not Found");
    exit("Invalid student ID.");
}

try {
    $db = getDB();
    // Retrieve only the passport_photo column
    $stmt = $db->prepare("SELECT passport_photo FROM students WHERE id = ?");
    $stmt->execute([$studentId]);
    $row = $stmt->fetch();
    
    if (!$row || empty($row['passport_photo'])) {
        header("HTTP/1.1 404 Not Found");
        exit("Avatar not found.");
    }
    
    $photo = $row['passport_photo'];
    
    // Parse data URL format: data:image/jpeg;base64,....
    if (strpos($photo, 'data:') === 0) {
        $parts = explode(',', $photo, 2);
        if (count($parts) === 2) {
            $header = $parts[0];
            $base64Data = $parts[1];
            
            // Extract MIME type
            $mime = 'image/jpeg';
            if (preg_match('/data:([^;]+)/', $header, $matches)) {
                $mime = $matches[1];
            }
            
            $binaryData = base64_decode($base64Data);
            
            // Send premium browser caching headers (cache for 30 days)
            $seconds_to_cache = 3600 * 24 * 30; // 30 days
            $ts = gmdate("D, d M Y H:i:s", time() + $seconds_to_cache) . " GMT";
            
            header("Content-Type: " . $mime);
            header("Content-Length: " . strlen($binaryData));
            header("Cache-Control: public, max-age=" . $seconds_to_cache . ", immutable");
            header("Expires: " . $ts);
            header("Pragma: cache");
            
            echo $binaryData;
            exit;
        }
    }
    
    // Fallback if formatting is unexpected
    header("HTTP/1.1 404 Not Found");
    exit("Invalid avatar format.");
    
} catch (Exception $e) {
    header("HTTP/1.1 500 Internal Server Error");
    exit("Database error.");
}
