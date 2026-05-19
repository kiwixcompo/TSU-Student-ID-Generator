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

// ── Pagination & per-page ─────────────────────────────────────────────────────
$allowedPerPage = [10, 25, 50, 100];
$perPage = (int) get('per_page', 10);
if (!in_array($perPage, $allowedPerPage)) $perPage = 10;

$page = max(1, (int) get('page', 1));

// ── Filters ───────────────────────────────────────────────────────────────────
$filters = [
    'search'     => get('search', ''),
    'programme'  => get('programme', ''),
    'status'     => get('status', ''),
    'printed'    => get('printed', ''),
    'year'       => get('year', ''),
    'faculty'    => get('faculty', ''),
    'department' => get('department', ''),
];

// ── Paginated data ────────────────────────────────────────────────────────────
$result   = getStudentsPaginated($filters, $page, $perPage, $session['programme_managed']);
$students = $result['rows'];
$total    = $result['total'];
$totalPages = max(1, (int) ceil($total / $perPage));
if ($page > $totalPages) $page = $totalPages;

// ── Stats (always full counts, unfiltered) ────────────────────────────────────
$allStudents = getStudents($session['programme_managed']);
$totalAll    = count($allStudents);
$totalGen    = count(array_filter($allStudents, fn($s) => $s['status'] === 'id_generated'));
$totalPend   = count(array_filter($allStudents, fn($s) => $s['status'] === 'pending'));

// ── Years for filter dropdown ─────────────────────────────────────────────────
$years = array_unique(array_map(fn($s) => extractYearFromRegNumber($s['reg_number']), $allStudents));
sort($years);
$years = array_reverse($years);

// ── Build query string helper (preserves all params except page) ──────────────
function pageUrl(int $p, array $extra = []): string {
    $params = array_merge($_GET, $extra, ['page' => $p]);
    return 'dashboard.php?' . http_build_query($params);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo APP_NAME; ?></title>
    <link rel="icon" type="image/png" href="<?php echo asset('public/tsu-logo.png'); ?>">
    <link rel="stylesheet" href="<?php echo asset('assets/css/style.css'); ?>">
    <style>
        body {
            background: var(--gray-50);
        }
        
        .dashboard-header {
            background: linear-gradient(135deg, var(--primary-blue) 0%, #3d4a8f 100%);
            color: white;
            padding: 0.875rem 0;
            box-shadow: var(--shadow-lg);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .header-logo {
            width: 50px;
            height: 50px;
            object-fit: contain;
        }
        
        .header-info h1 {
            font-size: 1.5rem;
            margin-bottom: 0.25rem;
            color: white;
        }
        
        .header-info p {
            font-size: 0.875rem;
            opacity: 0.9;
            margin: 0;
        }
        
        .header-right {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .user-badge {
            background: rgba(255, 255, 255, 0.2);
            padding: 0.5rem 1rem;
            border-radius: var(--radius-full);
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        .main-content {
            max-width: 1400px;
            margin: 1rem auto;
            padding: 0 1.5rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .stat-card {
            background: white;
            padding: 1rem 1.25rem;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            gap: 0.875rem;
            transition: var(--transition);
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
        }
        
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .stat-icon.blue  { background: linear-gradient(135deg, #3B82F6 0%, #2563EB 100%); }
        .stat-icon.green { background: linear-gradient(135deg, #10B981 0%, #059669 100%); }
        .stat-icon.yellow{ background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%); }
        
        .stat-icon svg { width: 24px; height: 24px; stroke: white; stroke-width: 2; }
        
        .stat-info h3 {
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--gray-900);
            margin-bottom: 0.1rem;
        }
        
        .stat-info p {
            font-size: 0.8rem;
            color: var(--gray-600);
            margin: 0;
        }
        
        .filters-section {
            background: white;
            padding: 1rem 1.25rem;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow);
            margin-bottom: 1rem;
        }
        
        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 0.75rem;
            margin-top: 0.75rem;
        }
        
        .filter-group { margin-bottom: 0; }
        
        .filter-group label {
            display: block;
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 0.35rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .filter-group input,
        .filter-group select {
            width: 100%;
            padding: 0.45rem 0.75rem;
            border: 2px solid var(--gray-300);
            border-radius: var(--radius);
            font-size: 0.8125rem;
            transition: var(--transition);
        }
        
        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: var(--primary-blue);
        }
        
        .filter-actions {
            display: flex;
            gap: 0.75rem;
            margin-top: 0.75rem;
        }
        
        .students-table-container {
            background: white;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow);
            overflow: hidden;
        }
        
        .table-header {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .table-header h2 {
            font-size: 1.1rem;
            color: var(--gray-900);
            margin: 0;
        }
        
        .results-count {
            background: var(--primary-blue);
            color: white;
            padding: 0.3rem 0.75rem;
            border-radius: var(--radius-full);
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .table-wrapper { overflow-x: auto; }
        
        table { width: 100%; border-collapse: collapse; }
        thead { background: var(--gray-50); }
        
        th {
            padding: 0.75rem 1rem;
            text-align: left;
            font-size: 0.7rem;
            font-weight: 700;
            color: var(--gray-700);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }
        
        td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid var(--gray-100);
            font-size: 0.8125rem;
        }
        
        tbody tr { transition: var(--transition); }
        tbody tr:hover { background: var(--gray-50); }
        
        .student-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .student-avatar {
            width: 48px;
            height: 48px;
            border-radius: var(--radius-lg);
            object-fit: cover;
            border: 2px solid var(--gray-200);
        }

        .student-avatar-initial {
            width: 44px;
            height: 44px;
            border-radius: var(--radius-lg);
            background: linear-gradient(135deg, var(--primary-blue) 0%, #3d4a8f 100%);
            color: white;
            font-size: .875rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            letter-spacing: .5px;
        }
        
        .student-details h4 {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: 0.125rem;
        }
        
        .student-details p {
            font-size: 0.75rem;
            color: var(--gray-600);
            margin: 0;
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .btn-icon {
            width: 36px;
            height: 36px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius);
            border: none;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .btn-icon svg {
            width: 18px;
            height: 18px;
            stroke: currentColor;
            stroke-width: 2;
        }
        
        .btn-icon.btn-note {
            background: #DBEAFE;
            color: #1E40AF;
        }
        
        .btn-icon.btn-note:hover {
            background: #3B82F6;
            color: white;
        }
        
        .btn-icon.btn-password {
            background: #FEF3C7;
            color: #92400E;
        }
        
        .btn-icon.btn-password:hover {
            background: #F59E0B;
            color: white;
        }
        
        .btn-icon.btn-delete {
            background: #FEE2E2;
            color: #991B1B;
        }
        
        .btn-icon.btn-delete:hover {
            background: #EF4444;
            color: white;
        }
        
        .btn-generate {
            padding: 0.5rem 1rem;
            background: linear-gradient(135deg, var(--success) 0%, #059669 100%);
            color: white;
            border: none;
            border-radius: var(--radius);
            font-size: 0.8125rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            white-space: nowrap;
        }
        
        .btn-generate:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
        }
        
        .empty-state svg {
            width: 80px;
            height: 80px;
            stroke: var(--gray-400);
            stroke-width: 1.5;
            margin-bottom: 1rem;
        }
        
        .empty-state h3 {
            font-size: 1.25rem;
            color: var(--gray-700);
            margin-bottom: 0.5rem;
        }
        
        .empty-state p {
            color: var(--gray-500);
            margin: 0;
        }

        /* ── Pagination ── */
        .pagination-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: .875rem 1.25rem;
            border-top: 1px solid var(--gray-200);
            flex-wrap: wrap;
            gap: .75rem;
        }
        .pagination-info {
            font-size: .8125rem;
            color: var(--gray-600);
        }
        .pagination-controls {
            display: flex;
            align-items: center;
            gap: .35rem;
            flex-wrap: wrap;
        }
        .pg-btn {
            min-width: 34px;
            height: 34px;
            padding: 0 .6rem;
            border: 2px solid var(--gray-300);
            border-radius: var(--radius);
            background: white;
            color: var(--gray-700);
            font-size: .8125rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all .15s;
        }
        .pg-btn:hover:not(.pg-disabled):not(.pg-active) {
            border-color: var(--primary-blue);
            color: var(--primary-blue);
        }
        .pg-btn.pg-active {
            background: var(--primary-blue);
            border-color: var(--primary-blue);
            color: white;
        }
        .pg-btn.pg-disabled {
            opacity: .4;
            cursor: not-allowed;
            pointer-events: none;
        }
    </style>
</head>
<body>
    <div class="dashboard-header">
        <div class="header-content">
            <div class="header-left">
                <img src="../public/tsu-logo.png" alt="TSU Logo" class="header-logo">
                <div class="header-info">
                    <h1>Admin Dashboard</h1>
                    <p>Student ID Card Management System</p>
                </div>
            </div>
            <div class="header-right">
                <div class="user-badge">
                    <?php echo e($session['username']); ?> 
                    (<?php echo e($session['programme_managed']); ?>)
                </div>
                <?php if ($session['programme_managed'] === 'SuperAdmin'): ?>
                <a href="bulk-upload.php" class="btn btn-sm" style="background:#166534;color:white;border:none;">
                    <svg style="width:15px;height:15px;stroke:currentColor;stroke-width:2;" fill="none" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                    </svg>
                    Bulk Upload
                </a>
                <a href="manage-faculties.php" class="btn btn-sm" style="background:#7c3aed;color:white;border:none;">
                    <svg style="width:15px;height:15px;stroke:currentColor;stroke-width:2;" fill="none" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    Faculties
                </a>
                <?php endif; ?>
                <a href="edit-student.php" class="btn btn-secondary btn-sm">
                    <svg style="width:15px;height:15px;stroke:currentColor;stroke-width:2;" fill="none" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Edit Profile
                </a>
                <a href="logout.php" class="btn btn-secondary btn-sm">Logout</a>
            </div>
        </div>
    </div>

    <div class="main-content">
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <div class="stat-info">
                    <h3><?php echo $totalAll; ?></h3>
                    <p>Total Students</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon green">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="stat-info">
                    <h3><?php echo $totalGen; ?></h3>
                    <p>IDs Generated</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon yellow">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="stat-info">
                    <h3><?php echo $totalPend; ?></h3>
                    <p>Pending Review</p>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters-section">
            <h3 style="margin-bottom: 0.5rem; color: var(--gray-900); font-size: 1rem;">Filter Students</h3>
            <form method="GET" id="filterForm">
                <div class="filters-grid">
                    <div class="filter-group">
                        <label>Search</label>
                        <input type="text" name="search" id="searchInput" placeholder="Name or Reg Number..." value="<?php echo e(get('search', '')); ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label>Programme</label>
                        <select name="programme" id="programmeFilter">
                            <option value="">All Programmes</option>
                            <option value="Sandwich" <?php echo get('programme') === 'Sandwich' ? 'selected' : ''; ?>>Sandwich</option>
                            <option value="IDELL" <?php echo get('programme') === 'IDELL' ? 'selected' : ''; ?>>IDELL</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>Status</label>
                        <select name="status" id="statusFilter">
                            <option value="">All Statuses</option>
                            <option value="pending"      <?php echo get('status') === 'pending'      ? 'selected' : ''; ?>>Pending</option>
                            <option value="id_generated" <?php echo get('status') === 'id_generated' ? 'selected' : ''; ?>>Generated</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>Print Status</label>
                        <select name="printed" id="printedFilter">
                            <option value="">All</option>
                            <option value="1" <?php echo get('printed') === '1' ? 'selected' : ''; ?>>Printed</option>
                            <option value="0" <?php echo get('printed') === '0' ? 'selected' : ''; ?>>Not Printed</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>Year</label>
                        <select name="year" id="yearFilter">
                            <option value="">All Years</option>
                            <?php foreach ($years as $year): ?>
                            <option value="<?php echo e($year); ?>" <?php echo get('year') === $year ? 'selected' : ''; ?>><?php echo e($year); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>Faculty</label>
                        <select name="faculty" id="facultyFilter">
                            <option value="">All Faculties</option>
                            <?php foreach ($tsuData as $faculty): ?>
                            <option value="<?php echo e($faculty['faculty']); ?>" <?php echo get('faculty') === $faculty['faculty'] ? 'selected' : ''; ?>><?php echo e($faculty['faculty']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>Department</label>
                        <select name="department" id="departmentFilter">
                            <option value="">All Departments</option>
                        </select>
                    </div>
                </div>
                
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary btn-sm">Apply Filters</button>
                    <a href="dashboard.php" class="btn btn-secondary btn-sm">Clear All</a>
                </div>
                <!-- preserve per_page across filter submissions -->
                <input type="hidden" name="per_page" value="<?php echo $perPage; ?>">
                <input type="hidden" name="page" value="1">
            </form>
        </div>

        <!-- Students Table -->
        <div class="students-table-container">
            <div class="table-header">
                <div>
                    <h2>Student Applications</h2>
                    <p style="margin:.2rem 0 0;font-size:.8rem;color:var(--gray-500);">
                        Showing <?php echo $total === 0 ? 0 : (($page - 1) * $perPage + 1); ?>–<?php echo min($page * $perPage, $total); ?> of <?php echo $total; ?> students
                    </p>
                </div>
                <div style="display:flex;align-items:center;gap:.75rem;flex-wrap:wrap;">
                    <!-- Per-page selector -->
                    <form method="GET" style="display:flex;align-items:center;gap:.4rem;">
                        <?php foreach ($filters as $k => $v): ?>
                        <?php if ($v !== ''): ?>
                        <input type="hidden" name="<?php echo e($k); ?>" value="<?php echo e($v); ?>">
                        <?php endif; ?>
                        <?php endforeach; ?>
                        <input type="hidden" name="page" value="1">
                        <label style="font-size:.8rem;font-weight:600;color:var(--gray-600);white-space:nowrap;">Show</label>
                        <select name="per_page" onchange="this.form.submit()"
                                style="padding:.35rem .6rem;border:2px solid var(--gray-300);border-radius:var(--radius);font-size:.8125rem;cursor:pointer;">
                            <?php foreach ([10, 25, 50, 100] as $opt): ?>
                            <option value="<?php echo $opt; ?>" <?php echo $perPage === $opt ? 'selected' : ''; ?>><?php echo $opt; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <label style="font-size:.8rem;font-weight:600;color:var(--gray-600);white-space:nowrap;">per page</label>
                    </form>
                    <span class="results-count"><?php echo $total; ?> Total</span>
                </div>
            </div>
            
            <div class="table-wrapper">
                <table id="studentsTable">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Reg Number</th>
                            <th>Programme</th>
                            <th>Faculty/Department</th>
                            <th>Blood Group</th>
                            <th>Status</th>
                            <th>Printed</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="studentsTableBody">
                        <?php if (empty($students)): ?>
                        <tr>
                            <td colspan="8">
                                <div class="empty-state">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                    </svg>
                                    <h3>No Students Found</h3>
                                    <p>There are no student applications to display</p>
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($students as $student): ?>
                        <tr data-student-id="<?php echo e($student['id']); ?>">
                            <td>
                                <div class="student-info">
                                    <div class="student-avatar-initial">
                                        <?php echo strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1)); ?>
                                    </div>
                                    <div class="student-details">
                                        <h4><?php echo e($student['last_name']); ?>, <?php echo e($student['first_name']); ?> <?php echo e($student['middle_name']); ?></h4>
                                        <p><?php echo e($student['course_of_study'] ?: $student['department']); ?></p>
                                    </div>
                                </div>
                            </td>
                            <td style="font-family: monospace; font-weight: 600;"><?php echo e($student['reg_number']); ?></td>
                            <td><?php echo e($student['programme']); ?></td>
                            <td>
                                <div style="font-size: 0.8125rem;">
                                    <div style="font-weight: 600; margin-bottom: 0.25rem;"><?php echo e($student['faculty']); ?></div>
                                    <div style="color: var(--gray-600);"><?php echo e($student['department']); ?></div>
                                </div>
                            </td>
                            <td><span class="badge badge-error"><?php echo e($student['blood_group']); ?></span></td>
                            <td>
                                <?php if ($student['status'] === 'id_generated'): ?>
                                <span class="badge badge-success">
                                    <svg style="width: 12px; height: 12px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Generated
                                </span>
                                <?php else: ?>
                                <span class="badge badge-warning">Pending</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($student['printed'])): ?>
                                <span class="badge badge-success" style="cursor:pointer;" onclick="togglePrinted(<?php echo e($student['id']); ?>, 0, this)" title="Click to mark as not printed">
                                    <svg style="width:12px;height:12px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    Printed
                                </span>
                                <?php else: ?>
                                <span class="badge badge-warning" style="cursor:pointer;" onclick="togglePrinted(<?php echo e($student['id']); ?>, 1, this)" title="Click to mark as printed">
                                    Not Printed
                                </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="edit-student.php?id=<?php echo e($student['id']); ?>" class="btn-icon" style="background:#EDE9FE;color:#5B21B6;" title="Edit Profile">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>
                                    <button class="btn-icon btn-note" onclick="openNoteModal(<?php echo e($student['id']); ?>, '<?php echo e(addslashes($student['admin_note'] ?? '')); ?>')" title="Add/Edit Note">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                                        </svg>
                                    </button>
                                    <button class="btn-icon btn-password" onclick="openPasswordModal(<?php echo e($student['id']); ?>)" title="Change Password">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                        </svg>
                                    </button>
                                    <button class="btn-icon btn-delete" onclick="confirmDelete(<?php echo e($student['id']); ?>, '<?php echo e(addslashes($student['reg_number'])); ?>')" title="Delete Student">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                    <a href="id-card.php?id=<?php echo e($student['id']); ?>" class="btn-generate">Generate ID</a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- ── Pagination bar ── -->
            <?php if ($totalPages > 1 || $total > 10): ?>
            <div class="pagination-bar">
                <div class="pagination-info">
                    Showing <?php echo $total === 0 ? 0 : (($page - 1) * $perPage + 1); ?>–<?php echo min($page * $perPage, $total); ?> of <?php echo $total; ?> students
                </div>
                <div class="pagination-controls">
                    <!-- First -->
                    <a href="<?php echo e(pageUrl(1)); ?>"
                       class="pg-btn <?php echo $page <= 1 ? 'pg-disabled' : ''; ?>" title="First">«</a>
                    <!-- Prev -->
                    <a href="<?php echo e(pageUrl(max(1, $page - 1))); ?>"
                       class="pg-btn <?php echo $page <= 1 ? 'pg-disabled' : ''; ?>" title="Previous">‹</a>

                    <?php
                    // Show window of page numbers around current page
                    $window = 2;
                    $start  = max(1, $page - $window);
                    $end    = min($totalPages, $page + $window);
                    if ($start > 1): ?>
                        <a href="<?php echo e(pageUrl(1)); ?>" class="pg-btn">1</a>
                        <?php if ($start > 2): ?><span class="pg-btn pg-disabled">…</span><?php endif; ?>
                    <?php endif; ?>

                    <?php for ($p = $start; $p <= $end; $p++): ?>
                    <a href="<?php echo e(pageUrl($p)); ?>"
                       class="pg-btn <?php echo $p === $page ? 'pg-active' : ''; ?>"><?php echo $p; ?></a>
                    <?php endfor; ?>

                    <?php if ($end < $totalPages): ?>
                        <?php if ($end < $totalPages - 1): ?><span class="pg-btn pg-disabled">…</span><?php endif; ?>
                        <a href="<?php echo e(pageUrl($totalPages)); ?>" class="pg-btn"><?php echo $totalPages; ?></a>
                    <?php endif; ?>

                    <!-- Next -->
                    <a href="<?php echo e(pageUrl(min($totalPages, $page + 1))); ?>"
                       class="pg-btn <?php echo $page >= $totalPages ? 'pg-disabled' : ''; ?>" title="Next">›</a>
                    <!-- Last -->
                    <a href="<?php echo e(pageUrl($totalPages)); ?>"
                       class="pg-btn <?php echo $page >= $totalPages ? 'pg-disabled' : ''; ?>" title="Last">»</a>
                </div>
            </div>
            <?php endif; ?>

        </div><!-- /students-table-container -->
    </div>

    <!-- Modals will be added via JavaScript -->
    <div id="modalContainer"></div>

    <script>
        // Pass TSU data to JavaScript
        window.tsuData = <?php echo json_encode($tsuData); ?>;
    </script>
    <script src="<?php echo asset('assets/js/admin-dashboard.js'); ?>"></script>
</body>
</html>
