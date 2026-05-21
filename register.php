<?php
require_once 'config.php';
require_once 'includes/session.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/tsu-data.php';

initSession();

$error = '';
$success = false;

if (isPost()) {
    try {
        $programme = post('programme');
        $first_name = sanitize(post('first_name'));
        $middle_name = sanitize(post('middle_name'));
        $last_name = sanitize(post('last_name'));
        $reg_number = sanitize(post('reg_number'));
        $blood_group = post('blood_group');
        $faculty = sanitize(post('faculty'));
        $department = sanitize(post('department'));
        $course_of_study = sanitize(post('course_of_study'));
        
        if (!$programme || !$first_name || !$last_name || !$reg_number || !$blood_group || !$faculty || !$department) {
            throw new Exception('All required fields must be filled.');
        }
        
        if (!isValidRegNumber($reg_number)) {
            throw new Exception('Registration number is required.');
        }
        
        if (!isset($_FILES['passport_photo']) || $_FILES['passport_photo']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Please upload a passport photo.');
        }
        
        $imageErrors = validateImage($_FILES['passport_photo']);
        if (!empty($imageErrors)) {
            throw new Exception(implode(' ', $imageErrors));
        }
        
        $passport_photo = imageToBase64($_FILES['passport_photo']);
        
        registerStudent([
            'programme' => $programme,
            'first_name' => $first_name,
            'middle_name' => $middle_name,
            'last_name' => $last_name,
            'reg_number' => $reg_number,
            'blood_group' => $blood_group,
            'faculty' => $faculty,
            'department' => $department,
            'course_of_study' => $course_of_study,
            'passport_photo' => $passport_photo
        ]);
        
        $success = true;
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$tsuData = getTsuData();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration - <?php echo APP_NAME; ?></title>
    <link rel="icon" type="image/png" href="<?php echo asset('public/tsu-logo.png'); ?>">
    <link rel="stylesheet" href="<?php echo asset('assets/css/style.css'); ?>">
    <style>
        body {
            background: var(--gray-50);
        }
        
        .registration-container {
            min-height: 100vh;
            padding: 1rem 1rem;
        }
        
        .registration-header {
            text-align: center;
            margin-bottom: 1rem;
        }
        
        .registration-logo {
            width: 60px;
            height: 60px;
            object-fit: contain;
            margin-bottom: 0.5rem;
            animation: float 3s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-6px); }
        }
        
        .registration-header h1 {
            font-size: 1.5rem;
            margin-bottom: 0.25rem;
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-gold) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .registration-header p {
            color: var(--gray-600);
            font-size: 0.875rem;
        }
        
        .registration-card {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 1.25rem;
            box-shadow: var(--shadow-lg);
            overflow: hidden;
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--primary-blue) 0%, #3d4a8f 100%);
            padding: 1.25rem 2rem;
            color: white;
        }
        
        .card-header h2 {
            color: white;
            font-size: 1.25rem;
            margin-bottom: 0.25rem;
        }
        
        .card-header p {
            margin: 0;
            opacity: 0.9;
            font-size: 0.875rem;
        }
        
        .card-body {
            padding: 1.5rem 2rem;
        }
        
        .form-section {
            margin-bottom: 1.25rem;
        }
        
        .form-section-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--gray-200);
        }
        
        .photo-upload-area {
            border: 2px dashed var(--gray-300);
            border-radius: var(--radius-lg);
            padding: 1.25rem;
            text-align: center;
            transition: var(--transition);
            cursor: pointer;
        }
        
        .photo-upload-area:hover {
            border-color: var(--primary-blue);
            background: var(--gray-50);
        }
        
        .photo-upload-area.has-image {
            border-style: solid;
            border-color: var(--success);
        }
        
        .photo-preview {
            width: 100px;
            height: 100px;
            margin: 0 auto 0.75rem;
            border-radius: var(--radius-lg);
            overflow: hidden;
            border: 3px solid var(--success);
            display: none;
        }
        
        .photo-preview.show { display: block; }
        .photo-preview img { width: 100%; height: 100%; object-fit: cover; }
        
        .upload-icon {
            width: 48px;
            height: 48px;
            margin: 0 auto 0.75rem;
            background: linear-gradient(135deg, var(--primary-blue) 0%, #3d4a8f 100%);
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .upload-icon svg { width: 24px; height: 24px; stroke: white; stroke-width: 2; }
        
        .back-link {
            text-align: center;
            margin-top: 1rem;
        }
        .back-link a {
            color: var(--gray-600);
            font-size: 0.875rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: var(--transition);
        }
        .back-link a:hover { color: var(--primary-blue); }
        .back-link svg { width: 16px; height: 16px; stroke: currentColor; stroke-width: 2; }
    </style>
</head>
<body>
    <?php if ($success): ?>
    <div class="success-container">
        <div class="success-card">
            <div class="success-icon">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h2 style="font-size: 1.75rem; margin-bottom: 1rem; color: var(--gray-900);">Registration Successful!</h2>
            <p style="color: var(--gray-600); margin-bottom: 2rem;">Your application has been submitted successfully. Please wait for ID card generation.</p>
            <a href="index.php" class="btn btn-primary btn-lg">Back to Home</a>
        </div>
    </div>
    <?php else: ?>
    <div class="registration-container">
        <div class="registration-header">
            <img src="public/tsu-logo.png" alt="TSU Logo" class="registration-logo">
            <h1>Student Registration</h1>
            <p>Register for your ID Card</p>
        </div>
        
        <div class="registration-card">
            <div class="card-header">
                <h2>New Student Registration</h2>
                <p>Please fill in all required information accurately</p>
            </div>
            
            <div class="card-body">
                <?php if ($error): ?>
                <div class="alert alert-error">
                    <svg style="width: 20px; height: 20px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <?php echo e($error); ?>
                </div>
                <?php endif; ?>
                
                <form method="POST" enctype="multipart/form-data" id="registrationForm">
                    <!-- Programme Selection -->
                    <div class="form-section">
                        <h3 class="form-section-title">Programme Type</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="form-check">
                                <input type="radio" name="programme" value="Sandwich" id="prog1" required>
                                <label for="prog1" style="text-transform: none; font-size: 1rem; font-weight: 600;">Sandwich Programme</label>
                            </div>
                            <div class="form-check">
                                <input type="radio" name="programme" value="IDELL" id="prog2" required>
                                <label for="prog2" style="text-transform: none; font-size: 1rem; font-weight: 600;">IDELL Programme</label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Personal Information -->
                    <div class="form-section">
                        <h3 class="form-section-title">Personal Information</h3>
                        <div class="grid grid-cols-3 gap-4">
                            <div class="form-group">
                                <label class="form-label">First Name *</label>
                                <input type="text" name="first_name" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Middle Name</label>
                                <input type="text" name="middle_name" class="form-control">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Last Name *</label>
                                <input type="text" name="last_name" class="form-control" required>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div class="form-group">
                                <label class="form-label">Registration Number *</label>
                                <input type="text" name="reg_number" class="form-control" required placeholder="e.g., TSU/SW/2023/001" style="font-family: monospace;">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Blood Group *</label>
                                <select name="blood_group" class="form-control" required>
                                    <option value="">Select Blood Group</option>
                                    <?php foreach (getBloodGroups() as $bg): ?>
                                    <option value="<?php echo $bg; ?>"><?php echo $bg; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Academic Information -->
                    <div class="form-section">
                        <h3 class="form-section-title">Academic Information</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="form-group">
                                <label class="form-label">Faculty *</label>
                                <select name="faculty" id="faculty" class="form-control" required>
                                    <option value="">Select Faculty</option>
                                    <?php foreach ($tsuData as $faculty): ?>
                                    <option value="<?php echo e($faculty['faculty']); ?>"><?php echo e($faculty['faculty']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Department *</label>
                                <select name="department" id="department" class="form-control" required disabled>
                                    <option value="">Select Department</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group" id="courseContainer" style="display:none;">
                            <label class="form-label">Course of Study *</label>
                            <select name="course_of_study" id="course" class="form-control">
                                <option value="">Select Course of Study</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Passport Photo -->
                    <div class="form-section">
                        <h3 class="form-section-title">Passport Photograph</h3>
                        <div class="photo-upload-area" id="uploadArea" onclick="document.getElementById('passport_photo').click()">
                            <div class="photo-preview" id="photoPreview">
                                <img id="previewImg" src="" alt="Preview">
                            </div>
                            <div id="uploadPrompt">
                                <div class="upload-icon">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                    </svg>
                                </div>
                                <h4 style="font-size: 1rem; font-weight: 600; margin-bottom: 0.5rem;">Click to upload passport photo</h4>
                                <p style="font-size: 0.875rem; color: var(--gray-600); margin: 0;">Maximum file size: 2MB • Formats: JPG, PNG</p>
                            </div>
                            <input type="file" name="passport_photo" id="passport_photo" accept="image/*" required style="display: none;">
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                        <a href="index.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary btn-lg">Submit Registration</button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="back-link">
            <a href="index.php">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Home
            </a>
        </div>
    </div>
    <?php endif; ?>

    <script>
        const tsuData = <?php echo getTsuDataJson(); ?>;
        
        // Faculty/Department/Course cascading
        const facultySelect = document.getElementById('faculty');
        const departmentSelect = document.getElementById('department');
        const courseSelect = document.getElementById('course');
        const courseContainer = document.getElementById('courseContainer');
        
        facultySelect?.addEventListener('change', function() {
            const selectedFaculty = this.value;
            departmentSelect.innerHTML = '<option value="">Select Department</option>';
            courseSelect.innerHTML = '<option value="">Select Course of Study</option>';
            courseContainer.style.display = 'none';
            
            if (selectedFaculty) {
                const faculty = tsuData.find(f => f.faculty === selectedFaculty);
                if (faculty) {
                    faculty.departments.forEach(dept => {
                        const option = document.createElement('option');
                        option.value = dept.name;
                        option.textContent = dept.name;
                        departmentSelect.appendChild(option);
                    });
                    departmentSelect.disabled = false;
                }
            } else {
                departmentSelect.disabled = true;
            }
        });
        
        departmentSelect?.addEventListener('change', function() {
            const selectedFaculty = facultySelect.value;
            const selectedDepartment = this.value;
            courseSelect.innerHTML = '<option value="">Select Course of Study</option>';
            
            if (selectedFaculty && selectedDepartment) {
                const faculty = tsuData.find(f => f.faculty === selectedFaculty);
                if (faculty) {
                    const department = faculty.departments.find(d => d.name === selectedDepartment);
                    if (department && department.programmes.length > 0) {
                        department.programmes.forEach(prog => {
                            // Regular option
                            const opt = document.createElement('option');
                            opt.value = prog;
                            opt.textContent = prog;
                            courseSelect.appendChild(opt);

                            // PG option — always shown for every course
                            const pgName = 'PG. ' + stripDegreePrefix(prog);
                            const pgOpt = document.createElement('option');
                            pgOpt.value = pgName;
                            pgOpt.textContent = pgName;
                            courseSelect.appendChild(pgOpt);
                        });
                        courseContainer.style.display = 'block';
                        courseSelect.required = true;
                    } else {
                        courseContainer.style.display = 'none';
                        courseSelect.required = false;
                    }
                }
            } else {
                courseContainer.style.display = 'none';
                courseSelect.required = false;
            }
        });

        // Remove the programme-change re-fire since PG is now always shown

        /**
         * Strips any degree prefix from a programme name.
         * e.g. "B. Sc. Economics" → "Economics"
         *      "B. A. (Ed) English" → "English"
         *      "B. Eng (Hons) Civil Engineering" → "Civil Engineering"
         *      "LLB Law" → "Law"
         *      "BNSc Nursing" → "Nursing"
         */
        function stripDegreePrefix(prog) {
            return prog
                // Remove common degree prefixes (order matters — longer first)
                .replace(/^B\.\s*Agric\s*\(Ed\)\s*/i, '')
                .replace(/^B\.\s*Agric[\.\-]?\s*/i, '')
                .replace(/^B\.\s*Eng\s*\(Hons\)\s*/i, '')
                .replace(/^B\.\s*Sc\.\s*\(Ed\)\s*/i, '')
                .replace(/^B\.\s*Sc[\.\-]?\s*/i, '')
                .replace(/^B\.\s*A\.\s*\(Ed\)\s*/i, '')
                .replace(/^B\.\s*A[\.\-]?\s*/i, '')
                .replace(/^B\.\s*Ed\s*/i, '')
                .replace(/^B\.\s*Library\s*&?\s*Info\s*Science\s*/i, '')
                .replace(/^B\.\s*Forest\s*Resource\s*and\s*/i, '')
                .replace(/^BMLS\s*/i, '')
                .replace(/^BNSc\s*/i, '')
                .replace(/^LLB\s*/i, '')
                .trim();
        }
        
        // Photo preview
        const photoInput = document.getElementById('passport_photo');
        const photoPreview = document.getElementById('photoPreview');
        const previewImg = document.getElementById('previewImg');
        const uploadPrompt = document.getElementById('uploadPrompt');
        const uploadArea = document.getElementById('uploadArea');
        
        photoInput?.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                if (file.size > 2 * 1024 * 1024) {
                    alert('Image must be less than 2MB');
                    this.value = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    photoPreview.classList.add('show');
                    uploadPrompt.style.display = 'none';
                    uploadArea.classList.add('has-image');
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>
