<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_secure' => isset($_SERVER['HTTPS']),
        'cookie_httponly' => true,
        'use_strict_mode' => true,
        'cookie_samesite' => 'Strict'
    ]);
}

// Define BASE_URL if not defined
if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    define('BASE_URL', $protocol . '://' . $host . '/Peer-Cart');
}

// Include dependencies
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

// ============ FIXED LOGOUT HANDLING ============
// Handle logout FIRST, before any session variables are used
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    // Store logout message before destroying session
    $logout_message = "You have been logged out successfully.";
    
    // Use the secureLogout function from functions.php
    secureLogout();
    
    // Redirect with message
    header("Location: " . BASE_URL . "/?message=" . urlencode($logout_message));
    exit;
}

// Page title
$title = $title ?? 'PeerCart - C2C Ecommerce';

// Fetch categories and cities
$categories = getCategories(6);
$cities = getCities(6);

// User info - check if user is logged in
$isLoggedIn = false;
$userType = 'buyer';
$userName = 'User';
$userAvatar = 'default_avatar.png';

// Only check session if user_id exists (meaning they're logged in)
if (isset($_SESSION['user_id'])) {
    $isLoggedIn = true;
    $userType = $_SESSION['user_type'] ?? 'buyer';
    $userName = htmlspecialchars($_SESSION['user_name'] ?? 'User');
    $userAvatar = $_SESSION['user_avatar'] ?? 'default_avatar.png';
}

// Current page for menu highlighting
$currentPage = $currentPage ?? '';

// Additional stylesheets and scripts from child pages
$additionalStyles = $additionalStyles ?? [];
$additionalScripts = $additionalScripts ?? [];

// Cart count for all pages
$cartCount = getCartTotalQuantity();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    
    <!-- Meta tags -->
    <meta name="description" content="PeerCart - Peer-to-peer ecommerce marketplace">
    <meta name="keywords" content="ecommerce, marketplace, buy, sell, peer-to-peer">
    <meta name="author" content="PeerCart">
    
    <!-- Preconnect for external resources -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Favicon -->
    <link rel="icon" href="<?= BASE_URL ?>/assets/images/favicon.ico" type="image/x-icon">
    <link rel="apple-touch-icon" href="<?= BASE_URL ?>/assets/images/apple-touch-icon.png">
    
    <!-- CSS Reset -->

    <!-- Add this before closing body tag in your layout -->
<div class="floating-support-btn" onclick="openSupportModal('help-center')">
    <i class="fas fa-headset"></i>
    <span class="support-tooltip">Need Help?</span>
</div>

<style>
    .floating-support-btn {
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.5rem;
        box-shadow: 0 8px 25px rgba(67, 97, 238, 0.4);
        cursor: pointer;
        z-index: 1000;
        transition: all var(--transition-normal);
        animation: floatPulse 3s infinite ease-in-out;
    }
    
    .floating-support-btn:hover {
        transform: scale(1.1) rotate(5deg);
        box-shadow: 0 12px 35px rgba(67, 97, 238, 0.6);
    }
    
    .floating-support-btn:hover .support-tooltip {
        opacity: 1;
        transform: translateX(-50%) translateY(0);
    }
    
    .support-tooltip {
        position: absolute;
        top: -45px;
        left: 50%;
        transform: translateX(-50%) translateY(10px);
        background: var(--dark);
        color: white;
        padding: 0.5rem 1rem;
        border-radius: var(--radius-md);
        font-size: 0.85rem;
        font-weight: 600;
        white-space: nowrap;
        opacity: 0;
        transition: all var(--transition-normal);
        pointer-events: none;
    }
    
    .support-tooltip::after {
        content: '';
        position: absolute;
        top: 100%;
        left: 50%;
        transform: translateX(-50%);
        border: 6px solid transparent;
        border-top-color: var(--dark);
    }
    
    @keyframes floatPulse {
        0%, 100% {
            transform: translateY(0);
            box-shadow: 0 8px 25px rgba(67, 97, 238, 0.4);
        }
        50% {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(67, 97, 238, 0.5);
        }
    }
    
    @media (max-width: 768px) {
        .floating-support-btn {
            bottom: 20px;
            right: 20px;
            width: 50px;
            height: 50px;
            font-size: 1.25rem;
        }
        
        .support-tooltip {
            display: none;
        }
    }
</style>

    <style>
        /* Reset default margins and paddings */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        /* Ensure html and body take full height */
        html, body {
            height: 100%;
            font-family: 'Poppins', sans-serif;
            overflow-x: hidden;
        }
        
        /* Remove list styling */
        ul, ol {
            list-style: none;
        }
        
        /* Remove default link styling */
        a {
            text-decoration: none;
            color: inherit;
        }
        
        /* Remove blue highlight on mobile */
        * {
            -webkit-tap-highlight-color: transparent;
        }
    </style>
    
    <!-- ADDITIONAL STYLES from child pages -->
    <?php foreach ($additionalStyles as $style): ?>
        <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/<?= htmlspecialchars($style) ?>.css?v=<?= time() ?>">
    <?php endforeach; ?>
    
    <!-- Page-specific meta/head content -->
    <?= $pageHead ?? '' ?>
    
    <!-- Theme color for browsers -->
    <meta name="theme-color" content="#4361ee">
    
    <!-- Navbar Styles -->
    <style>
        /* ============================================
           PEERCART NAVBAR - OPTIMIZED VERSION
           All CSS consolidated and optimized
           ============================================ */
        
        :root {
            /* Modern Color Palette */
            --primary: #4361ee;
            --primary-light: #4895ef;
            --primary-dark: #3a0ca3;
            --secondary: #7209b7;
            --accent: #f72585;
            --success: #4cc9f0;
            --warning: #f8961e;
            --danger: #e63946;
            
            /* Neutrals */
            --dark: #1a1a2e;
            --dark-gray: #2d3047;
            --gray: #6c757d;
            --light-gray: #f8f9fa;
            --white: #ffffff;
            --bg-primary: #ffffff;
            --bg-secondary: #f8f9fa;
            --text-primary: #1a1a2e;
            --text-secondary: #6c757d;
            --border-color: rgba(0, 0, 0, 0.1);
            
            /* Glassmorphism */
            --glass-bg: rgba(255, 255, 255, 0.95);
            --glass-border: rgba(255, 255, 255, 0.2);
            --glass-shadow: rgba(0, 0, 0, 0.1);
            
            /* Effects */
            --shadow-sm: 0 2px 8px rgba(0,0,0,0.05);
            --shadow-md: 0 8px 25px rgba(0,0,0,0.1);
            --shadow-lg: 0 15px 35px rgba(0,0,0,0.15);
            --shadow-xl: 0 25px 50px rgba(0,0,0,0.2);
            --glow-primary: 0 0 20px rgba(67, 97, 238, 0.3);
            
            /* Spacing */
            --space-xs: 0.5rem;
            --space-sm: 1rem;
            --space-md: 1.5rem;
            --space-lg: 2rem;
            --space-xl: 3rem;
            
            /* Border Radius */
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --radius-xl: 24px;
            --radius-full: 100px;
            
            /* Transitions */
            --transition-fast: 0.2s ease;
            --transition-normal: 0.3s ease;
            --transition-slow: 0.5s ease;
        }
        
        /* Dark Mode Variables */
        [data-theme="dark"] {
            --primary: #5a76ff;
            --primary-light: #6d8aff;
            --primary-dark: #4a5fcc;
            --secondary: #8d2bd4;
            --accent: #ff2b8c;
            --success: #5cd3f7;
            --warning: #ffaa47;
            --danger: #ff4d5c;
            
            --dark: #ffffff;
            --dark-gray: #e0e0e0;
            --gray: #a0a0a0;
            --light-gray: #2a2a3e;
            --white: #1a1a2e;
            --bg-primary: #121225;
            --bg-secondary: #1a1a2e;
            --text-primary: #ffffff;
            --text-secondary: #b0b0c0;
            --border-color: rgba(255, 255, 255, 0.1);
            
            --glass-bg: rgba(26, 26, 46, 0.95);
            --glass-border: rgba(255, 255, 255, 0.1);
            --glass-shadow: rgba(0, 0, 0, 0.3);
            
            --shadow-sm: 0 2px 8px rgba(0,0,0,0.2);
            --shadow-md: 0 8px 25px rgba(0,0,0,0.3);
            --shadow-lg: 0 15px 35px rgba(0,0,0,0.4);
            --shadow-xl: 0 25px 50px rgba(0,0,0,0.5);
            --glow-primary: 0 0 20px rgba(90, 118, 255, 0.4);
        }
        
        /* ============ BASE NAVBAR ============ */
        .navbar {
            position: sticky;
            top: 0;
            z-index: 1000;
            height: 80px;
            width: 100%;
            background: var(--glass-bg);
            backdrop-filter: blur(25px) saturate(200%);
            -webkit-backdrop-filter: blur(25px) saturate(200%);
            border-bottom: 1px solid var(--glass-border);
            box-shadow: var(--shadow-lg);
            transition: all var(--transition-normal);
        }
        
        /* Animated gradient border */
        .navbar::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, 
                var(--primary) 0%, 
                var(--secondary) 25%, 
                var(--accent) 50%, 
                var(--success) 75%, 
                var(--primary) 100%);
            background-size: 200% 100%;
            animation: gradientMove 3s ease infinite;
            z-index: 1001;
        }
        
        @keyframes gradientMove {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .navbar .container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 100%;
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 var(--space-lg);
            gap: var(--space-lg);
        }
        
        /* ============ LOGO ============ */
        .logo {
            flex-shrink: 0;
            position: relative;
        }
        
        .logo a {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            font-size: 2rem;
            font-weight: 900;
            color: transparent;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            letter-spacing: -0.5px;
            padding: var(--space-xs) 0;
            position: relative;
            background: linear-gradient(135deg, var(--primary), var(--accent), var(--secondary));
            -webkit-background-clip: text;
            background-clip: text;
            text-shadow: 0 2px 10px rgba(67, 97, 238, 0.3);
            transition: all var(--transition-normal);
        }
        
        .logo a:hover {
            transform: translateY(-2px);
            text-shadow: 0 4px 20px rgba(67, 97, 238, 0.4);
        }
        
        .logo span {
            position: relative;
            display: inline-block;
        }
        
        .logo span::after {
            content: '';
            position: absolute;
            bottom: -4px;
            left: 0;
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg, var(--primary), var(--accent));
            border-radius: var(--radius-full);
            transform: scaleX(0);
            transform-origin: right;
            transition: transform var(--transition-normal);
        }
        
        .logo a:hover span::after {
            transform: scaleX(1);
            transform-origin: left;
        }
        
        /* ============ MAIN NAVIGATION ============ */
        .main-nav {
            display: flex;
            align-items: center;
            position: relative;
        }
        
        .main-nav ul {
            display: flex;
            gap: var(--space-xs);
            list-style: none;
            margin: 0;
            padding: 0;
            align-items: center;
            position: relative;
        }
        
        .main-nav li {
            position: relative;
        }
        
        .main-nav a {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.875rem 1.5rem;
            font-weight: 700;
            font-size: 0.95rem;
            color: var(--text-primary);
            text-decoration: none;
            border-radius: var(--radius-full);
            transition: all var(--transition-normal);
            position: relative;
            overflow: hidden;
            white-space: nowrap;
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            box-shadow: var(--shadow-sm);
            backdrop-filter: blur(10px);
        }
        
        /* Floating effect */
        .main-nav a::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, 
                transparent, 
                rgba(255, 255, 255, 0.3), 
                transparent);
            transition: left 0.7s;
        }
        
        .main-nav a:hover::before {
            left: 100%;
        }
        
        .main-nav a:hover,
        .main-nav a.active {
            background: rgba(67, 97, 238, 0.15);
            color: var(--primary);
            border-color: rgba(67, 97, 238, 0.3);
            transform: translateY(-3px);
            box-shadow: var(--shadow-md), var(--glow-primary);
        }
        
        .main-nav a i:first-child {
            font-size: 1.1rem;
            width: 1.1rem;
            text-align: center;
            color: var(--primary);
            transition: all var(--transition-normal);
        }
        
        .main-nav a:hover i:first-child {
            transform: scale(1.2) rotate(5deg);
        }
        
        .main-nav .fa-chevron-down {
            font-size: 0.8rem;
            transition: transform var(--transition-normal) ease;
            margin-left: 0.25rem;
        }
        
        .main-nav .dropdown.active .fa-chevron-down,
        .main-nav .dropdown:hover .fa-chevron-down {
            transform: rotate(180deg);
        }
        
        /* Cart Count */
        .cart-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background: linear-gradient(135deg, var(--danger), #ff6b6b);
            color: white;
            border-radius: 50%;
            min-width: 22px;
            height: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 900;
            box-shadow: 0 4px 15px rgba(230, 57, 70, 0.5);
            border: 3px solid var(--white);
            z-index: 1;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        /* ============ THEME TOGGLE ============ */
        .theme-toggle {
            position: relative;
            width: 60px;
            height: 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: var(--radius-full);
            cursor: pointer;
            transition: all var(--transition-normal);
            display: flex;
            align-items: center;
            padding: 0 4px;
            margin: 0;
            overflow: hidden;
            box-shadow: var(--shadow-md);
        }
        
        .theme-toggle::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            opacity: 0;
            transition: opacity var(--transition-normal);
        }
        
        [data-theme="dark"] .theme-toggle::before {
            opacity: 1;
        }
        
        .theme-toggle::after {
            content: '';
            position: absolute;
            width: 22px;
            height: 22px;
            background: white;
            border-radius: 50%;
            transition: transform 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            transform: translateX(0);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            z-index: 2;
        }
        
        [data-theme="dark"] .theme-toggle::after {
            transform: translateX(30px);
            background: #ffd700;
        }
        
        .theme-toggle i {
            position: absolute;
            font-size: 0.85rem;
            transition: all var(--transition-normal);
            z-index: 3;
        }
        
        .theme-toggle .fa-moon {
            left: 10px;
            color: rgba(45, 47, 216, 0.537);
            opacity: 1;
            filter: drop-shadow(0 1px 2px rgba(0,0,0,0.2));
        }
        
        .theme-toggle .fa-sun {
            right: 10px;
            color: #ff9500;
            opacity: 0;
            filter: drop-shadow(0 1px 2px rgba(0,0,0,0.2));
        }
        
        [data-theme="dark"] .theme-toggle .fa-moon {
            opacity: 0;
        }
        
        [data-theme="dark"] .theme-toggle .fa-sun {
            opacity: 1;
        }
        
        .theme-toggle:hover {
            transform: scale(1.05);
            box-shadow: var(--shadow-lg), 0 0 25px rgba(102, 126, 234, 0.4);
        }
        
        /* ============ TOP ACCOUNT NAV ============ */
        .top-account-nav {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: var(--space-md);
            position: relative;
        }
        
        .account-welcome {
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--text-primary);
            padding: 0.75rem 1.5rem;
            background: var(--glass-bg);
            border-radius: var(--radius-full);
            border: 1px solid var(--glass-border);
            backdrop-filter: blur(10px);
            box-shadow: var(--shadow-sm);
            transition: all var(--transition-normal);
        }
        
        .account-welcome:hover {
            background: rgba(67, 97, 238, 0.1);
            border-color: rgba(67, 97, 238, 0.3);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        
        /* ============ DROPDOWNS ============ */
        .dropdown-content {
            position: absolute;
            top: calc(100% + 10px);
            left: 0;
            min-width: 260px;
            background: var(--glass-bg);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-xl);
            padding: var(--space-sm);
            opacity: 0;
            visibility: hidden;
            transform: translateY(-15px) scale(0.95);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1001;
            overflow: hidden;
        }
        
        .dropdown-content::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--accent));
        }
        
        .dropdown.active .dropdown-content {
            opacity: 1;
            visibility: visible;
            transform: translateY(0) scale(1);
        }
        
        .dropdown-content a {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.25rem;
            border-radius: var(--radius-md);
            font-size: 0.95rem;
            color: var(--text-primary);
            transition: all var(--transition-normal);
            background: transparent;
            margin-bottom: 0.5rem;
            position: relative;
            overflow: hidden;
        }
        
        .dropdown-content a::before {
            content: '';
            position: absolute;
            left: -100%;
            top: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(67, 97, 238, 0.1), transparent);
            transition: left 0.5s;
        }
        
        .dropdown-content a:hover::before {
            left: 100%;
        }
        
        .dropdown-content a:hover {
            background: rgba(67, 97, 238, 0.1);
            color: var(--primary);
            transform: translateX(8px);
            box-shadow: var(--shadow-sm);
        }
        
        .dropdown-content a i {
            width: 1.1rem;
            font-size: 1rem;
            color: var(--primary);
        }
        
        .dropdown-divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--border-color), transparent);
            margin: 1rem 0;
        }
        
        /* ============ MEGA MENU ============ */
        .mega-menu-collapsible {
            position: absolute;
            top: calc(100% + 10px);
            left: 50%;
            transform: translateX(-50%) translateY(-15px) scale(0.95);
            width: min(90vw, 650px);
            background: var(--glass-bg);
            backdrop-filter: blur(30px);
            -webkit-backdrop-filter: blur(30px);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-xl);
            padding: var(--space-lg);
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: var(--space-lg);
            opacity: 0;
            visibility: hidden;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1001;
            overflow: hidden;
        }
        
        .mega-menu-collapsible::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--secondary), var(--accent));
        }
        
        .dropdown.active .mega-menu-collapsible {
            opacity: 1;
            visibility: visible;
            transform: translateX(-50%) translateY(0) scale(1);
        }
        
        /* Mega Menu Sections */
        .mega-menu-section {
            display: flex;
            flex-direction: column;
            gap: var(--space-sm);
        }
        
        .mega-menu-section h4 {
            font-size: 1rem;
            color: var(--text-primary);
            margin-bottom: var(--space-xs);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .category-grid, .city-grid {
            display: grid;
            grid-template-columns: repeat(1, 1fr);
            gap: 0.75rem;
        }
        
        .category-link, .city-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            border-radius: var(--radius-md);
            background: rgba(67, 97, 238, 0.05);
            border: 1px solid var(--glass-border);
            transition: all var(--transition-normal);
            color: var(--text-primary);
            font-size: 0.9rem;
        }
        
        .category-link:hover, .city-link:hover {
            background: rgba(67, 97, 238, 0.15);
            transform: translateX(5px);
            border-color: rgba(67, 97, 238, 0.3);
        }
        
        .category-link i, .city-link i {
            color: var(--primary);
            font-size: 1rem;
            width: 1.25rem;
        }
        
        .category-count {
            margin-left: auto;
            background: var(--primary);
            color: white;
            font-size: 0.7rem;
            padding: 0.2rem 0.5rem;
            border-radius: var(--radius-full);
            font-weight: 600;
        }
        
        /* ============ MOBILE MENU BUTTON ============ */
        .mobile-menu-btn {
            display: none;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border: none;
            width: 48px;
            height: 48px;
            border-radius: var(--radius-md);
            cursor: pointer;
            color: white;
            font-size: 1.25rem;
            transition: all var(--transition-normal);
            position: relative;
            z-index: 1002;
            align-items: center;
            justify-content: center;
            box-shadow: var(--shadow-md);
        }
        
        .mobile-menu-btn:hover {
            transform: scale(1.05) rotate(90deg);
            box-shadow: var(--shadow-lg), 0 0 20px rgba(67, 97, 238, 0.4);
        }
        
        /* Hide mobile menu button when mobile menu is open */
        .mobile-menu.active ~ .navbar .mobile-menu-btn {
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transform: scale(0.8);
        }
        
        /* ============ MOBILE MENU HEADER CONTROLS ============ */
        .mobile-header-controls {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            position: relative;
            z-index: 3;
        }
        
        .mobile-header-theme-toggle {
            width: 45px;
            height: 25px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: var(--radius-full);
            cursor: pointer;
            transition: all var(--transition-normal);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 3px;
            margin: 0;
            overflow: hidden;
            box-shadow: var(--shadow-md);
            flex-shrink: 0;
            position: relative;
        }
        
        .mobile-header-theme-toggle::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            opacity: 0;
            transition: opacity var(--transition-normal);
        }
        
        [data-theme="dark"] .mobile-header-theme-toggle::before {
            opacity: 1;
        }
        
        .mobile-header-theme-toggle::after {
            content: '';
            position: absolute;
            width: 17px;
            height: 17px;
            background: white;
            border-radius: 50%;
            transition: transform 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            transform: translateX(-9px);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            z-index: 2;
            left: 4px;
        }
        
        [data-theme="dark"] .mobile-header-theme-toggle::after {
            transform: translateX(9px);
            background: #ffd700;
        }
        
        .mobile-header-theme-toggle i {
            position: absolute;
            font-size: 0.7rem;
            transition: all var(--transition-normal);
            z-index: 3;
            pointer-events: none;
        }
        
        .mobile-header-theme-toggle .fa-moon {
            left: 8px;
            color: rgba(255, 255, 255, 0.9);
            opacity: 1;
            filter: drop-shadow(0 1px 2px rgba(0,0,0,0.2));
        }
        
        .mobile-header-theme-toggle .fa-sun {
            right: 8px;
            color: #ffd700;
            opacity: 0;
            filter: drop-shadow(0 1px 2px rgba(0,0,0,0.2));
        }
        
        [data-theme="dark"] .mobile-header-theme-toggle .fa-moon {
            opacity: 0;
        }
        
        [data-theme="dark"] .mobile-header-theme-toggle .fa-sun {
            opacity: 1;
        }
        
        .mobile-header-theme-toggle:hover {
            transform: scale(1.05);
            box-shadow: var(--shadow-lg), 0 0 25px rgba(102, 126, 234, 0.4);
        }
        
        /* ============ MOBILE MENU ============ */
        .mobile-menu {
            position: fixed;
            top: 0;
            right: -100%;
            width: 100%;
            max-width: 350px;
            height: 100vh;
            background: var(--glass-bg);
            backdrop-filter: blur(30px);
            -webkit-backdrop-filter: blur(30px);
            z-index: 1001;
            transition: right 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            overflow-y: auto;
            box-shadow: var(--shadow-xl);
            border-left: 1px solid var(--glass-border);
        }
        
        .mobile-menu.active {
            right: 0;
        }
        
        .mobile-menu-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--glass-border);
            background: var(--glass-bg);
            position: sticky;
            top: 0;
            z-index: 2;
            gap: 1rem;
            min-height: 70px;
        }
        
        .mobile-menu-header .logo {
            flex: 1;
            min-width: 0;
        }
        
        .mobile-menu-header .logo a {
            font-size: 1.5rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .mobile-menu-close {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: white;
            font-size: 1rem;
            transition: all var(--transition-normal);
            box-shadow: var(--shadow-md);
            flex-shrink: 0;
        }
        
        .mobile-menu-close:hover {
            transform: rotate(90deg) scale(1.1);
            box-shadow: var(--shadow-lg);
        }
        
        .mobile-menu ul {
            padding: 1rem 1.5rem;
            padding-top: 0.5rem;
        }
        
        .mobile-menu li {
            margin-bottom: 0.5rem;
        }
        
        .mobile-menu a {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.25rem;
            border-radius: var(--radius-md);
            color: var(--text-primary);
            font-weight: 700;
            transition: all var(--transition-normal);
            background: rgba(67, 97, 238, 0.05);
            border: 1px solid transparent;
        }
        
        .mobile-menu a:hover,
        .mobile-menu a.active {
            background: rgba(67, 97, 238, 0.15);
            color: var(--primary);
            border-color: rgba(67, 97, 238, 0.3);
            transform: translateX(8px);
            box-shadow: var(--shadow-sm);
        }
        
        /* Mobile dropdowns */
        .mobile-dropdown-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.4s ease;
            padding-left: 2rem;
        }
        
        .mobile-dropdown.active .mobile-dropdown-content {
            max-height: 1000px;
        }
        
        .mobile-category-link, .mobile-city-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            color: var(--text-secondary);
            font-size: 0.9rem;
            border-left: 2px solid var(--border-color);
            margin-left: 0.5rem;
        }
        
        .mobile-category-link:hover, .mobile-city-link:hover {
            color: var(--primary);
            border-left-color: var(--primary);
        }
        
        .mobile-cart-count {
            margin-left: auto;
            background: var(--danger);
            color: white;
            font-size: 0.75rem;
            padding: 0.2rem 0.5rem;
            border-radius: var(--radius-full);
            font-weight: 600;
        }
        
        /* ============ RESPONSIVE DESIGN ============ */
        @media (max-width: 1200px) {
            .navbar .container {
                padding: 0 var(--space-md);
                gap: var(--space-md);
            }
            
            .main-nav a {
                padding: 0.75rem 1.25rem;
                font-size: 0.9rem;
            }
        }
        
        @media (max-width: 992px) {
            .navbar {
                height: 70px;
            }
            
            .logo a {
                font-size: 1.75rem;
            }
            
            .main-nav a {
                padding: 0.625rem 1rem;
            }
        }
        
        @media (max-width: 768px) {
            
            .main-nav {
                display: none;
            }
            
            .top-account-nav {
                display: none;
            }
            
            .mobile-menu-btn {
                display: flex;
            }
            
            .navbar .container {
                padding: 0 var(--space-sm);
                gap: var(--space-sm);
            }
            
            .logo a {
                font-size: 1.5rem;
            }
            
            .theme-toggle {
                width: 55px;
                height: 28px;
            }
            
            .theme-toggle::after {
                width: 20px;
                height: 20px;
            }
            
            [data-theme="dark"] .theme-toggle::after {
                transform: translateX(27px);
            }
            
            .mobile-theme-toggle-wrapper {
                display: flex;
                align-items: center;
                margin-right: 1rem;
            }
            
            /* Hide desktop theme toggle on mobile */
            .desktop-only {
                display: none;
            }
            
            .mobile-only {
                display: block;
            }
        }
        
        @media (max-width: 480px) {
            .mobile-menu {
                max-width: 70%;
            }
            
            .navbar .container {
                padding: 0 var(--space-xs);
            }
            
            .logo a {
                font-size: 1.25rem;
            }
            
            .navbar {
                height: 65px;
            }
            
            .mega-menu-collapsible {
                grid-template-columns: 1fr;
                width: 95vw;
            }
            
            .category-grid, .city-grid {
                grid-template-columns: 1fr;
            }
            
            .mobile-menu-header {
                padding: 0.75rem 1rem;
                min-height: 60px;
            }
            
            .mobile-menu-header .logo a {
                font-size: 1.25rem;
            }
            
            .mobile-header-controls {
                gap: 0.5rem;
            }
            
            .mobile-header-theme-toggle {
                width: 40px;
                height: 22px;
            }
            
            .mobile-header-theme-toggle::after {
                width: 15px;
                height: 15px;
                transform: translateX(-8px);
                left: 3px;
            }
            
            [data-theme="dark"] .mobile-header-theme-toggle::after {
                transform: translateX(8px);
            }
            
            .mobile-header-theme-toggle .fa-moon {
                left: 6px;
                font-size: 0.6rem;
            }
            
            .mobile-header-theme-toggle .fa-sun {
                right: 6px;
                font-size: 0.6rem;
            }
            
            .mobile-menu-close {
                width: 36px;
                height: 36px;
                font-size: 0.9rem;
            }
            
            .mobile-menu ul {
                padding: 1rem;
                padding-top: 0.5rem;
            }
        }
        
        /* ============ UTILITY CLASSES ============ */
        .desktop-only {
            display: block;
        }
        
        .mobile-only {
            display: none;
        }
        
        /* ============ ACCESSIBILITY ============ */
        @media (prefers-reduced-motion: reduce) {
            * {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }
        
        /* Focus styles for accessibility */
        a:focus-visible,
        button:focus-visible,
        .theme-toggle:focus-visible,
        .mobile-header-theme-toggle:focus-visible {
            outline: 3px solid var(--primary);
            outline-offset: 2px;
        }
        
        /* Screen reader only */
        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }
    </style>
</head>
<body>
    <!-- Skip to main content link for accessibility -->
    <a href="#main-content" class="sr-only">Skip to main content</a>
    
    <!-- Mobile Menu (placed before navbar for proper sibling selector) -->
    <div class="mobile-menu">
        <div class="mobile-menu-header">
            <div class="logo">
                <a href="<?= BASE_URL ?>/">Peer<span>Cart</span></a>
            </div>
            <div class="mobile-header-controls">
                <!-- Theme toggle in mobile menu header 
                <button class="mobile-header-theme-toggle" aria-label="Toggle dark/light mode">
                    <i class="fas fa-moon"></i>
                </button>-->
                <button class="mobile-menu-close" aria-label="Close menu">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        <ul>
            <li><a href="<?= BASE_URL ?>/" class="<?= $currentPage === 'home' ? 'active' : '' ?>"><i class="fas fa-home"></i> Home</a></li>
            
            <!-- Mobile Shop Dropdown -->
<li class="mobile-dropdown">
    <a href="javascript:void(0)" class="mobile-dropdown-toggle" aria-expanded="false">
        <i class="fas fa-shopping-bag"></i> Shop <i class="fas fa-chevron-down"></i>
    </a>
    <div class="mobile-dropdown-content">
        <?php if(!empty($categories)): ?>
        <div class="mobile-categories">
            <h5>Categories</h5>
            <?php foreach($categories as $category): ?>
            <a href="<?= BASE_URL ?>/pages/listings.php?category_id=<?= $category['id'] ?>" class="mobile-category-link">
                <?php if($category['icon']): ?>
                    <i class="<?= $category['icon'] ?>"></i>
                <?php endif; ?>
                <span><?= htmlspecialchars($category['name']) ?></span>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <?php if(!empty($cities)): ?>
        <div class="mobile-cities">
            <h5>Shop by City</h5>
            <?php foreach($cities as $city): ?>
            <a href="<?= BASE_URL ?>/pages/listings.php?city=<?= urlencode($city) ?>" class="mobile-city-link">
                <i class="fas fa-city"></i>
                <span><?= htmlspecialchars($city) ?></span>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</li>
            
            <li>
                <a href="<?= BASE_URL ?>/pages/cart.php">
                    <i class="fas fa-shopping-cart"></i> Cart
                    <?php if($cartCount > 0): ?>
                        <span class="mobile-cart-count"><?= $cartCount ?></span>
                    <?php endif; ?>
                </a>
            </li>

            <?php if($isLoggedIn): ?>
                <li class="mobile-dropdown">
                    <a href="javascript:void(0)" class="mobile-dropdown-toggle" aria-expanded="false">
                        <i class="fas fa-user"></i> Account <i class="fas fa-chevron-down"></i>
                    </a>
                    <div class="mobile-dropdown-content">
                        <a href="<?= BASE_URL ?>/pages/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                        <a href="<?= BASE_URL ?>/pages/orders.php"><i class="fas fa-shopping-bag"></i> My Orders</a>
                        <a href="<?= BASE_URL ?>/pages/sell.php"><i class="fas fa-plus-circle"></i> Sell Item</a>
                        <a href="<?= BASE_URL ?>/pages/settings.php"><i class="fas fa-user-cog"></i> Settings</a>
                        <?php if(isSeller()): ?>
                        <a href="<?= BASE_URL ?>/pages/manage-listings.php"><i class="fas fa-store"></i> My Listings</a>
                        <?php endif; ?>
                        <div class="mobile-dropdown-divider"></div>
                        <a href="<?= BASE_URL ?>/pages/support.php"><i class="fas fa-question-circle"></i> Help Center</a>
                        <a href="<?= BASE_URL ?>/controllers/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </li>
            <?php else: ?>
                <li><a href="<?= BASE_URL ?>/pages/auth.php?mode=login"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                <li><a href="<?= BASE_URL ?>/pages/auth.php?mode=register"><i class="fas fa-user-plus"></i> Register</a></li>
                <li><a href="<?= BASE_URL ?>/pages/support.php"><i class="fas fa-question-circle"></i> Help Center</a></li>
            <?php endif; ?>
        
        </ul>
    </div>

    <!-- Navbar -->
    <header class="navbar">
        <div class="container">
            <!-- Logo -->
            <div class="logo">
                <a href="<?= BASE_URL ?>/">Peer<span>Cart</span></a>
            </div>

            <!-- Top Account Navigation -->
            <?php if($isLoggedIn): ?>
            <div class="top-account-nav">
                <div class="account-welcome">
                    <span>Welcome, <?= $userName ?></span>
                </div>
            </div>
            <?php endif; ?>

            <!-- Main Navigation -->
            <nav class="main-nav" aria-label="Main Navigation">
                <ul>
                    <li><a href="<?= BASE_URL ?>/" class="<?= $currentPage === 'home' ? 'active' : '' ?>"><i class="fas fa-home"></i> Home</a></li>
<!-- Shop Mega Menu -->
<li class="dropdown">
    <a href="#" aria-haspopup="true" aria-expanded="false"><i class="fas fa-shopping-bag"></i> Shop <i class="fas fa-chevron-down"></i></a>
    <div class="dropdown-content mega-menu-collapsible">
        <?php if(!empty($categories)): ?>
        <div class="mega-menu-section">
            <h4><i class="fas fa-tags"></i> Categories</h4>
            <div class="category-grid">
                <?php foreach($categories as $category): ?>
                <a href="<?= BASE_URL ?>/pages/listings.php?category_id=<?= $category['id'] ?>" class="category-link">
                    <?php if($category['icon']): ?>
                        <i class="<?= $category['icon'] ?>"></i>
                    <?php endif; ?>
                    <span><?= htmlspecialchars($category['name']) ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if(!empty($cities)): ?>
        <div class="mega-menu-section">
            <h4><i class="fas fa-map-marker-alt"></i> Shop by City</h4>
            <div class="city-grid">
                <?php foreach($cities as $city): ?>
                <a href="<?= BASE_URL ?>/pages/listings.php?city=<?= urlencode($city) ?>" class="city-link">
                    <i class="fas fa-city"></i>
                    <span><?= htmlspecialchars($city) ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</li>

                    <!-- Cart -->
                    <li>
                        <a href="<?= BASE_URL ?>/pages/cart.php">
                            <i class="fas fa-shopping-cart"></i> Cart
                            
                        </a>
                        <?php if($cartCount > 0): ?>
                                <span class="cart-count" aria-label="<?= $cartCount ?> items in cart"><?= $cartCount ?></span>
                            <?php endif; ?>
                    </li>

                    <!-- Account Dropdown -->
                    <?php if($isLoggedIn): ?>
                        <li class="dropdown">
                            <a href="#" aria-haspopup="true" aria-expanded="false"><i class="fas fa-user"></i> Account <i class="fas fa-chevron-down"></i></a>
                            <div class="dropdown-content">
                                <a href="<?= BASE_URL ?>/pages/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                                <a href="<?= BASE_URL ?>/pages/orders.php"><i class="fas fa-shopping-bag"></i> My Orders</a>
                                <a href="<?= BASE_URL ?>/pages/sell.php"><i class="fas fa-plus-circle"></i> Sell Item</a>
                                <a href="<?= BASE_URL ?>/pages/settings.php"><i class="fas fa-user-cog"></i> Settings</a>
                                <?php if(isSeller()): ?>
                                <a href="<?= BASE_URL ?>/pages/manage-listings.php"><i class="fas fa-store"></i> My Listings</a>
                                <?php endif; ?>
                                <div class="dropdown-divider"></div>
                                <a href="<?= BASE_URL ?>/pages/support.php"><i class="fas fa-question-circle"></i> Help Center</a>
                                <a href="<?= BASE_URL ?>/controllers/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                            </div>
                        </li>
                    <?php else: ?>
                        <li class="dropdown">
                            <a href="#" aria-haspopup="true" aria-expanded="false"><i class="fas fa-user"></i> Account <i class="fas fa-chevron-down"></i></a>
                            <div class="dropdown-content">
                                <a href="<?= BASE_URL ?>/pages/auth.php?mode=login"><i class="fas fa-sign-in-alt"></i> Login</a>
                                <a href="<?= BASE_URL ?>/pages/auth.php?mode=register"><i class="fas fa-user-plus"></i> Register</a>
                                <div class="dropdown-divider"></div>
                                <a href="<?= BASE_URL ?>/pages/support.php"><i class="fas fa-question-circle"></i> Help Center</a>
                            </div>
                        </li>
                    <?php endif; ?>

                    <!-- Desktop Theme Toggle 
                    <li class="theme-toggle-item desktop-only">
                        <button class="theme-toggle" aria-label="Toggle dark/light mode">
                            <i class="fas fa-moon"></i>
                        </button>-->
                    </li>
                </ul>
            </nav>

            <!-- Mobile Theme Toggle 
            <div class="mobile-theme-toggle-wrapper mobile-only">
                <button class="theme-toggle" aria-label="Toggle dark/light mode">
                    <i class="fas fa-moon"></i>
                </button>
            </div>-->

            <!-- Mobile Menu Button -->
            <button class="mobile-menu-btn" aria-label="Toggle mobile menu">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </header>

    <!-- Main content wrapper -->
    <main id="main-content">
    
    <!-- JavaScript -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize theme from localStorage
        initializeTheme();
        
        // Mobile Menu Toggle
        const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
        const mobileMenu = document.querySelector('.mobile-menu');
        const mobileMenuClose = document.querySelector('.mobile-menu-close');
        
        // Get all theme toggles (desktop, mobile navbar, and mobile menu header)
        const themeToggles = document.querySelectorAll('.theme-toggle, .mobile-header-theme-toggle');
        
        if (mobileMenuBtn && mobileMenu) {
            mobileMenuBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                mobileMenu.classList.add('active');
                document.body.style.overflow = 'hidden';
                updateAriaAttributes(true);
            });
            
            if (mobileMenuClose) {
                mobileMenuClose.addEventListener('click', (e) => {
                    e.stopPropagation();
                    closeMobileMenu();
                });
            }
            
            // Mobile dropdown toggle
            document.querySelectorAll('.mobile-dropdown-toggle').forEach(toggle => {
                toggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const parent = this.parentElement;
                    const isExpanded = parent.classList.contains('active');
                    parent.classList.toggle('active');
                    this.setAttribute('aria-expanded', !isExpanded);
                });
            });
            
            // Close mobile menu when clicking on a link (except dropdown toggles)
            mobileMenu.querySelectorAll('a[href]').forEach(link => {
                link.addEventListener('click', (e) => {
                    if (!link.classList.contains('mobile-dropdown-toggle')) {
                        closeMobileMenu();
                    }
                });
            });
        }
        
        // Close mobile menu when clicking outside
        document.addEventListener('click', function(e) {
            if (mobileMenu && mobileMenu.classList.contains('active')) {
                if (!mobileMenu.contains(e.target) && !mobileMenuBtn.contains(e.target)) {
                    closeMobileMenu();
                }
            }
        });
        
        // Close mobile menu on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && mobileMenu && mobileMenu.classList.contains('active')) {
                closeMobileMenu();
            }
        });
        
        // Desktop dropdown interactions
        const desktopDropdowns = document.querySelectorAll('.dropdown');
        
        desktopDropdowns.forEach(dropdown => {
            const toggle = dropdown.querySelector('a[aria-haspopup="true"]');
            
            dropdown.addEventListener('mouseenter', function() {
                this.classList.add('active');
                if (toggle) toggle.setAttribute('aria-expanded', 'true');
            });
            
            dropdown.addEventListener('mouseleave', function() {
                this.classList.remove('active');
                if (toggle) toggle.setAttribute('aria-expanded', 'false');
            });
            
            // Keyboard navigation for dropdowns
            if (toggle) {
                toggle.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        const isExpanded = dropdown.classList.contains('active');
                        dropdown.classList.toggle('active');
                        this.setAttribute('aria-expanded', !isExpanded);
                    }
                });
            }
        });
        
        // Close desktop dropdowns when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.dropdown')) {
                desktopDropdowns.forEach(dropdown => {
                    dropdown.classList.remove('active');
                    const toggle = dropdown.querySelector('a[aria-haspopup="true"]');
                    if (toggle) toggle.setAttribute('aria-expanded', 'false');
                });
            }
        });
        
        // Theme Toggle functionality for all theme toggle buttons
        themeToggles.forEach(toggle => {
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                toggleTheme();
            });
            
            // Keyboard support for theme toggle
            toggle.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    toggleTheme();
                }
            });
        });
        
        // Functions
        function closeMobileMenu() {
            if (mobileMenu) {
                mobileMenu.classList.remove('active');
                document.body.style.overflow = '';
                updateAriaAttributes(false);
                
                // Close all mobile dropdowns
                document.querySelectorAll('.mobile-dropdown').forEach(dropdown => {
                    dropdown.classList.remove('active');
                    const toggle = dropdown.querySelector('.mobile-dropdown-toggle');
                    if (toggle) toggle.setAttribute('aria-expanded', 'false');
                });
            }
        }
        
        function updateAriaAttributes(isOpen) {
            if (mobileMenuBtn) {
                mobileMenuBtn.setAttribute('aria-expanded', isOpen);
                mobileMenuBtn.setAttribute('aria-label', isOpen ? 'Close mobile menu' : 'Open mobile menu');
            }
        }
        
        function initializeTheme() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            const html = document.documentElement;
            
            html.setAttribute('data-theme', savedTheme);
            updateThemeIcons(savedTheme);
        }
        
        function toggleTheme() {
            const html = document.documentElement;
            const currentTheme = html.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateThemeIcons(newTheme);
        }
        
        function updateThemeIcons(theme) {
            const iconClass = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
            // Update all theme toggle icons
            document.querySelectorAll('.theme-toggle i, .mobile-header-theme-toggle i').forEach(icon => {
                icon.className = iconClass;
            });
        }
        
        // Handle reduced motion preference
        const mediaQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
        if (mediaQuery.matches) {
            document.documentElement.style.setProperty('--transition-normal', '0.01ms');
            document.documentElement.style.setProperty('--transition-fast', '0.01ms');
            document.documentElement.style.setProperty('--transition-slow', '0.01ms');
        }
        
        // Add loading class to body for CSS transitions
        setTimeout(() => {
            document.body.classList.add('loaded');
        }, 100);
    });
    
// Support Modal Functions - Updated Version
function openSupportModal(tab = 'help-center') {
    const modal = document.getElementById('support-modal-container');
    
    if (!modal) {
        // Create modal container if it doesn't exist
        const modalContainer = document.createElement('div');
        modalContainer.id = 'support-modal-container';
        modalContainer.className = 'support-modal-container';
        document.body.appendChild(modalContainer);
        
        // Fetch support content via AJAX
        fetch(`<?= BASE_URL ?>/pages/support.php?modal=true&tab=${tab}`)
            .then(response => response.text())
            .then(html => {
                modalContainer.innerHTML = html;
                modalContainer.classList.add('active');
                document.body.style.overflow = 'hidden';
                
                // Execute any scripts in the loaded content
                const scripts = modalContainer.querySelectorAll('script');
                scripts.forEach(script => {
                    // Create new script element
                    const newScript = document.createElement('script');
                    
                    // Copy attributes
                    Array.from(script.attributes).forEach(attr => {
                        newScript.setAttribute(attr.name, attr.value);
                    });
                    
                    // Copy content
                    newScript.textContent = script.textContent;
                    
                    // Replace old script with new one (this will execute it)
                    script.parentNode.replaceChild(newScript, script);
                });
                
                // Initialize the support system
                setTimeout(() => {
                    // Call switchSupportTab to initialize the correct tab
                    if (typeof window.switchSupportTab === 'function') {
                        window.switchSupportTab(tab);
                    }
                }, 100);
            })
            .catch(error => {
                console.error('Error loading support modal:', error);
                modalContainer.innerHTML = `
                    <div class="error-message" style="color: var(--text-primary); padding: 2rem; text-align: center;">
                        <h3>Oops!</h3>
                        <p>Failed to load support content. Please try again.</p>
                        <button onclick="closeSupportModal()" style="margin-top: 1rem; padding: 0.5rem 1rem; background: var(--primary); color: white; border: none; border-radius: var(--radius-sm); cursor: pointer;">
                            Close
                        </button>
                    </div>
                `;
            });
    } else {
        // Modal exists, switch to requested tab
        if (typeof window.switchSupportTab === 'function') {
            window.switchSupportTab(tab);
        }
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeSupportModal() {
    const modal = document.getElementById('support-modal-container');
    if (modal) {
        modal.classList.remove('active');
        setTimeout(() => {
            document.body.style.overflow = '';
            // Don't remove the modal entirely, just hide it
            // This preserves the initialized state
        }, 300);
    }
}

// Make functions globally available
window.openSupportModal = openSupportModal;
window.closeSupportModal = closeSupportModal;

// Close modal when clicking outside
document.addEventListener('click', function(e) {
    const modal = document.getElementById('support-modal-container');
    if (modal && modal.classList.contains('active') && e.target === modal) {
        closeSupportModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeSupportModal();
    }
});

// Alternative: Pre-load the support modal content on page load for faster opening
document.addEventListener('DOMContentLoaded', function() {
    // Pre-load support modal in background
    setTimeout(() => {
        fetch(`<?= BASE_URL ?>/pages/support.php?modal=true&tab=help-center`)
            .then(response => response.text())
            .then(html => {
                // Create and cache the modal container
                let modal = document.getElementById('support-modal-container');
                if (!modal) {
                    modal = document.createElement('div');
                    modal.id = 'support-modal-container';
                    modal.className = 'support-modal-container';
                    modal.innerHTML = html;
                    document.body.appendChild(modal);
                    
                    // Execute scripts in pre-loaded content
                    const scripts = modal.querySelectorAll('script');
                    scripts.forEach(script => {
                        const newScript = document.createElement('script');
                        Array.from(script.attributes).forEach(attr => {
                            newScript.setAttribute(attr.name, attr.value);
                        });
                        newScript.textContent = script.textContent;
                        script.parentNode.replaceChild(newScript, script);
                    });
                }
            })
            .catch(error => console.error('Pre-load failed:', error));
    }, 2000); // Delay to not interfere with page loading
});
</script>

    
</body>
</html>