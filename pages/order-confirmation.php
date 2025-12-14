<?php
// order-confirmation.php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . '/includes/auth/login.php');
    exit;
}

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($order_id <= 0) {
    header('Location: ' . BASE_URL . '/pages/cart.php');
    exit;
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Get order details
    $stmt = $conn->prepare("
        SELECT o.*, u.name as user_name, u.email as user_email
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE o.id = ? AND o.user_id = ?
    ");
    $stmt->execute([$order_id, $_SESSION['user_id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        header('Location: ' . BASE_URL . '/pages/cart.php');
        exit;
    }
    
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
    
} catch (Exception $e) {
    $error = "Error loading order details: " . $e->getMessage();
}

$title = 'Order Confirmation - PeerCart';
includePartial('header', ['title' => $title]);
?>

<style>
.confirmation-page {
    padding: 50px 20px;
    text-align: center;
    min-height: 70vh;
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
}

.confirmation-card {
    background: white;
    border-radius: 15px;
    padding: 40px;
    max-width: 600px;
    margin: 0 auto;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.success-icon {
    font-size: 80px;
    color: #28a745;
    margin-bottom: 20px;
}

.order-number {
    background: #4361ee;
    color: white;
    padding: 10px 20px;
    border-radius: 50px;
    display: inline-block;
    margin: 20px 0;
    font-weight: bold;
}

.actions {
    margin-top: 30px;
    display: flex;
    gap: 15px;
    justify-content: center;
}
</style>

<div class="confirmation-page">
    <div class="confirmation-card">
        <div class="success-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        
        <h1>Order Confirmed!</h1>
        <p>Thank you for your purchase, <?= htmlspecialchars($_SESSION['user_name']) ?>!</p>
        
        <div class="order-number">
            Order #<?= htmlspecialchars($order['order_number']) ?>
        </div>
        
        <p>We've sent a confirmation email to <?= htmlspecialchars($order['user_email']) ?></p>
        
        <div style="margin: 30px 0;">
            <h4>Order Summary</h4>
            <p>Total Amount: <strong>R<?= number_format($order['total_amount'], 2) ?></strong></p>
            <p>Order Status: <span class="badge bg-warning"><?= ucfirst($order['status']) ?></span></p>
        </div>
        
        <div class="actions">
            <a href="<?= BASE_URL ?>/pages/orders.php" class="btn btn-primary">
                <i class="fas fa-shopping-bag me-2"></i> View My Orders
            </a>
            <a href="<?= BASE_URL ?>/pages/listings.php" class="btn btn-outline-primary">
                <i class="fas fa-shopping-bag me-2"></i> Continue Shopping
            </a>
        </div>
    </div>
</div>

<?php includePartial('footer'); ?>