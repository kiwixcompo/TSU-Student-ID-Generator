<?php
require_once '../config.php';
require_once '../includes/session.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/tsu-data.php';

initSession();
requireAdminAuth();

$session = getAdminSession();
$tsuData = getTsuData();

$student  = null;
$saved    = false;
$saveErr  = '';
$searchQ  = get('q', '');
$results  = [];
$editId   = (int) get('id', 0);

// ── Search ────────────────────────────────────────────────────────────────────
if ($searchQ !== '') {
    $results = searchStudents($searchQ, $session['programme_managed']);
}

// ── Load student for editing ──────────────────────────────────────────────────
if ($editId) {
    $student = getStudentById($editId);
    if (!$student) { $student = null; $editId = 0; }
    elseif (!canAdminAccessStudent($session['programme_managed'], $student['programme'])) {
        $student = null; $editId = 0;
    }
}

// ── Handle save ───────────────────────────────────────────────────────────────
if (isPost() && post('action') === 'save' && $editId) {
    try {
        $data = [
            'programme'      => post('programme'),
            'first_name'     => sanitize(post('first_name')),
            'middle_name'    => sanitize(post('middle_name')),
            'last_name'      => sanitize(post('last_name')),
            'reg_number'     => sanitize(post('reg_number')),
            'blood_group'    => post('blood_group') ?: null,
            'faculty'        => sanitize(post('faculty')),
            'department'     => sanitize(post('department')),
            'course_of_study'=> sanitize(post('course_of_study')),
            'admin_note'     => sanitize(post('admin_note')),
        ];

        if (empty($data['first_name']) || empty($data['last_name']) || empty($data['reg_number'])) {
            throw new Exception('First name, last name and registration number are required.');
        }

        // Photo upload
        if (!empty($_FILES['passport_photo']['tmp_name'])) {
            $imgErrors = validateImage($_FILES['passport_photo']);
            if (!empty($imgErrors)) throw new Exception(implode(' ', $imgErrors));
            $data['passport_photo'] = imageToBase64($_FILES['passport_photo']);
        }

        updateStudent($editId, $data);
        $saved   = true;
        $student = getStudentById($editId); // refresh
    } catch (Exception $e) {
        $saveErr = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student Profile – <?php echo APP_NAME; ?></title>
    <link rel="icon" type="image/png" href="<?php echo asset('public/tsu-logo.png'); ?>">
    <link rel="stylesheet" href="<?php echo asset('assets/css/style.css'); ?>">
    <style>
        body { background: var(--gray-50); }

        .page-header {
            background: linear-gradient(135deg, var(--primary-blue) 0%, #3d4a8f 100%);
            color: white; padding: .875rem 0;
            box-shadow: var(--shadow-lg); position: sticky; top: 0; z-index: 100;
        }
        .page-header .inner {
            max-width: 1100px; margin: 0 auto; padding: 0 1.5rem;
            display: flex; align-items: center; justify-content: space-between; gap: 1rem;
        }
        .page-header h1 { font-size: 1.25rem; color: white; margin: 0; }

        .main { max-width: 1100px; margin: 1.5rem auto; padding: 0 1.5rem; }

        /* ── Search ── */
        .search-card {
            background: white; border-radius: 1rem;
            box-shadow: var(--shadow); padding: 1.5rem; margin-bottom: 1.5rem;
        }
        .search-card h2 { font-size: 1rem; margin-bottom: 1rem; color: var(--gray-900); }
        .search-row { display: flex; gap: .75rem; }
        .search-row input {
            flex: 1; padding: .6rem 1rem; border: 2px solid var(--gray-300);
            border-radius: var(--radius); font-size: .9rem;
        }
        .search-row input:focus { outline: none; border-color: var(--primary-blue); }

        /* ── Results list ── */
        .results-list { margin-top: 1rem; display: flex; flex-direction: column; gap: .5rem; }
        .result-item {
            display: flex; align-items: center; gap: 1rem;
            padding: .75rem 1rem; border: 1px solid var(--gray-200);
            border-radius: .75rem; background: var(--gray-50);
            text-decoration: none; color: inherit; transition: all .2s;
        }
        .result-item:hover { border-color: var(--primary-blue); background: #eff6ff; }
        .result-avatar {
            width: 44px; height: 44px; border-radius: .5rem;
            object-fit: cover; border: 2px solid var(--gray-200); flex-shrink: 0;
        }
        .result-avatar-placeholder {
            width: 44px; height: 44px; border-radius: .5rem;
            background: var(--gray-200); display: flex; align-items: center;
            justify-content: center; font-weight: 700; color: var(--gray-500);
            font-size: 1.1rem; flex-shrink: 0;
        }
        .result-info { flex: 1; }
        .result-info strong { font-size: .9rem; color: var(--gray-900); display: block; }
        .result-info span { font-size: .8rem; color: var(--gray-500); }
        .result-missing { font-size: .75rem; color: #d97706; font-weight: 600; }

        /* ── Edit form ── */
        .edit-card {
            background: white; border-radius: 1rem;
            box-shadow: var(--shadow); overflow: hidden;
        }
        .edit-card-header {
            background: linear-gradient(135deg, var(--primary-blue) 0%, #3d4a8f 100%);
            padding: 1.25rem 1.5rem; color: white;
            display: flex; align-items: center; justify-content: space-between;
        }
        .edit-card-header h2 { color: white; font-size: 1.1rem; margin: 0; }
        .edit-card-body { padding: 1.5rem; }

        .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .form-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; }
        @media (max-width: 640px) {
            .form-grid-2, .form-grid-3 { grid-template-columns: 1fr; }
        }

        .section-title {
            font-size: .9rem; font-weight: 700; color: var(--gray-700);
            text-transform: uppercase; letter-spacing: .5px;
            border-bottom: 2px solid var(--gray-200);
            padding-bottom: .5rem; margin: 1.25rem 0 1rem;
        }
        .section-title:first-child { margin-top: 0; }

        /* ── Photo upload ── */
        .photo-section { display: flex; gap: 1.5rem; align-items: flex-start; flex-wrap: wrap; }
        .current-photo {
            width: 120px; height: 140px; object-fit: cover;
            border-radius: .75rem; border: 3px solid var(--gray-200);
            flex-shrink: 0;
        }
        .photo-placeholder-box {
            width: 120px; height: 140px; border-radius: .75rem;
            border: 2px dashed var(--gray-300); background: var(--gray-50);
            display: flex; flex-direction: column; align-items: center;
            justify-content: center; color: var(--gray-400); font-size: .8rem;
            text-align: center; flex-shrink: 0;
        }
        .photo-placeholder-box svg { width: 32px; height: 32px; stroke: currentColor; stroke-width: 1.5; margin-bottom: .5rem; }
        .photo-upload-area {
            flex: 1; border: 2px dashed var(--gray-300); border-radius: .75rem;
            padding: 1.25rem; text-align: center; cursor: pointer;
            transition: all .2s; background: var(--gray-50); min-width: 200px;
        }
        .photo-upload-area:hover { border-color: var(--primary-blue); background: #eff6ff; }
        .photo-upload-area p { margin: .25rem 0; font-size: .8125rem; color: var(--gray-600); }
        #photoPreviewImg { width: 100px; height: 120px; object-fit: cover; border-radius: .5rem; margin-bottom: .5rem; display: none; }

        .form-actions { display: flex; gap: .75rem; justify-content: flex-end; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid var(--gray-200); }
    </style>
</head>
<body>

<div class="page-header">
    <div class="inner">
        <div style="display:flex;align-items:center;gap:.875rem;">
            <img src="../public/tsu-logo.png" alt="TSU" style="width:38px;height:38px;object-fit:contain;">
            <h1>Edit Student Profile</h1>
        </div>
        <a href="dashboard.php" class="btn btn-secondary btn-sm">← Back to Dashboard</a>
    </div>
</div>

<div class="main">

    <?php if ($saved): ?>
    <div class="alert alert-success" style="margin-bottom:1rem;">
        <svg style="width:18px;height:18px;flex-shrink:0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
        Profile saved successfully.
    </div>
    <?php endif; ?>

    <?php if ($saveErr): ?>
    <div class="alert alert-error" style="margin-bottom:1rem;">
        <svg style="width:18px;height:18px;flex-shrink:0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <?php echo e($saveErr); ?>
    </div>
    <?php endif; ?>

    <!-- Search -->
    <div class="search-card">
        <h2>Search Student</h2>
        <form method="GET">
            <div class="search-row">
                <input type="text" name="q" value="<?php echo e($searchQ); ?>"
                       placeholder="Search by name or registration number…" autofocus>
                <button type="submit" class="btn btn-primary btn-sm">Search</button>
                <?php if ($searchQ): ?>
                <a href="edit-student.php" class="btn btn-secondary btn-sm">Clear</a>
                <?php endif; ?>
            </div>
        </form>

        <?php if ($searchQ && empty($results)): ?>
        <p style="margin-top:.75rem;color:var(--gray-500);font-size:.875rem;">No students found for "<?php echo e($searchQ); ?>".</p>
        <?php endif; ?>

        <?php if (!empty($results)): ?>
        <div class="results-list">
            <?php foreach ($results as $r): ?>
            <?php
                $missing = [];
                if (empty($r['blood_group']))    $missing[] = 'blood group';
                if (empty($r['passport_photo'])) $missing[] = 'photo';
                if (empty($r['faculty']))        $missing[] = 'faculty';
                if (empty($r['department']))     $missing[] = 'department';
            ?>
            <a href="edit-student.php?id=<?php echo $r['id']; ?>&q=<?php echo urlencode($searchQ); ?>"
               class="result-item">
                <?php if (!empty($r['passport_photo'])): ?>
                <img src="<?php echo e($r['passport_photo']); ?>" class="result-avatar" alt="">
                <?php else: ?>
                <div class="result-avatar-placeholder"><?php echo strtoupper(substr($r['first_name'],0,1)); ?></div>
                <?php endif; ?>
                <div class="result-info">
                    <strong><?php echo e($r['last_name'].', '.$r['first_name'].' '.($r['middle_name']??'')); ?></strong>
                    <span><?php echo e($r['reg_number']); ?> · <?php echo e($r['programme']); ?></span>
                    <?php if (!empty($missing)): ?>
                    <div class="result-missing">⚠ Missing: <?php echo implode(', ', $missing); ?></div>
                    <?php endif; ?>
                </div>
                <span style="color:var(--primary-blue);font-size:.8125rem;font-weight:600;">Edit →</span>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($student): ?>
    <!-- Edit Form -->
    <div class="edit-card">
        <div class="edit-card-header">
            <h2>
                <?php echo e($student['last_name'].', '.$student['first_name'].' '.($student['middle_name']??'')); ?>
            </h2>
            <span style="font-size:.8125rem;opacity:.85;"><?php echo e($student['reg_number']); ?></span>
        </div>
        <div class="edit-card-body">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="save">

                <!-- Passport Photo -->
                <div class="section-title">Passport Photo</div>
                <div class="photo-section">
                    <?php if (!empty($student['passport_photo'])): ?>
                    <img src="<?php echo e($student['passport_photo']); ?>" class="current-photo" id="currentPhoto" alt="Current photo">
                    <?php else: ?>
                    <div class="photo-placeholder-box" id="currentPhoto">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        No photo
                    </div>
                    <?php endif; ?>

                    <div class="photo-upload-area" onclick="document.getElementById('photoInput').click()">
                        <img id="photoPreviewImg" src="" alt="Preview">
                        <svg style="width:32px;height:32px;stroke:var(--gray-400);stroke-width:1.5;margin-bottom:.5rem;" fill="none" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                        <p><strong>Click to upload new photo</strong></p>
                        <p>JPG or PNG · max 2 MB</p>
                        <input type="file" name="passport_photo" id="photoInput" accept="image/*" style="display:none;">
                    </div>
                </div>

                <!-- Personal Info -->
                <div class="section-title">Personal Information</div>
                <div class="form-grid-3">
                    <div class="form-group">
                        <label class="form-label">First Name *</label>
                        <input type="text" name="first_name" class="form-control" required
                               value="<?php echo e($student['first_name']); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Middle Name</label>
                        <input type="text" name="middle_name" class="form-control"
                               value="<?php echo e($student['middle_name'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Last Name *</label>
                        <input type="text" name="last_name" class="form-control" required
                               value="<?php echo e($student['last_name']); ?>">
                    </div>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Registration Number *</label>
                        <input type="text" name="reg_number" class="form-control" required
                               style="font-family:monospace;"
                               value="<?php echo e($student['reg_number']); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Programme</label>
                        <select name="programme" class="form-control">
                            <option value="Sandwich" <?php echo $student['programme']==='Sandwich'?'selected':''; ?>>Sandwich</option>
                            <option value="IDELL"    <?php echo $student['programme']==='IDELL'   ?'selected':''; ?>>IDELL</option>
                        </select>
                    </div>
                </div>

                <!-- Medical -->
                <div class="section-title">Medical</div>
                <div style="max-width:200px;">
                    <div class="form-group">
                        <label class="form-label">Blood Group</label>
                        <select name="blood_group" class="form-control">
                            <option value="">— Not set —</option>
                            <?php foreach (getBloodGroups() as $bg): ?>
                            <option value="<?php echo $bg; ?>" <?php echo ($student['blood_group']??'')===$bg?'selected':''; ?>>
                                <?php echo $bg; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Academic -->
                <div class="section-title">Academic Information</div>
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Faculty</label>
                        <select name="faculty" id="editFaculty" class="form-control">
                            <option value="">— Select Faculty —</option>
                            <?php foreach ($tsuData as $fac): ?>
                            <option value="<?php echo e($fac['faculty']); ?>"
                                <?php echo ($student['faculty']??'')===$fac['faculty']?'selected':''; ?>>
                                <?php echo e($fac['faculty']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Department</label>
                        <select name="department" id="editDepartment" class="form-control">
                            <option value="">— Select Department —</option>
                            <?php
                            // Pre-populate departments for current faculty
                            $curFac = $student['faculty'] ?? '';
                            foreach ($tsuData as $fac) {
                                if ($fac['faculty'] === $curFac) {
                                    foreach ($fac['departments'] as $dept) {
                                        $sel = ($student['department']??'')===$dept['name']?'selected':'';
                                        echo '<option value="'.e($dept['name']).'" '.$sel.'>'.e($dept['name']).'</option>';
                                    }
                                    break;
                                }
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Course of Study</label>
                    <select name="course_of_study" id="editCourse" class="form-control">
                        <option value="">— Select Course —</option>
                        <?php
                        $curDept = $student['department'] ?? '';
                        foreach ($tsuData as $fac) {
                            if ($fac['faculty'] === $curFac) {
                                foreach ($fac['departments'] as $dept) {
                                    if ($dept['name'] === $curDept) {
                                        foreach ($dept['programmes'] as $prog) {
                                            $sel = ($student['course_of_study']??'')===$prog?'selected':'';
                                            echo '<option value="'.e($prog).'" '.$sel.'>'.e($prog).'</option>';
                                        }
                                        break 2;
                                    }
                                }
                            }
                        }
                        ?>
                    </select>
                </div>

                <!-- Admin Note -->
                <div class="section-title">Admin Note</div>
                <div class="form-group">
                    <textarea name="admin_note" class="form-control" rows="3"
                              placeholder="Optional message visible to the student…"><?php echo e($student['admin_note'] ?? ''); ?></textarea>
                </div>

                <div class="form-actions">
                    <a href="edit-student.php<?php echo $searchQ ? '?q='.urlencode($searchQ) : ''; ?>"
                       class="btn btn-secondary">Cancel</a>
                    <a href="id-card.php?id=<?php echo $student['id']; ?>"
                       class="btn btn-secondary" target="_blank">View ID Card</a>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

</div>

<script>
const tsuData = <?php echo getTsuDataJson(); ?>;

// ── Faculty → Department → Course cascading ──
const facSel  = document.getElementById('editFaculty');
const deptSel = document.getElementById('editDepartment');
const cosSel  = document.getElementById('editCourse');

if (facSel) {
    facSel.addEventListener('change', function () {
        populateDepts(this.value, '', '');
    });
    deptSel.addEventListener('change', function () {
        populateCourses(facSel.value, this.value, '');
    });
    // Re-populate courses when programme changes
    const progSel = document.querySelector('select[name="programme"]');
    if (progSel) {
        progSel.addEventListener('change', function () {
            if (deptSel.value) populateCourses(facSel.value, deptSel.value, cosSel.value);
        });
    }
}

function populateDepts(facName, selDept, selCos) {
    deptSel.innerHTML = '<option value="">— Select Department —</option>';
    cosSel.innerHTML  = '<option value="">— Select Course —</option>';
    const fac = tsuData.find(f => f.faculty === facName);
    if (!fac) return;
    fac.departments.forEach(d => {
        const o = document.createElement('option');
        o.value = d.name; o.textContent = d.name;
        if (d.name === selDept) o.selected = true;
        deptSel.appendChild(o);
    });
    if (selDept) populateCourses(facName, selDept, selCos);
}

function populateCourses(facName, deptName, selCos) {
    cosSel.innerHTML = '<option value="">— Select Course —</option>';
    const fac  = tsuData.find(f => f.faculty === facName);
    if (!fac) return;
    const dept = fac.departments.find(d => d.name === deptName);
    if (!dept) return;

    // Detect IDELL from the programme select
    const progSel = document.querySelector('select[name="programme"]');
    const isIDELL = progSel && progSel.value === 'IDELL';

    dept.programmes.forEach(p => {
        const o = document.createElement('option');
        o.value = p; o.textContent = p;
        if (p === selCos) o.selected = true;
        cosSel.appendChild(o);

        if (isIDELL) {
            const pgVal = 'PG. ' + p;
            const pg = document.createElement('option');
            pg.value = pgVal; pg.textContent = pgVal;
            if (pgVal === selCos) pg.selected = true;
            cosSel.appendChild(pg);
        }
    });
}

// ── Photo preview ──
const photoInput = document.getElementById('photoInput');
const previewImg = document.getElementById('photoPreviewImg');
const currentPhoto = document.getElementById('currentPhoto');

if (photoInput) {
    photoInput.addEventListener('change', function () {
        const file = this.files[0];
        if (!file) return;
        if (file.size > 2 * 1024 * 1024) { alert('Image must be less than 2MB'); this.value = ''; return; }
        const reader = new FileReader();
        reader.onload = e => {
            previewImg.src = e.target.result;
            previewImg.style.display = 'block';
            if (currentPhoto) currentPhoto.style.opacity = '.4';
        };
        reader.readAsDataURL(file);
    });
}
</script>
</body>
</html>
