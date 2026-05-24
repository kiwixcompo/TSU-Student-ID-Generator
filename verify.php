<?php
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$reg_number = get('reg');
$student    = null;
$found      = false;

if ($reg_number) {
    $student = getStudentByRegNumber($reg_number);
    $found   = ($student !== false);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify ID Card – <?php echo APP_NAME; ?></title>
    <link rel="icon" type="image/png" href="<?php echo asset('public/tsu-logo.png'); ?>">
    <link rel="stylesheet" href="<?php echo asset('assets/css/style.css'); ?>">
    <style>
        body { background: #f3f4f6; min-height: 100vh; }

        .verify-wrap {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }

        .verify-card {
            width: 100%;
            max-width: 680px;
            background: white;
            border-radius: 1.25rem;
            box-shadow: 0 10px 40px rgba(0,0,0,.12);
            overflow: hidden;
        }

        /* ── Header ── */
        .v-header {
            background: linear-gradient(135deg, #166534 0%, #14532d 100%);
            padding: 1.75rem 2rem;
            display: flex;
            align-items: center;
            gap: 1.25rem;
        }
        .v-header img { width: 56px; height: 56px; object-fit: contain; flex-shrink: 0; }
        .v-header-text h1 { color: white; font-size: 1.35rem; margin: 0 0 .2rem; }
        .v-header-text p  { color: rgba(255,255,255,.85); margin: 0; font-size: .875rem; }

        /* ── Body ── */
        .v-body { padding: 2rem; }

        /* ── Profile row ── */
        .profile-row {
            display: flex;
            gap: 1.75rem;
            align-items: flex-start;
            flex-wrap: wrap;
        }
        .profile-photo-wrap { flex-shrink: 0; text-align: center; }
        .profile-photo {
            width: 140px;
            height: 160px;
            object-fit: cover;
            border-radius: .75rem;
            border: 3px solid #166534;
            box-shadow: 0 4px 12px rgba(0,0,0,.12);
            display: block;
        }
        .photo-placeholder {
            width: 140px;
            height: 160px;
            background: #e2e8f0;
            border-radius: .75rem;
            border: 3px solid #166534;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            font-weight: 700;
            color: #64748b;
        }

        /* ── Details ── */
        .profile-info { flex: 1; min-width: 260px; }
        .profile-name {
            font-size: 1.4rem;
            font-weight: 800;
            color: #14532d;
            margin: 0 0 1rem;
            line-height: 1.2;
        }

        .detail-table { width: 100%; border-collapse: collapse; }
        .detail-table tr { border-bottom: 1px solid #f0f0f0; }
        .detail-table tr:last-child { border-bottom: none; }
        .detail-table td { padding: .55rem .25rem; font-size: .9rem; vertical-align: top; }
        .detail-table .lbl {
            width: 130px;
            font-weight: 700;
            color: #166534;
            white-space: nowrap;
        }
        .detail-table .val { color: #111827; font-weight: 600; }

        /* ── Verified badge ── */
        .verified-badge {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            background: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
            border-radius: 999px;
            padding: .3rem .85rem;
            font-size: .8rem;
            font-weight: 700;
            margin-top: .75rem;
        }
        .verified-badge svg { width: 14px; height: 14px; }

        /* ── Not found ── */
        .not-found {
            text-align: center;
            padding: 3rem 1rem;
        }
        .not-found-icon {
            width: 80px; height: 80px;
            background: #fee2e2;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1.25rem;
        }
        .not-found-icon svg { width: 40px; height: 40px; stroke: #dc2626; stroke-width: 2; }
        .not-found h3 { font-size: 1.3rem; color: #111827; margin-bottom: .5rem; }
        .not-found p  { color: #6b7280; margin-bottom: 1.5rem; }

        /* ── Footer ── */
        .v-footer {
            padding: 1rem 2rem;
            background: #f9fafb;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: .85rem;
            color: #6b7280;
        }
        .v-footer a { color: #166534; font-weight: 600; text-decoration: none; }
    </style>
</head>
<body>
<div class="verify-wrap">
    <div class="verify-card">

        <!-- Header -->
        <div class="v-header">
            <img src="public/tsu-logo.png" alt="TSU Logo">
            <div class="v-header-text">
                <h1>ID Card Verification</h1>
                <p>Taraba State University, Jalingo</p>
            </div>
        </div>

        <!-- Body -->
        <div class="v-body">
            <?php if (!$reg_number): ?>
                <div class="not-found">
                    <div class="not-found-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <h3>No Registration Number Provided</h3>
                    <p>Scan a valid TSU student ID card QR code to verify.</p>
                    <a href="index.php" class="btn btn-primary">Back to Home</a>
                </div>

            <?php elseif (!$found): ?>
                <div class="not-found">
                    <div class="not-found-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3>Student Not Found</h3>
                    <p>No record found for registration number <strong><?php echo e($reg_number); ?></strong>.</p>
                    <a href="index.php" class="btn btn-primary">Back to Home</a>
                </div>

            <?php else: ?>
                <div class="profile-row">

                    <!-- Photo -->
                    <div class="profile-photo-wrap">
                        <?php if (!empty($student['has_photo'])): ?>
                            <img src="avatar.php?id=<?php echo $student['id']; ?>"
                                 class="profile-photo" alt="Student Photo"
                                 onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                            <div class="photo-placeholder" style="display:none;">
                                <?php echo strtoupper(substr($student['first_name'], 0, 1)); ?>
                            </div>
                        <?php else: ?>
                            <div class="photo-placeholder">
                                <?php echo strtoupper(substr($student['first_name'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($student['status'] === 'id_generated'): ?>
                        <div class="verified-badge">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Verified
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Details -->
                    <div class="profile-info">
                        <div class="profile-name">
                            <?php echo e($student['last_name'] . ', ' . $student['first_name'] . ' ' . $student['middle_name']); ?>
                        </div>

                        <table class="detail-table">
                            <tr>
                                <td class="lbl">Reg Number:</td>
                                <td class="val" style="font-family:monospace;"><?php echo e($student['reg_number']); ?></td>
                            </tr>
                            <tr>
                                <td class="lbl">Programme:</td>
                                <td class="val"><?php echo e($student['programme']); ?></td>
                            </tr>
                            <tr>
                                <td class="lbl">Faculty:</td>
                                <td class="val"><?php echo e($student['faculty']); ?></td>
                            </tr>
                            <tr>
                                <td class="lbl">Department:</td>
                                <td class="val"><?php echo e($student['department']); ?></td>
                            </tr>
                            <?php if (!empty($student['course_of_study'])): ?>
                            <tr>
                                <td class="lbl">Course of Study:</td>
                                <td class="val"><?php echo e($student['course_of_study']); ?></td>
                            </tr>
                            <?php endif; ?>
                            <tr>
                                <td class="lbl">Blood Group:</td>
                                <td class="val" style="color:#dc2626;font-weight:800;"><?php echo e($student['blood_group']); ?></td>
                            </tr>
                            <tr>
                                <td class="lbl">ID Status:</td>
                                <td class="val">
                                    <?php if ($student['status'] === 'id_generated'): ?>
                                        <span style="color:#166534;font-weight:700;">✓ ID Generated</span>
                                    <?php else: ?>
                                        <span style="color:#d97706;font-weight:700;">Pending</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Footer -->
        <div class="v-footer">
            <a href="index.php">← Back to Home</a>
            &nbsp;·&nbsp;
            Taraba State University &copy; <?php echo date('Y'); ?>
        </div>

    </div>
</div>
</body>
</html>
