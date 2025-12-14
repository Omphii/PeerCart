<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/functions.php';

// Ensure user is logged in
if (!isLoggedIn()) {
    redirect('/includes/auth/login.php');
}

// Fetch orders for logged-in user
function getUserOrders(int $userId): array {
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT o.id, o.order_number, o.status, o.total_amount, o.created_at,
                   COUNT(oi.id) AS total_items
            FROM orders o
            LEFT JOIN order_items oi ON o.id = oi.order_id
            WHERE o.user_id = :user_id
            GROUP BY o.id
            ORDER BY o.created_at DESC
        ");
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching orders: " . $e->getMessage());
        return [];
    }
}

// Fetch orders
$orders = getUserOrders($_SESSION['user_id']);

includePartial('header', ['title' => 'My Orders', 'styles' => ['/css/main.css']]);
?>

<div class="container mt-4">
    <h1>My Orders</h1>

    <?php displayFlashMessage(); ?>

    <?php if (empty($orders)): ?>
        <p>You have no orders yet.</p>
        <a href="<?= BASE_URL ?>/pages/listings.php" class="btn btn-primary">Shop Now</a>
    <?php else: ?>
        <table class="table table-striped mt-3">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Date</th>
                    <th>Total</th>
                    <th>Items</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><?= htmlspecialchars($order['order_number']) ?></td>
                        <td><?= date('d M Y', strtotime($order['created_at'])) ?></td>
                        <td>R<?= number_format($order['total_amount'], 2) ?></td>
                        <td><?= $order['total_items'] ?></td>
                        <td>
                            <span class="badge 
                                <?= $order['status'] === 'pending' ? 'bg-warning' : '' ?>
                          s      <?= $order['status'] === 'processing' ? 'bg-info' : '' ?>
                                <?= $order['status'] === 'completed' ? 'bg-success' : '' ?>
                                <?= $order['status'] === 'cancelled' ? 'bg-danger' : '' ?>
                            ">
                                <?= ucfirst($order['status']) ?>
                            </span>
                        </td>
                        <td>
                            <a href="<?= BASE_URL ?>/pages/order-details.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-primary">
                                View
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php includePartial('footer'); ?>
