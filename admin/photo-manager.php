<?php
require_once '../config.php';
require_once '../includes/session.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

initSession();
requireAdminAuth();

$session = getAdminSession();
$programmeManaged = $session['programme_managed'];

// Fetch all students (filtered by admin access)
$db = getDB();
$cols = STUDENT_LIST_COLS;
$where = "WHERE passport_photo IS NOT NULL AND passport_photo != ''";
$params = [];

if ($programmeManaged && $programmeManaged !== 'SuperAdmin') {
    $where .= " AND programme = ?";
    $params[] = $programmeManaged;
}

$stmt = $db->prepare("SELECT id, programme, first_name, middle_name, last_name, reg_number, passport_photo FROM students $where ORDER BY created_at DESC");
$stmt->execute($params);
$students = $stmt->fetchAll();

// We will parse the photos inside JavaScript or here to determine width/height and filter
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Photo Orientation Manager – <?php echo APP_NAME; ?></title>
    <link rel="icon" type="image/png" href="<?php echo asset('public/tsu-logo.png'); ?>">
    <link rel="stylesheet" href="<?php echo asset('assets/css/style.css'); ?>">
    <style>
        body {
            background: #f8fafc;
        }

        .manager-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }

        .header-bar {
            background: white;
            padding: 1.5rem 2rem;
            border-radius: 1rem;
            box-shadow: var(--shadow-md);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .header-title h1 {
            font-size: 1.5rem;
            margin: 0 0 0.25rem 0;
            background: linear-gradient(135deg, var(--primary-blue) 0%, #3d4a8f 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .header-title p {
            color: var(--gray-600);
            margin: 0;
            font-size: 0.875rem;
        }

        .back-link {
            color: #4b5563;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            transition: var(--transition);
        }
        .back-link:hover { color: var(--primary-blue); }
        .back-link svg { width: 20px; height: 20px; stroke: currentColor; stroke-width: 2; }

        /* Filter Controls */
        .filter-section {
            background: white;
            padding: 1rem 1.5rem;
            border-radius: 0.75rem;
            box-shadow: var(--shadow-sm);
            margin-bottom: 1.5rem;
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 0.5rem 1.25rem;
            border-radius: 0.5rem;
            font-weight: 600;
            font-size: 0.875rem;
            border: 1px solid var(--gray-300);
            background: white;
            color: var(--gray-700);
            cursor: pointer;
            transition: var(--transition);
        }

        .filter-btn.active {
            background: var(--primary-blue);
            color: white;
            border-color: var(--primary-blue);
            box-shadow: var(--shadow-md);
        }

        .filter-btn:hover:not(.active) {
            background: var(--gray-50);
            border-color: var(--gray-400);
        }

        /* Photo Grid */
        .photo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        .photo-card {
            background: white;
            border-radius: 1rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-200);
            overflow: hidden;
            transition: var(--transition);
            display: flex;
            flex-direction: column;
        }

        .photo-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }

        .photo-card.landscape-border {
            border: 2px solid #ef4444; /* red border to highlight potentially flipped landscape images */
        }

        .photo-preview-box {
            height: 220px;
            background: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            padding: 1rem;
        }

        .photo-preview-box img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            transition: transform 0.3s ease;
        }

        .orientation-badge {
            position: absolute;
            top: 0.75rem;
            right: 0.75rem;
            padding: 0.25rem 0.6rem;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            border-radius: 9999px;
            box-shadow: var(--shadow-sm);
        }

        .orientation-badge.landscape {
            background: #fee2e2;
            color: #ef4444;
            border: 1px solid #fecaca;
        }

        .orientation-badge.portrait {
            background: #dcfce7;
            color: #15803d;
            border: 1px solid #bbf7d0;
        }

        .orientation-badge.square {
            background: #f3f4f6;
            color: #4b5563;
            border: 1px solid #e5e7eb;
        }

        .photo-details {
            padding: 1.25rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .student-name {
            font-size: 1rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 0.25rem;
            line-height: 1.3;
        }

        .student-reg {
            font-size: 0.8125rem;
            color: var(--gray-500);
            font-family: monospace;
            margin-bottom: 0.5rem;
        }

        .student-prog {
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--primary-blue);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 1rem;
        }

        .controls-row {
            display: flex;
            gap: 0.5rem;
            margin-top: auto;
        }

        .btn-action {
            flex: 1;
            padding: 0.5rem;
            font-size: 0.8rem;
            font-weight: 700;
            border-radius: 0.375rem;
            border: 1px solid var(--gray-300);
            background: white;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.25rem;
        }

        .btn-action:hover {
            background: var(--gray-50);
            border-color: var(--gray-400);
        }

        .btn-action.cw:hover {
            color: var(--primary-blue);
            border-color: var(--primary-blue);
            background: #eff6ff;
        }

        .btn-action.ccw:hover {
            color: #d97706;
            border-color: #f59e0b;
            background: #fffbeb;
        }

        .loading-overlay {
            position: absolute;
            inset: 0;
            background: rgba(255,255,255,0.85);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s ease;
            z-index: 10;
        }

        .photo-preview-box.loading .loading-overlay {
            opacity: 1;
            pointer-events: auto;
        }

        .spinner-small {
            width: 28px;
            height: 28px;
            border: 3px solid var(--gray-200);
            border-top-color: var(--primary-blue);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        .empty-state {
            background: white;
            border-radius: 1rem;
            padding: 4rem 2rem;
            text-align: center;
            box-shadow: var(--shadow-sm);
            grid-column: 1 / -1;
        }
        .empty-state svg {
            width: 64px; height: 64px;
            color: var(--gray-400);
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="manager-container">
        <!-- Header -->
        <div class="header-bar">
            <div class="header-title">
                <h1>Photo Orientation Manager</h1>
                <p>Detect potentially flipped landscape passport photos and rotate them upright.</p>
            </div>
            <a href="dashboard.php" class="back-link">
                <svg fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Back to Dashboard
            </a>
        </div>

        <!-- Filter controls -->
        <div class="filter-section">
            <span style="font-weight: 700; font-size: 0.875rem; color: var(--gray-700);">Filter Orientations:</span>
            <button class="filter-btn active" id="btnFilterLandscape">Flipped Candidates (Landscape)</button>
            <button class="filter-btn" id="btnFilterAll">All Photos</button>
            <button class="filter-btn" id="btnFilterPortrait">Portrait Photos</button>
        </div>

        <!-- Cards grid -->
        <div class="photo-grid" id="photoGrid">
            <!-- Rendered by JS -->
        </div>
    </div>

    <script>
        // Inject student array from PHP securely
        const students = <?php echo json_encode(array_map(function($s) {
            return [
                'id' => (int)$s['id'],
                'first_name' => $s['first_name'],
                'middle_name' => $s['middle_name'],
                'last_name' => $s['last_name'],
                'reg_number' => $s['reg_number'],
                'programme' => $s['programme'],
                'passport_photo' => $s['passport_photo']
            ];
        }, $students)); ?>;

        const photoGrid = document.getElementById('photoGrid');
        const btnFilterLandscape = document.getElementById('btnFilterLandscape');
        const btnFilterAll = document.getElementById('btnFilterAll');
        const btnFilterPortrait = document.getElementById('btnFilterPortrait');

        let activeFilter = 'landscape'; // 'landscape', 'all', 'portrait'
        let parsedStudents = [];

        // Analyze and cache image dimensions
        async function analyzeImages() {
            const promises = students.map(student => {
                return new Promise((resolve) => {
                    const img = new Image();
                    img.onload = function() {
                        const width = this.width;
                        const height = this.height;
                        let orientation = 'square';
                        if (width > height) orientation = 'landscape';
                        else if (width < height) orientation = 'portrait';
                        
                        resolve({
                            ...student,
                            width,
                            height,
                            orientation
                        });
                    };
                    img.onerror = function() {
                        resolve({
                            ...student,
                            width: 0,
                            height: 0,
                            orientation: 'unknown'
                        });
                    };
                    img.src = student.passport_photo;
                });
            });

            parsedStudents = await Promise.all(promises);
            renderGrid();
        }

        function renderGrid() {
            photoGrid.innerHTML = '';
            
            let filtered = parsedStudents;
            if (activeFilter === 'landscape') {
                filtered = parsedStudents.filter(s => s.orientation === 'landscape');
            } else if (activeFilter === 'portrait') {
                filtered = parsedStudents.filter(s => s.orientation === 'portrait');
            }

            if (filtered.length === 0) {
                photoGrid.innerHTML = `
                    <div class="empty-state">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <h3 style="font-size: 1.15rem; color: var(--gray-800); font-weight: 700; margin-bottom: 0.25rem;">No Photos Found</h3>
                        <p style="color: var(--gray-500); font-size: 0.875rem; margin: 0;">No passport photos match the selected orientation filter.</p>
                    </div>
                `;
                return;
            }

            filtered.forEach(student => {
                const isL = student.orientation === 'landscape';
                const card = document.createElement('div');
                card.className = `photo-card ${isL ? 'landscape-border' : ''}`;
                card.id = `card-${student.id}`;
                card.innerHTML = `
                    <div class="photo-preview-box" id="preview-box-${student.id}">
                        <div class="loading-overlay">
                            <div class="spinner-small"></div>
                        </div>
                        <span class="orientation-badge ${student.orientation}">${student.orientation} (${student.width}x${student.height})</span>
                        <img src="${student.passport_photo}" id="img-${student.id}" alt="Student photo">
                    </div>
                    <div class="photo-details">
                        <div class="student-name">${student.last_name.toUpperCase()}, ${student.first_name} ${student.middle_name || ''}</div>
                        <div class="student-reg">${student.reg_number}</div>
                        <div class="student-prog">${student.programme}</div>
                        <div class="controls-row">
                            <button class="btn-action ccw" onclick="rotatePhoto(${student.id}, 'ccw')">
                                Rotate CCW ↺
                            </button>
                            <button class="btn-action cw" onclick="rotatePhoto(${student.id}, 'cw')">
                                Rotate CW ↻
                            </button>
                        </div>
                    </div>
                `;
                photoGrid.appendChild(card);
            });
        }

        async function rotatePhoto(studentId, direction) {
            const previewBox = document.getElementById(`preview-box-${studentId}`);
            const img = document.getElementById(`img-${studentId}`);
            
            previewBox.classList.add('loading');
            
            try {
                const formData = new FormData();
                formData.append('action', 'rotate_photo');
                formData.append('student_id', studentId);
                formData.append('direction', direction);

                const response = await fetch('ajax-handlers.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Update cache/student object
                    const newPhoto = data.photo;
                    
                    // Reload the image in memory to re-calculate dimensions
                    const tempImg = new Image();
                    tempImg.onload = function() {
                        const studentIdx = parsedStudents.findIndex(s => s.id === studentId);
                        if (studentIdx !== -1) {
                            parsedStudents[studentIdx].passport_photo = newPhoto;
                            parsedStudents[studentIdx].width = this.width;
                            parsedStudents[studentIdx].height = this.height;
                            parsedStudents[studentIdx].orientation = (this.width > this.height) ? 'landscape' : ((this.width < this.height) ? 'portrait' : 'square');
                        }
                        
                        // Re-render
                        renderGrid();
                    };
                    tempImg.src = newPhoto;
                } else {
                    alert('Error: ' + (data.error || 'Failed to rotate image'));
                    previewBox.classList.remove('loading');
                }
            } catch (err) {
                console.error(err);
                alert('Connection error – please try again.');
                previewBox.classList.remove('loading');
            }
        }

        // Filter button click events
        btnFilterLandscape.addEventListener('click', () => {
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            btnFilterLandscape.classList.add('active');
            activeFilter = 'landscape';
            renderGrid();
        });

        btnFilterAll.addEventListener('click', () => {
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            btnFilterAll.classList.add('active');
            activeFilter = 'all';
            renderGrid();
        });

        btnFilterPortrait.addEventListener('click', () => {
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            btnFilterPortrait.classList.add('active');
            activeFilter = 'portrait';
            renderGrid();
        });

        // Initialize
        analyzeImages();
    </script>
</body>
</html>
