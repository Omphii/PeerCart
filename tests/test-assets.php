<?php
require_once __DIR__ . '/includes/bootstrap.php';
echo "BASE_URL: " . BASE_URL . "<br>";
echo "Asset test: " . asset('css/main.css') . "<br>";
echo "Image test: " . getListingImage('default-product.png') . "<br>";

// Check if file exists
$file = ROOT_PATH . '/assets/images/products/default-product.png';
echo "File exists: " . (file_exists($file) ? 'Yes' : 'No') . "<br>";
echo "File path: " . $file . "<br>";
?>