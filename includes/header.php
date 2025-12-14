<?php
// Check if bootstrap has been loaded
if (!defined('BASE_URL')) {
    die('Application not initialized properly. Please contact administrator.');
}

// Page title
$title = $title ?? 'PeerCart - C2C Ecommerce';

// Fetch categories and cities (with limits for performance)
$categories = getCategories(8); // Increased to 8 for better display
$cities = getCities(6);

// User info
$isLoggedIn = isLoggedIn();
$userType = $_SESSION['user_type'] ?? 'buyer';
$userName = htmlspecialchars($_SESSION['user_name'] ?? 'User');
$userAvatar = $_SESSION['user_avatar'] ?? 'default_avatar.png';

// Current page for menu highlighting
$currentPage = $currentPage ?? '';

// Cart count
$cartCount = getCartCount($_SESSION['user_id'] ?? null);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    
    <!-- Meta Tags -->
    <meta name="description" content="Peer-to-peer marketplace connecting buyers and sellers directly">
    <meta name="keywords" content="ecommerce, marketplace, buy, sell, trade, peer-to-peer">
    <meta name="author" content="PeerCart">
    
    <!-- Favicon -->
    <link rel="icon" href="<?= asset('images/favicon.ico') ?>" type="image/x-icon">
    <link rel="apple-touch-icon" href="<?= asset('images/logo.png') ?>">
    
    <!-- Fonts & Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Core CSS -->
    <link rel="stylesheet" href="<?= asset('css/main.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/navbar.css') ?>">
    
    <!-- Page-specific CSS -->
    <?php if (isset($css_files) && is_array($css_files)): ?>
        <?php foreach ($css_files as $css_file): ?>
            <link rel="stylesheet" href="<?= asset('css/' . $css_file) ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- JavaScript (deferred) -->
    <script src="<?= asset('js/script.js') ?>" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>
    
    <!-- JSON-LD Structured Data -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebSite",
        "name": "PeerCart",
        "url": "<?= BASE_URL ?>",
        "description": "Peer-to-peer marketplace connecting buyers and sellers directly"
    }
    </script>
</head>
<body data-page="<?= $currentPage ?>" data-logged-in="<?= $isLoggedIn ? 'true' : 'false' ?>">
    <!-- Skip to main content for accessibility -->
    <a href="#main-content" class="skip-to-content">Skip to main content</a>
    
    <!-- Header Container -->
    <div class="header-container">
        <!-- Top Bar -->
        <div class="top-bar">
            <div class="container">
                <div class="top-bar-content">
                    <!-- Announcement -->
                    <div class="announcement">
                        <i class="fas fa-shipping-fast"></i>
                        <span>Free shipping on orders over R500</span>
                    </div>
                    
                    <!-- Top Links -->
                    <div class="top-links">
                        <?php if ($isLoggedIn): ?>
                            <span class="welcome-text">
                                <i class="fas fa-user-circle"></i>
                                Welcome, <?= $userName ?>
                            </span>
                        <?php else: ?>
                            <a href="<?= url('pages/auth.php?mode=login') ?>">
                                <i class="fas fa-sign-in-alt"></i> Sign In
                            </a>
                            <a href="<?= url('pages/auth.php?mode=register') ?>">
                                <i class="fas fa-user-plus"></i> Register
                            </a>
                        <?php endif; ?>
                        <a href="<?= url('pages/help.php') ?>">
                            <i class="fas fa-question-circle"></i> Help
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Header -->
        <header class="navbar" role="banner">
            <div class="container">
                <!-- Logo -->
                <div class="logo">
                    <a href="<?= BASE_URL ?>/" aria-label="PeerCart Home">
                        <i class="fas fa-shopping-cart logo-icon"></i>
                        <span class="logo-text">Peer<span>Cart</span></span>
                    </a>
                </div>

                <!-- Search Bar -->
                <div class="search-container">
                    <form action="<?= url('pages/listings.php') ?>" method="GET" class="search-form" role="search">
                        <div class="search-wrapper">
                            <input type="text" 
                                   name="q" 
                                   placeholder="Search for products, brands, or sellers..." 
                                   aria-label="Search"
                                   value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
                            <button type="submit" class="search-btn" aria-label="Search">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                        <div class="search-options">
                            <select name="category" aria-label="Search category">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= $category['id'] ?>" 
                                            <?= (isset($_GET['category']) && $_GET['category'] == $category['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($category['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <select name="city" aria-label="Search location">
                                <option value="">All Locations</option>
                                <?php foreach ($cities as $city): ?>
                                    <option value="<?= urlencode($city) ?>"
                                            <?= (isset($_GET['city']) && $_GET['city'] == $city) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($city) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>
                </div>

                <!-- Action Buttons -->
                <div class="action-buttons">
                    <!-- Cart -->
                    <a href="<?= url('pages/cart.php') ?>" class="action-btn cart-btn" aria-label="Shopping Cart">
                        <i class="fas fa-shopping-cart"></i>
                        <?php if ($cartCount > 0): ?>
                            <span class="cart-count" aria-label="<?= $cartCount ?> items in cart">
                                <?= $cartCount ?>
                            </span>
                        <?php endif; ?>
                        <span class="btn-text">Cart</span>
                    </a>

                    <!-- Wishlist -->
                    <a href="<?= url('pages/wishlist.php') ?>" class="action-btn" aria-label="Wishlist">
                        <i class="fas fa-heart"></i>
                        <span class="btn-text">Wishlist</span>
                    </a>

                    <!-- User Account -->
                    <div class="dropdown user-dropdown">
                        <button class="action-btn dropdown-toggle" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-user"></i>
                            <span class="btn-text">Account</span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="dropdown-menu" role="menu">
                            <?php if ($isLoggedIn): ?>
                                <div class="user-info">
                                    <div class="user-avatar">
                                        <img src="<?= getUserImage($userAvatar) ?>" 
                                             alt="<?= $userName ?>'s profile picture">
                                    </div>
                                    <div class="user-details">
                                        <strong><?= $userName ?></strong>
                                        <small><?= ucfirst($userType) ?></small>
                                    </div>
                                </div>
                                <div class="dropdown-divider"></div>
                                
                                <?php if ($userType === 'seller'): ?>
                                    <a href="<?= url('pages/dashboard.php') ?>" class="dropdown-item">
                                        <i class="fas fa-tachometer-alt"></i> Dashboard
                                    </a>
                                    <a href="<?= url('pages/sell.php') ?>" class="dropdown-item">
                                        <i class="fas fa-plus-circle"></i> Sell Item
                                    </a>
                                <?php else: ?>
                                    <a href="<?= url('pages/orders.php') ?>" class="dropdown-item">
                                        <i class="fas fa-shopping-bag"></i> My Orders
                                    </a>
                                <?php endif; ?>
                                
                                <a href="<?= url('pages/profile.php') ?>" class="dropdown-item">
                                    <i class="fas fa-user-edit"></i> Profile
                                </a>
                                <a href="<?= url('pages/settings.php') ?>" class="dropdown-item">
                                    <i class="fas fa-cog"></i> Settings
                                </a>
                                <a href="<?= url('pages/messages.php') ?>" class="dropdown-item">
                                    <i class="fas fa-envelope"></i> Messages
                                </a>
                                <div class="dropdown-divider"></div>
                                <a href="<?= url('?action=logout') ?>" class="dropdown-item logout">
                                    <i class="fas fa-sign-out-alt"></i> Logout
                                </a>
                            <?php else: ?>
                                <a href="<?= url('pages/auth.php?mode=login') ?>" class="dropdown-item">
                                    <i class="fas fa-sign-in-alt"></i> Sign In
                                </a>
                                <a href="<?= url('pages/auth.php?mode=register') ?>" class="dropdown-item">
                                    <i class="fas fa-user-plus"></i> Register
                                </a>
                                <div class="dropdown-divider"></div>
                                <a href="<?= url('pages/help.php') ?>" class="dropdown-item">
                                    <i class="fas fa-question-circle"></i> Help Center
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Theme Toggle -->
                    <button class="action-btn theme-toggle" aria-label="Toggle theme">
                        <i class="fas fa-moon"></i>
                        <span class="btn-text">Theme</span>
                    </button>
                </div>

                <!-- Mobile Menu Toggle -->
                <button class="mobile-menu-toggle" aria-label="Toggle mobile menu" aria-expanded="false">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </header>

        <!-- Main Navigation -->
        <nav class="main-navigation" role="navigation" aria-label="Main navigation">
            <div class="container">
                <ul class="nav-menu">
                    <li class="<?= $currentPage === 'home' ? 'active' : '' ?>">
                        <a href="<?= BASE_URL ?>/">
                            <i class="fas fa-home"></i> Home
                        </a>
                    </li>

                    <!-- Categories Dropdown -->
                    <li class="dropdown mega-dropdown">
                        <a href="#" class="dropdown-toggle">
                            <i class="fas fa-shopping-bag"></i> Shop <i class="fas fa-chevron-down"></i>
                        </a>
                        <div class="dropdown-menu mega-menu">
                            <div class="mega-menu-container">
                                <!-- Categories -->
                                <div class="mega-menu-section">
                                    <h4><i class="fas fa-tags"></i> Categories</h4>
                                    <div class="category-grid">
                                        <?php foreach ($categories as $category): ?>
                                            <a href="<?= url('pages/listings.php?category=' . $category['id']) ?>" 
                                               class="category-item">
                                                <div class="category-icon">
                                                    <i class="fas <?= htmlspecialchars($category['icon'] ?? 'fa-tag') ?>"></i>
                                                </div>
                                                <div class="category-info">
                                                    <strong><?= htmlspecialchars($category['name']) ?></strong>
                                                    <small><?= $category['total_listings'] ?? 0 ?> items</small>
                                                </div>
                                            </a>
                                        <?php endforeach; ?>
                                        <a href="<?= url('pages/categories.php') ?>" class="category-item view-all">
                                            <div class="category-icon">
                                                <i class="fas fa-arrow-right"></i>
                                            </div>
                                            <div class="category-info">
                                                <strong>View All Categories</strong>
                                                <small>Browse all categories</small>
                                            </div>
                                        </a>
                                    </div>
                                </div>

                                <!-- Quick Links -->
                                <div class="mega-menu-section">
                                    <h4><i class="fas fa-bolt"></i> Quick Links</h4>
                                    <div class="quick-links">
                                        <a href="<?= url('pages/listings.php?discount=1') ?>">
                                            <i class="fas fa-percentage"></i> Discounted Items
                                        </a>
                                        <a href="<?= url('pages/listings.php?featured=1') ?>">
                                            <i class="fas fa-star"></i> Featured Products
                                        </a>
                                        <a href="<?= url('pages/listings.php?new=1') ?>">
                                            <i class="fas fa-certificate"></i> New Arrivals
                                        </a>
                                        <a href="<?= url('pages/listings.php?urgent=1') ?>">
                                            <i class="fas fa-bolt"></i> Urgent Sales
                                        </a>
                                        <a href="<?= url('pages/trending.php') ?>">
                                            <i class="fas fa-chart-line"></i> Trending Now
                                        </a>
                                        <a href="<?= url('pages/clearance.php') ?>">
                                            <i class="fas fa-fire"></i> Clearance Sale
                                        </a>
                                    </div>
                                </div>

                                <!-- Popular Cities -->
                                <div class="mega-menu-section">
                                    <h4><i class="fas fa-map-marker-alt"></i> Shop by City</h4>
                                    <div class="city-list">
                                        <?php foreach ($cities as $city): ?>
                                            <a href="<?= url('pages/listings.php?city=' . urlencode($city)) ?>">
                                                <i class="fas fa-city"></i> <?= htmlspecialchars($city) ?>
                                            </a>
                                        <?php endforeach; ?>
                                        <a href="<?= url('pages/cities.php') ?>" class="view-all">
                                            <i class="fas fa-arrow-right"></i> All Cities
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </li>

                    <!-- Sell Item -->
                    <?php if ($isLoggedIn && ($userType === 'seller' || $userType === 'both')): ?>
                        <li class="<?= $currentPage === 'sell' ? 'active' : '' ?>">
                            <a href="<?= url('pages/sell.php') ?>" class="sell-button">
                                <i class="fas fa-plus-circle"></i> Sell
                            </a>
                        </li>
                    <?php endif; ?>

                    <!-- More Links -->
                    <li><a href="<?= url('pages/deals.php') ?>"><i class="fas fa-tag"></i> Deals</a></li>
                    <li><a href="<?= url('pages/stores.php') ?>"><i class="fas fa-store"></i> Stores</a></li>
                    <li><a href="<?= url('pages/help.php') ?>"><i class="fas fa-question-circle"></i> Help</a></li>
                </ul>
            </div>
        </nav>
    </div>

    <!-- Mobile Menu -->
    <div class="mobile-menu" role="dialog" aria-label="Mobile menu" aria-hidden="true">
        <div class="mobile-menu-header">
            <button class="mobile-menu-close" aria-label="Close mobile menu">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="mobile-menu-content">
            <!-- Mobile menu content will be populated by JavaScript -->
        </div>
    </div>

    <!-- Backdrop for mobile menu -->
    <div class="menu-backdrop" aria-hidden="true"></div>

    <!-- Flash Messages -->
    <div class="flash-messages-container">
        <?php displayFlashMessage(); ?>
    </div>

    <!-- Main Content Wrapper -->
    <main id="main-content" class="main-content" role="main">