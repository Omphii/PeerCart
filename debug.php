<?php
// Enable all error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// First include bootstrap.php to define constants
require_once __DIR__ . '/includes/bootstrap.php';

echo "<h2>Path Debug</h2>";
echo "ROOT_PATH: " . (defined('ROOT_PATH') ? ROOT_PATH : 'NOT DEFINED') . "<br>";
echo "BASE_URL: " . (defined('BASE_URL') ? BASE_URL : 'NOT DEFINED') . "<br>";
echo "Current dir: " . __DIR__ . "<br>";
echo "Script name: " . $_SERVER['SCRIPT_NAME'] . "<br>";
echo "Request URI: " . $_SERVER['REQUEST_URI'] . "<br>";

// Test file existence
$test_files = [
    'main.css' => __DIR__ . '/assets/css/main.css',
    'default-product.png' => __DIR__ . '/assets/images/products/default-product.png',
    'default-user.png' => __DIR__ . '/assets/images/users/default-user.png',
    'bootstrap.php' => __DIR__ . '/includes/bootstrap.php',
    'functions.php' => __DIR__ . '/includes/functions.php'
];

echo "<h2>File Existence Check</h2>";
foreach ($test_files as $name => $path) {
    echo "$name: " . $path . " - Exists: " . (file_exists($path) ? '✅ YES' : '❌ NO') . "<br>";
}

// Test asset function if it exists
if (file_exists(__DIR__ . '/includes/functions.php')) {
    require_once __DIR__ . '/includes/functions.php';
    
    echo "<h2>Asset Function Test</h2>";
    echo "asset('css/main.css'): " . asset('css/main.css') . "<br>";
    echo "asset('images/products/default-product.png'): " . asset('images/products/default-product.png') . "<br>";
}

echo "<h2>Server Info</h2>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
?>