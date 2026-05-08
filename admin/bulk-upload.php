<?php
require_once '../config.php';
require_once '../includes/session.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

initSession();
requireAdminAuth();

$session = getAdminSession();

// Only SuperAdmin can bulk-upload
if ($session['programme_managed'] !== 'SuperAdmin') {
    redirect(baseUrl('admin/dashboard.php'));
}

$result   = null;
$preview  = [];
$parseErr = '';

// ── Helper: parse a CSV/Excel file into rows ──────────────────────────────────
function parseUploadedFile(array $file): array {
    $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $path = $file['tmp_name'];
    $rows = [];

    if ($ext === 'csv') {
        $handle = fopen($path, 'r');
        if (!$handle) throw new Exception('Cannot open uploaded file.');

        $headers = null;
        while (($line = fgetcsv($handle)) !== false) {
            if ($headers === null) {
                // Normalise header names
                $headers = array_map(fn($h) => strtolower(trim(preg_replace('/\s+/', '_', $h))), $line);
                continue;
            }
            if (count($line) !== count($headers)) continue; // skip malformed rows
            $rows[] = array_combine($headers, $line);
        }
        fclose($handle);

    } elseif (in_array($ext, ['xls', 'xlsx'])) {
        // Pure-PHP Excel reader (no Composer needed) — we use a minimal approach:
        // Read xlsx as a zip, parse the shared strings + sheet XML.
        if ($ext === 'xlsx') {
            $rows = parseXlsx($path);
        } else {
            throw new Exception('Legacy .xls format is not supported. Please save as .xlsx or .csv.');
        }
    } else {
        throw new Exception('Unsupported file type. Please upload .csv or .xlsx.');
    }

    return $rows;
}

// ── Minimal XLSX parser (no external library) ─────────────────────────────────
function parseXlsx(string $path): array {
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) throw new Exception('Cannot open .xlsx file.');

    // Shared strings
    $sharedStrings = [];
    $ssXml = $zip->getFromName('xl/sharedStrings.xml');
    if ($ssXml) {
        $ss = new SimpleXMLElement($ssXml);
        foreach ($ss->si as $si) {
            // Concatenate all <t> nodes (handles rich text)
            $text = '';
            foreach ($si->xpath('.//t') as $t) $text .= (string)$t;
            $sharedStrings[] = $text;
        }
    }

    // First sheet
    $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
    $zip->close();
    if (!$sheetXml) throw new Exception('Cannot read sheet data from .xlsx file.');

    $sheet   = new SimpleXMLElement($sheetXml);
    $headers = null;
    $rows    = [];

    foreach ($sheet->sheetData->row as $row) {
        $cells = [];
        foreach ($row->c as $c) {
            $t   = (string)($c['t'] ?? '');
            $val = (string)($c->v ?? '');
            if ($t === 's') $val = $sharedStrings[(int)$val] ?? '';
            elseif ($t === 'inlineStr') $val = (string)($c->is->t ?? '');
            $cells[] = $val;
        }

        if ($headers === null) {
            $headers = array_map(fn($h) => strtolower(trim(preg_replace('/\s+/', '_', $h))), $cells);
            continue;
        }
        // Pad short rows
        while (count($cells) < count($headers)) $cells[] = '';
        $rows[] = array_combine($headers, array_slice($cells, 0, count($headers)));
    }

    return $rows;
}

// ── Handle POST ───────────────────────────────────────────────────────────────
if (isPost()) {
    $action = post('action');

    if ($action === 'preview') {
        // Parse file and show preview
        try {
            if (empty($_FILES['csv_file']['tmp_name'])) throw new Exception('No file uploaded.');
            $preview  = parseUploadedFile($_FILES['csv_file']);
            if (empty($preview)) throw new Exception('The file appears to be empty or has no data rows.');
            // Store in session for the confirm step
            $_SESSION['bulk_preview'] = $preview;
        } catch (Exception $e) {
            $parseErr = $e->getMessage();
        }

    } elseif ($action === 'import') {
        // Confirm import from session-stored preview
        if (!empty($_SESSION['bulk_preview'])) {
            $result = bulkImportStudents($_SESSION['bulk_preview']);
            unset($_SESSION['bulk_preview']);
        } else {
            $parseErr = 'Session expired. Please re-upload the file.';
        }

    } elseif ($action === 'cancel') {
        unset($_SESSION['bulk_preview']);
        redirect(baseUrl('admin/bulk-upload.php'));
    }
}

// Restore preview from session if user hasn't confirmed yet
if (empty($preview) && !empty($_SESSION['bulk_preview'])) {
    $preview = $_SESSION['bulk_preview'];
}

$EXPECTED_COLS = ['programme','first_name','middle_name','last_name','reg_number',
                  'blood_group','faculty','department','course_of_study'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Upload Students – <?php echo APP_NAME; ?></title>
    <link rel="icon" type="image/png" href="<?php echo asset('public/tsu-logo.png'); ?>">
    <link rel="stylesheet" href="<?php echo asset('assets/css/style.css'); ?>">
    <style>
        body { background: var(--gray-50); }

        .page-header {
            background: linear-gradient(135deg, var(--primary-blue) 0%, #3d4a8f 100%);
            color: white;
            padding: .875rem 0;
            box-shadow: var(--shadow-lg);
            position: sticky; top: 0; z-index: 100;
        }
        .page-header .inner {
            max-width: 1200px; margin: 0 auto; padding: 0 1.5rem;
            display: flex; align-items: center; justify-content: space-between; gap: 1rem;
        }
        .page-header h1 { font-size: 1.25rem; color: white; margin: 0; }

        .main { max-width: 1200px; margin: 1.5rem auto; padding: 0 1.5rem; }

        /* ── Upload card ── */
        .upload-card {
            background: white; border-radius: 1rem;
            box-shadow: var(--shadow); padding: 2rem; margin-bottom: 1.5rem;
        }
        .upload-card h2 { font-size: 1.1rem; margin-bottom: 1.25rem; color: var(--gray-900); }

        .drop-zone {
            border: 2px dashed var(--gray-300); border-radius: .75rem;
            padding: 2.5rem 1rem; text-align: center; cursor: pointer;
            transition: all .2s; background: var(--gray-50);
        }
        .drop-zone:hover, .drop-zone.drag-over {
            border-color: var(--primary-blue); background: #eff6ff;
        }
        .drop-zone svg { width: 48px; height: 48px; stroke: var(--gray-400); stroke-width: 1.5; margin-bottom: .75rem; }
        .drop-zone p { margin: .25rem 0; color: var(--gray-600); font-size: .9rem; }
        .drop-zone strong { color: var(--primary-blue); }
        #fileInput { display: none; }
        #fileName { margin-top: .75rem; font-size: .875rem; font-weight: 600; color: var(--gray-700); }

        /* ── Template box ── */
        .template-box {
            background: #f0fdf4; border: 1px solid #86efac;
            border-radius: .75rem; padding: 1rem 1.25rem; margin-top: 1.25rem;
        }
        .template-box h3 { font-size: .9rem; font-weight: 700; color: #166534; margin-bottom: .5rem; }
        .template-box code {
            display: block; background: white; border: 1px solid #d1fae5;
            border-radius: .5rem; padding: .75rem 1rem; font-size: .8rem;
            color: #374151; white-space: pre-wrap; word-break: break-all;
            margin-bottom: .75rem;
        }
        .template-box ul { margin: 0; padding-left: 1.25rem; font-size: .8125rem; color: #374151; }
        .template-box li { margin-bottom: .25rem; }

        /* ── Preview table ── */
        .preview-card {
            background: white; border-radius: 1rem;
            box-shadow: var(--shadow); overflow: hidden; margin-bottom: 1.5rem;
        }
        .preview-header {
            padding: 1rem 1.25rem; border-bottom: 1px solid var(--gray-200);
            display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: .75rem;
        }
        .preview-header h2 { font-size: 1.1rem; margin: 0; color: var(--gray-900); }
        .preview-actions { display: flex; gap: .75rem; }

        .tbl-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: .8125rem; }
        thead { background: var(--gray-50); }
        th { padding: .6rem .875rem; text-align: left; font-size: .7rem; font-weight: 700;
             color: var(--gray-700); text-transform: uppercase; letter-spacing: .5px; white-space: nowrap; }
        td { padding: .6rem .875rem; border-bottom: 1px solid var(--gray-100); }
        tbody tr:hover { background: var(--gray-50); }
        .missing { color: #d97706; font-style: italic; font-size: .75rem; }
        .row-num { color: var(--gray-400); font-size: .75rem; }

        /* ── Result card ── */
        .result-card {
            background: white; border-radius: 1rem;
            box-shadow: var(--shadow); padding: 1.5rem; margin-bottom: 1.5rem;
        }
        .result-stats { display: flex; gap: 1.5rem; flex-wrap: wrap; margin-bottom: 1rem; }
        .stat-pill {
            display: flex; align-items: center; gap: .5rem;
            padding: .5rem 1rem; border-radius: 999px; font-weight: 700; font-size: .875rem;
        }
        .stat-pill.green { background: #dcfce7; color: #166534; }
        .stat-pill.yellow { background: #fef9c3; color: #854d0e; }
        .stat-pill.red { background: #fee2e2; color: #991b1b; }
        .error-list { max-height: 200px; overflow-y: auto; }
        .error-list li { font-size: .8125rem; color: #991b1b; padding: .2rem 0; }
    </style>
</head>
<body>

<div class="page-header">
    <div class="inner">
        <div style="display:flex;align-items:center;gap:.875rem;">
            <img src="../public/tsu-logo.png" alt="TSU" style="width:38px;height:38px;object-fit:contain;">
            <h1>Bulk Upload Students</h1>
        </div>
        <a href="dashboard.php" class="btn btn-secondary btn-sm">← Back to Dashboard</a>
    </div>
</div>

<div class="main">

    <?php if ($parseErr): ?>
    <div class="alert alert-error" style="margin-bottom:1rem;">
        <svg style="width:18px;height:18px;flex-shrink:0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <?php echo e($parseErr); ?>
    </div>
    <?php endif; ?>

    <?php if ($result): ?>
    <!-- Import Result -->
    <div class="result-card">
        <h2 style="font-size:1.1rem;margin-bottom:1rem;color:var(--gray-900);">Import Complete</h2>
        <div class="result-stats">
            <div class="stat-pill green">
                <svg style="width:16px;height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <?php echo $result['inserted']; ?> Imported
            </div>
            <div class="stat-pill yellow">
                <svg style="width:16px;height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <?php echo $result['skipped']; ?> Skipped
            </div>
            <?php if (!empty($result['errors'])): ?>
            <div class="stat-pill red">
                <svg style="width:16px;height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                <?php echo count($result['errors']); ?> Errors
            </div>
            <?php endif; ?>
        </div>
        <?php if (!empty($result['errors'])): ?>
        <details>
            <summary style="cursor:pointer;font-size:.875rem;font-weight:600;color:#991b1b;margin-bottom:.5rem;">
                Show error details
            </summary>
            <ul class="error-list">
                <?php foreach ($result['errors'] as $err): ?>
                <li><?php echo e($err); ?></li>
                <?php endforeach; ?>
            </ul>
        </details>
        <?php endif; ?>
        <div style="margin-top:1rem;display:flex;gap:.75rem;">
            <a href="dashboard.php" class="btn btn-primary btn-sm">View Students</a>
            <a href="bulk-upload.php" class="btn btn-secondary btn-sm">Upload Another File</a>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($preview) && $result === null): ?>
    <!-- Preview Table -->
    <div class="preview-card">
        <div class="preview-header">
            <div>
                <h2>Preview — <?php echo count($preview); ?> rows</h2>
                <p style="margin:.25rem 0 0;font-size:.8125rem;color:var(--gray-600);">
                    Review the data below. Missing optional fields are shown in amber.
                    Rows with no reg number or name will be skipped automatically.
                </p>
            </div>
            <div class="preview-actions">
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="action" value="cancel">
                    <button type="submit" class="btn btn-secondary btn-sm">Cancel</button>
                </form>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="action" value="import">
                    <button type="submit" class="btn btn-primary btn-sm">
                        Confirm Import (<?php echo count($preview); ?> rows)
                    </button>
                </form>
            </div>
        </div>
        <div class="tbl-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Reg Number</th>
                        <th>Last Name</th>
                        <th>First Name</th>
                        <th>Middle Name</th>
                        <th>Programme</th>
                        <th>Blood Group</th>
                        <th>Faculty</th>
                        <th>Department</th>
                        <th>Course of Study</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($preview as $i => $row): ?>
                <?php
                    $reg  = trim($row['reg_number']  ?? $row['matric_number'] ?? $row['matric'] ?? '');
                    $fn   = trim($row['first_name']   ?? '');
                    $ln   = trim($row['last_name']    ?? '');
                    $mn   = trim($row['middle_name']  ?? '');
                    $prog = trim($row['programme']    ?? '');
                    $bg   = trim($row['blood_group']  ?? '');
                    $fac  = trim($row['faculty']      ?? '');
                    $dep  = trim($row['department']   ?? '');
                    $cos  = trim($row['course_of_study'] ?? '');
                    $rowErr = ($reg === '' || $fn === '' || $ln === '');
                ?>
                <tr style="<?php echo $rowErr ? 'background:#fff7ed;' : ''; ?>">
                    <td class="row-num"><?php echo $i + 2; ?></td>
                    <td><?php echo $reg !== '' ? e($reg) : '<span class="missing">⚠ missing</span>'; ?></td>
                    <td><?php echo $ln  !== '' ? e($ln)  : '<span class="missing">⚠ missing</span>'; ?></td>
                    <td><?php echo $fn  !== '' ? e($fn)  : '<span class="missing">⚠ missing</span>'; ?></td>
                    <td><?php echo $mn  !== '' ? e($mn)  : '<span class="missing">—</span>'; ?></td>
                    <td><?php echo $prog!== '' ? e($prog): '<span class="missing">Sandwich</span>'; ?></td>
                    <td><?php echo $bg  !== '' ? e($bg)  : '<span class="missing">—</span>'; ?></td>
                    <td><?php echo $fac !== '' ? e($fac) : '<span class="missing">—</span>'; ?></td>
                    <td><?php echo $dep !== '' ? e($dep) : '<span class="missing">—</span>'; ?></td>
                    <td><?php echo $cos !== '' ? e($cos) : '<span class="missing">—</span>'; ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <?php if (empty($preview) && $result === null): ?>
    <!-- Upload Form -->
    <div class="upload-card">
        <h2>Upload Student List</h2>

        <form method="POST" enctype="multipart/form-data" id="uploadForm">
            <input type="hidden" name="action" value="preview">

            <div class="drop-zone" id="dropZone" onclick="document.getElementById('fileInput').click()">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
                <p><strong>Click to browse</strong> or drag & drop your file here</p>
                <p>Supported formats: <strong>.csv</strong> or <strong>.xlsx</strong></p>
                <div id="fileName"></div>
                <input type="file" name="csv_file" id="fileInput" accept=".csv,.xlsx" required>
            </div>

            <div style="margin-top:1.25rem;text-align:right;">
                <button type="submit" class="btn btn-primary" id="previewBtn" disabled>
                    Preview Data →
                </button>
            </div>
        </form>

        <!-- Template -->
        <div class="template-box">
            <h3>📋 Required Column Headers (first row of your file)</h3>
            <code>programme,first_name,middle_name,last_name,reg_number,blood_group,faculty,department,course_of_study</code>
            <ul>
                <li><strong>programme</strong> — <code>Sandwich</code> or <code>IDELL</code> (defaults to Sandwich if blank)</li>
                <li><strong>first_name</strong>, <strong>last_name</strong>, <strong>reg_number</strong> — <em>required</em>; rows missing these are skipped</li>
                <li><strong>middle_name</strong>, <strong>blood_group</strong>, <strong>faculty</strong>, <strong>department</strong>, <strong>course_of_study</strong> — optional; can be filled later via Edit Profile</li>
                <li>Blood group must be one of: A+, A-, B+, B-, O+, O-, AB+, AB-</li>
                <li>Registration number format: <code>TSU/SW/2024/001</code></li>
                <li>Default login password for each student = their registration number</li>
            </ul>
            <button onclick="downloadTemplate()" class="btn btn-secondary btn-sm" style="margin-top:.5rem;">
                ⬇ Download CSV Template
            </button>
        </div>
    </div>
    <?php endif; ?>

</div>

<script>
// ── Drag & drop ──
const dropZone  = document.getElementById('dropZone');
const fileInput = document.getElementById('fileInput');
const fileName  = document.getElementById('fileName');
const previewBtn= document.getElementById('previewBtn');

if (dropZone) {
    dropZone.addEventListener('dragover',  e => { e.preventDefault(); dropZone.classList.add('drag-over'); });
    dropZone.addEventListener('dragleave', () => dropZone.classList.remove('drag-over'));
    dropZone.addEventListener('drop', e => {
        e.preventDefault();
        dropZone.classList.remove('drag-over');
        const f = e.dataTransfer.files[0];
        if (f) setFile(f);
    });
    fileInput.addEventListener('change', () => {
        if (fileInput.files[0]) setFile(fileInput.files[0]);
    });
}

function setFile(f) {
    const allowed = ['csv','xlsx'];
    const ext = f.name.split('.').pop().toLowerCase();
    if (!allowed.includes(ext)) {
        alert('Please upload a .csv or .xlsx file.');
        return;
    }
    // Assign to the real input via DataTransfer
    const dt = new DataTransfer();
    dt.items.add(f);
    fileInput.files = dt.files;
    fileName.textContent = '📄 ' + f.name + ' (' + (f.size / 1024).toFixed(1) + ' KB)';
    previewBtn.disabled = false;
}

// ── Download CSV template ──
function downloadTemplate() {
    const header = 'programme,first_name,middle_name,last_name,reg_number,blood_group,faculty,department,course_of_study';
    const sample = 'Sandwich,John,Michael,Doe,TSU/SW/2024/001,O+,Faculty of Agriculture,Agronomy,B. Agric- Agronomy';
    const blob   = new Blob([header + '\n' + sample], { type: 'text/csv' });
    const a      = document.createElement('a');
    a.href       = URL.createObjectURL(blob);
    a.download   = 'tsu_students_template.csv';
    a.click();
}
</script>
</body>
</html>
