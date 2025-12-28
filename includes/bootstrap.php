<?php
// includes/bootstrap.php
session_start();

// Define base paths
if (!defined('BASE_PATH')) {
    define('BASE_PATH', realpath(dirname(__DIR__)));
}

// In bootstrap.php, update this part:
// In bootstrap.php - REPLACE the entire BASE_URL section with:

if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    
    // Get the script directory correctly
    $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
    
    // For InfinityFree/Page.gd hosting
    // If script is running from pages/ directory, go up one level
    if (strpos($scriptDir, '/pages') !== false) {
        // Remove /pages from the end
        $scriptDir = dirname($scriptDir);
    }
    
    // Build the base URL
    $baseUrl = $protocol . '://' . $host;
    if ($scriptDir && $scriptDir !== '/') {
        $baseUrl .= rtrim($scriptDir, '/');
    }
    
    define('BASE_URL', $baseUrl);
    define('ASSETS_URL', BASE_URL . '/assets');
}

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the Database class
require_once __DIR__ . '/database.php';

/**
 * SMART URL HELPER FUNCTIONS
 */

/**
 * Get URL for any asset (CSS, JS, images)
 * Usage: url('css/style.css') or url('images/logo.png')
 */

/**
 * Get URL for a page
 * Usage: page('home.php') or page('listings.php')
 */
/**
 * Get URL for a page
 * Usage: page('home.php') or page('listings.php')
 */
function page($page) {
    $page = ltrim($page, '/');
    // Check if page already starts with pages/
    if (strpos($page, 'pages/') === 0) {
        $path = $page;
    } else {
        $path = 'pages/' . $page;
    }
    
    return BASE_URL . '/' . $path;
}

/**
 * Get URL for an asset (CSS, JS, images in assets folder)
 * Usage: asset('css/style.css') or asset('images/logo.png')
 */
function asset($path) {
    $path = ltrim($path, '/');
    return BASE_URL . '/assets/' . $path;
}

/**
 * Get URL for uploaded files
 * Usage: upload('product-image.jpg')
 */
function upload($filename) {
    if (empty($filename)) {
        return asset('images/products/default-product.png');
    }
    
    // Check if it's already a URL
    if (strpos($filename, 'http://') === 0 || strpos($filename, 'https://') === 0) {
        return $filename;
    }
    
    // Remove any leading slashes
    $filename = ltrim($filename, './');
    
    // Try different upload locations
    $locations = [
        'uploads/' . $filename,
        'assets/uploads/' . $filename,
        'assets/images/products/' . $filename
    ];
    
    foreach ($locations as $location) {
        if (file_exists(BASE_PATH . '/' . $location)) {
            return BASE_URL . '/' . $location;
        }
    }
    
    // Return default if not found
    return asset('images/products/default-product.png');
}

/**
 * Get URL for user avatar
 * Usage: avatar() or avatar('user123.jpg')
 */
function avatar($filename = null) {
    if (empty($filename)) {
        return asset('images/users/default-user.png');
    }
    
    $path = 'assets/images/users/' . $filename;
    if (file_exists(BASE_PATH . '/' . $path)) {
        return BASE_URL . '/' . $path;
    }
    
    return asset('images/users/default-user.png');
}

// Helper function for easier database access
function db() {
    return Database::getInstance();
}
?>