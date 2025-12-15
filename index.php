<?php
ob_start();

require_once __DIR__.'/includes/bootstrap.php';

// Get the requested URL path
$requestUri = $_SERVER['REQUEST_URI'];
$baseDir = '/PeerCart2/';
$path = str_replace($baseDir, '', $requestUri);
$path = explode('?', $path)[0];
$path = trim($path, '/');

// Define public routes
$publicRoutes = ['login', 'register', 'forgot-password', ''];

// Redirect logged-in users away from auth pages
if (in_array($path, $publicRoutes) && isLoggedIn()) {
    redirectBasedOnRole();
}

// Route handling
switch ($path) {
    case '':
    case 'home':
        if (!isLoggedIn()) {
            require __DIR__.'/pages/home.php'; // Public landing page
        } else {
            header('Location: ' . BASE_PATH . 'dashboard');
            exit();
        }
        break;

    case 'auth':
        require __DIR__.'/includes/auth.php';
        break;

    case 'categories':
        require __DIR__.'/pages/categories.php';
        break;

    case 'sell':
        if (!isLoggedIn()) {
            header('Location: ' . BASE_PATH . 'login?redirect=sell');
            exit();
        }
        require __DIR__.'/pages/sell.php';
        break;

    case 'cart':
        require __DIR__.'/pages/cart.php';
        break;

    case 'dashboard':
        if (!isLoggedIn()) {
            header('Location: ' . BASE_PATH . 'login');
            exit();
        }
        require __DIR__.'/views/dashboard/index.php';
        break;

    case 'logout':
        require __DIR__.'/controllers/logout.php';
        break;

    case 'listings':
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = 12;
        require __DIR__.'/pages/listings/index.php';
        break;

    default:
        http_response_code(404); // set before output
        require __DIR__.'/pages/404.php';
        break;
}

ob_end_flush();
