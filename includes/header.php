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
    define('BASE_URL', 'http://localhost/peercart');
}

// Include dependencies
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

// Handle logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_unset();
    session_destroy();
    header("Location: " . BASE_URL . "/");
    exit;
}

// Page title
$title = $title ?? 'PeerCart - C2C Ecommerce';

// Fetch categories and cities
$categories = getCategories(6);
$cities = getCities(6);

// User info
$isLoggedIn = isset($_SESSION['user_id']);
$userType = $_SESSION['user_type'] ?? 'buyer';
$userName = htmlspecialchars($_SESSION['user_name'] ?? 'User');
$userAvatar = $_SESSION['user_avatar'] ?? 'default_avatar.png';

// Current page for menu highlighting
$currentPage = $currentPage ?? '';

// Additional stylesheets and scripts from child pages
$additionalStyles = $additionalStyles ?? [];
$additionalScripts = $additionalScripts ?? [];

// Cart count for all pages
$cartCount = $isLoggedIn ? getCartCount($_SESSION['user_id']) : 0;
?>
<!DOCTYPE html>
<html lang="en">
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
    
    <!-- CORE STYLES (Always loaded) -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css?v=<?= time() ?>">
    
    <!-- ADDITIONAL STYLES from child pages -->
    <?php foreach ($additionalStyles as $style): ?>
        <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/<?= htmlspecialchars($style) ?>.css?v=<?= time() ?>">
    <?php endforeach; ?>
    
    <!-- Page-specific meta/head content -->
    <?= $pageHead ?? '' ?>
    
    <!-- Theme color for browsers -->
    <meta name="theme-color" content="#3b82f6">
</head>
<body>
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
            <nav class="main-nav">
                <ul>
                    <li><a href="<?= BASE_URL ?>/" class="<?= $currentPage === 'home' ? 'active' : '' ?>">Home</a></li>

                    <!-- Shop Mega Menu -->
                    <li class="dropdown">
                        <a href="#"><i class="fas fa-shopping-bag"></i> Shop <i class="fas fa-chevron-down"></i></a>
                        <div class="dropdown-content mega-menu-collapsible">
                            <!-- Column 1: Browse Sections -->
                            <div class="dropdown-section">
                                <h4 class="section-title">
                                    <i class="fas fa-compass"></i> Browse
                                </h4>
                                <div class="section-items">
                                    <!-- Categories - Collapsible -->
                                    <div class="collapsible-section">
                                        <a href="javascript:void(0)" class="collapsible-header">
                                            <i class="fas fa-tags"></i> Categories <i class="fas fa-chevron-right"></i>
                                        </a>
                                        <div class="collapsible-content">
                                            <?php foreach ($categories as $category): ?>
                                                <a href="<?= BASE_URL ?>/pages/listings.php?category=<?= $category['id'] ?>" class="sub-item">
                                                    <i class="fas <?= htmlspecialchars($category['icon'] ?? 'fa-tag') ?>"></i> 
                                                    <?= htmlspecialchars($category['name']) ?>
                                                </a>
                                            <?php endforeach; ?>
                                            <a href="<?= BASE_URL ?>/pages/categories.php" class="view-all sub-item">
                                                <i class="fas fa-arrow-right"></i> View All Categories
                                            </a>
                                        </div>
                                    </div>
                                    
                                    <!-- Locations - Collapsible -->
                                    <div class="collapsible-section">
                                        <a href="javascript:void(0)" class="collapsible-header">
                                            <i class="fas fa-map-marker-alt"></i> Locations <i class="fas fa-chevron-right"></i>
                                        </a>
                                        <div class="collapsible-content">
                                            <?php foreach ($cities as $city): ?>
                                                <a href="<?= BASE_URL ?>/pages/listings.php?city=<?= urlencode($city) ?>" class="sub-item">
                                                    <i class="fas fa-city"></i> <?= htmlspecialchars($city) ?>
                                                </a>
                                            <?php endforeach; ?>
                                            <a href="<?= BASE_URL ?>/pages/cities.php" class="view-all sub-item">
                                                <i class="fas fa-globe"></i> All Cities
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Column 2: Quick Links -->
                            <div class="dropdown-section">
                                <h4 class="section-title">
                                    <i class="fas fa-bolt"></i> Quick Links
                                </h4>
                                <div class="section-items quick-links-grid">
                                    <a href="<?= BASE_URL ?>/pages/listings.php?discount=1" class="quick-link">
                                        <i class="fas fa-percentage"></i>
                                        <span>Discounted</span>
                                    </a>
                                    <a href="<?= BASE_URL ?>/pages/listings.php?featured=1" class="quick-link">
                                        <i class="fas fa-star"></i>
                                        <span>Featured</span>
                                    </a>
                                    <a href="<?= BASE_URL ?>/pages/listings.php?new=1" class="quick-link">
                                        <i class="fas fa-certificate"></i>
                                        <span>New</span>
                                    </a>
                                    <a href="<?= BASE_URL ?>/pages/listings.php?urgent=1" class="quick-link">
                                        <i class="fas fa-bolt"></i>
                                        <span>Urgent</span>
                                    </a>
                                    <a href="<?= BASE_URL ?>/pages/trending.php" class="quick-link">
                                        <i class="fas fa-chart-line"></i>
                                        <span>Trending</span>
                                    </a>
                                    <a href="<?= BASE_URL ?>/pages/clearance.php" class="quick-link">
                                        <i class="fas fa-fire"></i>
                                        <span>Clearance</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </li>

                    <!-- Cart -->
                    <li>
                        <a href="<?= BASE_URL ?>/pages/cart.php">
                            <i class="fas fa-shopping-cart"></i> Cart
                            <?php if($cartCount > 0): ?>
                                <span class="cart-count"><?= $cartCount ?></span>
                            <?php endif; ?>
                        </a>
                    </li>

                    <!-- Account Dropdown -->
                    <?php if($isLoggedIn): ?>
                        <li class="dropdown">
                            <a href="#"><i class="fas fa-user"></i> Account <i class="fas fa-chevron-down"></i></a>
                            <div class="dropdown-content">
                                <?php if($userType === 'seller'): ?>
                                    <a href="<?= BASE_URL ?>/pages/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                                    <a href="<?= BASE_URL ?>/pages/sell.php"><i class="fas fa-plus-circle"></i> Sell</a>
                                    <a href="<?= BASE_URL ?>/pages/messages.php"><i class="fas fa-envelope"></i> Messages</a>
                                    <a href="<?= BASE_URL ?>/pages/analytics.php"><i class="fas fa-chart-bar"></i> Analytics</a>
                                <?php else: ?>
                                    <a href="<?= BASE_URL ?>/pages/orders.php"><i class="fas fa-shopping-bag"></i> My Orders</a>
                                    <a href="<?= BASE_URL ?>/pages/wishlist.php"><i class="fas fa-heart"></i> Wishlist</a>
                                    <a href="<?= BASE_URL ?>/pages/track-order.php"><i class="fas fa-truck"></i> Track Order</a>
                                <?php endif; ?>
                                <div class="dropdown-divider"></div>
                                <a href="<?= BASE_URL ?>/pages/profile.php"><i class="fas fa-user-circle"></i> Profile</a>
                                <a href="<?= BASE_URL ?>/pages/settings.php"><i class="fas fa-cog"></i> Settings</a>
                                <a href="<?= BASE_URL ?>/pages/notifications.php"><i class="fas fa-bell"></i> Notifications</a>
                                <div class="dropdown-divider"></div>
                                <a href="<?= BASE_URL ?>/?action=logout" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
                            </div>
                        </li>
                    <?php else: ?>
                        <li class="dropdown">
                            <a href="#"><i class="fas fa-user"></i> Account <i class="fas fa-chevron-down"></i></a>
                            <div class="dropdown-content">
                                <a href="<?= BASE_URL ?>/includes/auth.php?action=login"><i class="fas fa-sign-in-alt"></i> Login</a>
                                <a href="<?= BASE_URL ?>/includes/auth.php?action=register"><i class="fas fa-user-plus"></i> Register</a>
                                <div class="dropdown-divider"></div>
                                <a href="<?= BASE_URL ?>/pages/help.php"><i class="fas fa-question-circle"></i> Help Center</a>
                            </div>
                        </li>
                    <?php endif; ?>

                    <!-- Theme Toggle -->
                    <li class="theme-toggle-item">
                        <button class="theme-toggle" aria-label="Toggle dark/light mode">
                            <i class="fas fa-moon"></i>
                        </button>
                    </li>
                </ul>
            </nav>

            <!-- Mobile Menu Button -->
            <div class="mobile-menu-btn" aria-label="Toggle mobile menu">
                <i class="fas fa-bars"></i>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div class="mobile-menu">
            <div class="mobile-menu-header">
                <div class="logo">
                    <a href="<?= BASE_URL ?>/">Peer<span>Cart</span></a>
                </div>
                <button class="mobile-menu-close" aria-label="Close menu">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <ul>
                <li><a href="<?= BASE_URL ?>/" class="<?= $currentPage === 'home' ? 'active' : '' ?>"><i class="fas fa-home"></i> Home</a></li>
                
                <!-- Mobile Shop Dropdown -->
                <li class="mobile-dropdown">
                    <a href="javascript:void(0)" class="mobile-dropdown-toggle">
                        <i class="fas fa-shopping-bag"></i> Shop <i class="fas fa-chevron-down"></i>
                    </a>
                    <div class="mobile-dropdown-content">
                        <h4>Categories</h4>
                        <?php foreach($categories as $category): ?>
                            <a href="<?= BASE_URL ?>/pages/listings.php?category=<?= $category['id'] ?>">
                                <i class="fas <?= $category['icon'] ?? 'fa-tag' ?>"></i> 
                                <?= htmlspecialchars($category['name']) ?>
                            </a>
                        <?php endforeach; ?>
                        <a href="<?= BASE_URL ?>/pages/categories.php" class="view-all">
                            <i class="fas fa-arrow-right"></i> All Categories
                        </a>
                        
                        <h4>Quick Links</h4>
                        <a href="<?= BASE_URL ?>/pages/listings.php?discount=1"><i class="fas fa-percentage"></i> Discounted Items</a>
                        <a href="<?= BASE_URL ?>/pages/listings.php?featured=1"><i class="fas fa-star"></i> Featured Products</a>
                        <a href="<?= BASE_URL ?>/pages/listings.php?new=1"><i class="fas fa-certificate"></i> New Arrivals</a>
                        <a href="<?= BASE_URL ?>/pages/listings.php?urgent=1"><i class="fas fa-bolt"></i> Urgent Sales</a>
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
                        <a href="javascript:void(0)" class="mobile-dropdown-toggle">
                            <i class="fas fa-user"></i> Account <i class="fas fa-chevron-down"></i>
                        </a>
                        <div class="mobile-dropdown-content">
                            <?php if($userType === 'seller'): ?>
                                <a href="<?= BASE_URL ?>/pages/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                                <a href="<?= BASE_URL ?>/pages/sell.php"><i class="fas fa-plus-circle"></i> Sell</a>
                                <a href="<?= BASE_URL ?>/pages/messages.php"><i class="fas fa-envelope"></i> Messages</a>
                                <a href="<?= BASE_URL ?>/pages/analytics.php"><i class="fas fa-chart-bar"></i> Analytics</a>
                            <?php else: ?>
                                <a href="<?= BASE_URL ?>/pages/orders.php"><i class="fas fa-shopping-bag"></i> My Orders</a>
                                <a href="<?= BASE_URL ?>/pages/wishlist.php"><i class="fas fa-heart"></i> Wishlist</a>
                                <a href="<?= BASE_URL ?>/pages/track-order.php"><i class="fas fa-truck"></i> Track Order</a>
                            <?php endif; ?>
                            <a href="<?= BASE_URL ?>/pages/profile.php"><i class="fas fa-user-circle"></i> Profile</a>
                            <a href="<?= BASE_URL ?>/pages/settings.php"><i class="fas fa-cog"></i> Settings</a>
                            <a href="<?= BASE_URL ?>/pages/notifications.php"><i class="fas fa-bell"></i> Notifications</a>
                            <div class="mobile-dropdown-divider"></div>
                            <a href="<?= BASE_URL ?>/?action=logout" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        </div>
                    </li>
                <?php else: ?>
                    <li><a href="<?= BASE_URL ?>/includes/auth.php?action=login"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                    <li><a href="<?= BASE_URL ?>/includes/auth.php?action=register"><i class="fas fa-user-plus"></i> Register</a></li>
                    <li><a href="<?= BASE_URL ?>/pages/help.php"><i class="fas fa-question-circle"></i> Help Center</a></li>
                <?php endif; ?>
                
                <!-- Mobile Theme Toggle -->
                <li class="mobile-theme-toggle">
                    <a href="javascript:void(0)" class="theme-toggle">
                        <i class="fas fa-moon"></i> Dark Mode
                        <span class="theme-toggle-switch"></span>
                    </a>
                </li>
            </ul>
        </div>
    </header>

    <!-- CORE SCRIPTS (Always loaded) -->
    <script src="<?= BASE_URL ?>/assets/js/main.js?v=<?= time() ?>"></script>
    <script src="<?= BASE_URL ?>/assets/js/navbar.js?v=<?= time() ?>"></script>
    <script src="<?= BASE_URL ?>/assets/js/theme-switcher.js?v=<?= time() ?>"></script>
    
    <!-- ADDITIONAL SCRIPTS from child pages -->
    <?php foreach ($additionalScripts as $script): ?>
        <script src="<?= BASE_URL ?>/assets/js/<?= htmlspecialchars($script) ?>.js?v=<?= time() ?>"></script>
    <?php endforeach; ?>

    <!-- Inline initialization script -->
    <script>
    // Initialize when DOM is fully loaded
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize collapsible sections in mega menu
        initCollapsibleSections();
        
        // Initialize mobile menu
        initMobileMenu();
        
        // Initialize theme toggle
        initThemeToggle();
        
        // Initialize dropdown menus
        initDropdowns();
    });

    function initCollapsibleSections() {
        document.querySelectorAll('.collapsible-header').forEach(header => {
            header.addEventListener('click', (e) => {
                e.preventDefault();
                const section = header.parentElement;
                const isActive = section.classList.contains('active');
                
                // Close all sections first
                document.querySelectorAll('.collapsible-section').forEach(s => {
                    s.classList.remove('active');
                });
                
                // Toggle current section
                if (!isActive) {
                    section.classList.add('active');
                }
            });
        });

        // Close all sections when mouse leaves mega menu
        const megaMenu = document.querySelector('.mega-menu-collapsible');
        if (megaMenu) {
            megaMenu.addEventListener('mouseleave', () => {
                document.querySelectorAll('.collapsible-section').forEach(section => {
                    section.classList.remove('active');
                });
            });
        }
    }

    function initMobileMenu() {
        const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
        const mobileMenu = document.querySelector('.mobile-menu');
        const mobileMenuClose = document.querySelector('.mobile-menu-close');
        
        if (mobileMenuBtn && mobileMenu) {
            mobileMenuBtn.addEventListener('click', () => {
                mobileMenu.classList.toggle('active');
                document.body.style.overflow = mobileMenu.classList.contains('active') ? 'hidden' : '';
            });
            
            if (mobileMenuClose) {
                mobileMenuClose.addEventListener('click', () => {
                    mobileMenu.classList.remove('active');
                    document.body.style.overflow = '';
                });
            }
            
            // Close menu when clicking on a link
            mobileMenu.querySelectorAll('a').forEach(link => {
                if (!link.classList.contains('mobile-dropdown-toggle')) {
                    link.addEventListener('click', () => {
                        mobileMenu.classList.remove('active');
                        document.body.style.overflow = '';
                    });
                }
            });
            
            // Mobile dropdown toggle
            document.querySelectorAll('.mobile-dropdown-toggle').forEach(toggle => {
                toggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    const dropdown = this.parentElement;
                    dropdown.classList.toggle('active');
                });
            });
        }
    }

    function initThemeToggle() {
        const themeToggles = document.querySelectorAll('.theme-toggle');
        themeToggles.forEach(toggle => {
            toggle.addEventListener('click', function() {
                const isDark = document.body.classList.toggle('dark-theme');
                localStorage.setItem('theme', isDark ? 'dark' : 'light');
                
                // Update icon
                const icon = this.querySelector('i');
                if (icon) {
                    icon.className = isDark ? 'fas fa-sun' : 'fas fa-moon';
                }
            });
            
            // Check saved theme
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme === 'dark') {
                document.body.classList.add('dark-theme');
                const icon = toggle.querySelector('i');
                if (icon) {
                    icon.className = 'fas fa-sun';
                }
            }
        });
    }

    function initDropdowns() {
        // Desktop dropdowns
        document.querySelectorAll('.dropdown > a').forEach(dropdownToggle => {
            dropdownToggle.addEventListener('click', function(e) {
                if (window.innerWidth <= 768) return; // Let mobile menu handle on small screens
                e.preventDefault();
                const dropdown = this.parentElement;
                dropdown.classList.toggle('active');
                
                // Close other dropdowns
                document.querySelectorAll('.dropdown').forEach(other => {
                    if (other !== dropdown) {
                        other.classList.remove('active');
                    }
                });
            });
        });
        
        // Close dropdowns when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.matches('.dropdown > a, .dropdown > a *')) {
                document.querySelectorAll('.dropdown').forEach(dropdown => {
                    dropdown.classList.remove('active');
                });
            }
        });
    }
    
    // Close dropdowns on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.dropdown, .collapsible-section').forEach(element => {
                element.classList.remove('active');
            });
        }
    });
    </script>
</body>
</html>