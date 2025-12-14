<?php
/**
 * PeerCart - Validation Functions
 */

function validateListingData(array $data): array {
    $errors = [];
    
    if (empty($data['name']) || strlen($data['name']) < 3 || strlen($data['name']) > 100) {
        $errors['name'] = "Item name must be between 3 and 100 characters";
    }
    
    if (empty($data['price']) || !is_numeric($data['price']) || $data['price'] < 0) {
        $errors['price'] = "Valid price is required";
    }
    
    if (empty($data['description']) || strlen($data['description']) < 10) {
        $errors['description'] = "Description must be at least 10 characters long";
    }
    
    if (empty($data['category_id']) || !is_numeric($data['category_id'])) {
        $errors['category_id'] = "Please select a valid category";
    }
    
    if (empty($data['quantity']) || !is_numeric($data['quantity']) || $data['quantity'] < 1) {
        $errors['quantity'] = "Valid quantity is required";
    }
    
    return $errors;
}

function validateImageUpload(array $file, int $maxSize = 5 * 1024 * 1024): array {
    $errors = [];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "File upload failed with error code: " . $file['error'];
        return $errors;
    }
    
    if ($file['size'] > $maxSize) {
        $errors[] = "File size must be less than " . ($maxSize / 1024 / 1024) . "MB";
    }
    
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        $errors[] = "Only JPEG, PNG, GIF, and WebP images are allowed";
    }
    
    return $errors;
}
?>