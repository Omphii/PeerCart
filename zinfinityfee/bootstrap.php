<?php
// includes/bootstrap.php
// FIXED: Added session_status check to avoid session start errors
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define base paths
if (!defined('BASE_PATH')) {
    define('BASE_PATH', realpath(dirname(__DIR__)));
}

// For InfinityFree hosting - Simplified version
// FIXED: Added null coalescing operator for HTTP_HOST
// FIXED: Simplified script directory handling
if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'peercart.page.gd';
    
    // Get the script directory - FIXED: Handle null case
    $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
    
    // Remove any redundant paths for InfinityFree
    $scriptDir = str_replace('/pages', '', $scriptDir);
    $scriptDir = str_replace('/controllers', '', $scriptDir);
    $scriptDir = str_replace('/admin', '', $scriptDir);
    
    // Build base URL
    $baseUrl = $protocol . '://' . $host . $scriptDir;
    
    // Remove trailing slash if present
    $baseUrl = rtrim($baseUrl, '/');
    
    define('BASE_URL', $baseUrl);
    define('ASSETS_URL', BASE_URL . '/assets');
}

// Error reporting - FIXED: Enable temporarily for debugging
// Change back to 0 when site works
error_reporting(E_ALL);
ini_set('display_errors', 1);
// When site is working:
// error_reporting(0);
// ini_set('display_errors', 0);

// Include the Database class - FIXED: Added file existence check
$databaseFile = __DIR__ . '/database.php';
if (file_exists($databaseFile)) {
    require_once $databaseFile;
} else {
    // Try capitalized version
    $databaseFile = __DIR__ . '/Database.php';
    if (file_exists($databaseFile)) {
        require_once $databaseFile;
    }
    // No error - let the site try to load
}

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