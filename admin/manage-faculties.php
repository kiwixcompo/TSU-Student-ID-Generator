<?php
require_once '../config.php';
require_once '../includes/session.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

initSession();
requireAdminAuth();

$session = getAdminSession();
if ($session['programme_managed'] !== 'SuperAdmin') {
    redirect(baseUrl('admin/dashboard.php'));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Faculties – <?php echo APP_NAME; ?></title>
    <link rel="icon" type="image/png" href="<?php echo asset('public/tsu-logo.png'); ?>">
    <link rel="stylesheet" href="<?php echo asset('assets/css/style.css'); ?>">
    <style>
        body { background: var(--gray-50); }

        /* ── Page header ── */
        .page-header {
            background: linear-gradient(135deg, var(--primary-blue) 0%, #3d4a8f 100%);
            padding: .875rem 0; position: sticky; top: 0; z-index: 100;
            box-shadow: var(--shadow-lg);
        }
        .page-header .inner {
            max-width: 1300px; margin: 0 auto; padding: 0 1.5rem;
            display: flex; align-items: center; justify-content: space-between; gap: 1rem;
        }
        .page-header h1 { font-size: 1.2rem; color: white; margin: 0; }

        /* ── Layout ── */
        .main { max-width: 1300px; margin: 1.5rem auto; padding: 0 1.5rem; }

        .three-col {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 1.25rem;
        }
        @media (max-width: 900px) { .three-col { grid-template-columns: 1fr; } }

        /* ── Panel ── */
        .panel {
            background: white; border-radius: 1rem;
            box-shadow: var(--shadow); overflow: hidden;
            display: flex; flex-direction: column;
        }
        .panel-header {
            padding: .875rem 1.25rem;
            border-bottom: 1px solid var(--gray-200);
            display: flex; align-items: center; justify-content: space-between;
        }
        .panel-header h2 { font-size: 1rem; margin: 0; color: var(--gray-900); }
        .panel-header span { font-size: .75rem; color: var(--gray-500); }

        /* ── Add form ── */
        .add-row {
            display: flex; gap: .5rem; padding: .875rem 1.25rem;
            border-bottom: 1px solid var(--gray-100);
        }
        .add-row input {
            flex: 1; padding: .5rem .75rem;
            border: 2px solid var(--gray-300); border-radius: var(--radius);
            font-size: .875rem;
        }
        .add-row input:focus { outline: none; border-color: var(--primary-blue); }
        .add-row button {
            padding: .5rem .875rem; background: var(--primary-blue); color: white;
            border: none; border-radius: var(--radius); font-weight: 600;
            font-size: .875rem; cursor: pointer; white-space: nowrap;
        }
        .add-row button:hover { opacity: .85; }

        /* ── List ── */
        .item-list { flex: 1; overflow-y: auto; max-height: 520px; }
        .item {
            display: flex; align-items: center; gap: .5rem;
            padding: .65rem 1.25rem; border-bottom: 1px solid var(--gray-100);
            cursor: pointer; transition: background .15s;
        }
        .item:hover { background: var(--gray-50); }
        .item.active { background: #eff6ff; border-left: 3px solid var(--primary-blue); }
        .item-name { flex: 1; font-size: .875rem; color: var(--gray-900); font-weight: 500; }
        .item-actions { display: flex; gap: .35rem; opacity: 0; transition: opacity .15s; }
        .item:hover .item-actions { opacity: 1; }
        .btn-xs {
            padding: .25rem .5rem; border: none; border-radius: .375rem;
            font-size: .75rem; font-weight: 600; cursor: pointer;
        }
        .btn-edit  { background: #dbeafe; color: #1e40af; }
        .btn-del   { background: #fee2e2; color: #991b1b; }
        .btn-edit:hover { background: #3b82f6; color: white; }
        .btn-del:hover  { background: #ef4444; color: white; }

        /* ── Empty state ── */
        .empty-msg {
            padding: 2rem; text-align: center;
            color: var(--gray-400); font-size: .875rem;
        }

        /* ── Breadcrumb ── */
        .breadcrumb {
            font-size: .8rem; color: var(--gray-500);
            padding: .5rem 1.25rem; background: #f8fafc;
            border-bottom: 1px solid var(--gray-100);
            min-height: 2rem;
        }
        .breadcrumb span { color: var(--primary-blue); font-weight: 600; }

        /* ── Toast ── */
        #toast {
            position: fixed; bottom: 1.5rem; right: 1.5rem;
            background: #166534; color: white;
            padding: .75rem 1.25rem; border-radius: .75rem;
            font-size: .875rem; font-weight: 600;
            box-shadow: 0 4px 12px rgba(0,0,0,.15);
            transform: translateY(100px); opacity: 0;
            transition: all .3s; z-index: 9999;
        }
        #toast.show { transform: translateY(0); opacity: 1; }
        #toast.error { background: #991b1b; }

        /* ── Inline edit ── */
        .inline-edit {
            display: flex; gap: .35rem; flex: 1;
        }
        .inline-edit input {
            flex: 1; padding: .25rem .5rem;
            border: 2px solid var(--primary-blue); border-radius: .375rem;
            font-size: .875rem;
        }
        .inline-edit button {
            padding: .25rem .5rem; border: none; border-radius: .375rem;
            font-size: .75rem; font-weight: 600; cursor: pointer;
        }
        .btn-save-inline { background: #166534; color: white; }
        .btn-cancel-inline { background: var(--gray-200); color: var(--gray-700); }

        /* ── Seed banner ── */
        .seed-banner {
            background: #fef9c3; border: 1px solid #fde047;
            border-radius: .75rem; padding: 1rem 1.25rem;
            margin-bottom: 1.25rem; display: flex;
            align-items: center; justify-content: space-between; gap: 1rem;
            flex-wrap: wrap;
        }
        .seed-banner p { margin: 0; font-size: .875rem; color: #854d0e; }
        .seed-banner button {
            padding: .5rem 1.25rem; background: #854d0e; color: white;
            border: none; border-radius: .5rem; font-weight: 600;
            font-size: .875rem; cursor: pointer; white-space: nowrap;
        }
        .seed-banner button:hover { background: #713f12; }
    </style>
</head>
<body>

<div class="page-header">
    <div class="inner">
        <div style="display:flex;align-items:center;gap:.875rem;">
            <img src="../public/tsu-logo.png" alt="TSU" style="width:38px;height:38px;object-fit:contain;">
            <h1>Manage Faculties, Departments &amp; Courses</h1>
        </div>
        <a href="dashboard.php" class="btn btn-secondary btn-sm">← Back to Dashboard</a>
    </div>
</div>

<div class="main">

    <!-- Seed banner (shown until DB is populated) -->
    <div class="seed-banner" id="seedBanner" style="display:none;">
        <p>⚠️ The database tables are empty. Click <strong>Seed from defaults</strong> to import all existing faculties, departments and courses into the database so you can manage them here.</p>
        <button onclick="seedData()">Seed from defaults</button>
    </div>

    <div class="three-col">

        <!-- ── FACULTIES ── -->
        <div class="panel">
            <div class="panel-header">
                <h2>Faculties</h2>
                <span id="facCount">0 faculties</span>
            </div>
            <div class="add-row">
                <input type="text" id="newFaculty" placeholder="New faculty name…" maxlength="255">
                <button onclick="addFaculty()">+ Add</button>
            </div>
            <div class="item-list" id="facultyList">
                <div class="empty-msg">Loading…</div>
            </div>
        </div>

        <!-- ── DEPARTMENTS ── -->
        <div class="panel">
            <div class="panel-header">
                <h2>Departments</h2>
                <span id="deptCount">Select a faculty</span>
            </div>
            <div class="breadcrumb" id="deptBreadcrumb">← Select a faculty</div>
            <div class="add-row" id="deptAddRow" style="display:none;">
                <input type="text" id="newDept" placeholder="New department name…" maxlength="255">
                <button onclick="addDept()">+ Add</button>
            </div>
            <div class="item-list" id="deptList">
                <div class="empty-msg">Select a faculty first</div>
            </div>
        </div>

        <!-- ── COURSES ── -->
        <div class="panel">
            <div class="panel-header">
                <h2>Courses of Study</h2>
                <span id="courseCount">Select a department</span>
            </div>
            <div class="breadcrumb" id="courseBreadcrumb">← Select a department</div>
            <div class="add-row" id="courseAddRow" style="display:none;">
                <input type="text" id="newCourse" placeholder="New course name…" maxlength="255">
                <button onclick="addCourse()">+ Add</button>
            </div>
            <div class="item-list" id="courseList">
                <div class="empty-msg">Select a department first</div>
            </div>
        </div>

    </div>
</div>

<div id="toast"></div>

<script>
const API = 'faculty-ajax.php';
let activeFacId   = null;
let activeFacName = '';
let activeDeptId   = null;
let activeDeptName = '';

// ── Helpers ───────────────────────────────────────────────────────────────────
async function api(params) {
    const body = new URLSearchParams(params);
    const res  = await fetch(API, { method: 'POST', body });
    return res.json();
}

function toast(msg, isError = false) {
    const el = document.getElementById('toast');
    el.textContent = msg;
    el.className = 'show' + (isError ? ' error' : '');
    clearTimeout(el._t);
    el._t = setTimeout(() => el.className = '', 3000);
}

function esc(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Seed ──────────────────────────────────────────────────────────────────────
async function seedData() {
    const btn = document.querySelector('.seed-banner button');
    btn.disabled = true; btn.textContent = 'Seeding…';
    const r = await api({ action: 'seed_defaults' });
    if (r.success) {
        document.getElementById('seedBanner').style.display = 'none';
        toast('Seeded ' + r.faculties + ' faculties, ' + r.departments + ' departments, ' + r.courses + ' courses');
        loadFaculties();
    } else {
        toast(r.error || 'Seed failed', true);
        btn.disabled = false; btn.textContent = 'Seed from defaults';
    }
}

// ── FACULTIES ─────────────────────────────────────────────────────────────────
async function loadFaculties() {
    const r = await api({ action: 'list_faculties' });
    const list = document.getElementById('facultyList');
    document.getElementById('facCount').textContent = r.data.length + ' faculties';

    if (r.data.length === 0) {
        document.getElementById('seedBanner').style.display = 'flex';
        list.innerHTML = '<div class="empty-msg">No faculties yet. Add one above or seed defaults.</div>';
        return;
    }
    document.getElementById('seedBanner').style.display = 'none';

    list.innerHTML = r.data.map(f => `
        <div class="item ${f.id == activeFacId ? 'active' : ''}" id="fac-${f.id}" onclick="selectFaculty(${f.id}, '${esc(f.name)}')">
            <span class="item-name" id="fac-name-${f.id}">${esc(f.name)}</span>
            <div class="item-actions">
                <button class="btn-xs btn-edit" onclick="event.stopPropagation();editFaculty(${f.id})">Edit</button>
                <button class="btn-xs btn-del"  onclick="event.stopPropagation();deleteFaculty(${f.id}, '${esc(f.name)}')">Delete</button>
            </div>
        </div>`).join('');
}

async function addFaculty() {
    const inp = document.getElementById('newFaculty');
    const name = inp.value.trim();
    if (!name) return;
    const r = await api({ action: 'add_faculty', name });
    if (r.success) { inp.value = ''; toast('Faculty added'); loadFaculties(); }
    else toast(r.error, true);
}
document.getElementById('newFaculty').addEventListener('keydown', e => { if (e.key === 'Enter') addFaculty(); });

function editFaculty(id) {
    const nameEl = document.getElementById('fac-name-' + id);
    const current = nameEl.textContent;
    nameEl.innerHTML = `<div class="inline-edit">
        <input type="text" value="${esc(current)}" id="fac-edit-${id}" maxlength="255">
        <button class="btn-save-inline" onclick="saveFaculty(${id})">Save</button>
        <button class="btn-cancel-inline" onclick="loadFaculties()">✕</button>
    </div>`;
    document.getElementById('fac-edit-' + id).focus();
    document.getElementById('fac-edit-' + id).addEventListener('keydown', e => { if (e.key === 'Enter') saveFaculty(id); });
}

async function saveFaculty(id) {
    const name = document.getElementById('fac-edit-' + id).value.trim();
    if (!name) return;
    const r = await api({ action: 'rename_faculty', id, name });
    if (r.success) { toast('Faculty renamed'); if (activeFacId == id) { activeFacName = name; updateDeptBreadcrumb(); } loadFaculties(); }
    else toast(r.error, true);
}

async function deleteFaculty(id, name) {
    if (!confirm(`Delete faculty "${name}" and ALL its departments and courses?\nThis cannot be undone.`)) return;
    const r = await api({ action: 'delete_faculty', id });
    if (r.success) {
        toast('Faculty deleted');
        if (activeFacId == id) { activeFacId = null; activeFacName = ''; clearDepts(); }
        loadFaculties();
    } else toast(r.error, true);
}

function selectFaculty(id, name) {
    activeFacId   = id;
    activeFacName = name;
    activeDeptId   = null;
    activeDeptName = '';
    document.querySelectorAll('#facultyList .item').forEach(el => el.classList.remove('active'));
    const el = document.getElementById('fac-' + id);
    if (el) el.classList.add('active');
    loadDepts();
    clearCourses();
}

// ── DEPARTMENTS ───────────────────────────────────────────────────────────────
function updateDeptBreadcrumb() {
    document.getElementById('deptBreadcrumb').innerHTML = activeFacName
        ? `Faculty: <span>${esc(activeFacName)}</span>`
        : '← Select a faculty';
}

function clearDepts() {
    document.getElementById('deptList').innerHTML = '<div class="empty-msg">Select a faculty first</div>';
    document.getElementById('deptCount').textContent = 'Select a faculty';
    document.getElementById('deptAddRow').style.display = 'none';
    document.getElementById('deptBreadcrumb').innerHTML = '← Select a faculty';
}

async function loadDepts() {
    if (!activeFacId) return;
    updateDeptBreadcrumb();
    const r = await api({ action: 'list_departments', faculty_id: activeFacId });
    const list = document.getElementById('deptList');
    document.getElementById('deptCount').textContent = r.data.length + ' departments';
    document.getElementById('deptAddRow').style.display = 'flex';

    if (r.data.length === 0) {
        list.innerHTML = '<div class="empty-msg">No departments yet. Add one above.</div>';
        return;
    }
    list.innerHTML = r.data.map(d => `
        <div class="item ${d.id == activeDeptId ? 'active' : ''}" id="dept-${d.id}" onclick="selectDept(${d.id}, '${esc(d.name)}')">
            <span class="item-name" id="dept-name-${d.id}">${esc(d.name)}</span>
            <div class="item-actions">
                <button class="btn-xs btn-edit" onclick="event.stopPropagation();editDept(${d.id})">Edit</button>
                <button class="btn-xs btn-del"  onclick="event.stopPropagation();deleteDept(${d.id}, '${esc(d.name)}')">Delete</button>
            </div>
        </div>`).join('');
}

async function addDept() {
    const inp = document.getElementById('newDept');
    const name = inp.value.trim();
    if (!name || !activeFacId) return;
    const r = await api({ action: 'add_department', faculty_id: activeFacId, name });
    if (r.success) { inp.value = ''; toast('Department added'); loadDepts(); }
    else toast(r.error, true);
}
document.getElementById('newDept').addEventListener('keydown', e => { if (e.key === 'Enter') addDept(); });

function editDept(id) {
    const nameEl = document.getElementById('dept-name-' + id);
    const current = nameEl.textContent;
    nameEl.innerHTML = `<div class="inline-edit">
        <input type="text" value="${esc(current)}" id="dept-edit-${id}" maxlength="255">
        <button class="btn-save-inline" onclick="saveDept(${id})">Save</button>
        <button class="btn-cancel-inline" onclick="loadDepts()">✕</button>
    </div>`;
    document.getElementById('dept-edit-' + id).focus();
    document.getElementById('dept-edit-' + id).addEventListener('keydown', e => { if (e.key === 'Enter') saveDept(id); });
}

async function saveDept(id) {
    const name = document.getElementById('dept-edit-' + id).value.trim();
    if (!name) return;
    const r = await api({ action: 'rename_department', id, name });
    if (r.success) { toast('Department renamed'); if (activeDeptId == id) { activeDeptName = name; updateCourseBreadcrumb(); } loadDepts(); }
    else toast(r.error, true);
}

async function deleteDept(id, name) {
    if (!confirm(`Delete department "${name}" and ALL its courses?\nThis cannot be undone.`)) return;
    const r = await api({ action: 'delete_department', id });
    if (r.success) {
        toast('Department deleted');
        if (activeDeptId == id) { activeDeptId = null; activeDeptName = ''; clearCourses(); }
        loadDepts();
    } else toast(r.error, true);
}

function selectDept(id, name) {
    activeDeptId   = id;
    activeDeptName = name;
    document.querySelectorAll('#deptList .item').forEach(el => el.classList.remove('active'));
    const el = document.getElementById('dept-' + id);
    if (el) el.classList.add('active');
    loadCourses();
}

// ── COURSES ───────────────────────────────────────────────────────────────────
function updateCourseBreadcrumb() {
    document.getElementById('courseBreadcrumb').innerHTML = activeDeptName
        ? `Dept: <span>${esc(activeDeptName)}</span>`
        : '← Select a department';
}

function clearCourses() {
    document.getElementById('courseList').innerHTML = '<div class="empty-msg">Select a department first</div>';
    document.getElementById('courseCount').textContent = 'Select a department';
    document.getElementById('courseAddRow').style.display = 'none';
    document.getElementById('courseBreadcrumb').innerHTML = '← Select a department';
}

async function loadCourses() {
    if (!activeDeptId) return;
    updateCourseBreadcrumb();
    const r = await api({ action: 'list_courses', department_id: activeDeptId });
    const list = document.getElementById('courseList');
    document.getElementById('courseCount').textContent = r.data.length + ' courses';
    document.getElementById('courseAddRow').style.display = 'flex';

    if (r.data.length === 0) {
        list.innerHTML = '<div class="empty-msg">No courses yet. Add one above.</div>';
        return;
    }
    list.innerHTML = r.data.map(c => `
        <div class="item" id="course-${c.id}">
            <span class="item-name" id="course-name-${c.id}">${esc(c.name)}</span>
            <div class="item-actions">
                <button class="btn-xs btn-edit" onclick="editCourse(${c.id})">Edit</button>
                <button class="btn-xs btn-del"  onclick="deleteCourse(${c.id}, '${esc(c.name)}')">Delete</button>
            </div>
        </div>`).join('');
}

async function addCourse() {
    const inp = document.getElementById('newCourse');
    const name = inp.value.trim();
    if (!name || !activeDeptId) return;
    const r = await api({ action: 'add_course', department_id: activeDeptId, name });
    if (r.success) { inp.value = ''; toast('Course added'); loadCourses(); }
    else toast(r.error, true);
}
document.getElementById('newCourse').addEventListener('keydown', e => { if (e.key === 'Enter') addCourse(); });

function editCourse(id) {
    const nameEl = document.getElementById('course-name-' + id);
    const current = nameEl.textContent;
    nameEl.innerHTML = `<div class="inline-edit">
        <input type="text" value="${esc(current)}" id="course-edit-${id}" maxlength="255">
        <button class="btn-save-inline" onclick="saveCourse(${id})">Save</button>
        <button class="btn-cancel-inline" onclick="loadCourses()">✕</button>
    </div>`;
    document.getElementById('course-edit-' + id).focus();
    document.getElementById('course-edit-' + id).addEventListener('keydown', e => { if (e.key === 'Enter') saveCourse(id); });
}

async function saveCourse(id) {
    const name = document.getElementById('course-edit-' + id).value.trim();
    if (!name) return;
    const r = await api({ action: 'rename_course', id, name });
    if (r.success) { toast('Course renamed'); loadCourses(); }
    else toast(r.error, true);
}

async function deleteCourse(id, name) {
    if (!confirm(`Delete course "${name}"?`)) return;
    const r = await api({ action: 'delete_course', id });
    if (r.success) { toast('Course deleted'); loadCourses(); }
    else toast(r.error, true);
}

// ── Init ──────────────────────────────────────────────────────────────────────
loadFaculties();
</script>
</body>
</html>
