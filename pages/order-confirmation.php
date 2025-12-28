<?php
// order-confirmation.php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . '/pages/auth.php');
    exit;
}

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($order_id <= 0) {
    header('Location: ' . BASE_URL . '/pages/cart.php');
    exit;
}

$order = null;
$order_items = [];
$error = null;

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Get order details
    $stmt = $conn->prepare("
        SELECT o.*, u.name as user_name, u.email as user_email
        FROM orders o
        JOIN users u ON o.buyer_id = u.id
        WHERE o.id = ? AND o.buyer_id = ?
    ");
    $stmt->execute([$order_id, $_SESSION['user_id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        // Check if order exists but user doesn't have permission
        $stmt = $conn->prepare("SELECT id FROM orders WHERE id = ?");
        $stmt->execute([$order_id]);
        $order_exists = $stmt->fetch();
        
        if ($order_exists) {
            $error = "You don't have permission to view this order.";
        } else {
            header('Location: ' . BASE_URL . '/pages/cart.php');
            exit;
        }
    } else {
        // Get order items
        $stmt = $conn->prepare("
            SELECT oi.*, l.name as listing_name, l.image, u.name as seller_name
            FROM order_items oi
            JOIN listings l ON oi.listing_id = l.id
            JOIN users u ON oi.seller_id = u.id
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$order_id]);
        $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Parse JSON addresses if they exist
        if (!empty($order['shipping_address'])) {
            $shipping_address = json_decode($order['shipping_address'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $order['shipping_address_parsed'] = $shipping_address;
            }
        }
        
        if (!empty($order['billing_address'])) {
            $billing_address = json_decode($order['billing_address'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $order['billing_address_parsed'] = $billing_address;
            }
        }
    }
    
} catch (Exception $e) {
    $error = "Error loading order details: " . $e->getMessage();
    error_log("Order confirmation error: " . $e->getMessage());
}

// Include header
includePartial('header', [
    'title' => 'Order Confirmation - PeerCart',
    'additionalStyles' => []
]);
?>

<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/pages/order-confirmation.css">

<div class="confirmation-page">
    <div class="confirmation-bg-blob"></div>
    <div class="confirmation-bg-blob"></div>
    
    <div class="confirmation-container">
        <div class="confirmation-card">
            <?php if ($error): ?>
                <!-- Error Message -->
                <div class="error-animation">
                    <div class="error-circle">
                        <i class="fas fa-exclamation"></i>
                    </div>
                </div>
                
                <h1 class="confirmation-title error-text">Order Error</h1>
                
                <p class="confirmation-message error-message">
                    <?= htmlspecialchars($error) ?>
                </p>
                
                <div class="action-buttons-grid">
                    <a href="<?= BASE_URL ?>/pages/orders.php" class="action-btn action-btn-primary">
                        <i class="fas fa-shopping-bag"></i> View My Orders
                    </a>
                    <a href="<?= BASE_URL ?>/" class="action-btn action-btn-outline">
                        <i class="fas fa-home"></i> Go Home
                    </a>
                </div>
                
            <?php elseif ($order): ?>
                <!-- Success Animation -->
                <div class="success-animation">
                    <div class="success-circle">
                        <i class="fas fa-check"></i>
                    </div>
                </div>
                
                <h1 class="confirmation-title">Order Confirmed!</h1>
                
                <p class="confirmation-message">
                    Thank you for your purchase, <strong><?= htmlspecialchars($_SESSION['user_name'] ?? 'Customer') ?></strong>!
                    <?php if (!empty($order['user_email'])): ?>
                        We've sent a confirmation email to <strong><?= htmlspecialchars($order['user_email']) ?></strong>
                    <?php endif; ?>
                </p>
                
                <div class="order-number-badge">
                    ORDER #<?= htmlspecialchars($order['order_number'] ?? 'N/A') ?>
                </div>
                
                <!-- Order Summary -->
                <div class="order-summary-grid">
                    <div class="summary-item">
                        <div class="summary-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <span class="summary-label">Order Date</span>
                        <span class="summary-value">
                            <?= !empty($order['created_at']) ? date('d M Y', strtotime($order['created_at'])) : 'N/A' ?>
                        </span>
                    </div>
                    
                    <div class="summary-item">
                        <div class="summary-icon">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <span class="summary-label">Total Amount</span>
                        <span class="summary-value">
                            R<?= number_format($order['total_amount'] ?? 0, 2) ?>
                        </span>
                    </div>
                    
                    <div class="summary-item">
                        <div class="summary-icon">
                            <i class="fas fa-truck"></i>
                        </div>
                        <span class="summary-label">Status</span>
                        <span class="summary-value status-badge status-<?= strtolower($order['status'] ?? 'pending') ?>">
                            <?= ucfirst($order['status'] ?? 'Pending') ?>
                        </span>
                    </div>
                </div>
                
                <!-- Items List -->
                <?php if(!empty($order_items)): ?>
                <div class="order-items-section">
                    <h3 class="section-title">Order Items (<?= count($order_items) ?>)</h3>
                    <?php foreach($order_items as $item): ?>
                    <div class="order-item-card">
                        <img src="<?= getListingImage($item['image']) ?>" 
                             alt="<?= htmlspecialchars($item['listing_name']) ?>"
                             class="order-item-image"
                             onerror="this.src='<?= BASE_URL ?>/assets/images/products/default-product.png'">
                        <div class="order-item-details">
                            <div class="order-item-name">
                                <?= htmlspecialchars($item['listing_name']) ?>
                            </div>
                            <div class="order-item-seller">
                                <i class="fas fa-store"></i> Sold by: <?= htmlspecialchars($item['seller_name']) ?>
                            </div>
                            <div class="order-item-meta">
                                <span class="order-item-price">
                                    <i class="fas fa-tag"></i> R<?= number_format($item['unit_price'] ?? $item['price'] ?? 0, 2) ?> × <?= $item['quantity'] ?>
                                </span>
                                <span class="order-item-total">
                                    <i class="fas fa-calculator"></i> Total: R<?= number_format($item['total_price'] ?? ($item['unit_price'] * $item['quantity']), 2) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <!-- Order Details -->
                <div class="details-grid">
                    <!-- Shipping Address -->
                    <?php if(isset($order['shipping_address_parsed'])): ?>
                    <div class="detail-card">
                        <h4 class="detail-title">
                            <i class="fas fa-shipping-fast"></i> Shipping Address
                        </h4>
                        <div class="detail-content">
                            <p><strong><?= htmlspecialchars($order['shipping_address_parsed']['contact_name'] ?? '') ?></strong></p>
                            <p>
                                <?= htmlspecialchars($order['shipping_address_parsed']['house_number'] ?? '') ?> 
                                <?= htmlspecialchars($order['shipping_address_parsed']['street_name'] ?? '') ?>
                            </p>
                            <?php if(!empty($order['shipping_address_parsed']['unit_number'])): ?>
                                <p>Unit <?= htmlspecialchars($order['shipping_address_parsed']['unit_number']) ?></p>
                            <?php endif; ?>
                            <p>
                                <?= htmlspecialchars($order['shipping_address_parsed']['suburb'] ?? '') ?>, 
                                <?= htmlspecialchars($order['shipping_address_parsed']['city'] ?? '') ?>
                            </p>
                            <p>
                                <?= htmlspecialchars($order['shipping_address_parsed']['province'] ?? '') ?>, 
                                <?= htmlspecialchars($order['shipping_address_parsed']['postal_code'] ?? '') ?>
                            </p>
                            <p><?= htmlspecialchars($order['shipping_address_parsed']['country'] ?? 'South Africa') ?></p>
                            <p><i class="fas fa-phone"></i> <?= htmlspecialchars($order['shipping_address_parsed']['contact_phone'] ?? '') ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Billing Address -->
                    <?php if(isset($order['billing_address_parsed'])): ?>
                    <div class="detail-card">
                        <h4 class="detail-title">
                            <i class="fas fa-file-invoice-dollar"></i> Billing Address
                        </h4>
                        <div class="detail-content">
                            <?php if($order['billing_address_parsed']['same_as_shipping'] ?? false): ?>
                                <p><em>Same as shipping address</em></p>
                            <?php else: ?>
                                <p><strong><?= htmlspecialchars($order['billing_address_parsed']['contact_name'] ?? '') ?></strong></p>
                                <p>
                                    <?= htmlspecialchars($order['billing_address_parsed']['house_number'] ?? '') ?> 
                                    <?= htmlspecialchars($order['billing_address_parsed']['street_name'] ?? '') ?>
                                </p>
                                <?php if(!empty($order['billing_address_parsed']['unit_number'])): ?>
                                    <p>Unit <?= htmlspecialchars($order['billing_address_parsed']['unit_number']) ?></p>
                                <?php endif; ?>
                                <p>
                                    <?= htmlspecialchars($order['billing_address_parsed']['suburb'] ?? '') ?>, 
                                    <?= htmlspecialchars($order['billing_address_parsed']['city'] ?? '') ?>
                                </p>
                                <p>
                                    <?= htmlspecialchars($order['billing_address_parsed']['province'] ?? '') ?>, 
                                    <?= htmlspecialchars($order['billing_address_parsed']['postal_code'] ?? '') ?>
                                </p>
                                <p><?= htmlspecialchars($order['billing_address_parsed']['country'] ?? 'South Africa') ?></p>
                                <p><i class="fas fa-phone"></i> <?= htmlspecialchars($order['billing_address_parsed']['contact_phone'] ?? '') ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Payment & Shipping -->
                    <div class="detail-card">
                        <h4 class="detail-title">
                            <i class="fas fa-info-circle"></i> Order Details
                        </h4>
                        <div class="detail-content">
                            <div class="detail-row">
                                <span>Payment Method:</span>
                                <span class="detail-value"><?= ucfirst($order['payment_method'] ?? 'Cash on Delivery') ?></span>
                            </div>
                            <div class="detail-row">
                                <span>Payment Status:</span>
                                <span class="detail-value status-badge status-<?= strtolower($order['payment_status'] ?? 'pending') ?>">
                                    <?= ucfirst($order['payment_status'] ?? 'Pending') ?>
                                </span>
                            </div>
                            <div class="detail-row">
                                <span>Shipping Cost:</span>
                                <span class="detail-value">R<?= number_format($order['shipping_amount'] ?? 0, 2) ?></span>
                            </div>
                            <div class="detail-row">
                                <span>VAT Amount:</span>
                                <span class="detail-value">R<?= number_format($order['vat_amount'] ?? 0, 2) ?></span>
                            </div>
                            <div class="detail-row">
                                <span>Discount:</span>
                                <span class="detail-value">R<?= number_format($order['discount_amount'] ?? 0, 2) ?></span>
                            </div>
                            <?php if(!empty($order['notes'])): ?>
                            <div class="detail-row-full">
                                <span>Order Notes:</span>
                                <p class="detail-notes"><?= htmlspecialchars($order['notes']) ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Timeline -->
                <div class="timeline-section">
                    <h3 class="section-title">Order Timeline</h3>
                    <div class="timeline">
                        <div class="timeline-item">
                            <div class="timeline-dot <?= in_array($order['status'], ['pending', 'processing', 'shipped', 'delivered', 'completed']) ? 'active' : '' ?>"></div>
                            <div class="timeline-content">
                                <div class="timeline-title">Order Placed</div>
                                <div class="timeline-desc">
                                    Your order has been confirmed and is being processed.
                                    <div class="timeline-date"><?= date('d M Y, H:i', strtotime($order['created_at'])) ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-dot <?= in_array($order['status'], ['processing', 'shipped', 'delivered', 'completed']) ? 'active' : '' ?>"></div>
                            <div class="timeline-content">
                                <div class="timeline-title">Processing</div>
                                <div class="timeline-desc">
                                    We're preparing your items for shipping.
                                    <?php if($order['status'] == 'processing'): ?>
                                        <div class="timeline-current"><i class="fas fa-circle text-success"></i> Current Step</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-dot <?= in_array($order['status'], ['shipped', 'delivered', 'completed']) ? 'active' : '' ?>"></div>
                            <div class="timeline-content">
                                <div class="timeline-title">Shipped</div>
                                <div class="timeline-desc">
                                    Your order is on its way to you.
                                    <?php if($order['status'] == 'shipped'): ?>
                                        <div class="timeline-current"><i class="fas fa-circle text-success"></i> Current Step</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-dot <?= in_array($order['status'], ['delivered', 'completed']) ? 'active' : '' ?>"></div>
                            <div class="timeline-content">
                                <div class="timeline-title">Delivered</div>
                                <div class="timeline-desc">
                                    Your order has been delivered.
                                    <?php if($order['status'] == 'delivered'): ?>
                                        <div class="timeline-current"><i class="fas fa-circle text-success"></i> Current Step</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-dot <?= in_array($order['status'], ['completed']) ? 'active' : '' ?>"></div>
                            <div class="timeline-content">
                                <div class="timeline-title">Completed</div>
                                <div class="timeline-desc">
                                    Order completed successfully.
                                    <?php if($order['status'] == 'completed'): ?>
                                        <div class="timeline-current"><i class="fas fa-circle text-success"></i> Current Step</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="action-buttons-grid">
                    <a href="<?= BASE_URL ?>/pages/orders.php" class="action-btn action-btn-primary">
                        <i class="fas fa-shopping-bag"></i> View All Orders
                    </a>
                    <a href="<?= BASE_URL ?>/pages/listings.php" class="action-btn action-btn-outline">
                        <i class="fas fa-shopping-bag"></i> Continue Shopping
                    </a>
                    <a href="<?= BASE_URL ?>/pages/dashboard.php" class="action-btn action-btn-outline">
                        <i class="fas fa-tachometer-alt"></i> Go to Dashboard
                    </a>
                    <button onclick="window.print()" class="action-btn action-btn-outline">
                        <i class="fas fa-print"></i> Print Receipt
                    </button>
                </div>
                
                <!-- Print Styles -->
                <style media="print">
                    .action-buttons-grid,
                    .timeline-section,
                    .confirmation-bg-blob {
                        display: none !important;
                    }
                    
                    .confirmation-card {
                        box-shadow: none !important;
                        border: 1px solid #ddd !important;
                    }
                    
                    .success-animation {
                        text-align: left !important;
                    }
                    
                    .success-circle {
                        width: 60px !important;
                        height: 60px !important;
                        animation: none !important;
                    }
                    
                    .success-circle i {
                        font-size: 25px !important;
                    }
                </style>
                
            <?php endif; ?>
        </div>
    </div>
</div>

<?php includePartial('footer'); ?>