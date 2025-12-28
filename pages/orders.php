<?php
// orders.php - My Orders Page
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/functions.php';

// Ensure user is logged in
if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . '/pages/auth.php?action=login&redirect=' . urlencode('/pages/orders.php'));
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch orders for logged-in user
function getUserOrders(int $userId): array {
    try {
        $db = Database::getInstance()->getConnection();
        
        // Fixed: Changed user_id to buyer_id to match your database schema
        $stmt = $db->prepare("
            SELECT 
                o.id, 
                o.order_number, 
                o.status, 
                o.payment_status,
                o.total_amount, 
                o.created_at,
                o.shipping_amount,
                o.vat_amount,
                o.discount_amount,
                COUNT(oi.id) AS total_items,
                GROUP_CONCAT(DISTINCT l.name SEPARATOR ', ') AS item_names
            FROM orders o
            LEFT JOIN order_items oi ON o.id = oi.order_id
            LEFT JOIN listings l ON oi.listing_id = l.id
            WHERE o.buyer_id = :user_id
            GROUP BY o.id, o.order_number, o.status, o.payment_status, 
                     o.total_amount, o.created_at, o.shipping_amount,
                     o.vat_amount, o.discount_amount
            ORDER BY o.created_at DESC
        ");
        $stmt->execute([':user_id' => $userId]);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format the data
        foreach ($orders as &$order) {
            // Truncate long item names
            if (strlen($order['item_names']) > 50) {
                $order['item_names'] = substr($order['item_names'], 0, 47) . '...';
            }
            
            // Ensure numeric values
            $order['total_amount'] = floatval($order['total_amount']);
            $order['total_items'] = intval($order['total_items']);
            
            // Calculate subtotal
            $order['subtotal'] = $order['total_amount'] - 
                                 floatval($order['shipping_amount']) - 
                                 floatval($order['vat_amount']) + 
                                 floatval($order['discount_amount']);
        }
        
        return $orders;
    } catch (PDOException $e) {
        error_log("Error fetching orders: " . $e->getMessage());
        return [];
    }
}

// Handle order actions (cancel, track, etc.)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (validateCSRFToken($csrf_token, 'order_action')) {
        switch ($_POST['action']) {
            case 'cancel':
                try {
                    $db = Database::getInstance()->getConnection();
                    
                    // Check if order belongs to user and can be cancelled
                    $stmt = $db->prepare("
                        SELECT status FROM orders 
                        WHERE id = :order_id AND buyer_id = :user_id
                    ");
                    $stmt->execute([
                        ':order_id' => $order_id,
                        ':user_id' => $user_id
                    ]);
                    $order = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($order && $order['status'] === 'pending') {
                        // Cancel the order
                        $stmt = $db->prepare("
                            UPDATE orders 
                            SET status = 'cancelled', updated_at = NOW() 
                            WHERE id = :order_id AND buyer_id = :user_id
                        ");
                        $stmt->execute([
                            ':order_id' => $order_id,
                            ':user_id' => $user_id
                        ]);
                        
                        // Return items to stock
                        $stmt = $db->prepare("
                            UPDATE listings l
                            JOIN order_items oi ON l.id = oi.listing_id
                            SET l.quantity = l.quantity + oi.quantity
                            WHERE oi.order_id = :order_id
                        ");
                        $stmt->execute([':order_id' => $order_id]);
                        
                        $_SESSION['success_message'] = "Order #$order_id has been cancelled successfully.";
                    } else {
                        $_SESSION['error_message'] = "Order cannot be cancelled at this stage.";
                    }
                } catch (Exception $e) {
                    $_SESSION['error_message'] = "Error cancelling order: " . $e->getMessage();
                }
                break;
                
            case 'track':
                // Implement tracking functionality here
                $_SESSION['info_message'] = "Tracking information will be available when order is shipped.";
                break;
        }
        
        // Redirect to refresh the page
        header('Location: ' . BASE_URL . '/pages/orders.php');
        exit;
    } else {
        $_SESSION['error_message'] = "Invalid security token.";
    }
}

// Fetch orders
$orders = getUserOrders($user_id);

// Generate CSRF token for order actions
$csrfToken = generateCSRFToken('order_action');

// Include header
includePartial('header', [
    'title' => 'My Orders - PeerCart',
    'additionalStyles' => []
]);
?>

<link rel="stylesheet" href="<?= asset('css/pages/orders.css') ?>?v=<?= time() ?>">

<div class="orders-page">
    <div class="orders-bg-blob"></div>
    <div class="orders-bg-blob"></div>
    
    <div class="orders-container">
        <?php if (empty($orders)): ?>
            <!-- Empty Orders State -->
            <div class="cart-page">
                <div class="cart-bg-blob"></div>
                <div class="cart-bg-blob"></div>
                
                <div class="cart-container">
                    <div class="cart-items-column">
                        <div class="cart-header">
                            <h1 class="cart-title">
                                <i class="fas fa-shopping-bag me-2"></i> My Orders
                            </h1>
                            <p class="orders-subtitle">Track and manage all your purchases in one place</p>
                        </div>
                        
                        <div class="glass-card">
                            <div class="empty-cart-state">
                                <div class="empty-cart-icon">
                                    <i class="fas fa-shopping-bag"></i>
                                </div>
                                <h3 class="empty-cart-title">Your Orders Are Empty</h3>
                                <p class="empty-cart-message">
                                    Looks like you haven't placed any orders yet. Start shopping to see your orders here!
                                </p>
                                <div class="action-buttons-grid">
                                    <a href="<?= BASE_URL ?>/pages/listings.php" class="btn btn-primary btn-lg">
                                        <i class="fas fa-shopping-bag me-2"></i> Start Shopping
                                    </a>
                                    <a href="<?= BASE_URL ?>/pages/listings.php?featured=1" class="btn btn-outline-primary btn-lg">
                                        <i class="fas fa-star me-2"></i> View Featured Items
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Orders with items -->
            <div class="orders-header">
                <h1 class="orders-title">
                    <i class="fas fa-shopping-bag me-2"></i> My Orders
                </h1>
                <p class="orders-subtitle">Track and manage all your purchases in one place</p>
            </div>
            
            <!-- Stats cards -->
            <?php
            $total_spent = array_sum(array_column($orders, 'total_amount'));
            $pending_orders = array_filter($orders, fn($order) => $order['status'] === 'pending');
            $completed_orders = array_filter($orders, fn($order) => in_array($order['status'], ['completed', 'delivered']));
            ?>
            <div class="orders-stats">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-box-open"></i>
                    </div>
                    <div class="stat-value"><?= count($orders) ?></div>
                    <div class="stat-label">Total Orders</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stat-value">R<?= number_format($total_spent, 2) ?></div>
                    <div class="stat-label">Total Spent</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-value"><?= count($pending_orders) ?></div>
                    <div class="stat-label">Pending</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-value"><?= count($completed_orders) ?></div>
                    <div class="stat-label">Completed</div>
                </div>
            </div>
            
            <!-- Success/Error Messages -->
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success glass-card">
                    <i class="fas fa-check-circle me-2"></i> <?= htmlspecialchars($_SESSION['success_message']) ?>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger glass-card">
                    <i class="fas fa-exclamation-circle me-2"></i> <?= htmlspecialchars($_SESSION['error_message']) ?>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['info_message'])): ?>
                <div class="alert alert-info glass-card">
                    <i class="fas fa-info-circle me-2"></i> <?= htmlspecialchars($_SESSION['info_message']) ?>
                </div>
                <?php unset($_SESSION['info_message']); ?>
            <?php endif; ?>
            
            <div class="glass-card">
                <div class="glass-card-body">
                    <div class="orders-filter">
                        <div class="filter-buttons">
                            <button class="filter-btn active" data-filter="all">All Orders</button>
                            <button class="filter-btn" data-filter="pending">Pending</button>
                            <button class="filter-btn" data-filter="processing">Processing</button>
                            <button class="filter-btn" data-filter="shipped">Shipped</button>
                            <button class="filter-btn" data-filter="delivered">Delivered</button>
                            <button class="filter-btn" data-filter="completed">Completed</button>
                            <button class="filter-btn" data-filter="cancelled">Cancelled</button>
                        </div>
                    </div>
                    
                    <div class="orders-table-container">
                        <table class="orders-table">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Date</th>
                                    <th>Items</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Payment</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr class="order-row" data-status="<?= $order['status'] ?>">
                                        <td data-label="Order #">
                                            <div class="order-number">
                                                <strong>#<?= htmlspecialchars($order['order_number']) ?></strong>
                                            </div>
                                            <?php if (!empty($order['item_names'])): ?>
                                            <div class="order-items-preview small">
                                                <?= htmlspecialchars($order['item_names']) ?>
                                            </div>
                                            <?php endif; ?>
                                        </td>
                                        <td data-label="Date">
                                            <div class="order-date">
                                                <?= date('d M Y', strtotime($order['created_at'])) ?>
                                                <div class="text-muted small">
                                                    <?= date('H:i', strtotime($order['created_at'])) ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td data-label="Items">
                                            <div class="order-items-count">
                                                <?= $order['total_items'] ?> item<?= $order['total_items'] != 1 ? 's' : '' ?>
                                            </div>
                                        </td>
                                        <td data-label="Total">
                                            <div class="order-total">
                                                <strong>R<?= number_format($order['total_amount'], 2) ?></strong>
                                                <div class="text-muted small">
                                                    Subtotal: R<?= number_format($order['subtotal'], 2) ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td data-label="Status">
                                            <span class="status-badge status-<?= $order['status'] ?>">
                                                <i class="fas fa-circle"></i>
                                                <?= ucfirst($order['status']) ?>
                                            </span>
                                        </td>
                                        <td data-label="Payment">
                                            <span class="payment-status payment-<?= $order['payment_status'] ?>">
                                                <?= ucfirst($order['payment_status']) ?>
                                            </span>
                                        </td>
                                        <td data-label="Actions">
                                            <div class="action-buttons">
                                                <a href="<?= BASE_URL ?>/pages/order-confirmation.php?id=<?= $order['id'] ?>" 
                                                   class="view-btn" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                
                                                <?php if ($order['status'] === 'pending'): ?>
                                                    <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to cancel this order?');">
                                                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                                        <input type="hidden" name="action" value="cancel">
                                                        <button type="submit" class="cancel-btn" title="Cancel Order">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                
                                                <?php if (in_array($order['status'], ['processing', 'shipped'])): ?>
                                                    <button class="track-btn" title="Track Order" onclick="trackOrder(<?= $order['id'] ?>)">
                                                        <i class="fas fa-shipping-fast"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Order Actions Modal -->
            <div class="modal fade" id="orderModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Order Actions</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body" id="modalBody">
                            Loading...
                        </div>
                    </div>
                </div>
            </div>
            
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Filter orders
    const filterButtons = document.querySelectorAll('.filter-btn');
    const orderRows = document.querySelectorAll('.order-row');
    
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            const filter = this.getAttribute('data-filter');
            
            // Update active button
            filterButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            // Filter rows
            orderRows.forEach(row => {
                if (filter === 'all' || row.getAttribute('data-status') === filter) {
                    row.style.display = '';
                    setTimeout(() => {
                        row.style.opacity = '1';
                        row.style.transform = 'translateY(0)';
                    }, 10);
                } else {
                    row.style.opacity = '0';
                    row.style.transform = 'translateY(-10px)';
                    setTimeout(() => {
                        row.style.display = 'none';
                    }, 300);
                }
            });
        });
    });
    
    // Order action confirmation
    document.querySelectorAll('form[onsubmit]').forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!confirm('Are you sure you want to perform this action?')) {
                e.preventDefault();
                return false;
            }
            return true;
        });
    });
    
    // Order row click to view details
    orderRows.forEach(row => {
        row.addEventListener('click', function(e) {
            // Don't trigger if clicking on buttons or links
            if (e.target.tagName === 'A' || e.target.tagName === 'BUTTON' || 
                e.target.closest('a') || e.target.closest('button')) {
                return;
            }
            
            const viewBtn = this.querySelector('.view-btn');
            if (viewBtn) {
                window.location.href = viewBtn.href;
            }
        });
    });
    
    // Add hover effects
    orderRows.forEach(row => {
        row.style.cursor = 'pointer';
        row.style.transition = 'all 0.3s ease';
        
        row.addEventListener('mouseenter', function() {
            this.style.backgroundColor = 'rgba(67, 97, 238, 0.03)';
            this.style.boxShadow = '0 5px 15px rgba(0, 0, 0, 0.05)';
        });
        
        row.addEventListener('mouseleave', function() {
            this.style.backgroundColor = '';
            this.style.boxShadow = '';
        });
    });
    
    // Auto-refresh orders every 30 seconds if there are pending orders
    <?php if (!empty($pending_orders)): ?>
    setTimeout(() => {
        window.location.reload();
    }, 30000); // 30 seconds
    <?php endif; ?>
});

function trackOrder(orderId) {
    alert('Tracking information for order #' + orderId + ' will be available when the order is shipped.');
}

function printReceipt(orderId) {
    window.open('<?= BASE_URL ?>/pages/order-confirmation.php?id=' + orderId + '&print=1', '_blank');
}
</script>

<?php includePartial('footer'); ?>