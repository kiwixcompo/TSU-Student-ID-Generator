<?php
/**
 * TSU Student ID Generator – One-Time Migration / Verification Script
 * ====================================================================
 * What this does:
 *   - Verifies the Social Work & Community Development department is
 *     recognised in the live data.
 *   - Reports all IDELL students and their current course_of_study so
 *     you can see which ones may need updating.
 *   - Optionally bulk-updates a specific old course value to a new one
 *     (use the ?rename_from / ?rename_to query params).
 *
 * Security: protected by a secret key.
 *
 * Usage (read-only report):
 *   https://sig.tsuniversity.ng/migrate_idell_courses.php?key=MIGRATE_SIG_2026
 *
 * Rename a course value across all IDELL students (e.g. fix a typo):
 *   https://sig.tsuniversity.ng/migrate_idell_courses.php
 *       ?key=MIGRATE_SIG_2026
 *       &rename_from=B.+Sc.+Economics
 *       &rename_to=PG.+B.+Sc.+Economics
 *       &apply=1
 *
 * DELETE THIS FILE from the server after use.
 */

define('MIGRATE_KEY', 'MIGRATE_SIG_2026');

if (($_GET['key'] ?? '') !== MIGRATE_KEY) {
    http_response_code(403);
    die('403 Forbidden – supply ?key=MIGRATE_SIG_2026');
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/tsu-data.php';

header('Content-Type: text/plain; charset=utf-8');

$dryRun    = empty($_GET['apply']);
$renameFrom = trim($_GET['rename_from'] ?? '');
$renameTo   = trim($_GET['rename_to']   ?? '');

echo "=== TSU Student ID Generator – IDELL Course Migration ===\n";
echo "Time : " . date('Y-m-d H:i:s') . "\n";
echo "Mode : " . ($dryRun ? 'DRY RUN (add &apply=1 to apply changes)' : 'LIVE – applying changes') . "\n\n";

$db = getDB();

// ── 1. Verify Social Work dept exists in tsu-data ────────────────────────────
echo "--- 1. Social Work & Community Development check ---\n";
$tsuData = getTsuData();
$found = false;
foreach ($tsuData as $fac) {
    if ($fac['faculty'] === 'Faculty of Social Sciences') {
        foreach ($fac['departments'] as $dept) {
            if ($dept['name'] === 'Social Work & Community Development') {
                $found = true;
                echo "OK  Department found in tsu-data.php\n";
                echo "    Programmes: " . implode(', ', $dept['programmes']) . "\n";
                echo "    PG versions will show as: PG. " . implode(', PG. ', $dept['programmes']) . "\n";
            }
        }
    }
}
if (!$found) {
    echo "FAIL  'Social Work & Community Development' NOT found in tsu-data.php\n";
    echo "      Please check includes/tsu-data.php\n";
}

// ── 2. Report all IDELL students ─────────────────────────────────────────────
echo "\n--- 2. IDELL students report ---\n";
$stmt = $db->query("
    SELECT id, reg_number, last_name, first_name, course_of_study
    FROM students
    WHERE programme = 'IDELL'
    ORDER BY last_name, first_name
");
$students = $stmt->fetchAll();
echo "Total IDELL students: " . count($students) . "\n\n";

foreach ($students as $s) {
    $cos = $s['course_of_study'] ?? '(none)';
    $isPG = (stripos($cos, 'PG. ') === 0);
    echo ($isPG ? '[PG]  ' : '[REG] ')
        . "[{$s['reg_number']}] {$s['last_name']}, {$s['first_name']} — {$cos}\n";
}

// ── 3. Optional rename ────────────────────────────────────────────────────────
if ($renameFrom !== '' && $renameTo !== '') {
    echo "\n--- 3. Rename course_of_study ---\n";
    echo "From : \"{$renameFrom}\"\n";
    echo "To   : \"{$renameTo}\"\n";

    // Count affected rows first
    $count = $db->prepare("
        SELECT COUNT(*) FROM students
        WHERE programme = 'IDELL' AND course_of_study = ?
    ");
    $count->execute([$renameFrom]);
    $affected = (int) $count->fetchColumn();
    echo "Rows that will be updated: {$affected}\n";

    if ($affected === 0) {
        echo "Nothing to update.\n";
    } elseif ($dryRun) {
        echo "DRY RUN – no changes made. Add &apply=1 to apply.\n";
    } else {
        $upd = $db->prepare("
            UPDATE students
            SET course_of_study = ?
            WHERE programme = 'IDELL' AND course_of_study = ?
        ");
        $upd->execute([$renameTo, $renameFrom]);
        echo "DONE – {$affected} row(s) updated.\n";
    }
} else {
    echo "\n--- 3. Rename (not requested) ---\n";
    echo "To rename a course value, add:\n";
    echo "  &rename_from=OLD+VALUE&rename_to=NEW+VALUE&apply=1\n";
    echo "Example – mark all Economics IDELL students as PG:\n";
    echo "  &rename_from=B.+Sc.+Economics&rename_to=PG.+B.+Sc.+Economics&apply=1\n";
}

echo "\n=== Done ===\n";
echo "\nIMPORTANT: Delete this file from the server after use.\n";
