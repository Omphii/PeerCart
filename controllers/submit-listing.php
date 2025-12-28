<?php
// controllers/submit-listing.php
ob_start(); // Start output buffering

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/functions.php';

// Clean any previous output
ob_clean();

// Set JSON header first
header('Content-Type: application/json; charset=utf-8');

// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Check login
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please login to list items']);
    exit;
}

// Only POST allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// CSRF check
if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'], 'listing_submit')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid security token. Please refresh the page.']);
    exit;
}

$response = ['success' => false, 'message' => ''];

try {
    $db = Database::getInstance()->getConnection();

    // Validate required fields
    $required = ['title', 'price', 'category_id', 'quantity', 'item_condition', 'province'];
    foreach ($required as $field) {
        if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
            throw new Exception(ucfirst(str_replace('_', ' ', $field)) . ' is required');
        }
    }

    // Check terms agreement
    if (!isset($_POST['terms']) || $_POST['terms'] != '1') {
        throw new Exception('You must agree to the Terms of Service');
    }

    // Validate price
    $price = floatval($_POST['price']);
    if ($price <= 0 || $price > 999999.99) {
        throw new Exception('Price must be between R0.01 and R999,999.99');
    }
    
    // Validate quantity
    $quantity = intval($_POST['quantity']);
    if ($quantity < 1 || $quantity > 999) {
        throw new Exception('Quantity must be between 1 and 999');
    }
    
    // Original price (optional)
    $original_price = null;
    if (!empty($_POST['original_price']) && trim($_POST['original_price']) !== '') {
        $original_price = floatval($_POST['original_price']);
        if ($original_price <= 0) {
            throw new Exception('Original price must be greater than 0');
        }
    }

    // Description
    $description = !empty($_POST['description']) ? trim($_POST['description']) : 'No description provided.';

    // Validate condition
    $validConditions = ['new', 'used_like_new', 'used_good', 'used_fair'];
    $condition = trim($_POST['item_condition']);
    if (!in_array($condition, $validConditions)) {
        throw new Exception('Invalid condition selected');
    }

    // Validate province
    $validProvinces = ['EC', 'FS', 'GP', 'KZN', 'LP', 'MP', 'NC', 'NW', 'WC'];
    $province = trim($_POST['province']);
    if (!in_array($province, $validProvinces)) {
        throw new Exception('Invalid province selected');
    }

    // Handle images
    $main_image = null;
    $all_images = null;
    
    if (!empty($_FILES['images']['name'][0])) {
        $uploadDir = __DIR__ . '/../assets/uploads/listings/';
        
        // Create directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                throw new Exception('Could not create upload directory');
            }
        }
        
        // Check if directory is writable
        if (!is_writable($uploadDir)) {
            throw new Exception('Upload directory is not writable');
        }

        $imagePaths = [];
        $filesCount = count($_FILES['images']['name']);
        
        // Limit to 5 images
        if ($filesCount > 5) {
            throw new Exception('Maximum 5 images allowed');
        }

        for ($i = 0; $i < $filesCount; $i++) {
            // Check for upload errors
            if ($_FILES['images']['error'][$i] !== UPLOAD_ERR_OK) {
                error_log("Upload error for file {$i}: " . $_FILES['images']['error'][$i]);
                continue;
            }
            
            // Validate file type
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $file_name = $_FILES['images']['name'][$i];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            if (!in_array($file_ext, $allowed_extensions)) {
                error_log("Invalid file type: $file_ext");
                continue; // Skip invalid file types
            }
            
            // Validate file size (5MB)
            if ($_FILES['images']['size'][$i] > 5 * 1024 * 1024) {
                error_log("File too large: " . $_FILES['images']['size'][$i] . " bytes");
                continue; // Skip files that are too large
            }

            // Generate secure filename
            $fileName = uniqid('listing_', true) . '.' . $file_ext;
            $targetPath = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['images']['tmp_name'][$i], $targetPath)) {
                $imagePaths[] = 'assets/uploads/listings/' . $fileName;
            } else {
                error_log("Failed to move uploaded file: " . $_FILES['images']['tmp_name'][$i]);
            }
        }
        
        // Set images
        if (!empty($imagePaths)) {
            $main_image = $imagePaths[0];
            $all_images = json_encode($imagePaths);
        }
    }

    // Get user's city
    $stmt = $db->prepare("SELECT city FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user_city = $stmt->fetchColumn();
    if (!$user_city) {
        $user_city = 'Unknown';
    }
    
    // Generate slug - ADD THIS FUNCTION IF IT DOESN'T EXIST
    if (!function_exists('generateSlug')) {
        function generateSlug($string) {
            $slug = strtolower(trim($string));
            $slug = preg_replace('/[^a-z0-9-]+/', '-', $slug);
            $slug = preg_replace('/-+/', '-', $slug);
            $slug = trim($slug, '-');
            return $slug ?: 'item-' . uniqid();
        }
    }
    
    $slug = generateSlug($_POST['title']);
    
    // Check if slug exists
    $stmt = $db->prepare("SELECT id FROM listings WHERE slug = ?");
    $stmt->execute([$slug]);
    
    $counter = 1;
    $original_slug = $slug;
    while ($stmt->fetch()) {
        $slug = $original_slug . '-' . $counter;
        $stmt->execute([$slug]);
        $counter++;
    }
    
    // Determine featured status
    $featured = isset($_POST['featured']) && $_POST['featured'] == '1' ? 1 : 0;
    
    // Insert listing
    $sql = "INSERT INTO listings (
        seller_id, 
        category_id, 
        name, 
        slug, 
        description, 
        price, 
        original_price,
        quantity, 
        image, 
        images,
        item_condition, 
        status, 
        featured, 
        province,
        city,
        created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', ?, ?, ?, NOW())";
    
    $stmt = $db->prepare($sql);
    
    $params = [
        $_SESSION['user_id'],
        $_POST['category_id'],
        trim($_POST['title']),
        $slug,
        $description,
        $price,
        $original_price,
        $quantity,
        $main_image,
        $all_images,
        $condition,
        $featured,
        $province,
        $user_city
    ];
    
    $result = $stmt->execute($params);
    
    if (!$result) {
        $errorInfo = $stmt->errorInfo();
        error_log("Database error: " . print_r($errorInfo, true));
        throw new Exception('Failed to save listing to database. Error: ' . ($errorInfo[2] ?? 'Unknown'));
    }
    
    $listing_id = $db->lastInsertId();
    
    // FIXED: Correct redirect URL
    // FIXED: Correct redirect URL
// FIXED: Correct redirect URL - use the full path
$redirect_url = url('pages/listing.php', ['id' => $listing_id]);

$response = [
    'success' => true,
    'message' => 'Listing created successfully!',
    'redirect' => $redirect_url
];

} catch (PDOException $e) {
    error_log("Database error in submit-listing: " . $e->getMessage());
    $response['message'] = "Database error. Please try again.";
} catch (Exception $e) {
    error_log("Error in submit-listing: " . $e->getMessage());
    $response['message'] = $e->getMessage();
}

// Clean any output and send JSON
ob_clean();
echo json_encode($response);
exit;
?>