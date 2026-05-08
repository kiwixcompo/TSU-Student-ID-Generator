<?php
require_once 'config.php';
require_once 'includes/session.php';
initSession();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?></title>
    <link rel="icon" type="image/png" href="<?php echo asset('public/tsu-logo.png'); ?>">
    <link rel="stylesheet" href="<?php echo asset('assets/css/style.css'); ?>">
    <style>
        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            position: relative;
            overflow: hidden;
        }
        
        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 50%, rgba(74, 91, 168, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(244, 196, 48, 0.1) 0%, transparent 50%);
            pointer-events: none;
        }
        
        .hero-content {
            position: relative;
            z-index: 1;
            max-width: 1200px;
            width: 100%;
        }
        
        .logo-container {
            text-align: center;
            margin-bottom: 1rem;
            animation: fadeInDown 0.8s ease;
        }
        
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-30px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        
        .logo-container img {
            width: 90px;
            height: 90px;
            object-fit: contain;
            filter: drop-shadow(0 6px 12px rgba(0,0,0,0.15));
            animation: float 3s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-6px); }
        }
        
        .hero-title {
            font-size: 2.25rem;
            font-weight: 800;
            text-align: center;
            margin-bottom: 0.4rem;
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-gold) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: fadeIn 1s ease 0.3s both;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to   { opacity: 1; }
        }
        
        .hero-subtitle {
            text-align: center;
            font-size: 1rem;
            color: var(--gray-600);
            margin-bottom: 0.3rem;
            animation: fadeIn 1s ease 0.5s both;
        }
        
        .hero-motto {
            text-align: center;
            font-size: 0.9rem;
            color: var(--primary-gold);
            font-style: italic;
            font-weight: 600;
            margin-bottom: 1.25rem;
            animation: fadeIn 1s ease 0.7s both;
        }
        
        .portal-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 1.25rem;
        }
        
        .portal-card {
            background: white;
            border-radius: 1.25rem;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 6px 20px rgba(0,0,0,0.08);
            transition: all 0.4s cubic-bezier(0.4,0,0.2,1);
            position: relative;
            overflow: hidden;
            animation: fadeInUp 0.8s ease both;
        }
        
        .portal-card:nth-child(1) { animation-delay: 0.2s; }
        .portal-card:nth-child(2) { animation-delay: 0.4s; }
        .portal-card:nth-child(3) { animation-delay: 0.6s; }
        
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        
        .portal-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-blue), var(--primary-gold));
            transform: scaleX(0);
            transition: transform 0.4s ease;
        }
        .portal-card:hover::before { transform: scaleX(1); }
        .portal-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 14px 28px rgba(0,0,0,0.12);
        }
        
        .portal-icon {
            width: 60px;
            height: 60px;
            margin: 0 auto 1rem;
            background: linear-gradient(135deg, var(--primary-blue) 0%, #3d4a8f 100%);
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 6px 12px rgba(74,91,168,0.3);
            transition: all 0.4s ease;
        }
        .portal-card:hover .portal-icon {
            transform: scale(1.1) rotate(5deg);
        }
        .portal-icon svg {
            width: 30px;
            height: 30px;
            stroke: white;
            stroke-width: 2;
        }
        
        .portal-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 0.5rem;
        }
        
        .portal-description {
            font-size: 0.875rem;
            color: var(--gray-600);
            line-height: 1.5;
            margin-bottom: 1rem;
        }
        
        .portal-link {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.6rem 1.25rem;
            background: linear-gradient(135deg, var(--primary-blue) 0%, #3d4a8f 100%);
            color: white;
            border-radius: 0.6rem;
            font-weight: 600;
            font-size: 0.875rem;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 3px 8px rgba(74,91,168,0.3);
        }
        .portal-link:hover {
            transform: translateX(4px);
            color: white;
        }
        .portal-link svg {
            width: 14px;
            height: 14px;
            stroke: currentColor;
            stroke-width: 2;
        }
        
        @media (max-width: 768px) {
            .hero-title   { font-size: 1.75rem; }
            .hero-subtitle { font-size: 0.9rem; }
            .portal-grid  { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="hero">
        <div class="hero-content">
            <div class="logo-container">
                <img src="public/tsu-logo.png" alt="Taraba State University Logo">
            </div>
            
            <h1 class="hero-title">Taraba State University</h1>
            <p class="hero-subtitle">Student ID Card Generation Portal</p>
            <p class="hero-motto">"Harnessing Nature's Gift"</p>
            
            <div class="portal-grid">
                <div class="portal-card">
                    <div class="portal-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                        </svg>
                    </div>
                    <h3 class="portal-title">New Registration</h3>
                    <p class="portal-description">Register as a new student and submit your details for ID card generation</p>
                    <a href="register.php" class="portal-link">
                        Get Started
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                    </a>
                </div>
                
                <div class="portal-card">
                    <div class="portal-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <h3 class="portal-title">Student Portal</h3>
                    <p class="portal-description">Check your ID card status and view your profile information</p>
                    <a href="student/login.php" class="portal-link">
                        Login
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                    </a>
                </div>
                
                <div class="portal-card">
                    <div class="portal-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    <h3 class="portal-title">Admin Portal</h3>
                    <p class="portal-description">Manage student applications and generate ID cards</p>
                    <a href="admin/login.php" class="portal-link">
                        Access
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
