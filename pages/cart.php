<?php
// Add this at the very top of cart.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// cart.php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
    } elseif ($listing_id <= 0 && $action !== 'clear') {
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
            
            // Redirect with messages
            if ($success || $error) {
                $redirect_url = BASE_URL . '/pages/cart.php';
                if ($success) {
                    $redirect_url .= '?success=' . urlencode($success);
                } elseif ($error) {
                    $redirect_url .= '?error=' . urlencode($error);
                }
                header('Location: ' . $redirect_url);
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
        
        // If guest cart has items, fetch their details from database
        if (!empty($cartItems)) {
            $listing_ids = array_column($cartItems, 'listing_id');
            $placeholders = str_repeat('?,', count($listing_ids) - 1) . '?';
            
            $stmt = $conn->prepare("
                SELECT 
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
                FROM listings l
                LEFT JOIN users u ON l.seller_id = u.id
                WHERE l.id IN ($placeholders)
                  AND l.status = 'active'
                  AND l.is_active = 1
            ");
            $stmt->execute($listing_ids);
            $listings = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Merge cart quantities with listing details
            $mergedItems = [];
            foreach ($listings as $listing) {
                foreach ($cartItems as $cartItem) {
                    if ($cartItem['listing_id'] == $listing['listing_id']) {
                        $mergedItem = array_merge($listing, [
                            'quantity' => $cartItem['quantity'],
                            'created_at' => $cartItem['created_at'] ?? date('Y-m-d H:i:s')
                        ]);
                        $mergedItems[] = $mergedItem;
                        break;
                    }
                }
            }
            $cartItems = $mergedItems;
        }
    }
    
    // Group items by seller for better organization
    $itemsBySeller = [];
    foreach ($cartItems as $item) {
        $sellerId = $item['seller_id'];
        if (!isset($itemsBySeller[$sellerId])) {
            $itemsBySeller[$sellerId] = [
                'seller_name' => $item['seller_name'] ?? 'Unknown Seller',
                'seller_city' => $item['seller_city'] ?? '',
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
        // This needs to be calculated per seller, not per item
    }
    
    // Calculate shipping per seller
    foreach ($itemsBySeller as $sellerId => $sellerData) {
        $sellerItemCount = 0;
        foreach ($sellerData['items'] as $item) {
            $sellerItemCount += $item['quantity'];
        }
        $shipping_estimate += 50; // Base shipping per seller
        if ($sellerItemCount > 1) {
            $shipping_estimate += ($sellerItemCount - 1) * 10;
        }
    }
    
    // VAT CALCULATION: Prices are VAT inclusive
    $vat_rate = 0.15;
    $net_amount = $subtotal / (1 + $vat_rate);
    $vat_amount = $net_amount * $vat_rate;
    
    // Round to 2 decimal places
    $net_amount = round($net_amount, 2);
    $vat_amount = round($vat_amount, 2);
    $subtotal = round($subtotal, 2);
    
    // Calculate total
    $total = $subtotal + $shipping_estimate;
    
} catch (Exception $e) {
    $error = "Error loading cart: " . $e->getMessage();
    $cartItems = [];
    $itemsBySeller = [];
    $subtotal = 0;
    $net_amount = 0;
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

<link rel="stylesheet" href="<?= asset('css/pages/cart.css') ?>">

<div class="cart-page">
    <!-- Background Elements -->
    <div class="cart-bg-blob"></div>
    <div class="cart-bg-blob"></div>
    
    <div class="cart-container">
        <!-- Left Column: Cart Items -->
        <div class="cart-items-column">
            <!-- Cart Header -->
            <div class="cart-header">
                <h1 class="cart-title">
                    <i class="fas fa-shopping-cart me-2"></i> Shopping Cart
                </h1>
            </div>
            
            <!-- Guest user warning -->
            <?php if (!isLoggedIn()): ?>
            <div class="glass-card mb-md">
                <div class="glass-card-body">
                    <div class="alert alert-warning mb-0">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <i class="fas fa-exclamation-circle me-2"></i>
                                You're shopping as a guest. 
                                <a href="<?= BASE_URL ?>/pages/auth.php?mode=login" class="alert-link">Login</a> or 
                                <a href="<?= BASE_URL ?>/pages/auth.php?mode=register" class="alert-link">Register</a> 
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
            
            <?php if ($error): ?>
                <div class="glass-card mb-md">
                    <div class="glass-card-body">
                        <div class="alert alert-danger mb-0">
                            <i class="fas fa-exclamation-circle me-2"></i> <?= htmlspecialchars($error) ?>
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
                            <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to clear your entire cart?');">
                                <input type="hidden" name="action" value="clear">
                                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="fas fa-trash me-1"></i> Clear Cart
                                </button>
                            </form>
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
                                        <?php foreach ($sellerData['items'] as $item): 
                                            $item_total = $item['price'] * $item['quantity'];
                                        ?>
                                            <div class="cart-item-card" data-listing-id="<?= $item['listing_id'] ?>">
                                                <!-- Product Image -->
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
                                                                   max="<?= max(1, min(100, $item['stock_quantity'])) ?>"
                                                                   data-listing-id="<?= $item['listing_id'] ?>"
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
        <?php if (!empty($cartItems)): ?>
        <div class="order-summary-column">
            <div class="order-summary-card">
                <div class="glass-card">
                    <div class="order-summary-header">
                        <h5 class="mb-0">
                            <i class="fas fa-receipt me-2"></i> Order Summary
                        </h5>
                    </div>
                    
                    <div class="order-summary-body">
                        <!-- Summary Details -->
                        <div class="summary-items">
                            <div class="summary-item">
                                <span class="summary-label">Items (<?= $total_items ?>)</span>
                                <span class="summary-value">R<?= number_format($subtotal, 2) ?></span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Net Amount (excl. VAT)</span>
                                <span class="summary-value">R<?= number_format($net_amount, 2) ?></span>
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
                            <span class="total-label">Total (VAT inclusive)</span>
                            <span class="total-value">R<?= number_format($total, 2) ?></span>
                        </div>
                        
                        <!-- Checkout Button -->
                        <?php if (isLoggedIn()): ?>
                            <button class="checkout-btn" onclick="window.location.href='<?= BASE_URL ?>/pages/checkout.php'">
                                <i class="fas fa-lock me-2"></i> Proceed to Checkout
                            </button>
                        <?php else: ?>
                            <button class="checkout-btn" onclick="window.location.href='<?= BASE_URL ?>/pages/auth.php?mode=login&redirect=<?= urlencode(BASE_URL . '/pages/checkout.php') ?>'">
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
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- JavaScript -->
<script>
// Update quantity function
function updateQuantity(listingId, newQuantity) {
    newQuantity = parseInt(newQuantity);
    const max = document.querySelector(`input[data-listing-id="${listingId}"]`)?.getAttribute('max') || 100;
    
    if (newQuantity < 1) {
        if (confirm('Remove this item from cart?')) {
            removeFromCart(listingId);
        }
        return;
    }
    
    if (newQuantity > parseInt(max)) {
        newQuantity = parseInt(max);
        showAlert('warning', `Maximum quantity is ${max}`);
    }
    
    // Create form data
    const formData = new FormData();
    formData.append('action', 'update');
    formData.append('listing_id', listingId);
    formData.append('quantity', newQuantity);
    formData.append('csrf_token', '<?= $csrfToken ?>');
    
    // Show loading
    const quantityInput = document.querySelector(`input[data-listing-id="${listingId}"]`);
    const itemCard = document.querySelector(`.cart-item-card[data-listing-id="${listingId}"]`);
    
    if (quantityInput) {
        quantityInput.disabled = true;
        quantityInput.value = newQuantity;
    }
    
    // Make AJAX request to current page
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (response.redirected) {
            window.location.href = response.url;
            return { success: true };
        }
        return response.text();
    })
    .then(data => {
        if (data.success) {
            // Update cart summary
            setTimeout(() => {
                window.location.reload();
            }, 500);
        } else {
            showAlert('danger', 'Failed to update cart. Please try again.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('danger', 'Failed to update cart. Please try again.');
    })
    .finally(() => {
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
    formData.append('action', 'remove');
    formData.append('listing_id', listingId);
    formData.append('csrf_token', '<?= $csrfToken ?>');
    
    fetch(window.location.href, {
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
    alertDiv.className = `alert alert-${type} alert-toast`;
    alertDiv.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        max-width: 400px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    `;
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
        // Store original value for undo
        let originalValue = input.value;
        
        input.addEventListener('focus', function() {
            originalValue = this.value;
        });
        
        input.addEventListener('change', function() {
            const value = parseInt(this.value);
            const max = parseInt(this.getAttribute('max')) || 100;
            const min = parseInt(this.getAttribute('min')) || 1;
            
            if (isNaN(value) || value < min) {
                this.value = min;
                updateQuantity(this.dataset.listingId, min);
            } else if (value > max) {
                this.value = max;
                showAlert('warning', `Maximum quantity is ${max}`);
                updateQuantity(this.dataset.listingId, max);
            } else if (value !== parseInt(originalValue)) {
                updateQuantity(this.dataset.listingId, value);
            }
        });
        
        input.addEventListener('blur', function() {
            if (this.value === '') {
                this.value = originalValue;
            }
        });
    });
    
    // Dismissible alerts
    const alertCloseButtons = document.querySelectorAll('.alert .btn-close');
    alertCloseButtons.forEach(button => {
        button.addEventListener('click', function() {
            this.closest('.alert').remove();
        });
    });
});
</script>

<?php includePartial('footer'); ?>