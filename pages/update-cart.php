<?php
// update-cart.php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/functions.php';

// Set JSON header
header('Content-Type: application/json');

// Start output buffering
ob_start();

try {
    $action = $_POST['action'] ?? '';
    $listing_id = isset($_POST['listing_id']) ? (int)$_POST['listing_id'] : 0;
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    
    // Validate input
    if (empty($action)) {
        throw new Exception('No action specified.');
    }
    
    if ($listing_id <= 0 && $action !== 'clear') {
        throw new Exception('Invalid listing ID.');
    }
    
    if ($quantity <= 0 && $action === 'update') {
        $quantity = 1; // Default to 1
    }
    
    // Get database connection
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Initialize cart count
    $cartCount = 0;
    
    // Check if user is logged in
    if (isLoggedIn()) {
        // Logged-in user: Use database cart
        $user_id = $_SESSION['user_id'];
        $message = '';
        
        switch ($action) {
            case 'add':
                // Check if listing exists and is available
                $stmt = $conn->prepare("
                    SELECT id, name, price, quantity, status 
                    FROM listings 
                    WHERE id = ? AND is_active = 1
                ");
                $stmt->execute([$listing_id]);
                $listing = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$listing) {
                    throw new Exception('Item not found or not available.');
                }
                
                if ($listing['status'] !== 'active') {
                    throw new Exception('Item is not available for purchase.');
                }
                
                if ($listing['quantity'] > 0 && $quantity > $listing['quantity']) {
                    throw new Exception('Not enough stock available. Only ' . $listing['quantity'] . ' left.');
                }
                
                // Check if item is already in cart
                $stmt = $conn->prepare("
                    SELECT id, quantity 
                    FROM cart 
                    WHERE user_id = ? AND listing_id = ?
                ");
                $stmt->execute([$user_id, $listing_id]);
                $existingItem = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($existingItem) {
                    $newQuantity = $existingItem['quantity'] + $quantity;
                    
                    if ($listing['quantity'] > 0 && $newQuantity > $listing['quantity']) {
                        throw new Exception('Cannot add more than available stock.');
                    }
                    
                    $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
                    $stmt->execute([$newQuantity, $existingItem['id']]);
                    $message = 'Item quantity updated in cart.';
                } else {
                    $stmt = $conn->prepare("
                        INSERT INTO cart (user_id, listing_id, quantity) 
                        VALUES (?, ?, ?)
                    ");
                    $stmt->execute([$user_id, $listing_id, $quantity]);
                    $message = 'Item added to cart successfully!';
                }
                break;
                
            case 'update':
                // Check if item is in cart
                $stmt = $conn->prepare("
                    SELECT id 
                    FROM cart 
                    WHERE user_id = ? AND listing_id = ?
                ");
                $stmt->execute([$user_id, $listing_id]);
                
                if ($stmt->rowCount() === 0) {
                    throw new Exception('Item not found in cart.');
                }
                
                if ($quantity <= 0) {
                    $stmt = $conn->prepare("
                        DELETE FROM cart 
                        WHERE user_id = ? AND listing_id = ?
                    ");
                    $stmt->execute([$user_id, $listing_id]);
                    $message = 'Item removed from cart.';
                } else {
                    $stmt = $conn->prepare("
                        SELECT quantity 
                        FROM listings 
                        WHERE id = ? AND is_active = 1 AND status = 'active'
                    ");
                    $stmt->execute([$listing_id]);
                    $listing = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$listing) {
                        throw new Exception('Item no longer available.');
                    }
                    
                    if ($listing['quantity'] > 0 && $quantity > $listing['quantity']) {
                        throw new Exception('Cannot update to more than available stock.');
                    }
                    
                    $stmt = $conn->prepare("
                        UPDATE cart 
                        SET quantity = ? 
                        WHERE user_id = ? AND listing_id = ?
                    ");
                    $stmt->execute([$quantity, $user_id, $listing_id]);
                    $message = 'Cart updated successfully.';
                }
                break;
                
            case 'remove':
                $stmt = $conn->prepare("
                    DELETE FROM cart 
                    WHERE user_id = ? AND listing_id = ?
                ");
                $stmt->execute([$user_id, $listing_id]);
                $message = 'Item removed from cart.';
                break;
                
            case 'clear':
                $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $message = 'Cart cleared successfully.';
                break;
                
            default:
                throw new Exception('Invalid action.');
        }
        
        // Get updated cart count for logged-in user
        $stmt = $conn->prepare("
            SELECT COUNT(*) as cart_count 
            FROM cart 
            WHERE user_id = ?
        ");
        $stmt->execute([$user_id]);
        $cartCount = $stmt->fetch(PDO::FETCH_ASSOC)['cart_count'];
        $_SESSION['cart_count'] = $cartCount;
        
    } else {
        // Guest user: Use session-based cart
        if (!isset($_SESSION['guest_cart'])) {
            $_SESSION['guest_cart'] = [];
        }
        
        $guestCart = &$_SESSION['guest_cart'];
        $message = '';
        
        switch ($action) {
            case 'add':
                // Check if listing exists and is available
                $stmt = $conn->prepare("
                    SELECT id, name, price, quantity, status 
                    FROM listings 
                    WHERE id = ? AND is_active = 1
                ");
                $stmt->execute([$listing_id]);
                $listing = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$listing) {
                    throw new Exception('Item not found or not available.');
                }
                
                if ($listing['status'] !== 'active') {
                    throw new Exception('Item is not available for purchase.');
                }
                
                if ($listing['quantity'] > 0 && $quantity > $listing['quantity']) {
                    throw new Exception('Not enough stock available. Only ' . $listing['quantity'] . ' left.');
                }
                
                // Check if item is already in cart
                if (isset($guestCart[$listing_id])) {
                    $newQuantity = $guestCart[$listing_id] + $quantity;
                    
                    if ($listing['quantity'] > 0 && $newQuantity > $listing['quantity']) {
                        throw new Exception('Cannot add more than available stock.');
                    }
                    
                    $guestCart[$listing_id] = $newQuantity;
                    $message = 'Item quantity updated in cart.';
                } else {
                    $guestCart[$listing_id] = $quantity;
                    $message = 'Item added to cart successfully!';
                }
                break;
                
            case 'update':
                if (!isset($guestCart[$listing_id])) {
                    throw new Exception('Item not found in cart.');
                }
                
                if ($quantity <= 0) {
                    unset($guestCart[$listing_id]);
                    $message = 'Item removed from cart.';
                } else {
                    $stmt = $conn->prepare("
                        SELECT quantity 
                        FROM listings 
                        WHERE id = ? AND is_active = 1 AND status = 'active'
                    ");
                    $stmt->execute([$listing_id]);
                    $listing = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$listing) {
                        throw new Exception('Item no longer available.');
                    }
                    
                    if ($listing['quantity'] > 0 && $quantity > $listing['quantity']) {
                        throw new Exception('Cannot update to more than available stock.');
                    }
                    
                    $guestCart[$listing_id] = $quantity;
                    $message = 'Cart updated successfully.';
                }
                break;
                
            case 'remove':
                if (isset($guestCart[$listing_id])) {
                    unset($guestCart[$listing_id]);
                    $message = 'Item removed from cart.';
                } else {
                    throw new Exception('Item not found in cart.');
                }
                break;
                
            case 'clear':
                $_SESSION['guest_cart'] = [];
                $message = 'Cart cleared successfully.';
                break;
                
            default:
                throw new Exception('Invalid action.');
        }
        
        // Get cart count for guest
        $cartCount = count($guestCart);
        $_SESSION['cart_count'] = $cartCount;
    }
    
    // Clear output buffer
    ob_end_clean();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => $message,
        'cart_count' => $cartCount,
        'is_logged_in' => isLoggedIn()
    ]);
    
} catch (Exception $e) {
    // Clear output buffer
    ob_end_clean();
    
    // Log the error
    error_log("Cart update error: " . $e->getMessage());
    
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>