<?php
// checkout.php - Fixed version with complete integration
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . '/pages/auth.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$user_id = $_SESSION['user_id'];
$error = null;
$success = null;
$validation_errors = [];
$user = [];
$user_address = null;
$cartItems = [];
$itemsBySeller = [];
$subtotal = 0;
$vat_amount = 0;
$shipping_estimate = 0;
$total = 0;
$total_items = 0;
$discount_amount = 0;

// Fetch user details with addresses
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Get user information
    $stmt = $conn->prepare("
        SELECT id, name, surname, email, phone, city, province, suburb, street_address, postal_code
        FROM users 
        WHERE id = :user_id
    ");
    $stmt->execute(['user_id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        $error = "User not found. Please login again.";
        $user = [];
    } else {
        // Get user's default shipping address
        $stmt = $conn->prepare("
            SELECT * FROM addresses 
            WHERE user_id = :user_id AND (address_type = 'shipping' OR address_type = 'both') AND is_default = 1
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $stmt->execute(['user_id' => $user_id]);
        $user_address = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // If no default address, get any shipping address
        if (!$user_address) {
            $stmt = $conn->prepare("
                SELECT * FROM addresses 
                WHERE user_id = :user_id AND (address_type = 'shipping' OR address_type = 'both')
                ORDER BY created_at DESC 
                LIMIT 1
            ");
            $stmt->execute(['user_id' => $user_id]);
            $user_address = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }
    
} catch (Exception $e) {
    $error = "Error loading user information: " . $e->getMessage();
    error_log("Checkout user info error: " . $e->getMessage());
    $user = [];
    $user_address = null;
}

// Set default user values if array is empty
if (empty($user)) {
    $user = [
        'name' => '',
        'surname' => '',
        'email' => '',
        'phone' => '',
        'city' => '',
        'province' => '',
        'suburb' => '',
        'street_address' => '',
        'postal_code' => ''
    ];
}

// Fetch cart items with enhanced validation
try {
    if (!isset($conn)) {
        throw new Exception("Database connection not available");
    }
    
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
            u.email as seller_email,
            u.phone as seller_phone
        FROM cart c
        JOIN listings l ON c.listing_id = l.id
        LEFT JOIN users u ON l.seller_id = u.id
        WHERE c.user_id = :user_id 
          AND l.status = 'active'
          AND l.is_active = 1
          AND l.quantity > 0
        ORDER BY l.seller_id, c.created_at DESC
    ");
    $stmt->execute(['user_id' => $user_id]);
    $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Validate cart items
    $validCartItems = [];
    foreach ($cartItems as $item) {
        // Check stock availability
        if ($item['quantity'] > $item['stock_quantity']) {
            $validation_errors[] = "Only {$item['stock_quantity']} available for '{$item['listing_name']}'. Please update quantity.";
            continue;
        }
        
        // Check listing is still active
        if ($item['listing_status'] !== 'active') {
            $validation_errors[] = "'{$item['listing_name']}' is no longer available.";
            continue;
        }
        
        $validCartItems[] = $item;
    }
    
    $cartItems = $validCartItems;
    
    // Group items by seller
    $itemsBySeller = [];
    foreach ($cartItems as $item) {
        $sellerId = $item['seller_id'];
        if (!isset($itemsBySeller[$sellerId])) {
            $itemsBySeller[$sellerId] = [
                'seller_id' => $sellerId,
                'seller_name' => $item['seller_name'],
                'seller_city' => $item['seller_city'],
                'seller_email' => $item['seller_email'],
                'seller_phone' => $item['seller_phone'],
                'items' => []
            ];
        }
        $itemsBySeller[$sellerId]['items'][] = $item;
    }
    
    // Check if cart is empty
    if (empty($cartItems)) {
        if (!empty($validation_errors)) {
            $_SESSION['checkout_errors'] = $validation_errors;
        }
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
        
        // Calculate shipping per seller (dynamic based on location)
        $sellerId = $item['seller_id'];
        if (!isset($seller_shipping[$sellerId])) {
            // Base shipping cost
            $base_shipping = 50;
            $seller_shipping[$sellerId] = [
                'base' => $base_shipping,
                'additional' => 0,
                'items_count' => 0
            ];
        }
        
        $seller_shipping[$sellerId]['items_count'] += $item['quantity'];
        if ($item['quantity'] > 1) {
            $seller_shipping[$sellerId]['additional'] += ($item['quantity'] - 1) * 10;
        }
    }
    
    // Calculate total shipping with discount for multiple items
    foreach ($seller_shipping as $sellerId => $shipping) {
        $seller_total_shipping = $shipping['base'] + $shipping['additional'];
        
        // Apply discount for bulk orders from same seller
        if ($shipping['items_count'] >= 3) {
            $seller_total_shipping *= 0.9; // 10% discount
        }
        
        $shipping_estimate += $seller_total_shipping;
    }
    
    // Calculate VAT (15%)
    $vat_rate = 0.15;
    $vat_amount = $subtotal * $vat_rate;
    
    // Calculate total
    $total = $subtotal + $vat_amount + $shipping_estimate;
    
    // Apply discounts if any
    $discount_amount = 0;
    $discount_code = $_SESSION['discount_code'] ?? null;
    if ($discount_code) {
        // Validate discount code
        $discount_amount = $total * 0.1; // Example: 10% discount
        $total -= $discount_amount;
    }
    
} catch (Exception $e) {
    $error = "Error loading cart items: " . $e->getMessage();
    error_log("Checkout cart items error: " . $e->getMessage());
    $cartItems = [];
    $itemsBySeller = [];
}

// Handle checkout form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_valid = true;
    
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'], 'checkout')) {
        $error = "Invalid security token. Please refresh the page and try again.";
        $form_valid = false;
    }
    
    // Validate required fields
    $required_fields = [
        'shipping_name' => 'Full Name',
        'shipping_phone' => 'Phone Number',
        'shipping_house_number' => 'House Number',
        'shipping_street_name' => 'Street Name',
        'shipping_suburb' => 'Suburb',
        'shipping_city' => 'City',
        'shipping_province' => 'Province',
        'shipping_postal_code' => 'Postal Code'
    ];
    
    foreach ($required_fields as $field => $label) {
        if (empty(trim($_POST[$field] ?? ''))) {
            $validation_errors[] = "$label is required.";
            $form_valid = false;
        }
    }
    
    // Validate phone number
    if (!empty($_POST['shipping_phone'])) {
        $phone = preg_replace('/\D/', '', $_POST['shipping_phone']);
        if (strlen($phone) < 10 || strlen($phone) > 15) {
            $validation_errors[] = "Please enter a valid phone number (10-15 digits).";
            $form_valid = false;
        }
    }
    
    // Validate postal code
    if (!empty($_POST['shipping_postal_code'])) {
        $postal_code = preg_replace('/\D/', '', $_POST['shipping_postal_code']);
        if (strlen($postal_code) !== 4) {
            $validation_errors[] = "Please enter a valid 4-digit postal code.";
            $form_valid = false;
        }
    }
    
    // Validate terms acceptance
    if (!isset($_POST['terms']) || $_POST['terms'] !== 'on') {
        $validation_errors[] = "You must accept the Terms and Conditions to continue.";
        $form_valid = false;
    }
    
    if ($form_valid && empty($validation_errors)) {
        try {
            // Start transaction
            $conn->beginTransaction();
            
            // Revalidate stock before proceeding
            foreach ($cartItems as $item) {
                $stmt = $conn->prepare("
                    SELECT quantity FROM listings 
                    WHERE id = ? AND status = 'active' AND is_active = 1
                ");
                $stmt->execute([$item['listing_id']]);
                $current_stock = $stmt->fetchColumn();
                
                if ($current_stock < $item['quantity']) {
                    throw new Exception("Insufficient stock for '{$item['listing_name']}'. Only $current_stock available.");
                }
            }
            
            // Calculate shipping address array
            $shipping_address_array = [
                'contact_name' => sanitizeInput($_POST['shipping_name']),
                'contact_phone' => sanitizeInput($_POST['shipping_phone']),
                'unit_number' => sanitizeInput($_POST['shipping_unit_number'] ?? ''),
                'complex_name' => sanitizeInput($_POST['shipping_complex_name'] ?? ''),
                'house_number' => sanitizeInput($_POST['shipping_house_number']),
                'street_name' => sanitizeInput($_POST['shipping_street_name']),
                'suburb' => sanitizeInput($_POST['shipping_suburb']),
                'city' => sanitizeInput($_POST['shipping_city']),
                'province' => sanitizeInput($_POST['shipping_province']),
                'postal_code' => sanitizeInput($_POST['shipping_postal_code']),
                'country' => 'South Africa'
            ];
            
            // Calculate billing address array
            $billing_address_array = [];
            if (isset($_POST['billing_same']) && $_POST['billing_same'] === 'on') {
                $billing_address_array = $shipping_address_array;
                $billing_address_array['same_as_shipping'] = true;
            } else {
                $billing_address_array = [
                    'same_as_shipping' => false,
                    'contact_name' => sanitizeInput($_POST['billing_name'] ?? $shipping_address_array['contact_name']),
                    'contact_phone' => sanitizeInput($_POST['billing_phone'] ?? $shipping_address_array['contact_phone']),
                    'unit_number' => sanitizeInput($_POST['billing_unit_number'] ?? ''),
                    'complex_name' => sanitizeInput($_POST['billing_complex_name'] ?? ''),
                    'house_number' => sanitizeInput($_POST['billing_house_number'] ?? $shipping_address_array['house_number']),
                    'street_name' => sanitizeInput($_POST['billing_street_name'] ?? $shipping_address_array['street_name']),
                    'suburb' => sanitizeInput($_POST['billing_suburb'] ?? $shipping_address_array['suburb']),
                    'city' => sanitizeInput($_POST['billing_city'] ?? $shipping_address_array['city']),
                    'province' => sanitizeInput($_POST['billing_province'] ?? $shipping_address_array['province']),
                    'postal_code' => sanitizeInput($_POST['billing_postal_code'] ?? $shipping_address_array['postal_code']),
                    'country' => 'South Africa'
                ];
            }
            
            // 1. Create main order - Generate unique order number
            do {
                $order_number = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid('', true), -8));
                
                // Check if order number already exists
                $stmt_check = $conn->prepare("SELECT COUNT(*) FROM orders WHERE order_number = :order_number");
                $stmt_check->execute(['order_number' => $order_number]);
                $exists = $stmt_check->fetchColumn();
            } while ($exists > 0);

            // Get the first seller ID for the main order record
            $sellerIds = array_keys($itemsBySeller);
            $firstSellerId = $sellerIds[0] ?? 0;

            if ($firstSellerId == 0) {
                throw new Exception("No valid seller found in cart.");
            }
            
            // Create main order record
            $stmt = $conn->prepare("
                INSERT INTO orders (
                    order_number, 
                    buyer_id, 
                    seller_id,
                    total_amount, 
                    subtotal, 
                    vat_amount, 
                    shipping_amount, 
                    discount_amount,
                    status, 
                    payment_status,
                    payment_method, 
                    shipping_address, 
                    billing_address, 
                    notes
                ) VALUES (
                    :order_number, 
                    :buyer_id, 
                    :seller_id,
                    :total_amount, 
                    :subtotal, 
                    :vat_amount, 
                    :shipping_amount, 
                    :discount_amount,
                    'pending', 
                    'pending',
                    :payment_method, 
                    :shipping_address, 
                    :billing_address, 
                    :notes
                )
            ");

            $stmt->execute([
                'order_number' => $order_number,
                'buyer_id' => $user_id,
                'seller_id' => $firstSellerId,
                'total_amount' => $total,
                'subtotal' => $subtotal,
                'vat_amount' => $vat_amount,
                'shipping_amount' => $shipping_estimate,
                'discount_amount' => $discount_amount,
                'payment_method' => sanitizeInput($_POST['payment_method'] ?? 'cash'),
                'shipping_address' => json_encode($shipping_address_array, JSON_UNESCAPED_UNICODE),
                'billing_address' => json_encode($billing_address_array, JSON_UNESCAPED_UNICODE),
                'notes' => sanitizeInput($_POST['order_notes'] ?? '')
            ]);
            
            $main_order_id = $conn->lastInsertId();
            
            if (!$main_order_id) {
                throw new Exception("Order creation failed - no ID returned");
            }
            
            // 2. Create order items for each seller
            $vat_rate = 0.15;
            foreach ($itemsBySeller as $sellerId => $sellerData) {
                foreach ($sellerData['items'] as $item) {
                    $item_total = $item['price'] * $item['quantity'];
                    $item_vat = $item_total * $vat_rate;
                    
                    // Insert order item
                    $stmt_item = $conn->prepare("
                        INSERT INTO order_items (
                            order_id, 
                            listing_id, 
                            seller_id,
                            quantity, 
                            unit_price, 
                            total_price,
                            vat_amount
                        ) VALUES (
                            :order_id, 
                            :listing_id, 
                            :seller_id,
                            :quantity, 
                            :unit_price, 
                            :total_price,
                            :vat_amount
                        )
                    ");
                    
                    $stmt_item->execute([
                        'order_id' => $main_order_id,
                        'listing_id' => $item['listing_id'],
                        'seller_id' => $sellerId,
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['price'],
                        'total_price' => $item_total,
                        'vat_amount' => $item_vat
                    ]);
                    
                    // Update listing stock
                    $stmt_update = $conn->prepare("
                        UPDATE listings 
                        SET quantity = quantity - :quantity,
                            updated_at = NOW()
                        WHERE id = :listing_id
                    ");
                    
                    $stmt_update->execute([
                        'quantity' => $item['quantity'],
                        'listing_id' => $item['listing_id']
                    ]);
                }
            }
            
            // 3. Save shipping address to addresses table if checkbox is checked
            if (isset($_POST['save_shipping_address']) && $_POST['save_shipping_address'] === 'on') {
                // Check if address already exists
                $stmt_check = $conn->prepare("
                    SELECT id FROM addresses 
                    WHERE user_id = :user_id 
                      AND contact_name = :contact_name
                      AND contact_phone = :contact_phone
                      AND unit_number = :unit_number
                      AND complex_name = :complex_name
                      AND house_number = :house_number
                      AND street_name = :street_name
                      AND suburb = :suburb
                      AND city = :city
                      AND province = :province
                      AND postal_code = :postal_code
                    LIMIT 1
                ");
                
                $stmt_check->execute([
                    'user_id' => $user_id,
                    'contact_name' => $shipping_address_array['contact_name'],
                    'contact_phone' => $shipping_address_array['contact_phone'],
                    'unit_number' => $shipping_address_array['unit_number'],
                    'complex_name' => $shipping_address_array['complex_name'],
                    'house_number' => $shipping_address_array['house_number'],
                    'street_name' => $shipping_address_array['street_name'],
                    'suburb' => $shipping_address_array['suburb'],
                    'city' => $shipping_address_array['city'],
                    'province' => $shipping_address_array['province'],
                    'postal_code' => $shipping_address_array['postal_code']
                ]);
                
                if (!$stmt_check->fetch()) {
                    // Address doesn't exist, insert it
                    $stmt_addr = $conn->prepare("
                        INSERT INTO addresses (
                            user_id, 
                            address_type, 
                            contact_name, 
                            contact_phone,
                            unit_number, 
                            complex_name, 
                            house_number, 
                            street_name,
                            suburb, 
                            city, 
                            province, 
                            postal_code, 
                            country,
                            is_default
                        ) VALUES (
                            :user_id, 
                            'shipping', 
                            :contact_name, 
                            :contact_phone,
                            :unit_number, 
                            :complex_name, 
                            :house_number, 
                            :street_name,
                            :suburb, 
                            :city, 
                            :province, 
                            :postal_code, 
                            :country,
                            :is_default
                        )
                    ");
                    
                    // Check if user has any addresses
                    $stmt_count = $conn->prepare("SELECT COUNT(*) FROM addresses WHERE user_id = ?");
                    $stmt_count->execute([$user_id]);
                    $address_count = $stmt_count->fetchColumn();
                    
                    $stmt_addr->execute([
                        'user_id' => $user_id,
                        'contact_name' => $shipping_address_array['contact_name'],
                        'contact_phone' => $shipping_address_array['contact_phone'],
                        'unit_number' => $shipping_address_array['unit_number'],
                        'complex_name' => $shipping_address_array['complex_name'],
                        'house_number' => $shipping_address_array['house_number'],
                        'street_name' => $shipping_address_array['street_name'],
                        'suburb' => $shipping_address_array['suburb'],
                        'city' => $shipping_address_array['city'],
                        'province' => $shipping_address_array['province'],
                        'postal_code' => $shipping_address_array['postal_code'],
                        'country' => 'South Africa',
                        'is_default' => ($address_count == 0) ? 1 : 0
                    ]);
                }
            }
            
            // 4. Clear user's cart
            $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = :user_id");
            $stmt->execute(['user_id' => $user_id]);
            
            // 5. Update user's cart count in session
            $_SESSION['cart_count'] = 0;
            
            // 6. Update user's basic info in users table
            $stmt = $conn->prepare("
                UPDATE users 
                SET phone = :phone,
                    city = :city,
                    province = :province,
                    suburb = :suburb,
                    street_address = :street_address,
                    postal_code = :postal_code,
                    updated_at = NOW()
                WHERE id = :user_id
            ");
            
            $stmt->execute([
                'phone' => sanitizeInput($_POST['shipping_phone']),
                'city' => sanitizeInput($_POST['shipping_city']),
                'province' => sanitizeInput($_POST['shipping_province']),
                'suburb' => sanitizeInput($_POST['shipping_suburb']),
                'street_address' => sanitizeInput(trim(($_POST['shipping_house_number'] ?? '') . ' ' . ($_POST['shipping_street_name'] ?? ''))),
                'postal_code' => sanitizeInput($_POST['shipping_postal_code']),
                'user_id' => $user_id
            ]);
            
            // Commit transaction
            $conn->commit();
            
            // Success - redirect to order confirmation
            $_SESSION['checkout_confirmation'] = [
                'order_number' => $order_number,
                'order_id' => $main_order_id,
                'total' => $total,
                'seller_count' => count($itemsBySeller)
            ];
            
            header('Location: ' . BASE_URL . '/pages/order-confirmation.php?id=' . $main_order_id);
            exit;
            
        } catch (Exception $e) {
            // Rollback on error
            if (isset($conn)) {
                $conn->rollBack();
            }
            
            $error = "Checkout failed: " . $e->getMessage();
            error_log("Checkout error: " . $e->getMessage());
            
        }
    } else {
        $error = "Please fix the errors below:";
    }
}

// Generate CSRF token
$csrfToken = generateCSRFToken('checkout');

// Page title
$title = 'Checkout - PeerCart';

// Include header
includePartial('header', ['title' => $title]);
?>

<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/pages/checkout.css">

<div class="checkout-page">
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay" style="display: none;">
        <div class="loading-spinner"></div>
        <p>Processing your order...</p>
    </div>
    
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
            
            <?php if (!empty($validation_errors)): ?>
                <div class="validation-errors">
                    <strong>Please fix the following errors:</strong>
                    <ul>
                        <?php foreach ($validation_errors as $validation_error): ?>
                            <li><?= htmlspecialchars($validation_error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <!-- Checkout Form -->
            <form method="POST" id="checkoutForm" novalidate>
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                
                <!-- Step 1: Shipping Address -->
                <div class="checkout-step">
                    <div class="step-header">
                        <div class="step-number">1</div>
                        <h3 class="step-title">Shipping Address</h3>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="shipping_name" class="required">Full Name</label>
                            <input type="text" id="shipping_name" name="shipping_name" 
                                   value="<?= htmlspecialchars($_POST['shipping_name'] ?? ($user_address['contact_name'] ?? ($user['name'] ?? '') . ' ' . ($user['surname'] ?? ''))) ?>" 
                                   required
                                   data-validate="text">
                            <div class="validation-error" id="shipping_name_error"></div>
                        </div>
                        
                        <div class="form-group">
                            <label for="shipping_phone" class="required">Phone Number</label>
                            <input type="tel" id="shipping_phone" name="shipping_phone" 
                                   value="<?= htmlspecialchars($_POST['shipping_phone'] ?? $user_address['contact_phone'] ?? $user['phone'] ?? '') ?>" 
                                   required
                                   data-validate="phone">
                            <div class="validation-error" id="shipping_phone_error"></div>
                        </div>
                    </div>
                    
                    <div class="address-section">
                        <div class="address-section-title">
                            <i class="fas fa-home"></i> Street Address Details
                        </div>
                        
                        <div class="form-row-2">
                            <div class="form-group">
                                <label for="shipping_unit_number">Unit/Suite Number (Optional)</label>
                                <input type="text" id="shipping_unit_number" name="shipping_unit_number" 
                                       value="<?= htmlspecialchars($_POST['shipping_unit_number'] ?? $user_address['unit_number'] ?? '') ?>"
                                       placeholder="e.g., Unit 12, Apt 3B">
                            </div>
                            
                            <div class="form-group">
                                <label for="shipping_complex_name">Complex/Building Name (Optional)</label>
                                <input type="text" id="shipping_complex_name" name="shipping_complex_name" 
                                       value="<?= htmlspecialchars($_POST['shipping_complex_name'] ?? $user_address['complex_name'] ?? '') ?>"
                                       placeholder="e.g., The Pines, Sandton City">
                            </div>
                        </div>
                        
                        <div class="form-row-2">
                            <div class="form-group">
                                <label for="shipping_house_number" class="required">House/Stand Number</label>
                                <input type="text" id="shipping_house_number" name="shipping_house_number" 
                                       value="<?= htmlspecialchars($_POST['shipping_house_number'] ?? $user_address['house_number'] ?? '') ?>" 
                                       required
                                       placeholder="e.g., 45, Erf 123"
                                       data-validate="text">
                                <div class="validation-error" id="shipping_house_number_error"></div>
                            </div>
                            
                            <div class="form-group">
                                <label for="shipping_street_name" class="required">Street Name</label>
                                <input type="text" id="shipping_street_name" name="shipping_street_name" 
                                       value="<?= htmlspecialchars($_POST['shipping_street_name'] ?? $user_address['street_name'] ?? '') ?>" 
                                       required
                                       placeholder="e.g., Jan Smuts Avenue"
                                       data-validate="text">
                                <div class="validation-error" id="shipping_street_name_error"></div>
                            </div>
                        </div>
                        
                        <div class="form-row-2">
                            <div class="form-group">
                                <label for="shipping_suburb" class="required">Suburb</label>
                                <input type="text" id="shipping_suburb" name="shipping_suburb" 
                                       value="<?= htmlspecialchars($_POST['shipping_suburb'] ?? $user_address['suburb'] ?? $user['suburb'] ?? '') ?>" 
                                       required
                                       placeholder="e.g., Rosebank, Sandton"
                                       data-validate="text">
                                <div class="validation-error" id="shipping_suburb_error"></div>
                            </div>
                            
                            <div class="form-group">
                                <label for="shipping_city" class="required">City</label>
                                <input type="text" id="shipping_city" name="shipping_city" 
                                       value="<?= htmlspecialchars($_POST['shipping_city'] ?? $user_address['city'] ?? $user['city'] ?? '') ?>" 
                                       required
                                       placeholder="e.g., Johannesburg"
                                       data-validate="text">
                                <div class="validation-error" id="shipping_city_error"></div>
                            </div>
                        </div>
                        
                        <div class="form-row-2">
                            <div class="form-group">
                                <label for="shipping_province" class="required">Province</label>
                                <select id="shipping_province" name="shipping_province" required>
                                    <option value="">Select Province</option>
                                    <?php
                                    $provinces = [
                                        'Eastern Cape', 'Free State', 'Gauteng', 'KwaZulu-Natal',
                                        'Limpopo', 'Mpumalanga', 'North West', 'Northern Cape', 'Western Cape'
                                    ];
                                    $current_province = $_POST['shipping_province'] ?? $user_address['province'] ?? $user['province'] ?? '';
                                    foreach ($provinces as $province): ?>
                                        <option value="<?= $province ?>" <?= $current_province === $province ? 'selected' : '' ?>>
                                            <?= $province ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="validation-error" id="shipping_province_error"></div>
                            </div>
                            
                            <div class="form-group">
                                <label for="shipping_postal_code" class="required">Postal Code</label>
                                <input type="text" id="shipping_postal_code" name="shipping_postal_code" 
                                       value="<?= htmlspecialchars($_POST['shipping_postal_code'] ?? $user_address['postal_code'] ?? $user['postal_code'] ?? '') ?>" 
                                       required
                                       placeholder="e.g., 2196"
                                       maxlength="4"
                                       data-validate="postal_code">
                                <div class="validation-error" id="shipping_postal_code_error"></div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="shipping_country" class="required">Country</label>
                            <input type="text" id="shipping_country" name="shipping_country" 
                                   value="South Africa" 
                                   readonly
                                   style="background-color: #f5f5f5;">
                        </div>
                        
                        <!-- Save address checkbox -->
                        <div class="checkbox-group">
                            <input type="checkbox" id="save_shipping_address" name="save_shipping_address" checked>
                            <label for="save_shipping_address">Save this address for future orders</label>
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
                        <div class="form-row">
                            <div class="form-group">
                                <label for="billing_name">Billing Name</label>
                                <input type="text" id="billing_name" name="billing_name" 
                                       value="<?= htmlspecialchars($_POST['billing_name'] ?? '') ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="billing_phone">Billing Phone</label>
                                <input type="tel" id="billing_phone" name="billing_phone" 
                                       value="<?= htmlspecialchars($_POST['billing_phone'] ?? '') ?>">
                            </div>
                        </div>
                        
                        <div class="address-section">
                            <div class="address-section-title">
                                <i class="fas fa-file-invoice-dollar"></i> Billing Address Details
                            </div>
                            
                            <div class="form-row-2">
                                <div class="form-group">
                                    <label for="billing_unit_number">Unit/Suite Number (Optional)</label>
                                    <input type="text" id="billing_unit_number" name="billing_unit_number" 
                                           value="<?= htmlspecialchars($_POST['billing_unit_number'] ?? '') ?>"
                                           placeholder="e.g., Unit 12">
                                </div>
                                
                                <div class="form-group">
                                    <label for="billing_complex_name">Complex/Building Name (Optional)</label>
                                    <input type="text" id="billing_complex_name" name="billing_complex_name" 
                                           value="<?= htmlspecialchars($_POST['billing_complex_name'] ?? '') ?>"
                                           placeholder="e.g., The Pines">
                                </div>
                            </div>
                            
                            <div class="form-row-2">
                                <div class="form-group">
                                    <label for="billing_house_number">House/Stand Number</label>
                                    <input type="text" id="billing_house_number" name="billing_house_number" 
                                           value="<?= htmlspecialchars($_POST['billing_house_number'] ?? '') ?>" 
                                           placeholder="e.g., 45">
                                </div>
                                
                                <div class="form-group">
                                    <label for="billing_street_name">Street Name</label>
                                    <input type="text" id="billing_street_name" name="billing_street_name" 
                                           value="<?= htmlspecialchars($_POST['billing_street_name'] ?? '') ?>" 
                                           placeholder="e.g., Jan Smuts Avenue">
                                </div>
                            </div>
                            
                            <div class="form-row-2">
                                <div class="form-group">
                                    <label for="billing_suburb">Suburb</label>
                                    <input type="text" id="billing_suburb" name="billing_suburb" 
                                           value="<?= htmlspecialchars($_POST['billing_suburb'] ?? '') ?>" 
                                           placeholder="e.g., Rosebank">
                                </div>
                                
                                <div class="form-group">
                                    <label for="billing_city">City</label>
                                    <input type="text" id="billing_city" name="billing_city" 
                                           value="<?= htmlspecialchars($_POST['billing_city'] ?? '') ?>" 
                                           placeholder="e.g., Johannesburg">
                                </div>
                            </div>
                            
                            <div class="form-row-2">
                                <div class="form-group">
                                    <label for="billing_province">Province</label>
                                    <select id="billing_province" name="billing_province">
                                        <option value="">Select Province</option>
                                        <?php
                                        $current_billing_province = $_POST['billing_province'] ?? '';
                                        foreach ($provinces as $province): ?>
                                            <option value="<?= $province ?>" <?= $current_billing_province === $province ? 'selected' : '' ?>>
                                                <?= $province ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="billing_postal_code">Postal Code</label>
                                    <input type="text" id="billing_postal_code" name="billing_postal_code" 
                                           value="<?= htmlspecialchars($_POST['billing_postal_code'] ?? '') ?>" 
                                           placeholder="e.g., 2196"
                                           maxlength="4">
                                </div>
                            </div>
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
                            <input type="radio" id="payment_card" name="payment_method" value="card" checked required>
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
                        
                        <div class="payment-method">
                            <input type="radio" id="payment_mobile" name="payment_method" value="mobile">
                            <label for="payment_mobile">
                                <i class="fas fa-mobile-alt"></i>
                                <span>Mobile Payment</span>
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
                                  placeholder="Special instructions, delivery preferences, etc."><?= htmlspecialchars($_POST['order_notes'] ?? '') ?></textarea>
                    </div>
                </div>
                
                <!-- Terms and Conditions -->
                <div class="checkout-step" style="background: #f8f9fa;">
                    <div class="checkbox-group">
                        <input type="checkbox" id="terms" name="terms" required>
                        <label for="terms" class="required">
                            I agree to the <a href="<?= BASE_URL ?>/pages/terms.php" target="_blank" rel="noopener">Terms and Conditions</a> 
                            and <a href="<?= BASE_URL ?>/pages/privacy.php" target="_blank" rel="noopener">Privacy Policy</a>
                        </label>
                    </div>
                    <div class="validation-error" id="terms_error"></div>
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
                                <i class="fas fa-store"></i><?= htmlspecialchars($sellerData['seller_name']) ?>
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
                    
                    <?php if ($discount_amount > 0): ?>
                    <div class="total-row" style="color: #28a745;">
                        <span>Discount</span>
                        <span>-R<?= number_format($discount_amount, 2) ?></span>
                    </div>
                    <?php endif; ?>
                    
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
            
            <!-- Need Help -->
            <div style="background: #f8f9fa; border-radius: 8px; padding: 15px; margin-top: 20px;">
                <h5 style="margin-bottom: 10px;"><i class="fas fa-question-circle me-2"></i>Need Help?</h5>
                <p style="color: #666; font-size: 0.9em; margin-bottom: 10px;">
                    Call us: <strong>0800 123 456</strong><br>
                    Email: <strong>support@peercart.co.za</strong>
                </p>
                <a href="<?= BASE_URL ?>/pages/contact.php" class="btn btn-sm btn-outline-primary">Contact Support</a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Billing address toggle
    const billingSame = document.getElementById('billing_same');
    const billingFields = document.getElementById('billing-fields');
    
    billingSame.addEventListener('change', function() {
        billingFields.style.display = this.checked ? 'none' : 'block';
        
        // Clear validation errors for billing fields when hidden
        if (this.checked) {
            document.querySelectorAll('#billing-fields input, #billing-fields select').forEach(field => {
                field.classList.remove('invalid');
                const errorElement = document.getElementById(field.id + '_error');
                if (errorElement) {
                    errorElement.style.display = 'none';
                    errorElement.textContent = '';
                }
            });
        }
    });
    
    // Form validation
    const form = document.getElementById('checkoutForm');
    const submitBtn = document.getElementById('submitBtn');
    const loadingOverlay = document.getElementById('loadingOverlay');
    
    // Validation functions
    const validators = {
        text: (value) => value.trim().length >= 2,
        phone: (value) => /^[0-9+\-\s()]{10,15}$/.test(value),
        address: (value) => value.trim().length >= 5,
        postal_code: (value) => /^[0-9]{4}$/.test(value.replace(/\s/g, ''))
    };
    
    // Real-time validation
    form.querySelectorAll('[data-validate]').forEach(input => {
        const validatorType = input.getAttribute('data-validate');
        const errorElement = document.getElementById(input.id + '_error');
        
        input.addEventListener('blur', function() {
            validateField(this, validatorType, errorElement);
        });
        
        input.addEventListener('input', function() {
            if (this.classList.contains('invalid')) {
                validateField(this, validatorType, errorElement);
            }
        });
    });
    
    // Phone number auto-format
    const shippingPhone = document.getElementById('shipping_phone');
    const billingPhone = document.getElementById('billing_phone');
    
    [shippingPhone, billingPhone].forEach(phoneInput => {
        if (phoneInput) {
            phoneInput.addEventListener('input', function() {
                let value = this.value.replace(/\D/g, '');
                if (value.length > 2 && value.length <= 5) {
                    value = value.slice(0, 3) + ' ' + value.slice(3);
                } else if (value.length > 5 && value.length <= 9) {
                    value = value.slice(0, 3) + ' ' + value.slice(3, 6) + ' ' + value.slice(6);
                } else if (value.length > 9) {
                    value = value.slice(0, 3) + ' ' + value.slice(3, 6) + ' ' + value.slice(6, 10);
                }
                this.value = value;
            });
        }
    });
    
    // Postal code formatting
    const postalCodes = document.querySelectorAll('input[name*="postal_code"]');
    postalCodes.forEach(postalCode => {
        postalCode.addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '').slice(0, 4);
        });
    });
    
    // Copy shipping to billing when "same as shipping" is checked
    billingSame.addEventListener('change', function() {
        if (this.checked) {
            // Copy values from shipping to billing
            const shippingFields = {
                'shipping_house_number': 'billing_house_number',
                'shipping_street_name': 'billing_street_name',
                'shipping_unit_number': 'billing_unit_number',
                'shipping_complex_name': 'billing_complex_name',
                'shipping_suburb': 'billing_suburb',
                'shipping_city': 'billing_city',
                'shipping_province': 'billing_province',
                'shipping_postal_code': 'billing_postal_code',
                'shipping_name': 'billing_name',
                'shipping_phone': 'billing_phone'
            };
            
            for (const [shippingId, billingId] of Object.entries(shippingFields)) {
                const shippingField = document.getElementById(shippingId);
                const billingField = document.getElementById(billingId);
                
                if (shippingField && billingField) {
                    if (billingField.tagName === 'SELECT') {
                        billingField.value = shippingField.value;
                    } else {
                        billingField.value = shippingField.value;
                    }
                }
            }
        }
    });
    
    // Form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validate all fields
        let isValid = true;
        
        // Required fields validation
        form.querySelectorAll('[required]').forEach(field => {
            if (field.type === 'checkbox') {
                if (!field.checked) {
                    isValid = false;
                    const errorElement = document.getElementById(field.id + '_error');
                    if (errorElement) {
                        showError(field, 'This field is required', errorElement);
                    }
                }
            } else if (field.value.trim() === '') {
                isValid = false;
                const errorElement = document.getElementById(field.id + '_error');
                showError(field, 'This field is required', errorElement);
            }
        });
        
        // Custom validation for fields with data-validate
        form.querySelectorAll('[data-validate]').forEach(field => {
            const validatorType = field.getAttribute('data-validate');
            const errorElement = document.getElementById(field.id + '_error');
            if (!validateField(field, validatorType, errorElement)) {
                isValid = false;
            }
        });
        
        if (!isValid) {
            // Scroll to first error
            const firstError = form.querySelector('.invalid');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            return;
        }
        
        // Show loading overlay
        loadingOverlay.style.display = 'flex';
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Processing...';
        
        // Submit form
        this.submit();
    });
    
    // Field validation helper
    function validateField(field, validatorType, errorElement) {
        const value = field.value.trim();
        let isValid = true;
        let errorMessage = '';
        
        if (field.hasAttribute('required') && value === '') {
            isValid = false;
            errorMessage = 'This field is required';
        } else if (value !== '' && validators[validatorType]) {
            if (!validators[validatorType](value)) {
                isValid = false;
                switch(validatorType) {
                    case 'phone':
                        errorMessage = 'Please enter a valid phone number (10-15 digits)';
                        break;
                    case 'postal_code':
                        errorMessage = 'Please enter a valid 4-digit postal code';
                        break;
                    case 'address':
                        errorMessage = 'Address must be at least 5 characters';
                        break;
                    default:
                        errorMessage = 'Please enter a valid value';
                }
            }
        }
        
        // Update UI
        if (isValid) {
            field.classList.remove('invalid');
            field.classList.add('valid');
            if (errorElement) {
                errorElement.style.display = 'none';
                errorElement.textContent = '';
            }
        } else {
            field.classList.remove('valid');
            field.classList.add('invalid');
            if (errorElement) {
                errorElement.style.display = 'block';
                errorElement.textContent = errorMessage;
            }
        }
        
        return isValid;
    }
    
    // Show error function
    function showError(field, message, errorElement = null) {
        field.classList.add('invalid');
        if (errorElement) {
            errorElement.style.display = 'block';
            errorElement.textContent = message;
        }
    }
    
    // Auto-save form data to localStorage
    const formDataKey = 'checkout_form_data';
    const sensitiveFields = ['payment_method', 'csrf_token', 'terms', 'save_shipping_address'];
    
    // Save form data on change
    form.addEventListener('input', debounce(function() {
        const formData = {};
        new FormData(form).forEach((value, key) => {
            if (!sensitiveFields.includes(key)) {
                formData[key] = value;
            }
        });
        localStorage.setItem(formDataKey, JSON.stringify(formData));
    }, 500));
    
    // Load saved form data
    const savedData = localStorage.getItem(formDataKey);
    if (savedData) {
        try {
            const formData = JSON.parse(savedData);
            Object.keys(formData).forEach(key => {
                const field = form.querySelector(`[name="${key}"]`);
                if (field && !sensitiveFields.includes(key)) {
                    if (field.type === 'checkbox' || field.type === 'radio') {
                        field.checked = formData[key] === 'on' || formData[key] === field.value;
                    } else {
                        field.value = formData[key];
                    }
                }
            });
        } catch (e) {
            console.error('Error loading saved form data:', e);
        }
    }
    
    // Clear saved data on successful form submission
    form.addEventListener('submit', function() {
        localStorage.removeItem(formDataKey);
    });
    
    // Prevent double submission
    let formSubmitted = false;
    form.addEventListener('submit', function(e) {
        if (formSubmitted) {
            e.preventDefault();
            return;
        }
        formSubmitted = true;
    });
    
    // Debounce function for performance
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
});
</script>

<?php includePartial('footer'); ?>