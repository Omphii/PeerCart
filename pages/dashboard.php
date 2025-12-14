<?php
// pages/dashboard.php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . '/pages/auth.php?mode=login&redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Get user data safely
$userName = htmlspecialchars($_SESSION['user_name'] ?? 'User');
$userType = $_SESSION['user_type'] ?? 'buyer';
$userId = $_SESSION['user_id'];

// Get database connection
try {
    $db = Database::getInstance()->getConnection();
    
    // Get stats for dashboard
    if ($userType === 'seller') {
        // Seller stats
        $listingsCount = $db->prepare("SELECT COUNT(*) FROM listings WHERE seller_id = ? AND is_active = 1");
        $listingsCount->execute([$userId]);
        $totalListings = $listingsCount->fetchColumn() ?? 0;
        
        $activeListings = $db->prepare("SELECT COUNT(*) FROM listings WHERE seller_id = ? AND is_active = 1 AND status = 'active'");
        $activeListings->execute([$userId]);
        $totalActiveListings = $activeListings->fetchColumn() ?? 0;
        
        $soldItems = $db->prepare("SELECT COUNT(*) FROM orders WHERE seller_id = ? AND status = 'completed'");
        $soldItems->execute([$userId]);
        $totalSold = $soldItems->fetchColumn() ?? 0;
        
        $revenue = $db->prepare("SELECT SUM(total_amount) FROM orders WHERE seller_id = ? AND status = 'completed'");
        $revenue->execute([$userId]);
        $totalRevenue = $revenue->fetchColumn() ?? 0;
        
        // Get recent orders
        $recentOrders = $db->prepare("
            SELECT o.id, o.order_number, o.total_amount, o.status, o.created_at,
                   u.name as buyer_name
            FROM orders o
            JOIN users u ON o.buyer_id = u.id
            WHERE o.seller_id = ?
            ORDER BY o.created_at DESC
            LIMIT 5
        ");
        $recentOrders->execute([$userId]);
        $recentOrders = $recentOrders->fetchAll(PDO::FETCH_ASSOC);
        
    } else {
        // Buyer stats
        $ordersCount = $db->prepare("SELECT COUNT(*) FROM orders WHERE buyer_id = ?");
        $ordersCount->execute([$userId]);
        $totalOrders = $ordersCount->fetchColumn() ?? 0;
        
        $cartItems = $db->prepare("SELECT COUNT(*) FROM cart WHERE user_id = ?");
        $cartItems->execute([$userId]);
        $totalCartItems = $cartItems->fetchColumn() ?? 0;
        
        $wishlistItems = $db->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id = ?");
        $wishlistItems->execute([$userId]);
        $totalWishlistItems = $wishlistItems->fetchColumn() ?? 0;
        
        // Get recent orders
        $recentOrders = $db->prepare("
            SELECT o.id, o.order_number, o.total_amount, o.status, o.created_at,
                   u.name as seller_name
            FROM orders o
            JOIN users u ON o.seller_id = u.id
            WHERE o.buyer_id = ?
            ORDER BY o.created_at DESC
            LIMIT 5
        ");
        $recentOrders->execute([$userId]);
        $recentOrders = $recentOrders->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get recent messages
    $recentMessages = $db->prepare("
        SELECT m.id, m.message, m.sent_at,
               u.name as sender_name,
               CASE WHEN m.sender_id = ? THEN 'sent' ELSE 'received' END as message_type
        FROM messages m
        JOIN users u ON (m.sender_id = u.id)
        WHERE m.sender_id = ? OR m.receiver_id = ?
        ORDER BY m.sent_at DESC
        LIMIT 5
    ");
    $recentMessages->execute([$userId, $userId, $userId]);
    $recentMessages = $recentMessages->fetchAll(PDO::FETCH_ASSOC);
    
    // Get recent listings (for seller) or recommendations (for buyer)
    if ($userType === 'seller') {
        $recentListings = $db->prepare("
            SELECT id, name, price, status, created_at
            FROM listings
            WHERE seller_id = ? AND is_active = 1
            ORDER BY created_at DESC
            LIMIT 5
        ");
        $recentListings->execute([$userId]);
        $recentListings = $recentListings->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Get recommended listings (simple: based on recently viewed)
        $recommendedListings = $db->prepare("
            SELECT l.id, l.name, l.price, l.image, l.created_at,
                   u.name as seller_name
            FROM listings l
            JOIN users u ON l.seller_id = u.id
            WHERE l.is_active = 1 AND l.status = 'active'
            ORDER BY RAND()
            LIMIT 4
        ");
        $recommendedListings->execute();
        $recommendedListings = $recommendedListings->fetchAll(PDO::FETCH_ASSOC);
    }
    
} catch (PDOException $e) {
    error_log("Dashboard query error: " . $e->getMessage());
    // Set defaults
    if ($userType === 'seller') {
        $totalListings = 0;
        $totalActiveListings = 0;
        $totalSold = 0;
        $totalRevenue = 0;
        $recentOrders = [];
        $recentListings = [];
    } else {
        $totalOrders = 0;
        $totalCartItems = 0;
        $totalWishlistItems = 0;
        $recentOrders = [];
        $recommendedListings = [];
    }
    $recentMessages = [];
}

// Set page title
$title = "Dashboard | PeerCart";

// Include header
include __DIR__ . '/../includes/header.php';
?>

<!-- Include Dashboard CSS -->
<link rel="stylesheet" href="<?= asset('css/dashboard.css') ?>">

<div class="dashboard-container">
    <!-- Sidebar Navigation -->
    <aside class="dashboard-sidebar">
        <div class="sidebar-header">
            <div class="logo-container">
                <a href="<?= BASE_URL ?>/">
                    <i class="fas fa-shopping-cart logo-icon"></i>
                    <span class="logo-text">PeerCart</span>
                </a>
            </div>
            <div class="user-info">
                <div class="user-avatar">
                    <?php 
                    $avatarSrc = !empty($_SESSION['user_avatar']) 
                        ? BASE_URL . '/uploads/profile/' . basename($_SESSION['user_avatar'])
                        : asset('images/users/default-user.png');
                    ?>
                    <img src="<?= $avatarSrc ?>" 
                         alt="Profile Picture" 
                         onerror="this.src='<?= asset('images/users/default-user.png') ?>'">
                </div>
                <div class="user-details">
                    <h3><?= $userName ?></h3>
                    <span class="user-role"><?= ucfirst($userType) ?></span>
                </div>
            </div>
        </div>
        
        <nav class="sidebar-nav">
            <ul>
                <li class="active">
                    <a href="<?= BASE_URL ?>/pages/dashboard.php">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                
                <?php if ($userType === 'seller'): ?>
                <li>
                    <a href="<?= BASE_URL ?>/pages/listings.php?my=1">
                        <i class="fas fa-list"></i>
                        <span>My Listings</span>
                        <?php if ($totalActiveListings > 0): ?>
                        <span class="badge"><?= $totalActiveListings ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li>
                    <a href="<?= BASE_URL ?>/pages/sell.php">
                        <i class="fas fa-plus-circle"></i>
                        <span>Sell Item</span>
                    </a>
                </li>
                <?php endif; ?>
                
                <li>
                    <a href="<?= BASE_URL ?>/pages/orders.php">
                        <i class="fas fa-shopping-bag"></i>
                        <span>My Orders</span>
                        <?php if (($userType === 'buyer' && $totalOrders > 0) || ($userType === 'seller' && $totalSold > 0)): ?>
                        <span class="badge"><?= $userType === 'buyer' ? $totalOrders : $totalSold ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                
                <li>
                    <a href="<?= BASE_URL ?>/pages/cart.php">
                        <i class="fas fa-shopping-cart"></i>
                        <span>My Cart</span>
                        <?php if ($totalCartItems > 0): ?>
                        <span class="badge"><?= $totalCartItems ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                
                <li>
                    <a href="<?= BASE_URL ?>/pages/wishlist.php">
                        <i class="fas fa-heart"></i>
                        <span>Wishlist</span>
                        <?php if ($totalWishlistItems > 0): ?>
                        <span class="badge"><?= $totalWishlistItems ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                
                <li>
                    <a href="<?= BASE_URL ?>/pages/messages.php">
                        <i class="fas fa-envelope"></i>
                        <span>Messages</span>
                        <?php if (!empty($recentMessages)): ?>
                        <span class="badge"><?= count($recentMessages) ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                
                <li>
                    <a href="<?= BASE_URL ?>/pages/settings.php">
                        <i class="fas fa-cog"></i>
                        <span>Settings</span>
                    </a>
                </li>
            </ul>
        </nav>
        
        <div class="sidebar-footer">
            <a href="<?= BASE_URL ?>/" class="sidebar-btn">
                <i class="fas fa-store"></i>
                <span>Back to Shopping</span>
            </a>
            <a href="<?= BASE_URL ?>/?action=logout" class="sidebar-btn logout">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </aside>

    <!-- Main Content Area -->
    <main class="dashboard-main">
        <!-- Header -->
        <header class="dashboard-header">
            <div class="header-content">
                <h1>Welcome back, <?= $userName ?>! 👋</h1>
                <p class="header-subtitle">Here's what's happening with your account today.</p>
            </div>
            <div class="header-actions">
                <button class="btn-refresh" title="Refresh Dashboard">
                    <i class="fas fa-sync-alt"></i>
                </button>
                <div class="date-display">
                    <i class="fas fa-calendar-alt"></i>
                    <?= date('F j, Y') ?>
                </div>
            </div>
        </header>
        
        <!-- Quick Stats -->
        <div class="quick-stats">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(67, 97, 238, 0.1); color: #4361ee;">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-info">
                    <h3>
                        <?php if ($userType === 'seller'): ?>
                            R<?= number_format($totalRevenue, 2) ?>
                        <?php else: ?>
                            <?= $totalOrders ?>
                        <?php endif; ?>
                    </h3>
                    <p><?= $userType === 'seller' ? 'Total Revenue' : 'Total Orders' ?></p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(46, 204, 113, 0.1); color: #2ecc71;">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <div class="stat-info">
                    <h3>
                        <?php if ($userType === 'seller'): ?>
                            <?= $totalActiveListings ?>
                        <?php else: ?>
                            <?= $totalCartItems ?>
                        <?php endif; ?>
                    </h3>
                    <p><?= $userType === 'seller' ? 'Active Listings' : 'Cart Items' ?></p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(241, 196, 15, 0.1); color: #f1c40f;">
                    <i class="fas fa-heart"></i>
                </div>
                <div class="stat-info">
                    <h3><?= $totalWishlistItems ?? 0 ?></h3>
                    <p>Wishlist Items</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(155, 89, 182, 0.1); color: #9b59b6;">
                    <i class="fas fa-envelope"></i>
                </div>
                <div class="stat-info">
                    <h3><?= !empty($recentMessages) ? count($recentMessages) : 0 ?></h3>
                    <p>New Messages</p>
                </div>
            </div>
        </div>
        
        <!-- Main Content Grid -->
        <div class="content-grid">
            <!-- Left Column -->
            <div class="content-left">
                <!-- Recent Activity -->
                <div class="content-card">
                    <div class="card-header">
                        <h3><i class="fas fa-history"></i> Recent Orders</h3>
                        <a href="<?= BASE_URL ?>/pages/orders.php" class="view-all">View All</a>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($recentOrders)): ?>
                            <div class="activity-list">
                                <?php foreach ($recentOrders as $order): 
                                    $statusClass = '';
                                    switch($order['status']) {
                                        case 'completed': $statusClass = 'success'; break;
                                        case 'pending': $statusClass = 'warning'; break;
                                        case 'cancelled': $statusClass = 'danger'; break;
                                        default: $statusClass = 'info';
                                    }
                                ?>
                                <div class="activity-item">
                                    <div class="activity-icon bg-<?= $statusClass ?>">
                                        <i class="fas fa-shopping-bag"></i>
                                    </div>
                                    <div class="activity-content">
                                        <h4>Order #<?= $order['order_number'] ?></h4>
                                        <p>
                                            <?php if ($userType === 'seller'): ?>
                                                From: <?= htmlspecialchars($order['buyer_name']) ?>
                                            <?php else: ?>
                                                Seller: <?= htmlspecialchars($order['seller_name']) ?>
                                            <?php endif; ?>
                                        </p>
                                        <div class="activity-meta">
                                            <span class="amount">R<?= number_format($order['total_amount'], 2) ?></span>
                                            <span class="badge badge-<?= $statusClass ?>"><?= ucfirst($order['status']) ?></span>
                                            <span class="time"><?= time_elapsed_string($order['created_at']) ?></span>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-shopping-bag fa-2x"></i>
                                <p>No recent orders found</p>
                                <a href="<?= BASE_URL ?>/pages/listings.php" class="btn btn-primary">Start Shopping</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Recent Messages -->
                <div class="content-card">
                    <div class="card-header">
                        <h3><i class="fas fa-envelope"></i> Recent Messages</h3>
                        <a href="<?= BASE_URL ?>/pages/messages.php" class="view-all">View All</a>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($recentMessages)): ?>
                            <div class="messages-list">
                                <?php foreach ($recentMessages as $message): ?>
                                <div class="message-item">
                                    <div class="message-avatar">
                                        <img src="https://ui-avatars.com/api/?name=<?= urlencode($message['sender_name']) ?>&background=4361ee&color=fff" 
                                             alt="<?= htmlspecialchars($message['sender_name']) ?>">
                                    </div>
                                    <div class="message-content">
                                        <h4><?= htmlspecialchars($message['sender_name']) ?></h4>
                                        <p class="message-preview"><?= htmlspecialchars(substr($message['message'], 0, 60)) ?>...</p>
                                        <div class="message-meta">
                                            <span class="message-type <?= $message['message_type'] ?>">
                                                <?= $message['message_type'] === 'sent' ? 'Sent' : 'Received' ?>
                                            </span>
                                            <span class="message-time"><?= time_elapsed_string($message['sent_at']) ?></span>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-envelope fa-2x"></i>
                                <p>No messages yet</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Right Column -->
            <div class="content-right">
                <!-- Quick Actions -->
                <div class="content-card">
                    <div class="card-header">
                        <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                    </div>
                    <div class="card-body">
                        <div class="quick-actions-grid">
                            <a href="<?= BASE_URL ?>/pages/listings.php" class="action-btn">
                                <i class="fas fa-search"></i>
                                <span>Browse Listings</span>
                            </a>
                            
                            <?php if ($userType === 'seller'): ?>
                            <a href="<?= BASE_URL ?>/pages/sell.php" class="action-btn">
                                <i class="fas fa-plus-circle"></i>
                                <span>Sell New Item</span>
                            </a>
                            <a href="<?= BASE_URL ?>/pages/listings.php?my=1" class="action-btn">
                                <i class="fas fa-edit"></i>
                                <span>Manage Listings</span>
                            </a>
                            <?php else: ?>
                            <a href="<?= BASE_URL ?>/pages/cart.php" class="action-btn">
                                <i class="fas fa-shopping-cart"></i>
                                <span>View Cart</span>
                            </a>
                            <a href="<?= BASE_URL ?>/pages/wishlist.php" class="action-btn">
                                <i class="fas fa-heart"></i>
                                <span>Wishlist</span>
                            </a>
                            <?php endif; ?>
                            
                            <a href="<?= BASE_URL ?>/pages/settings.php" class="action-btn">
                                <i class="fas fa-cog"></i>
                                <span>Settings</span>
                            </a>
                            <a href="<?= BASE_URL ?>/" class="action-btn">
                                <i class="fas fa-store"></i>
                                <span>Back to Shop</span>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Listings or Recommendations -->
                <div class="content-card">
                    <div class="card-header">
                        <h3>
                            <?php if ($userType === 'seller'): ?>
                                <i class="fas fa-list"></i> Recent Listings
                            <?php else: ?>
                                <i class="fas fa-star"></i> Recommended For You
                            <?php endif; ?>
                        </h3>
                        <a href="<?= BASE_URL ?>/pages/listings.php" class="view-all">Browse All</a>
                    </div>
                    <div class="card-body">
                        <?php if ($userType === 'seller' && !empty($recentListings)): ?>
                            <div class="listings-list">
                                <?php foreach ($recentListings as $listing): 
                                    $statusClass = $listing['status'] === 'active' ? 'success' : 'warning';
                                ?>
                                <div class="listing-item">
                                    <div class="listing-info">
                                        <h4><?= htmlspecialchars($listing['name']) ?></h4>
                                        <p class="listing-price">R<?= number_format($listing['price'], 2) ?></p>
                                        <div class="listing-meta">
                                            <span class="badge badge-<?= $statusClass ?>"><?= ucfirst($listing['status']) ?></span>
                                            <span class="listing-time"><?= time_elapsed_string($listing['created_at']) ?></span>
                                        </div>
                                    </div>
                                    <a href="<?= BASE_URL ?>/pages/listing.php?id=<?= $listing['id'] ?>" class="btn-view">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php elseif ($userType === 'buyer' && !empty($recommendedListings)): ?>
                            <div class="recommendations-grid">
                                <?php foreach ($recommendedListings as $item): ?>
                                <div class="recommendation-item">
                                    <div class="recommendation-image">
                                        <?php
                                        $imageSrc = !empty($item['image']) 
                                            ? getImageUrl($item['image'], 'listing')
                                            : asset('images/products/default-product.png');
                                        ?>
                                        <img src="<?= $imageSrc ?>" 
                                             alt="<?= htmlspecialchars($item['name']) ?>"
                                             onerror="this.src='<?= asset('images/products/default-product.png') ?>'">
                                    </div>
                                    <div class="recommendation-info">
                                        <h4><?= htmlspecialchars(substr($item['name'], 0, 30)) ?>...</h4>
                                        <p class="recommendation-price">R<?= number_format($item['price'], 2) ?></p>
                                        <p class="recommendation-seller"><?= htmlspecialchars($item['seller_name']) ?></p>
                                    </div>
                                    <a href="<?= BASE_URL ?>/pages/listing.php?id=<?= $item['id'] ?>" class="btn-view">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-box-open fa-2x"></i>
                                <p>
                                    <?php if ($userType === 'seller'): ?>
                                        No listings yet. Start selling!
                                    <?php else: ?>
                                        No recommendations available
                                    <?php endif; ?>
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Statistics Chart (Placeholder) -->
                <div class="content-card">
                    <div class="card-header">
                        <h3><i class="fas fa-chart-bar"></i> Statistics</h3>
                        <select class="chart-period">
                            <option value="week">This Week</option>
                            <option value="month">This Month</option>
                            <option value="year">This Year</option>
                        </select>
                    </div>
                    <div class="card-body">
                        <div class="chart-placeholder">
                            <div class="chart-bars">
                                <div class="chart-bar" style="height: 80%; background: #4361ee;"></div>
                                <div class="chart-bar" style="height: 60%; background: #3a0ca3;"></div>
                                <div class="chart-bar" style="height: 90%; background: #7209b7;"></div>
                                <div class="chart-bar" style="height: 70%; background: #f72585;"></div>
                                <div class="chart-bar" style="height: 85%; background: #4cc9f0;"></div>
                                <div class="chart-bar" style="height: 55%; background: #4895ef;"></div>
                                <div class="chart-bar" style="height: 75%; background: #560bad;"></div>
                            </div>
                            <div class="chart-labels">
                                <span>Mon</span>
                                <span>Tue</span>
                                <span>Wed</span>
                                <span>Thu</span>
                                <span>Fri</span>
                                <span>Sat</span>
                                <span>Sun</span>
                            </div>
                        </div>
                        <div class="chart-legend">
                            <div class="legend-item">
                                <span class="legend-color" style="background: #4361ee;"></span>
                                <span>Views</span>
                            </div>
                            <div class="legend-item">
                                <span class="legend-color" style="background: #f72585;"></span>
                                <span>Orders</span>
                            </div>
                            <div class="legend-item">
                                <span class="legend-color" style="background: #4cc9f0;"></span>
                                <span>Revenue</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Dashboard JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Refresh button functionality
    const refreshBtn = document.querySelector('.btn-refresh');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', function() {
            this.classList.add('spinning');
            setTimeout(() => {
                window.location.reload();
            }, 500);
        });
    }
    
    // Update current time
    function updateTime() {
        const now = new Date();
        const options = { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        };
        const timeElements = document.querySelectorAll('.date-display');
        timeElements.forEach(el => {
            el.innerHTML = `<i class="fas fa-calendar-alt"></i> ${now.toLocaleDateString('en-US', options)}`;
        });
    }
    
    // Update time initially and every minute
    updateTime();
    setInterval(updateTime, 60000);
    
    // Chart period selector
    const chartPeriod = document.querySelector('.chart-period');
    if (chartPeriod) {
        chartPeriod.addEventListener('change', function() {
            // In a real app, this would fetch new chart data
            console.log('Period changed to:', this.value);
            // Show loading state
            const chartPlaceholder = document.querySelector('.chart-placeholder');
            chartPlaceholder.innerHTML = '<div class="chart-loading"><i class="fas fa-spinner fa-spin"></i> Loading chart...</div>';
            
            // Simulate API call
            setTimeout(() => {
                // Reload the chart placeholder
                chartPlaceholder.innerHTML = `
                    <div class="chart-bars">
                        <div class="chart-bar" style="height: ${Math.random() * 100}%; background: #4361ee;"></div>
                        <div class="chart-bar" style="height: ${Math.random() * 100}%; background: #3a0ca3;"></div>
                        <div class="chart-bar" style="height: ${Math.random() * 100}%; background: #7209b7;"></div>
                        <div class="chart-bar" style="height: ${Math.random() * 100}%; background: #f72585;"></div>
                        <div class="chart-bar" style="height: ${Math.random() * 100}%; background: #4cc9f0;"></div>
                        <div class="chart-bar" style="height: ${Math.random() * 100}%; background: #4895ef;"></div>
                        <div class="chart-bar" style="height: ${Math.random() * 100}%; background: #560bad;"></div>
                    </div>
                    <div class="chart-labels">
                        <span>Mon</span>
                        <span>Tue</span>
                        <span>Wed</span>
                        <span>Thu</span>
                        <span>Fri</span>
                        <span>Sat</span>
                        <span>Sun</span>
                    </div>
                `;
            }, 1000);
        });
    }
    
    // Activity item hover effects
    const activityItems = document.querySelectorAll('.activity-item, .message-item, .listing-item');
    activityItems.forEach(item => {
        item.addEventListener('mouseenter', function() {
            this.style.transform = 'translateX(5px)';
            this.style.transition = 'transform 0.2s ease';
        });
        
        item.addEventListener('mouseleave', function() {
            this.style.transform = 'translateX(0)';
        });
    });
    
    // Quick action buttons animation
    const actionButtons = document.querySelectorAll('.action-btn');
    actionButtons.forEach(button => {
        button.addEventListener('mouseenter', function() {
            const icon = this.querySelector('i');
            if (icon) {
                icon.style.transform = 'scale(1.2)';
                icon.style.transition = 'transform 0.2s ease';
            }
        });
        
        button.addEventListener('mouseleave', function() {
            const icon = this.querySelector('i');
            if (icon) {
                icon.style.transform = 'scale(1)';
            }
        });
    });
    
    // Mobile menu toggle (if needed)
    const mobileMenuBtn = document.createElement('button');
    mobileMenuBtn.className = 'mobile-menu-toggle';
    mobileMenuBtn.innerHTML = '<i class="fas fa-bars"></i>';
    mobileMenuBtn.style.cssText = 'position: fixed; top: 10px; left: 10px; z-index: 1000; background: #4361ee; color: white; border: none; width: 40px; height: 40px; border-radius: 8px; display: none; cursor: pointer;';
    
    document.body.appendChild(mobileMenuBtn);
    
    // Check if mobile
    function checkMobile() {
        if (window.innerWidth <= 768) {
            mobileMenuBtn.style.display = 'block';
            const sidebar = document.querySelector('.dashboard-sidebar');
            if (sidebar) {
                sidebar.style.transform = 'translateX(-100%)';
                sidebar.style.transition = 'transform 0.3s ease';
            }
        } else {
            mobileMenuBtn.style.display = 'none';
            const sidebar = document.querySelector('.dashboard-sidebar');
            if (sidebar) {
                sidebar.style.transform = 'translateX(0)';
            }
        }
    }
    
    checkMobile();
    window.addEventListener('resize', checkMobile);
    
    // Mobile menu toggle functionality
    mobileMenuBtn.addEventListener('click', function() {
        const sidebar = document.querySelector('.dashboard-sidebar');
        if (sidebar) {
            if (sidebar.style.transform === 'translateX(0%)' || !sidebar.style.transform) {
                sidebar.style.transform = 'translateX(-100%)';
            } else {
                sidebar.style.transform = 'translateX(0%)';
            }
        }
    });
});
</script>

<?php
// Include footer
include __DIR__ . '/../includes/footer.php';
?>