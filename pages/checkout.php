<?php
// checkout.php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . '/includes/auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$user_id = $_SESSION['user_id'];
$error = null;
$success = null;

// Fetch user details
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Get user information
    $stmt = $conn->prepare("
        SELECT id, name, surname, email, phone, address, city, province, postal_code, country
        FROM users 
        WHERE id = :user_id
    ");
    $stmt->execute([':user_id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        $error = "User not found. Please login again.";
    }
    
} catch (Exception $e) {
    $error = "Error loading user information: " . $e->getMessage();
}

// Fetch cart items
try {
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
            u.city as seller_city,
            u.email as seller_email
        FROM cart c
        JOIN listings l ON c.listing_id = l.id
        LEFT JOIN users u ON l.seller_id = u.id
        WHERE c.user_id = :user_id 
          AND l.status = 'active'
          AND l.is_active = 1
        ORDER BY l.seller_id, c.created_at DESC
    ");
    $stmt->execute([':user_id' => $user_id]);
    $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group items by seller
    $itemsBySeller = [];
    foreach ($cartItems as $item) {
        $sellerId = $item['seller_id'];
        if (!isset($itemsBySeller[$sellerId])) {
            $itemsBySeller[$sellerId] = [
                'seller_name' => $item['seller_name'],
                'seller_city' => $item['seller_city'],
                'seller_email' => $item['seller_email'],
                'items' => []
            ];
        }
        $itemsBySeller[$sellerId]['items'][] = $item;
    }
    
    // Check if cart is empty
    if (empty($cartItems)) {
        header('Location: ' . BASE_URL . '/pages/cart.php');
        exit;
    }
    
    // Calculate totals
    $subtotal = 0;
    $total_items = 0;
    $shipping_estimate = 0;
    $seller_shipping = [];
    
    foreach ($cartItems as $item) {
        $item_total = $item['price'] * $item['quantity'];
        $subtotal += $item_total;
        $total_items += $item['quantity'];
        
        // Calculate shipping per seller
        $sellerId = $item['seller_id'];
        if (!isset($seller_shipping[$sellerId])) {
            $seller_shipping[$sellerId] = [
                'base' => 50, // Base shipping per seller
                'additional' => 0
            ];
        }
        
        if ($item['quantity'] > 1) {
            $seller_shipping[$sellerId]['additional'] += ($item['quantity'] - 1) * 10;
        }
    }
    
    // Calculate total shipping
    foreach ($seller_shipping as $shipping) {
        $shipping_estimate += $shipping['base'] + $shipping['additional'];
    }
    
    // Calculate VAT (15%)
    $vat_rate = 0.15;
    $vat_amount = $subtotal * $vat_rate;
    
    // Calculate total
    $total = $subtotal + $vat_amount + $shipping_estimate;
    
} catch (Exception $e) {
    $error = "Error loading cart items: " . $e->getMessage();
    $cartItems = [];
    $itemsBySeller = [];
    $subtotal = 0;
    $vat_amount = 0;
    $shipping_estimate = 0;
    $total = 0;
    $total_items = 0;
}

// Handle checkout form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'], 'checkout')) {
        $error = "Invalid security token. Please try again.";
    } else {
        try {
            // Start transaction
            $conn->beginTransaction();
            
            // 1. Create order
            $order_number = 'ORD-' . strtoupper(uniqid());
            
            $stmt = $conn->prepare("
                INSERT INTO orders (
                    order_number, user_id, total_amount, vat_amount, shipping_amount,
                    payment_method, shipping_address, billing_address, notes, status
                ) VALUES (
                    :order_number, :user_id, :total_amount, :vat_amount, :shipping_amount,
                    :payment_method, :shipping_address, :billing_address, :notes, 'pending'
                )
            ");
            
            $stmt->execute([
                ':order_number' => $order_number,
                ':user_id' => $user_id,
                ':total_amount' => $total,
                ':vat_amount' => $vat_amount,
                ':shipping_amount' => $shipping_estimate,
                ':payment_method' => sanitizeInput($_POST['payment_method'] ?? 'card'),
                ':shipping_address' => json_encode([
                    'name' => sanitizeInput($_POST['shipping_name'] ?? $user['name'] . ' ' . $user['surname']),
                    'phone' => sanitizeInput($_POST['shipping_phone'] ?? $user['phone']),
                    'address' => sanitizeInput($_POST['shipping_address'] ?? $user['address']),
                    'city' => sanitizeInput($_POST['shipping_city'] ?? $user['city']),
                    'province' => sanitizeInput($_POST['shipping_province'] ?? $user['province']),
                    'postal_code' => sanitizeInput($_POST['shipping_postal_code'] ?? $user['postal_code']),
                    'country' => sanitizeInput($_POST['shipping_country'] ?? $user['country'] ?? 'South Africa')
                ]),
                ':billing_address' => json_encode([
                    'same_as_shipping' => isset($_POST['billing_same']) ? 1 : 0,
                    'name' => isset($_POST['billing_same']) ? '' : sanitizeInput($_POST['billing_name'] ?? ''),
                    'address' => isset($_POST['billing_same']) ? '' : sanitizeInput($_POST['billing_address'] ?? ''),
                    'city' => isset($_POST['billing_same']) ? '' : sanitizeInput($_POST['billing_city'] ?? '')
                ]),
                ':notes' => sanitizeInput($_POST['order_notes'] ?? '')
            ]);
            
            $order_id = $conn->lastInsertId();
            
            // 2. Create order items and update listings stock
            foreach ($cartItems as $item) {
                // Insert order item
                $stmt = $conn->prepare("
                    INSERT INTO order_items (
                        order_id, listing_id, seller_id, quantity, unit_price, item_total
                    ) VALUES (
                        :order_id, :listing_id, :seller_id, :quantity, :unit_price, :item_total
                    )
                ");
                
                $item_total = $item['price'] * $item['quantity'];
                
                $stmt->execute([
                    ':order_id' => $order_id,
                    ':listing_id' => $item['listing_id'],
                    ':seller_id' => $item['seller_id'],
                    ':quantity' => $item['quantity'],
                    ':unit_price' => $item['price'],
                    ':item_total' => $item_total
                ]);
                
                // Update listing stock
                $stmt = $conn->prepare("
                    UPDATE listings 
                    SET quantity = quantity - :quantity 
                    WHERE id = :listing_id AND quantity >= :quantity
                ");
                
                $stmt->execute([
                    ':quantity' => $item['quantity'],
                    ':listing_id' => $item['listing_id']
                ]);
                
                if ($stmt->rowCount() === 0) {
                    throw new Exception("Insufficient stock for item: " . $item['listing_name']);
                }
            }
            
            // 3. Clear user's cart
            $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = :user_id");
            $stmt->execute([':user_id' => $user_id]);
            
            // 4. Update user's cart count in session
            $_SESSION['cart_count'] = 0;
            
            // Commit transaction
            $conn->commit();
            
            // Success - redirect to order confirmation
            header('Location: ' . BASE_URL . '/pages/order-confirmation.php?id=' . $order_id);
            exit;
            
        } catch (Exception $e) {
            // Rollback on error
            $conn->rollBack();
            $error = "Checkout failed: " . $e->getMessage();
            log_error("Checkout error: " . $e->getMessage(), ['user_id' => $user_id]);
        }
    }
}

// Generate CSRF token
$csrfToken = generateCSRFToken('checkout');

// Page title
$title = 'Checkout - PeerCart';

// Include header
includePartial('header', ['title' => $title]);
?>

<!-- Add checkout-specific CSS -->
<style>
.checkout-page {
    padding: 30px 0;
    min-height: 70vh;
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
}

.checkout-container {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 30px;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.checkout-step {
    background: white;
    border-radius: 10px;
    padding: 25px;
    margin-bottom: 20px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    border: 1px solid #e0e0e0;
}

.step-header {
    display: flex;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #f0f0f0;
}

.step-number {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    background: #4361ee;
    color: white;
    border-radius: 50%;
    margin-right: 15px;
    font-weight: bold;
}

.step-title {
    font-size: 1.2rem;
    font-weight: 600;
    color: #333;
    margin: 0;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #555;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 12px 15px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 16px;
    transition: border 0.3s;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #4361ee;
    box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.checkbox-group {
    display: flex;
    align-items: center;
    gap: 10px;
}

.payment-methods {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
    margin-top: 15px;
}

.payment-method {
    position: relative;
}

.payment-method input {
    position: absolute;
    opacity: 0;
}

.payment-method label {
    display: flex;
    align-items: center;
    padding: 15px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s;
}

.payment-method input:checked + label {
    border-color: #4361ee;
    background: rgba(67, 97, 238, 0.05);
}

.payment-method i {
    font-size: 24px;
    margin-right: 10px;
    width: 30px;
}

.payment-method .fa-cc-visa { color: #1a1f71; }
.payment-method .fa-cc-mastercard { color: #eb001b; }
.payment-method .fa-cc-paypal { color: #003087; }
.payment-method .fa-university { color: #28a745; }

.order-summary {
    position: sticky;
    top: 20px;
}

.summary-card {
    background: white;
    border-radius: 10px;
    padding: 25px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    border: 1px solid #e0e0e0;
}

.summary-header {
    text-align: center;
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 2px solid #f0f0f0;
}

.summary-header h3 {
    margin: 0;
    color: #333;
}

.summary-items {
    max-height: 300px;
    overflow-y: auto;
    margin-bottom: 20px;
    padding-right: 10px;
}

.summary-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 12px;
    padding-bottom: 12px;
    border-bottom: 1px solid #f5f5f5;
}

.summary-item:last-child {
    border-bottom: none;
}

.summary-total {
    border-top: 2px solid #f0f0f0;
    padding-top: 20px;
    margin-top: 20px;
}

.total-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
    font-weight: 500;
}

.total-row.grand-total {
    font-size: 1.3rem;
    font-weight: bold;
    color: #333;
    margin-top: 15px;
    padding-top: 15px;
    border-top: 2px solid #f0f0f0;
}

.checkout-btn {
    background: #28a745;
    color: white;
    border: none;
    padding: 16px;
    width: 100%;
    border-radius: 8px;
    font-size: 18px;
    font-weight: bold;
    cursor: pointer;
    margin-top: 25px;
    transition: background 0.3s;
}

.checkout-btn:hover {
    background: #218838;
}

.checkout-btn:disabled {
    background: #ccc;
    cursor: not-allowed;
}

.seller-group {
    margin-bottom: 20px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
}

.seller-name {
    font-weight: 600;
    color: #4361ee;
    margin-bottom: 5px;
}

.alert {
    padding: 15px;
    border-radius: 6px;
    margin-bottom: 20px;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-danger {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

/* Responsive Design */
@media (max-width: 992px) {
    .checkout-container {
        grid-template-columns: 1fr;
    }
    
    .order-summary {
        order: -1;
    }
}

@media (max-width: 576px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .payment-methods {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="checkout-page">
    <div class="checkout-container">
        <!-- Left Column: Checkout Form -->
        <div class="checkout-form">
            <!-- Breadcrumb -->
            <div class="breadcrumb" style="margin-bottom: 30px;">
                <a href="<?= BASE_URL ?>/">Home</a> › 
                <a href="<?= BASE_URL ?>/pages/cart.php">Cart</a> › 
                <span>Checkout</span>
            </div>
            
            <!-- Page Header -->
            <h1 style="margin-bottom: 5px;">Checkout</h1>
            <p style="color: #666; margin-bottom: 30px;">Complete your purchase in a few simple steps</p>
            
            <!-- Error/Success Messages -->
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <!-- Checkout Form -->
            <form method="POST" id="checkoutForm">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                
                <!-- Step 1: Shipping Address -->
                <div class="checkout-step">
                    <div class="step-header">
                        <div class="step-number">1</div>
                        <h3 class="step-title">Shipping Address</h3>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="shipping_name">Full Name *</label>
                            <input type="text" id="shipping_name" name="shipping_name" 
                                   value="<?= htmlspecialchars($user['name'] . ' ' . $user['surname']) ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="shipping_phone">Phone Number *</label>
                            <input type="tel" id="shipping_phone" name="shipping_phone" 
                                   value="<?= htmlspecialchars($user['phone'] ?? '') ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="shipping_address">Street Address *</label>
                        <input type="text" id="shipping_address" name="shipping_address" 
                               value="<?= htmlspecialchars($user['address'] ?? '') ?>" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="shipping_city">City *</label>
                            <input type="text" id="shipping_city" name="shipping_city" 
                                   value="<?= htmlspecialchars($user['city'] ?? '') ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="shipping_province">Province *</label>
                            <select id="shipping_province" name="shipping_province" required>
                                <option value="">Select Province</option>
                                <option value="Eastern Cape" <?= ($user['province'] ?? '') === 'Eastern Cape' ? 'selected' : '' ?>>Eastern Cape</option>
                                <option value="Free State" <?= ($user['province'] ?? '') === 'Free State' ? 'selected' : '' ?>>Free State</option>
                                <option value="Gauteng" <?= ($user['province'] ?? '') === 'Gauteng' ? 'selected' : '' ?>>Gauteng</option>
                                <option value="KwaZulu-Natal" <?= ($user['province'] ?? '') === 'KwaZulu-Natal' ? 'selected' : '' ?>>KwaZulu-Natal</option>
                                <option value="Limpopo" <?= ($user['province'] ?? '') === 'Limpopo' ? 'selected' : '' ?>>Limpopo</option>
                                <option value="Mpumalanga" <?= ($user['province'] ?? '') === 'Mpumalanga' ? 'selected' : '' ?>>Mpumalanga</option>
                                <option value="North West" <?= ($user['province'] ?? '') === 'North West' ? 'selected' : '' ?>>North West</option>
                                <option value="Northern Cape" <?= ($user['province'] ?? '') === 'Northern Cape' ? 'selected' : '' ?>>Northern Cape</option>
                                <option value="Western Cape" <?= ($user['province'] ?? '') === 'Western Cape' ? 'selected' : '' ?>>Western Cape</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="shipping_postal_code">Postal Code *</label>
                            <input type="text" id="shipping_postal_code" name="shipping_postal_code" 
                                   value="<?= htmlspecialchars($user['postal_code'] ?? '') ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="shipping_country">Country *</label>
                            <input type="text" id="shipping_country" name="shipping_country" 
                                   value="<?= htmlspecialchars($user['country'] ?? 'South Africa') ?>" required>
                        </div>
                    </div>
                </div>
                
                <!-- Step 2: Billing Address -->
                <div class="checkout-step">
                    <div class="step-header">
                        <div class="step-number">2</div>
                        <h3 class="step-title">Billing Address</h3>
                    </div>
                    
                    <div class="checkbox-group" style="margin-bottom: 20px;">
                        <input type="checkbox" id="billing_same" name="billing_same" checked>
                        <label for="billing_same">Same as shipping address</label>
                    </div>
                    
                    <div id="billing-fields" style="display: none;">
                        <div class="form-group">
                            <label for="billing_name">Billing Name</label>
                            <input type="text" id="billing_name" name="billing_name">
                        </div>
                        
                        <div class="form-group">
                            <label for="billing_address">Billing Address</label>
                            <input type="text" id="billing_address" name="billing_address">
                        </div>
                        
                        <div class="form-group">
                            <label for="billing_city">Billing City</label>
                            <input type="text" id="billing_city" name="billing_city">
                        </div>
                    </div>
                </div>
                
                <!-- Step 3: Payment Method -->
                <div class="checkout-step">
                    <div class="step-header">
                        <div class="step-number">3</div>
                        <h3 class="step-title">Payment Method</h3>
                    </div>
                    
                    <div class="payment-methods">
                        <div class="payment-method">
                            <input type="radio" id="payment_card" name="payment_method" value="card" checked>
                            <label for="payment_card">
                                <i class="fab fa-cc-visa"></i>
                                <span>Credit/Debit Card</span>
                            </label>
                        </div>
                        
                        <div class="payment-method">
                            <input type="radio" id="payment_mastercard" name="payment_method" value="mastercard">
                            <label for="payment_mastercard">
                                <i class="fab fa-cc-mastercard"></i>
                                <span>Mastercard</span>
                            </label>
                        </div>
                        
                        <div class="payment-method">
                            <input type="radio" id="payment_paypal" name="payment_method" value="paypal">
                            <label for="payment_paypal">
                                <i class="fab fa-cc-paypal"></i>
                                <span>PayPal</span>
                            </label>
                        </div>
                        
                        <div class="payment-method">
                            <input type="radio" id="payment_bank" name="payment_method" value="bank_transfer">
                            <label for="payment_bank">
                                <i class="fas fa-university"></i>
                                <span>Bank Transfer</span>
                            </label>
                        </div>
                    </div>
                </div>
                
                <!-- Step 4: Order Notes -->
                <div class="checkout-step">
                    <div class="step-header">
                        <div class="step-number">4</div>
                        <h3 class="step-title">Additional Information</h3>
                    </div>
                    
                    <div class="form-group">
                        <label for="order_notes">Order Notes (Optional)</label>
                        <textarea id="order_notes" name="order_notes" rows="4" 
                                  placeholder="Special instructions, delivery preferences, etc."></textarea>
                    </div>
                </div>
                
                <!-- Terms and Conditions -->
                <div class="checkout-step" style="background: #f8f9fa;">
                    <div class="checkbox-group">
                        <input type="checkbox" id="terms" name="terms" required>
                        <label for="terms">
                            I agree to the <a href="<?= BASE_URL ?>/pages/terms.php" target="_blank">Terms and Conditions</a> 
                            and <a href="<?= BASE_URL ?>/pages/privacy.php" target="_blank">Privacy Policy</a> *
                        </label>
                    </div>
                </div>
                
                <!-- Submit Button -->
                <button type="submit" class="checkout-btn" id="submitBtn">
                    <i class="fas fa-lock me-2"></i> Complete Order - R<?= number_format($total, 2) ?>
                </button>
            </form>
        </div>
        
        <!-- Right Column: Order Summary -->
        <div class="order-summary">
            <div class="summary-card">
                <div class="summary-header">
                    <h3>Order Summary</h3>
                    <p><?= $total_items ?> item<?= $total_items !== 1 ? 's' : '' ?> in cart</p>
                </div>
                
                <div class="summary-items">
                    <?php foreach ($itemsBySeller as $sellerId => $sellerData): ?>
                        <div class="seller-group">
                            <div class="seller-name">
                                <i class="fas fa-store me-2"></i><?= htmlspecialchars($sellerData['seller_name']) ?>
                            </div>
                            <?php foreach ($sellerData['items'] as $item): ?>
                                <div class="summary-item">
                                    <div>
                                        <div style="font-weight: 500;"><?= htmlspecialchars($item['listing_name']) ?></div>
                                        <div style="font-size: 0.9em; color: #666;">
                                            Qty: <?= $item['quantity'] ?> × R<?= number_format($item['price'], 2) ?>
                                        </div>
                                    </div>
                                    <div style="font-weight: 600;">
                                        R<?= number_format($item['price'] * $item['quantity'], 2) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="summary-total">
                    <div class="total-row">
                        <span>Subtotal</span>
                        <span>R<?= number_format($subtotal, 2) ?></span>
                    </div>
                    
                    <div class="total-row">
                        <span>Shipping</span>
                        <span>R<?= number_format($shipping_estimate, 2) ?></span>
                    </div>
                    
                    <div class="total-row">
                        <span>VAT (15%)</span>
                        <span>R<?= number_format($vat_amount, 2) ?></span>
                    </div>
                    
                    <div class="total-row grand-total">
                        <span>Total</span>
                        <span>R<?= number_format($total, 2) ?></span>
                    </div>
                </div>
                
                <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #f0f0f0;">
                    <p style="color: #666; font-size: 0.9em;">
                        <i class="fas fa-info-circle me-2"></i>
                        All prices include 15% VAT. Shipping costs are estimates and may vary.
                    </p>
                </div>
            </div>
            
            <!-- Security Badges -->
            <div style="text-align: center; margin-top: 20px;">
                <div style="display: flex; justify-content: center; gap: 15px; margin-bottom: 15px;">
                    <i class="fas fa-shield-alt fa-2x text-success"></i>
                    <i class="fas fa-lock fa-2x text-primary"></i>
                    <i class="fas fa-credit-card fa-2x text-info"></i>
                </div>
                <p style="color: #666; font-size: 0.9em;">
                    <strong>Secure Checkout</strong><br>
                    Your payment information is encrypted and secure
                </p>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Billing address toggle
    const billingSame = document.getElementById('billing_same');
    const billingFields = document.getElementById('billing-fields');
    
    billingSame.addEventListener('change', function() {
        billingFields.style.display = this.checked ? 'none' : 'block';
    });
    
    // Form validation
    const form = document.getElementById('checkoutForm');
    const submitBtn = document.getElementById('submitBtn');
    
    form.addEventListener('submit', function(e) {
        if (!document.getElementById('terms').checked) {
            e.preventDefault();
            alert('Please agree to the Terms and Conditions.');
            return;
        }
        
        // Disable button to prevent double submission
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Processing...';
    });
    
    // Real-time shipping address validation
    const shippingPhone = document.getElementById('shipping_phone');
    shippingPhone.addEventListener('blur', function() {
        const phonePattern = /^[0-9+\-\s()]{10,15}$/;
        if (!phonePattern.test(this.value)) {
            this.style.borderColor = '#dc3545';
        } else {
            this.style.borderColor = '#28a745';
        }
    });
    
    // Postal code validation
    const postalCode = document.getElementById('shipping_postal_code');
    postalCode.addEventListener('blur', function() {
        const codePattern = /^[0-9]{4}$/;
        if (!codePattern.test(this.value)) {
            this.style.borderColor = '#dc3545';
        } else {
            this.style.borderColor = '#28a745';
        }
    });
    
    // Auto-format phone number
    shippingPhone.addEventListener('input', function() {
        let value = this.value.replace(/\D/g, '');
        if (value.length > 2 && value.length <= 5) {
            value = value.slice(0, 2) + ' ' + value.slice(2);
        } else if (value.length > 5 && value.length <= 9) {
            value = value.slice(0, 2) + ' ' + value.slice(2, 5) + ' ' + value.slice(5);
        } else if (value.length > 9) {
            value = value.slice(0, 2) + ' ' + value.slice(2, 5) + ' ' + value.slice(5, 9);
        }
        this.value = value;
    });
});
</script>

<?php includePartial('footer'); ?>