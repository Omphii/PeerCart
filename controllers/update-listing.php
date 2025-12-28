<?php
// includes/handlers/update-listing.php
require_once __DIR__ . '/../../includes/bootstrap.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is seller
if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] ?? 'buyer') !== 'seller') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'];
$listingId = $_POST['listing_id'] ?? 0;

header('Content-Type: application/json');

try {
    $db = Database::getInstance()->getConnection();
    
    if (!$db) {
        throw new Exception("Database connection failed");
    }
    
    // Check if listing belongs to user
    $checkStmt = $db->prepare("SELECT id FROM listings WHERE id = ? AND seller_id = ?");
    $checkStmt->execute([$listingId, $userId]);
    
    if (!$checkStmt->fetch()) {
        throw new Exception("Listing not found or you don't have permission to edit it.");
    }
    
    // Prepare update data
    $updates = [];
    $params = [];
    
    // List of fields that can be updated
    $allowedFields = ['name', 'price', 'quantity', 'status', 'description', 'category_id'];
    
    foreach ($allowedFields as $field) {
        if (isset($_POST[$field]) && $_POST[$field] !== '') {
            $updates[] = "$field = ?";
            $params[] = $_POST[$field];
        }
    }
    
    // Handle boolean fields
    if (isset($_POST['featured'])) {
        $updates[] = "featured = ?";
        $params[] = $_POST['featured'] ? 1 : 0;
    }
    
    if (isset($_POST['is_active'])) {
        $updates[] = "is_active = ?";
        $params[] = $_POST['is_active'] ? 1 : 0;
    }
    
    // Add updated_at
    $updates[] = "updated_at = NOW()";
    
    if (empty($updates)) {
        throw new Exception("No changes provided.");
    }
    
    // Build and execute query
    $params[] = $listingId;
    $params[] = $userId;
    
    $sql = "UPDATE listings SET " . implode(', ', $updates) . " WHERE id = ? AND seller_id = ?";
    $stmt = $db->prepare($sql);
    
    if ($stmt->execute($params)) {
        echo json_encode([
            'success' => true,
            'message' => 'Listing updated successfully!'
        ]);
    } else {
        throw new Exception("Failed to update listing.");
    }
    
} catch (Exception $e) {
    error_log("Update listing error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}