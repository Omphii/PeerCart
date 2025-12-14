<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if(!isLoggedIn()){
    http_response_code(401);
    echo json_encode(['success'=>false,'message'=>'Please login to list items']);
    exit;
}

// Only POST allowed
if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    http_response_code(405);
    echo json_encode(['success'=>false,'message'=>'Method not allowed']);
    exit;
}

// CSRF check
if(!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])){
    http_response_code(403);
    echo json_encode(['success'=>false,'message'=>'Invalid CSRF token']);
    exit;
}

$response = ['success'=>false,'message'=>''];

try {
    $db = Database::getInstance()->getConnection();

    // Required fields
    $required = ['title','description','price','category_id','quantity','item_condition','province'];
    foreach($required as $field){
        if(empty($_POST[$field])){
            throw new Exception("Please fill in all required fields");
        }
    }

    // Validate numeric values
    if(!is_numeric($_POST['price']) || $_POST['price'] < 0) throw new Exception("Invalid price");
    if(!is_numeric($_POST['original_price'])) $_POST['original_price'] = null;
    if(!is_numeric($_POST['quantity']) || $_POST['quantity'] < 1) throw new Exception("Invalid quantity");

    // Validate select options
    $validConditions = ['new','used_like_new','used_good','used_fair'];
    if(!in_array($_POST['item_condition'],$validConditions)) throw new Exception("Invalid condition");

    $validProvinces = ['EC','FS','GP','KZN','LP','MP','NC','NW','WC'];
    if(!in_array($_POST['province'],$validProvinces)) throw new Exception("Invalid province");

    // Handle images
    $imagePaths = [];
    if(!empty($_FILES['images'])){
        $uploadDir = __DIR__.'/../assets/uploads/';
        if(!file_exists($uploadDir)) mkdir($uploadDir,0777,true);

        $filesCount = count($_FILES['images']['name']);
        if($filesCount > 5) throw new Exception("Maximum 5 images allowed");

        for($i=0;$i<$filesCount;$i++){
            if($_FILES['images']['error'][$i] === UPLOAD_ERR_OK){
                $tmpName = $_FILES['images']['tmp_name'][$i];
                $fileType = mime_content_type($tmpName);
                if(!in_array($fileType,['image/jpeg','image/png','image/gif'])) continue;

                $fileName = uniqid().'_'.basename($_FILES['images']['name'][$i]);
                $targetPath = $uploadDir.$fileName;

                if(move_uploaded_file($tmpName,$targetPath)){
                    $imagePaths[] = 'assets/uploads/'.$fileName;
                }
            }
        }
    }

    // Check what columns exist in your listings table
    // Uncomment to debug:
    // $stmt = $db->query("DESCRIBE listings");
    // $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    // error_log("Listings table columns: " . print_r($columns, true));
    
    // Most likely your column is named 'seller_id' instead of 'user_id'
    $sellerIdColumn = 'seller_id'; // Change this if it's different
    
    // Also check if 'images' column exists or if it's named 'image' (singular)
    $imageColumn = 'images'; // Change to 'image' if that's the column name

    // Insert listing - FIXED COLUMN NAMES
    $stmt = $db->prepare("INSERT INTO listings 
        (seller_id, title, description, price, original_price, category_id, quantity, item_condition, province, images, featured, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    
    $stmt->execute([
        $_SESSION['user_id'], // This is the seller's user ID from session
        $_POST['title'],
        $_POST['description'],
        $_POST['price'],
        $_POST['original_price'] ?: null,
        $_POST['category_id'],
        $_POST['quantity'],
        $_POST['item_condition'],
        $_POST['province'],
        !empty($imagePaths) ? json_encode($imagePaths) : null,
        isset($_POST['featured']) && $_POST['featured']=='1' ? 1 : 0
    ]);

    $response = [
        'success'=>true,
        'message'=>'Listing created successfully!',
        'redirect'=>url('dashboard.php')
    ];

} catch (PDOException $e) {
    // Log database errors for debugging
    error_log("Database error in submit-listing.php: " . $e->getMessage());
    $response['message'] = "Database error: " . $e->getMessage();
} catch(Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);