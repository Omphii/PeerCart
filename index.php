<?php
/**
 * PeerCart - Main Entry Point
 * Routes requests to appropriate pages
 */

// Include bootstrap
require_once __DIR__ . '/includes/bootstrap.php';

// Get the requested path
$request = $_SERVER['REQUEST_URI'] ?? '/';
$basePath = BASE_PATH ?: '';

// Remove base path from request
if ($basePath && strpos($request, $basePath) === 0) {
    $request = substr($request, strlen($basePath));
}

// Remove query string
$request = strtok($request, '?');

// Default page
if ($request === '/' || $request === '') {
    require_once __DIR__ . '/pages/home.php';
    exit;
}

// Route to specific pages
$routes = [
    '/home' => 'home.php',
    '/shop' => 'listings.php',
    '/listing' => 'listing.php',
    '/cart' => 'cart.php',
    '/checkout' => 'checkout.php',
    '/dashboard' => 'dashboard.php',
    '/sell' => 'sell.php',
    '/categories' => 'categories.php',
    '/orders' => 'orders.php',
    '/wishlist' => 'wishlist.php',
    '/messages' => 'messages.php',
    '/settings' => 'settings.php',
    '/auth' => 'auth.php',
    '/login' => 'auth.php?mode=login',
    '/register' => 'auth.php?mode=register',
    '/about' => 'about.php',
    '/help' => 'help.php',
    '/contact' => 'contact.php',
    '/privacy' => 'privacy.php',
    '/terms' => 'terms.php',
    '/404' => '404.php',
];

// Check if route exists
foreach ($routes as $route => $page) {
    if ($request === $route || strpos($request, $route . '/') === 0) {
        // Extract parameters from URL
        $params = [];
        if (strpos($request, $route . '/') === 0) {
            $paramStr = substr($request, strlen($route) + 1);
            $params = explode('/', $paramStr);
        }
        
        // Add query parameters
        parse_str($_SERVER['QUERY_STRING'] ?? '', $queryParams);
        
        // Load the page
        if (strpos($page, '?') !== false) {
            list($page, $pageQuery) = explode('?', $page, 2);
            parse_str($pageQuery, $pageParams);
            $_GET = array_merge($pageParams, $_GET);
        }
        
        if (file_exists(__DIR__ . '/pages/' . $page)) {
            require_once __DIR__ . '/pages/' . $page;
            exit;
        }
    }
}

// If no route matches, try to find the page directly
$pagePath = __DIR__ . '/pages' . $request . '.php';
if (file_exists($pagePath)) {
    require_once $pagePath;
    exit;
}

// Check if it's a directory
$pagePath = __DIR__ . '/pages' . $request;
if (is_dir($pagePath) && file_exists($pagePath . '/index.php')) {
    require_once $pagePath . '/index.php';
    exit;
}

// 404 - Page not found
header("HTTP/1.0 404 Not Found");
require_once __DIR__ . '/pages/404.php';
exit;