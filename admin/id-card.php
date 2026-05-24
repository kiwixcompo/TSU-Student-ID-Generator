<?php
require_once '../config.php';
require_once '../includes/session.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

initSession();
requireAdminAuth();

$studentId = get('id');
if (!$studentId) {
    redirect(baseUrl('admin/dashboard.php'));
}

$student = getStudentById($studentId);
if (!$student) {
    redirect(baseUrl('admin/dashboard.php'));
}

$session = getAdminSession();
if (!canAdminAccessStudent($session['programme_managed'], $student['programme'])) {
    redirect(baseUrl('admin/dashboard.php'));
}

$verificationUrl = baseUrl('verify.php?reg=' . urlencode($student['reg_number']));
$isGenerated = $student['status'] === 'id_generated';

// Build full name
$nameParts = array_filter([
    $student['last_name']   ?? '',
    $student['first_name']  ?? '',
    $student['middle_name'] ?? '',
]);
$fullName = strtoupper(trim(implode(', ', [
    $student['last_name'],
    trim(($student['first_name'] ?? '') . ' ' . ($student['middle_name'] ?? ''))
])));

// Dynamic font size for long names
$nameLen   = strlen($fullName);
$nameClass = 'full-name';
if ($nameLen > 30)      $nameClass .= ' name-extra-long';
elseif ($nameLen > 25)  $nameClass .= ' name-very-long';
elseif ($nameLen > 20)  $nameClass .= ' name-long';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ID Card – <?php echo e($student['first_name'] . ' ' . $student['last_name']); ?></title>
    <link rel="icon" type="image/png" href="<?php echo asset('public/tsu-logo.png'); ?>">
    <link rel="stylesheet" href="<?php echo asset('assets/css/style.css'); ?>">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        /* ── PAGE ── */
        body {
            background: #f0f0f0;
            padding: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .id-card-container { max-width: 1000px; margin: 0 auto; }

        /* ── CONTROLS BAR ── */
        .controls-bar {
            background: white;
            padding: 1.25rem 1.5rem;
            border-radius: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,.08);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .controls-left a {
            color: #4b5563;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: .5rem;
        }
        .controls-left a:hover { color: #166534; }
        .controls-left svg { width: 20px; height: 20px; stroke: currentColor; stroke-width: 2; }
        .controls-right { display: flex; gap: .75rem; flex-wrap: wrap; }

        .cards-wrapper {
            display: flex;
            gap: 2.5rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        .card-label {
            text-align: center;
            font-weight: 700;
            color: #374151;
            margin-bottom: .5rem;
            font-size: .9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* ── CARD SHELL ── */
        .id-card {
            width: 350px;
            height: 550px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,.2);
            overflow: hidden;
            position: relative;
        }

        /* ══════════════════════════════
           FRONT
        ══════════════════════════════ */
        .id-card-front {
            height: 100%;
            position: relative;
            background-color: #f8f9fa;
            display: flex;
            flex-direction: column;
        }

        /* Building backdrop */
        .id-card-front::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image: url('../public/tsu-building.jpg');
            background-size: cover;
            background-position: center;
            opacity: 0.15;
            z-index: 0;
        }

        /* ── Header ── */
        .header-section {
            text-align: center;
            padding-top: 20px;
            position: relative;
            z-index: 2;
        }
        .header-logo {
            width: 65px;
            height: 65px;
            object-fit: contain;
            margin-bottom: 3px;
        }
        .uni-name {
            color: #166534;
            font-weight: 800;
            font-size: 15px;
            text-transform: uppercase;
            margin: 0;
            line-height: 1.1;
        }
        .uni-location {
            display: inline-block;
            color: #166534;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            border-top: 1px solid #166534;
            border-bottom: 1px solid #166534;
            padding: 1px 8px;
            margin-top: 2px;
        }

        /* ── Photo ── */
        .photo-section {
            text-align: center;
            margin-top: 10px;
            position: relative;
            z-index: 2;
            height: 155px;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .profile-photo {
            width: 125px;
            height: 150px;
            object-fit: cover;
            border-radius: 8px;
            border: 3px solid #166534;
            box-shadow: 0 3px 6px rgba(0,0,0,.15);
            background: #fff;
        }
        .photo-placeholder {
            width: 125px;
            height: 150px;
            background: #e2e8f0;
            color: #64748b;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 50px;
            font-weight: bold;
            border-radius: 8px;
            border: 3px solid #166534;
        }

        /* ── Vertical bar ── */
        .vertical-bar {
            position: absolute;
            left: 20px;
            bottom: 40px;
            width: 40px;
            height: 195px;
            background-color: #166534;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px 8px 0 0;
            z-index: 3;
            box-shadow: 2px -2px 5px rgba(0,0,0,.1);
        }
        .vertical-text {
            transform: rotate(-90deg);
            white-space: nowrap;
            font-weight: 800;
            letter-spacing: 2px;
            text-transform: uppercase;
            font-size: 13px;
            color: white;
        }

        /* ── Name ── */
        .name-section {
            text-align: center;
            margin-top: 8px;
            position: relative;
            z-index: 2;
            padding: 0 10px;
        }
        .full-name {
            color: #14532d;
            font-weight: 800;
            font-size: 17px;
            margin: 0;
            line-height: 1.1;
            word-wrap: break-word;
            word-break: break-word;
        }
        .full-name.name-long       { font-size: 15px; }
        .full-name.name-very-long  { font-size: 13px; }
        .full-name.name-extra-long { font-size: 11px; }
        .designation {
            color: #4b5563;
            font-size: 11px;
            font-weight: 600;
            margin-top: 2px;
            word-wrap: break-word;
        }

        /* ── Details table ── */
        .details-section {
            margin-top: 12px;
            margin-left: 72px;
            margin-right: 12px;
            position: relative;
            z-index: 2;
        }
        .details-table { width: 100%; border-collapse: collapse; }
        .details-table td { vertical-align: top; padding-bottom: 6px; }
        .details-label {
            font-weight: 800;
            color: #166534;
            width: 52px;
            white-space: nowrap;
            font-size: 11px;
            padding-right: 4px;
        }
        .details-value {
            color: #111;
            font-weight: 600;
            line-height: 1.3;
            font-size: 12px;
            word-break: break-word;
            hyphens: auto;
        }

        /* ── Footer ── */
        .card-footer {
            position: absolute;
            bottom: 0; left: 0; right: 0;
            height: 40px;
            background: #166534;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 600;
            z-index: 2;
        }

        /* ══════════════════════════════
           BACK
        ══════════════════════════════ */
        .id-card-back {
            height: 100%;
            position: relative;
            background: #fff;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .tsu-watermark {
            position: absolute;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%) rotate(-30deg);
            font-size: 120px;
            font-weight: 900;
            color: rgba(22, 101, 52, 0.05);
            z-index: 0;
            pointer-events: none;
            user-select: none;
        }
        .back-content {
            position: relative;
            z-index: 2;
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
            text-align: center;
        }
        .qr-container { margin-bottom: 20px; text-align: center; }
        .scan-instruction {
            font-size: 14px;
            color: #166534;
            font-weight: 800;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: .5px;
        }
        .qr-code {
            width: 200px;
            height: 200px;
            border: 4px solid #14532d;
            border-radius: 12px;
            padding: 5px;
            background: white;
            box-shadow: 0 4px 12px rgba(0,0,0,.15);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .blood-group-box {
            border: 3px solid #dc2626;
            border-radius: 10px;
            padding: 8px 30px;
            margin-bottom: 20px;
            background: rgba(255,255,255,.95);
            min-width: 140px;
            box-shadow: 0 2px 5px rgba(0,0,0,.05);
        }
        .bg-label {
            font-size: 12px;
            text-transform: uppercase;
            color: #dc2626;
            font-weight: 800;
            letter-spacing: 1px;
        }
        .bg-value {
            font-size: 32px;
            font-weight: 900;
            color: #333;
            line-height: 1.1;
        }
        .return-info {
            font-size: 11px;
            color: #4b5563;
            line-height: 1.4;
            margin-top: auto;
            margin-bottom: 5px;
        }
        .return-info strong {
            color: #166534;
            display: block;
            font-size: 13px;
            margin: 2px 0;
        }
        .back-footer {
            position: absolute;
            bottom: 0; left: 0; right: 0;
            height: 40px;
            background: #166534;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 600;
            z-index: 2;
        }

        /* ── Badges / Buttons ── */
        .badge-generated {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            background: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
            border-radius: 999px;
            padding: .35rem .85rem;
            font-size: .8rem;
            font-weight: 700;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .5rem 1.1rem;
            border-radius: .5rem;
            font-weight: 600;
            font-size: .875rem;
            cursor: pointer;
            border: none;
            transition: opacity .2s;
        }
        .btn:hover { opacity: .85; }
        .btn:disabled { opacity: .5; cursor: not-allowed; }
        .btn-secondary { background: #f3f4f6; color: #374151; border: 1px solid #d1d5db; }
        .btn-primary   { background: #166534; color: white; }
        .btn-outline   { background: transparent; color: #166534; border: 2px solid #166534; }

        @media print {
            body { background: white; padding: 0; }
            .no-print { display: none !important; }
            .id-card { box-shadow: none; page-break-after: always; }
        }
    </style>
</head>
<body>
<div class="id-card-container">

    <!-- Controls -->
    <div class="controls-bar no-print">
        <div class="controls-left">
            <a href="dashboard.php">
                <svg fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Back to Dashboard
            </a>
        </div>
        <div class="controls-right">
            <?php if ($isGenerated): ?>
            <span class="badge-generated">
                <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Already Generated
            </span>
            <?php endif; ?>
            <?php if (!empty($student['printed'])): ?>
            <span class="badge-generated" id="printedBadge">
                <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Printed<?php if (!empty($student['printed_at'])): ?> – <?php echo date('M j, Y', strtotime($student['printed_at'])); ?><?php endif; ?>
            </span>
            <button onclick="setPrinted(0)" class="btn btn-secondary" id="printedBtn">Unmark Printed</button>
            <?php else: ?>
            <button onclick="setPrinted(1)" class="btn btn-outline" id="printedBtn">
                <svg style="width:16px;height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Mark as Printed
            </button>
            <?php endif; ?>
            <button onclick="window.print()" class="btn btn-secondary">
                <svg style="width:16px;height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Print
            </button>
            <button onclick="downloadCards()" class="btn btn-primary" id="downloadBtn">
                <svg style="width:16px;height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Export as PNG
            </button>
        </div>
    </div>

    <!-- Cards -->
    <div class="cards-wrapper">

        <!-- ══ FRONT ══ -->
        <div>
            <div class="card-label no-print">Front</div>
            <div class="id-card" id="cardFront">
                <div class="id-card-front">

                    <!-- Vertical bar -->
                    <div class="vertical-bar">
                        <div class="vertical-text">STUDENT ID CARD</div>
                    </div>

                    <!-- Header -->
                    <div class="header-section">
                        <img src="../public/tsu-logo.png" alt="TSU Logo" class="header-logo">
                        <div class="uni-name">TARABA STATE UNIVERSITY</div>
                        <div class="uni-location">JALINGO</div>
                    </div>

                    <!-- Photo -->
                    <div class="photo-section">
                        <?php if (!empty($student['has_photo'])): ?>
                            <img src="../avatar.php?id=<?php echo $student['id']; ?>"
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
                    </div>

                    <!-- Name -->
                    <div class="name-section">
                        <div class="<?php echo $nameClass; ?>"><?php echo e($fullName); ?></div>
                        <div class="designation"><?php echo e($student['programme']); ?> / <?php echo e($student['course_of_study'] ?: $student['department']); ?></div>
                    </div>

                    <!-- Details -->
                    <div class="details-section">
                        <table class="details-table">
                            <tr>
                                <td class="details-label">Reg No:</td>
                                <td class="details-value"><?php echo e($student['reg_number']); ?></td>
                            </tr>
                            <tr>
                                <td class="details-label">Faculty:</td>
                                <td class="details-value"><?php echo e(preg_replace('/^Faculty of\s*/i', '', $student['faculty'])); ?></td>
                            </tr>
                            <tr>
                                <td class="details-label">Dept:</td>
                                <td class="details-value"><?php echo e($student['department']); ?></td>
                            </tr>
                        </table>
                    </div>

                    <!-- Footer -->
                    <div class="card-footer">Issued: <?php echo date('F Y'); ?></div>

                </div>
            </div>
        </div>

        <!-- ══ BACK ══ -->
        <div>
            <div class="card-label no-print">Back</div>
            <div class="id-card" id="cardBack">
                <div class="id-card-back">
                    <div class="tsu-watermark">TSU</div>

                    <div class="back-content">
                        <!-- QR Code -->
                        <div class="qr-container">
                            <div class="scan-instruction">SCAN THIS TO VERIFY</div>
                            <div class="qr-code" id="qrcode"></div>
                        </div>

                        <!-- Blood Group -->
                        <div class="blood-group-box">
                            <div class="bg-label">Blood Group</div>
                            <div class="bg-value"><?php echo e($student['blood_group']); ?></div>
                        </div>

                        <!-- Return info -->
                        <div class="return-info">
                            <p style="margin:0;">If found, please return to:</p>
                            <strong>SECURITY UNIT</strong>
                            <p style="margin:0;">Taraba State University<br>Jalingo, Nigeria</p>
                        </div>
                    </div>

                    <div class="back-footer">Property of Taraba State University</div>
                </div>
            </div>
        </div>

    </div><!-- /cards-wrapper -->
</div><!-- /id-card-container -->

<script>
    // ── QR Code ──
    new QRCode(document.getElementById('qrcode'), {
        text: <?php echo json_encode(
            "Name: " . d($student['last_name'] ?? '') . ", " . d($student['first_name'] ?? '') . " " . d($student['middle_name'] ?? '') . "\n" .
            "Reg No: " . d($student['reg_number'] ?? '') . "\n" .
            "Faculty: " . d($student['faculty'] ?? '') . "\n" .
            "Dept: " . d($student['department'] ?? '') . "\n" .
            "Verify: " . $verificationUrl
        ); ?>,

        width: 190,
        height: 190,
        colorDark: '#14532d',
        colorLight: '#ffffff',
        correctLevel: QRCode.CorrectLevel.M
    });

    // ── Export as PNG ──
    async function downloadCards() {
        const btn = document.getElementById('downloadBtn');
        btn.disabled = true;
        btn.textContent = 'Generating…';

        try {
            const opts = { scale: 3, useCORS: true, allowTaint: true, backgroundColor: '#ffffff' };
            const regSafe = <?php echo json_encode(str_replace('/', '_', $student['reg_number'])); ?>;

            const frontCanvas = await html2canvas(document.getElementById('cardFront'), opts);
            const a1 = document.createElement('a');
            a1.download = regSafe + '_front.png';
            a1.href = frontCanvas.toDataURL();
            a1.click();

            await new Promise(r => setTimeout(r, 500));

            const backCanvas = await html2canvas(document.getElementById('cardBack'), opts);
            const a2 = document.createElement('a');
            a2.download = regSafe + '_back.png';
            a2.href = backCanvas.toDataURL();
            a2.click();

            <?php if (!$isGenerated): ?>
            await fetch('ajax-handlers.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=mark_generated&reg_number=<?php echo urlencode($student['reg_number']); ?>'
            });
            <?php endif; ?>

            btn.textContent = '✓ Downloaded!';
            setTimeout(() => { btn.disabled = false; btn.textContent = 'Export as PNG'; }, 2500);
        } catch (err) {
            console.error(err);
            btn.disabled = false;
            btn.textContent = 'Error – Try Again';
        }
    }

    // ── Mark generated on print ──
    window.onbeforeprint = function () {
        <?php if (!$isGenerated): ?>
        fetch('ajax-handlers.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=mark_generated&reg_number=<?php echo urlencode($student['reg_number']); ?>'
        });
        <?php endif; ?>
        // Also mark as printed
        fetch('ajax-handlers.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=mark_printed&student_id=<?php echo (int)$student['id']; ?>&printed=1'
        });
    };

    // ── Mark as Printed button ──
    async function setPrinted(value) {
        const btn = document.getElementById('printedBtn');
        btn.disabled = true;
        try {
            const res = await fetch('ajax-handlers.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=mark_printed&student_id=<?php echo (int)$student['id']; ?>&printed=${value}`
            });
            const data = await res.json();
            if (data.success) {
                // Reload to reflect updated badge
                window.location.reload();
            } else {
                btn.disabled = false;
                alert('Error: ' + (data.error || 'Failed to update'));
            }
        } catch (e) {
            btn.disabled = false;
            alert('Network error. Please try again.');
        }
    }
</script>
</body>
</html>
