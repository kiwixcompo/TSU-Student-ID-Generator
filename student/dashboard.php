<?php
require_once '../config.php';
require_once '../includes/session.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

initSession();
requireStudentAuth();

$session = getStudentSession();
$student = getStudentByRegNumber($session['reg_number']);

if (!$student) {
    logoutStudent();
    redirect(baseUrl('student/login.php'));
}

$verificationUrl = baseUrl('verify.php?reg=' . urlencode($student['reg_number']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - <?php echo APP_NAME; ?></title>
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
            max-width: 1200px;
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
        
        .main-content {
            max-width: 1200px;
            margin: 1rem auto;
            padding: 0 1.5rem;
        }
        
        .profile-card {
            background: white;
            border-radius: 1.25rem;
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            margin-bottom: 1rem;
        }
        
        .profile-header {
            background: linear-gradient(135deg, var(--primary-blue) 0%, #3d4a8f 100%);
            padding: 1.25rem 1.5rem;
            position: relative;
            overflow: hidden;
        }
        
        .profile-header::before {
            content: '';
            position: absolute;
            top: -50%; right: -50%;
            width: 200%; height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: shimmer 4s infinite;
        }
        
        @keyframes shimmer {
            0%, 100% { transform: translate(0, 0); }
            50% { transform: translate(-10%, -10%); }
        }
        
        .profile-content {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            gap: 1.25rem;
            flex-wrap: wrap;
        }
        
        .profile-avatar {
            width: 90px;
            height: 90px;
            border-radius: 0.875rem;
            object-fit: cover;
            border: 3px solid white;
            box-shadow: var(--shadow-lg);
        }
        
        .profile-info h2 {
            color: white;
            font-size: 1.35rem;
            margin-bottom: 0.3rem;
        }
        
        .profile-info p {
            color: rgba(255,255,255,0.9);
            margin: 0.15rem 0;
            font-size: 0.875rem;
        }
        
        .profile-body {
            padding: 1.25rem 1.5rem;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 0.875rem;
        }
        
        .info-item {
            padding: 0.75rem 1rem;
            background: var(--gray-50);
            border-radius: var(--radius-lg);
            border-left: 4px solid var(--primary-blue);
        }
        
        .info-label {
            font-size: 0.7rem;
            font-weight: 700;
            color: var(--gray-600);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.3rem;
        }
        
        .info-value {
            font-size: 0.9375rem;
            font-weight: 600;
            color: var(--gray-900);
        }
        
        .verification-card {
            background: white;
            border-radius: 1.25rem;
            box-shadow: var(--shadow-lg);
            padding: 1.25rem 1.5rem;
            margin-bottom: 1rem;
        }
        
        .verification-card h3 {
            font-size: 1.1rem;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .verification-card svg { width: 20px; height: 20px; stroke: var(--success); stroke-width: 2; }
        
        .verification-link-box {
            display: flex;
            gap: 0.75rem;
            align-items: center;
        }
        
        .verification-link-box input {
            flex: 1;
            padding: 0.6rem 1rem;
            border: 2px solid var(--gray-300);
            border-radius: var(--radius);
            font-size: 0.8125rem;
            font-family: monospace;
            background: var(--gray-50);
        }
        
        .copy-btn {
            padding: 0.6rem 1.25rem;
            background: var(--primary-blue);
            color: white;
            border: none;
            border-radius: var(--radius);
            font-weight: 600;
            font-size: 0.875rem;
            cursor: pointer;
            transition: var(--transition);
            white-space: nowrap;
        }
        .copy-btn:hover { background: #3d4a8f; }
    </style>
</head>
<body>
    <div class="dashboard-header">
        <div class="header-content">
            <div class="header-left">
                <img src="../public/tsu-logo.png" alt="TSU Logo" class="header-logo">
                <div class="header-info">
                    <h1>Student Dashboard</h1>
                    <p>Welcome back, <?php echo e($student['first_name']); ?>!</p>
                </div>
            </div>
            <a href="logout.php" class="btn btn-secondary btn-sm">Logout</a>
        </div>
    </div>

    <div class="main-content">
        <!-- Admin Note Alert -->
        <?php if (!empty($student['admin_note'])): ?>
        <div class="alert alert-info">
            <svg style="width: 20px; height: 20px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>
                <strong>Message from Admin:</strong><br>
                <?php echo nl2br(e($student['admin_note'])); ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Profile Card -->
        <div class="profile-card">
            <div class="profile-header">
                <div class="profile-content">
                    <?php if (!empty($student['has_photo'])): ?>
                        <img src="../avatar.php?id=<?php echo $student['id']; ?>" alt="Student Photo" class="profile-avatar">
                    <?php else: ?>
                        <div class="profile-avatar" style="background: var(--gray-200); color: var(--gray-600); display: flex; align-items: center; justify-content: center; font-size: 2.25rem; font-weight: 700;">
                            <?php echo strtoupper(substr($student['first_name'], 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                    <div class="profile-info">
                        <h2><?php echo e($student['last_name']); ?>, <?php echo e($student['first_name']); ?> <?php echo e($student['middle_name']); ?></h2>
                        <p><strong>Reg Number:</strong> <?php echo e($student['reg_number']); ?></p>
                        <p><strong>Programme:</strong> <?php echo e($student['programme']); ?></p>
                        <div style="margin-top: 1rem;">
                            <?php if ($student['status'] === 'id_generated'): ?>
                            <span class="badge badge-success" style="font-size: 0.875rem; padding: 0.5rem 1rem;">
                                <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                ID Card Generated
                            </span>
                            <?php else: ?>
                            <span class="badge badge-warning" style="font-size: 0.875rem; padding: 0.5rem 1rem;">
                                <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Pending Review
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="profile-body">
                <h3 style="font-size: 1.125rem; margin-bottom: 1.5rem; color: var(--gray-900);">Personal Information</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Blood Group</div>
                        <div class="info-value" style="color: var(--error);"><?php echo e($student['blood_group']); ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Faculty</div>
                        <div class="info-value"><?php echo e($student['faculty']); ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Department</div>
                        <div class="info-value"><?php echo e($student['department']); ?></div>
                    </div>
                    
                    <?php if (!empty($student['course_of_study'])): ?>
                    <div class="info-item">
                        <div class="info-label">Course of Study</div>
                        <div class="info-value"><?php echo e($student['course_of_study']); ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="info-item">
                        <div class="info-label">Registration Date</div>
                        <div class="info-value"><?php echo formatDate($student['created_at'], 'F j, Y'); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Verification Card -->
        <div class="verification-card">
            <h3>
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Verification Link
            </h3>
            <p style="color: var(--gray-600); margin-bottom: 1rem;">Share this link to verify your ID card:</p>
            <div class="verification-link-box">
                <input type="text" id="verificationLink" value="<?php echo e($verificationUrl); ?>" readonly>
                <button class="copy-btn" onclick="copyLink()">Copy Link</button>
            </div>
        </div>

        <div class="text-center">
            <a href="../index.php" class="btn btn-secondary">Back to Home</a>
        </div>
    </div>

    <script>
        function copyLink() {
            const input = document.getElementById('verificationLink');
            input.select();
            document.execCommand('copy');
            
            const btn = event.target;
            const originalText = btn.textContent;
            btn.textContent = 'Copied!';
            btn.style.background = 'var(--success)';
            
            setTimeout(() => {
                btn.textContent = originalText;
                btn.style.background = '';
            }, 2000);
        }
    </script>
</body>
</html>
