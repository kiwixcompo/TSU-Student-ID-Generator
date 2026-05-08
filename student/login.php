<?php
require_once '../config.php';
require_once '../includes/session.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

initSession();

if (isStudentLoggedIn()) {
    redirect(baseUrl('student/dashboard.php'));
}

$error = '';

if (isPost()) {
    $reg_number = sanitize(post('reg_number'));
    $password = post('password');
    
    if ($reg_number && $password) {
        if (!isValidRegNumber($reg_number)) {
            $error = 'Invalid registration number format';
        } else {
            $student = verifyStudentPassword($reg_number, $password);
            if ($student) {
                setStudentSession($student);
                redirect(baseUrl('student/dashboard.php'));
            } else {
                $error = 'Invalid registration number or password';
            }
        }
    } else {
        $error = 'Please enter both registration number and password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo asset('assets/css/style.css'); ?>">
    <style>
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            position: relative;
            overflow: hidden;
        }
        
        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 50%, rgba(74, 91, 168, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(244, 196, 48, 0.15) 0%, transparent 50%);
            pointer-events: none;
        }
        
        .login-card {
            position: relative;
            z-index: 1;
            background: white;
            border-radius: 2rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            max-width: 480px;
            width: 100%;
            animation: slideUp 0.6s ease;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .login-header {
            background: linear-gradient(135deg, var(--primary-blue) 0%, #3d4a8f 100%);
            padding: 1.5rem 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .login-header::before {
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
        
        .login-logo {
            width: 72px;
            height: 72px;
            object-fit: contain;
            margin-bottom: 0.75rem;
            filter: drop-shadow(0 4px 12px rgba(0,0,0,0.2));
            animation: float 3s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-6px); }
        }
        
        .login-header h1 {
            color: white;
            font-size: 1.4rem;
            margin-bottom: 0.25rem;
            position: relative;
            z-index: 1;
        }
        
        .login-header p {
            color: rgba(255,255,255,0.9);
            font-size: 0.875rem;
            margin: 0;
            position: relative;
            z-index: 1;
        }
        
        .login-body {
            padding: 1.5rem 2rem;
        }
        
        .info-box {
            background: linear-gradient(135deg, #FEF3C7 0%, #FDE68A 100%);
            border-left: 4px solid var(--warning);
            padding: 0.75rem 1rem;
            border-radius: var(--radius-lg);
            margin-bottom: 1.25rem;
            font-size: 0.8125rem;
            color: #92400E;
            display: flex;
            gap: 0.75rem;
        }
        .info-box svg { width: 18px; height: 18px; flex-shrink: 0; stroke: currentColor; stroke-width: 2; }
        
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
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <img src="../public/tsu-logo.png" alt="TSU Logo" class="login-logo">
                <h1>Student Portal</h1>
                <p>Check Your ID Card Status</p>
            </div>
            
            <div class="login-body">
                <?php if ($error): ?>
                <div class="alert alert-error">
                    <svg style="width: 20px; height: 20px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <?php echo e($error); ?>
                </div>
                <?php endif; ?>
                
                <div class="info-box">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <strong>Default Password:</strong> Your registration number<br>
                        <small>Contact admin if you need to reset your password</small>
                    </div>
                </div>
                
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">Registration Number</label>
                        <input type="text" name="reg_number" class="form-control" required autofocus placeholder="e.g., TSU/SW/2023/001" style="font-family: monospace;">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required placeholder="Enter your password">
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-lg w-full">
                        <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                        </svg>
                        Sign In
                    </button>
                </form>
                
                <div class="back-link">
                    <a href="../index.php">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Back to Home
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
