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
require_once __DIR__ . '/welcome_message.php';

// ============ FIXED LOGOUT HANDLING ============
// Handle logout FIRST, before any session variables are used
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    // Store logout message in session so it shows only once
    $_SESSION['logout_message'] = "You have been logged out successfully.";
    
    // Use the secureLogout function from functions.php
    secureLogout();
    
    // Redirect WITHOUT message parameter
    header("Location: " . BASE_URL . "/");
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
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
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
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
        
        /* Prevent horizontal scroll */
        img, svg, video, canvas, audio, iframe, embed, object {
            max-width: 100%;
            height: auto;
        }
    </style>
    
    <!-- Navbar CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/pages/navbar.css?v=<?= time() ?>">
    
    <!-- ADDITIONAL STYLES from child pages -->
    <?php foreach ($additionalStyles as $style): ?>
        <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/<?= htmlspecialchars($style) ?>.css?v=<?= time() ?>">
    <?php endforeach; ?>
    
    <!-- Page-specific meta/head content -->
    <?= $pageHead ?? '' ?>
    
    <!-- Theme color for browsers -->
    <meta name="theme-color" content="#4361ee">
</head>
<body>
    <!-- Skip to main content link for accessibility -->
    <a href="#main-content" class="sr-only">Skip to main content</a>
    
    <!-- Navbar Wrapper -->
    <div class="peercart-navbar">
        <!-- Mobile Menu (placed before navbar for proper sibling selector) -->
        <div class="mobile-menu">
            <div class="mobile-menu-header">
                <div class="logo">
                    <a href="<?= BASE_URL ?>/">Peer<span>Cart</span></a>
                </div>
                <div class="mobile-header-controls">
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
                            <h5 style="font-size: 0.9rem; color: var(--text-primary); margin: 0.75rem 0 0.5rem 0; padding-left: 1rem;">Categories</h5>
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
                            <h5 style="font-size: 0.9rem; color: var(--text-primary); margin: 0.75rem 0 0.5rem 0; padding-left: 1rem;">Shop by City</h5>
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
                            <div style="height: 1px; background: var(--border-color); margin: 0.75rem 0;"></div>
                            <a href="<?= BASE_URL ?>/pages/support.php"><i class="fas fa-question-circle"></i> Help Center</a>
                            <a href="<?= BASE_URL ?>/?action=logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
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

                <!-- Top Account Navigation (Desktop only) -->
                <?php if($isLoggedIn): ?>
                <div class="top-account-nav">
                    <div class="account-welcome">
                        <span>Welcome, <?= $userName ?></span>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Main Navigation (Desktop only) -->
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
                                <?php if($cartCount > 0): ?>
                                    <span class="cart-count" aria-label="<?= $cartCount ?> items in cart"><?= $cartCount ?></span>
                                <?php endif; ?>
                            </a>
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
                                    <a href="<?= BASE_URL ?>/?action=logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
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
                    </ul>
                </nav>

                <!-- Mobile Menu Button (Hidden on Desktop) -->
                <button class="mobile-menu-btn" aria-label="Toggle mobile menu">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </header>
    </div> <!-- End of peercart-navbar wrapper -->

    <!-- Main content wrapper -->
    <main id="main-content">
    
    <!-- Display all messages from welcome_message.php -->
    <?= displayAllMessages() ?>
    
    <!-- Floating Support Button -->
    <div class="floating-support-btn" onclick="openSupportModal('help-center')">
        <i class="fas fa-headset"></i>
        <span class="support-tooltip">Need Help?</span>
    </div>

    <!-- JavaScript -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Mobile Menu Toggle
        const mobileMenuBtn = document.querySelector('.peercart-navbar .mobile-menu-btn');
        const mobileMenu = document.querySelector('.peercart-navbar .mobile-menu');
        const mobileMenuClose = document.querySelector('.peercart-navbar .mobile-menu-close');
        
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
            
            // Mobile dropdown toggle - improved touch handling
            document.querySelectorAll('.peercart-navbar .mobile-dropdown-toggle').forEach(toggle => {
                toggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const parent = this.parentElement;
                    const isExpanded = parent.classList.contains('active');
                    parent.classList.toggle('active');
                    this.setAttribute('aria-expanded', !isExpanded);
                });
                
                // Add touch event for better mobile experience
                toggle.addEventListener('touchend', function(e) {
                    e.preventDefault();
                    this.click();
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
        
        // Desktop dropdown interactions (only on desktop)
        if (window.innerWidth >= 992) {
            const desktopDropdowns = document.querySelectorAll('.peercart-navbar .dropdown');
            
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
                if (!e.target.closest('.peercart-navbar .dropdown')) {
                    desktopDropdowns.forEach(dropdown => {
                        dropdown.classList.remove('active');
                        const toggle = dropdown.querySelector('a[aria-haspopup="true"]');
                        if (toggle) toggle.setAttribute('aria-expanded', 'false');
                    });
                }
            });
        }
        
        // Functions
        function closeMobileMenu() {
            if (mobileMenu) {
                mobileMenu.classList.remove('active');
                document.body.style.overflow = '';
                updateAriaAttributes(false);
                
                // Close all mobile dropdowns
                document.querySelectorAll('.peercart-navbar .mobile-dropdown').forEach(dropdown => {
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
        
        // Fix for mobile viewport height
        function setVh() {
            let vh = window.innerHeight * 0.01;
            document.documentElement.style.setProperty('--vh', `${vh}px`);
        }
        
        setVh();
        window.addEventListener('resize', setVh);
        window.addEventListener('orientationchange', setVh);
    });
    
    // Support Modal Functions
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
                        const newScript = document.createElement('script');
                        Array.from(script.attributes).forEach(attr => {
                            newScript.setAttribute(attr.name, attr.value);
                        });
                        newScript.textContent = script.textContent;
                        script.parentNode.replaceChild(newScript, script);
                    });
                    
                    // Initialize the support system
                    setTimeout(() => {
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
    </script>