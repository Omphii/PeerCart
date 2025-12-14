<?php
// Add this at the very top of cart.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// cart.php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/functions.php';


$user_id = isLoggedIn() ? $_SESSION['user_id'] : null;
$error = null;
$success = null;

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $listing_id = isset($_POST['listing_id']) ? (int)$_POST['listing_id'] : 0;
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'], 'cart-action')) {
        $error = "Invalid security token. Please try again.";
    } elseif ($listing_id <= 0) {
        $error = "Invalid item selection.";
    } else {
        try {
            switch ($action) {
                case 'update':
                    if ($quantity <= 0) {
                        // Remove item if quantity is 0 or less
                        $result = removeFromCart($user_id, $listing_id);
                        if ($result) {
                            $success = "Item removed from cart.";
                        } else {
                            $error = "Failed to remove item.";
                        }
                    } else {
                        // Update quantity
                        $result = updateCartQuantity($user_id, $listing_id, $quantity);
                        if ($result) {
                            $success = "Cart updated successfully.";
                        } else {
                            $error = "Failed to update cart.";
                        }
                    }
                    break;
                    
                case 'remove':
                    $result = removeFromCart($user_id, $listing_id);
                    if ($result) {
                        $success = "Item removed from cart.";
                    } else {
                        $error = "Failed to remove item.";
                    }
                    break;
                    
                case 'clear':
                    // Clear entire cart
                    $result = clearCart($user_id);
                    if ($result) {
                        $success = "Cart cleared successfully.";
                    } else {
                        $error = "Failed to clear cart.";
                    }
                    break;
                    
                default:
                    $error = "Invalid action.";
            }
            
            // Refresh page to show updated cart
            if ($success) {
                header('Location: ' . BASE_URL . '/pages/cart.php?success=' . urlencode($success));
                exit;
            } elseif ($error) {
                header('Location: ' . BASE_URL . '/pages/cart.php?error=' . urlencode($error));
                exit;
            }
            
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Fetch cart items with listing details
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Get cart items based on user status
    if (isLoggedIn()) {
        // Logged-in user: fetch from database
        $stmt = $conn->prepare("
            SELECT 
                c.*,
                l.id as listing_id,
                l.name as listing_name,
                l.description,
                l.price,
                l.image,
                l.quantity as stock_quantity,
                l.status as listing_status,
                l.seller_id,
                u.name as seller_name,
                u.city as seller_city
            FROM cart c
            JOIN listings l ON c.listing_id = l.id
            LEFT JOIN users u ON l.seller_id = u.id
            WHERE c.user_id = :user_id 
              AND l.status = 'active'
              AND l.is_active = 1
            ORDER BY c.created_at DESC
        ");
        $stmt->execute([':user_id' => $user_id]);
        $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Guest user: fetch from session
        $cartItems = getGuestCartItems();
    }
    // Debug: Log what we got
log_debug("Cart items fetched", [
    'is_logged_in' => isLoggedIn(),
    'user_id' => $user_id,
    'total_items' => count($cartItems),
    'cart_items_sample' => array_slice($cartItems, 0, 2) // First 2 items
]);

// Also check session for guest
if (!isLoggedIn()) {
    log_debug("Guest session cart", ['guest_cart' => $_SESSION['guest_cart'] ?? []]);
}
    
    // Group items by seller for better organization
    $itemsBySeller = [];
    foreach ($cartItems as $item) {
        $sellerId = $item['seller_id'];
        if (!isset($itemsBySeller[$sellerId])) {
            $itemsBySeller[$sellerId] = [
                'seller_name' => $item['seller_name'],
                'seller_city' => $item['seller_city'],
                'items' => []
            ];
        }
        $itemsBySeller[$sellerId]['items'][] = $item;
    }
    
    // Calculate totals
    $subtotal = 0;
    $total_items = 0;
    $shipping_estimate = 0;
    
    foreach ($cartItems as $item) {
        $item_total = $item['price'] * $item['quantity'];
        $subtotal += $item_total;
        $total_items += $item['quantity'];
        
        // Simple shipping estimate (R50 per seller, R10 per additional item)
        $shipping_estimate += 50; // Base shipping per seller
        if ($item['quantity'] > 1) {
            $shipping_estimate += ($item['quantity'] - 1) * 10;
        }
    }
    
    // Calculate VAT (15%)
    $vat_rate = 0.15;
    $vat_amount = $subtotal * $vat_rate;
    
    // Calculate total
    $total = $subtotal + $vat_amount + $shipping_estimate;
    
} catch (Exception $e) {
    $error = "Error loading cart: " . $e->getMessage();
    $cartItems = [];
    $itemsBySeller = [];
    $subtotal = 0;
    $vat_amount = 0;
    $shipping_estimate = 0;
    $total = 0;
    $total_items = 0;
}

// Check for success/error messages from redirect
if (isset($_GET['success'])) {
    $success = urldecode($_GET['success']);
}
if (isset($_GET['error'])) {
    $error = urldecode($_GET['error']);
}

// Generate CSRF token for forms
$csrfToken = generateCSRFToken('cart-action');

// Page title
$title = 'Shopping Cart - PeerCart';

// Include header
includePartial('header', ['title' => $title]);
?>

<link rel="stylesheet" href="<?= asset('css/cart.css') ?>">
<!-- Replace the main container with this structure -->
<div class="cart-page">
    <!-- Background Elements -->
    <div class="cart-bg-blob"></div>
    <div class="cart-bg-blob"></div>
    
    <div class="cart-container">
        <!-- Left Column: Cart Items -->
        <div class="cart-items-column">
            <!-- Breadcrumb -->
            <div class="breadcrumb-container">
                <div class="breadcrumb-glass">
                    <div class="breadcrumb-item">
                        <a href="<?= BASE_URL ?>/" class="breadcrumb-link">Home</a>
                        <span class="breadcrumb-separator">›</span>
                        <span class="breadcrumb-active">Shopping Cart</span>
                    </div>
                </div>
            </div>
            
            <!-- Cart Header -->
            <div class="cart-header">
                <h1 class="cart-title">
                    <i class="fas fa-shopping-cart me-2"></i> Shopping Cart
                </h1>
                <p class="cart-subtitle">Review your items and proceed to checkout</p>
            </div>
            <!-- Add this for guest users -->
<?php if (!isLoggedIn()): ?>
<div class="glass-card mb-md">
    <div class="glass-card-body">
        <div class="alert alert-warning mb-0">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <i class="fas fa-exclamation-circle me-2"></i>
                    You're shopping as a guest. 
                    <a href="<?= BASE_URL ?>/includes/auth/login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" 
                       class="alert-link">Login</a> or 
                    <a href="<?= BASE_URL ?>/includes/auth.php" class="alert-link">Register</a> 
                    to save your cart and checkout faster.
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
            
            <!-- Messages -->
            <?php if ($success): ?>
                <div class="glass-card mb-md">
                    <div class="glass-card-body">
                        <div class="alert alert-success mb-0">
                            <i class="fas fa-check-circle me-2"></i> <?= htmlspecialchars($success) ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Cart Content -->
            <?php if (empty($cartItems)): ?>
                <!-- Empty Cart -->
                <div class="glass-card">
                    <div class="empty-cart-state">
                        <div class="empty-cart-icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <h3 class="empty-cart-title">Your cart is empty</h3>
                        <p class="empty-cart-message">
                            Looks like you haven't added any items to your cart yet.
                        </p>
                        <div class="action-buttons-grid">
                            <a href="<?= BASE_URL ?>/pages/listings.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-shopping-bag me-2"></i> Start Shopping
                            </a>
                            <a href="<?= BASE_URL ?>/pages/listings.php?featured=1" class="btn btn-outline-primary btn-lg">
                                <i class="fas fa-star me-2"></i> View Featured
                            </a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Cart with Items -->
                <div class="cart-items-section">
                    <!-- Cart Header Card -->
                    <div class="glass-card mb-md">
                        <div class="glass-card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-boxes me-2"></i> Your Items (<?= count($cartItems) ?>)
                            </h5>
                            <button type="button" class="btn btn-sm btn-outline-danger" 
        onclick="clearCart()">
    <i class="fas fa-trash me-1"></i> Clear Cart
</button>
                        </div>
                        
                        <div class="glass-card-body">
                            <!-- Seller Groups -->
                            <?php foreach ($itemsBySeller as $sellerId => $sellerData): ?>
                                <div class="seller-group mb-lg">
                                    <div class="seller-header">
                                        <div class="seller-avatar">
                                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($sellerData['seller_name']) ?>&background=4361ee&color=fff" 
                                                 alt="<?= htmlspecialchars($sellerData['seller_name']) ?>">
                                        </div>
                                        <div class="seller-info">
                                            <h5 class="mb-0">Sold by: <?= htmlspecialchars($sellerData['seller_name']) ?></h5>
                                            <div class="seller-location">
                                                <i class="fas fa-map-marker-alt me-1"></i> 
                                                <?= htmlspecialchars($sellerData['seller_city']) ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Cart Items -->
                                    <div class="seller-items">
                                        <?php foreach ($sellerData['items'] as $item): ?>
                                            <div class="cart-item-card">
                                                <!-- Product Image -->
                                                <!-- Replace lines 239-242 in cart.php -->
<div class="product-image-container">
    <?php
    $image_path = !empty($item['image']) ? BASE_URL . '/assets/uploads/' . $item['image'] : BASE_URL . '/assets/images/products/default-product.png';
    ?>
    <img src="<?= $image_path ?>" 
         alt="<?= htmlspecialchars($item['listing_name']) ?>"
         class="product-image"
         onerror="this.src='<?= BASE_URL ?>/assets/images/products/default-product.png'">
</div>
                                                
                                                <!-- Product Info -->
                                                <div class="product-info">
                                                    <h6 class="product-title">
                                                        <a href="<?= BASE_URL ?>/pages/listing.php?id=<?= $item['listing_id'] ?>">
                                                            <?= htmlspecialchars($item['listing_name']) ?>
                                                        </a>
                                                    </h6>
                                                    
                                                    <?php if (!empty($item['description'])): ?>
                                                        <p class="product-description">
                                                            <?= nl2br(htmlspecialchars(substr($item['description'], 0, 100))) ?>...
                                                        </p>
                                                    <?php endif; ?>
                                                    
                                                    <!-- Stock Status -->
                                                    <?php if ($item['stock_quantity'] <= 0): ?>
                                                        <span class="stock-status stock-out">Out of Stock</span>
                                                    <?php elseif ($item['quantity'] > $item['stock_quantity']): ?>
                                                        <span class="stock-status stock-low">
                                                            Only <?= $item['stock_quantity'] ?> left
                                                        </span>
                                                    <?php endif; ?>
                                                    
                                                    <!-- Quantity Controls -->
                                                    <div class="quantity-controls">
                                                        <div class="quantity-control-group">
                                                            <button type="button" class="quantity-btn minus" 
                                                                    onclick="updateQuantity(<?= $item['listing_id'] ?>, <?= $item['quantity'] - 1 ?>)">
                                                                <i class="fas fa-minus"></i>
                                                            </button>
                                                            <input type="number" 
                                                                   class="quantity-input" 
                                                                   value="<?= $item['quantity'] ?>" 
                                                                   min="1" 
                                                                   max="<?= max(100, $item['stock_quantity']) ?>"
                                                                   onchange="updateQuantity(<?= $item['listing_id'] ?>, this.value)">
                                                            <button type="button" class="quantity-btn plus" 
                                                                    onclick="updateQuantity(<?= $item['listing_id'] ?>, <?= $item['quantity'] + 1 ?>)">
                                                                <i class="fas fa-plus"></i>
                                                            </button>
                                                        </div>
                                                        
                                                        <!-- Price & Remove -->
                                                        <div class="price-section">
                                                            <div class="item-total">R<?= number_format($item_total, 2) ?></div>
                                                            <div class="item-unit">R<?= number_format($item['price'], 2) ?> each</div>
                                                            <button type="button" class="remove-btn" 
                                                                    onclick="removeFromCart(<?= $item['listing_id'] ?>)">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Continue Shopping -->
                    <div class="flex-between mt-lg">
                        <a href="<?= BASE_URL ?>/pages/listings.php" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-left me-2"></i> Continue Shopping
                        </a>
                        <a href="<?= BASE_URL ?>/pages/listings.php?discount=1" class="btn btn-outline-success">
                            <i class="fas fa-percentage me-2"></i> View Discounted Items
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Right Column: Order Summary -->
        <div class="order-summary-column">
            <div class="order-summary-card">
                <div class="glass-card">
                    <div class="order-summary-header">
                        <h5 class="mb-0">
                            <i class="fas fa-receipt me-2"></i> Order Summary
                        </h5>
                    </div>
                    
                    <div class="order-summary-body">
                        <?php if (!empty($cartItems)): ?>
                            <!-- Summary Details -->
                            <div class="summary-items">
                                <div class="summary-item">
                                    <span class="summary-label">Subtotal (<?= $total_items ?> items)</span>
                                    <span class="summary-value">R<?= number_format($subtotal, 2) ?></span>
                                </div>
                                <div class="summary-item">
                                    <span class="summary-label">VAT (15%)</span>
                                    <span class="summary-value">R<?= number_format($vat_amount, 2) ?></span>
                                </div>
                                <div class="summary-item">
                                    <span class="summary-label">Shipping Estimate</span>
                                    <span class="summary-value">R<?= number_format($shipping_estimate, 2) ?></span>
                                </div>
                            </div>
                            
                            <!-- Total -->
                            <div class="summary-total">
                                <span class="total-label">Total</span>
                                <span class="total-value">R<?= number_format($total, 2) ?></span>
                            </div>
                            
                            <!-- Checkout Button -->
                            <!-- Checkout Button -->
<?php if (isLoggedIn()): ?>
    <button class="checkout-btn" onclick="window.location.href='<?= BASE_URL ?>/pages/checkout.php'">
        <i class="fas fa-lock me-2"></i> Proceed to Checkout
    </button>
<?php else: ?>
    <button class="checkout-btn" onclick="window.location.href='<?= BASE_URL ?>/includes/auth/login.php?redirect=<?= urlencode(BASE_URL . '/pages/checkout.php') ?>'">
        <i class="fas fa-sign-in-alt me-2"></i> Login to Checkout
    </button>
    <p class="text-center mt-2 small text-muted">
        Guest checkout coming soon. Please login or register to complete your purchase.
    </p>
<?php endif; ?>
                            
                            <!-- Payment Methods -->
                            <div class="payment-methods">
                                <i class="fab fa-cc-visa payment-icon"></i>
                                <i class="fab fa-cc-mastercard payment-icon"></i>
                                <i class="fab fa-cc-paypal payment-icon"></i>
                                <i class="fab fa-cc-apple-pay payment-icon"></i>
                            </div>
                            
                            <!-- Trust Badges -->
                            <div class="trust-badges">
                                <div class="trust-badge">
                                    <i class="fas fa-shipping-fast text-primary"></i>
                                    <span>Free Shipping*</span>
                                </div>
                                <div class="trust-badge">
                                    <i class="fas fa-undo text-success"></i>
                                    <span>30-Day Returns</span>
                                </div>
                                <div class="trust-badge">
                                    <i class="fas fa-headset text-info"></i>
                                    <span>24/7 Support</span>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- Empty Summary -->
                            <div class="text-center py-4">
                                <p class="text-muted">Add items to your cart to see order summary</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript -->
<script>
// Update quantity function with AJAX
function updateQuantity(listingId, newQuantity) {
    if (newQuantity < 1) {
        if (confirm('Remove this item from cart?')) {
            removeFromCart(listingId);
        }
        return;
    }
    
    // Create form data
    const formData = new FormData();
    formData.append('csrf_token', '<?= $csrfToken ?>');
    formData.append('action', 'update');
    formData.append('listing_id', listingId);
    formData.append('quantity', newQuantity);
    
    // Show loading
    const quantityInput = document.querySelector(`input[value="${newQuantity - 1}"], input[value="${newQuantity + 1}"]`);
    if (quantityInput) {
        quantityInput.disabled = true;
    }
    
    // Make AJAX request
    fetch('<?= BASE_URL ?>/pages/cart.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (response.redirected) {
            window.location.href = response.url;
        } else {
            return response.text();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('danger', 'Failed to update cart. Please try again.');
        if (quantityInput) {
            quantityInput.disabled = false;
        }
    });
}

// Remove from cart function
function removeFromCart(listingId) {
    if (!confirm('Are you sure you want to remove this item from your cart?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('csrf_token', '<?= $csrfToken ?>');
    formData.append('action', 'remove');
    formData.append('listing_id', listingId);
    
    fetch('<?= BASE_URL ?>/pages/cart.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (response.redirected) {
            window.location.href = response.url;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('danger', 'Failed to remove item. Please try again.');
    });
}

// Show alert message
function showAlert(type, message) {
    // Remove existing alerts
    const existingAlerts = document.querySelectorAll('.alert-toast');
    existingAlerts.forEach(alert => alert.remove());
    
    // Create new alert
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-toast position-fixed top-0 end-0 m-3`;
    alertDiv.style.zIndex = '9999';
    alertDiv.innerHTML = `
        <div class="d-flex align-items-center">
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
            <span>${message}</span>
            <button type="button" class="btn-close ms-3" onclick="this.parentElement.parentElement.remove()"></button>
        </div>
    `;
    document.body.appendChild(alertDiv);
    
    // Auto-remove after 3 seconds
    setTimeout(() => {
        if (alertDiv.parentElement) {
            alertDiv.remove();
        }
    }, 3000);
}

// Input validation for quantity
document.addEventListener('DOMContentLoaded', function() {
    const quantityInputs = document.querySelectorAll('.quantity-input');
    quantityInputs.forEach(input => {
        input.addEventListener('change', function() {
            const value = parseInt(this.value);
            const max = parseInt(this.getAttribute('max')) || 100;
            const min = parseInt(this.getAttribute('min')) || 1;
            
            if (isNaN(value) || value < min) {
                this.value = min;
            } else if (value > max) {
                this.value = max;
                showAlert('warning', `Maximum quantity is ${max}`);
            }
        });
    });
    
    // Add animation to cart items
    const cartItems = document.querySelectorAll('.cart-item');
    cartItems.forEach(item => {
        item.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
            this.style.transition = 'transform 0.2s ease';
        });
        
        item.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
});
// Add this function in cart.php JavaScript section
function clearCart() {
    if (!confirm('Are you sure you want to clear your entire cart?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('csrf_token', '<?= $csrfToken ?>');
    formData.append('action', 'clear');
    
    fetch('<?= BASE_URL ?>/pages/cart.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (response.redirected) {
            window.location.href = response.url;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('danger', 'Failed to clear cart. Please try again.');
    });
}
</script>

<?php includePartial('footer'); ?>