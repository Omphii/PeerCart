<?php
/**
 * PeerCart - Core Functions
 * This file contains essential helper functions used throughout the site.
 * Keep functions organized and add proper documentation.
 */

// ============================================================================
// LOGGING FUNCTIONS
// ============================================================================

/**
 * Log user activity
 * 
 * @param string $message Activity message
 * @param array $context Additional context data
 * @return bool True if logged successfully
 */

// Add this at the top of functions.php to ensure BASE_URL is defined
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/bootstrap.php';
}

// Add this function to handle InfinityFree paths better
function getInfinityFreeUrl($path = '') {
    $base = BASE_URL;
    $path = ltrim($path, '/');
    
    // Handle special cases for InfinityFree
    if (strpos($path, 'assets/') === 0) {
        return $base . '/' . $path;
    }
    
    // For pages, ensure they're in the pages directory
    if (strpos($path, 'pages/') !== 0 && 
        !strpos($path, 'controllers/') && 
        !strpos($path, 'admin/') &&
        !strpos($path, 'api/')) {
        $path = 'pages/' . $path;
    }
    
    return $base . '/' . $path;
}

function log_activity($message, $context = []) {
    // Database logging
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare("
            INSERT INTO activity_logs 
            (user_id, activity_type, description, ip_address, user_agent, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        $user_id = $_SESSION['user_id'] ?? null;
        $activity_type = 'testimonial_submission'; // You can make this dynamic
        $description = $message . ' ' . json_encode($context);
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        return $stmt->execute([$user_id, $activity_type, $description, $ip_address, $user_agent]);
    } catch (Exception $e) {
        // Fallback to file logging if database fails
        error_log("Activity Log Error: " . $e->getMessage());
        return false;
    }
}

/**
 * URL Helper Functions for consistent URL handling
 */

/**
 * Get the correct URL for any page
 * This fixes the common issue with paths like /controllers/pages/...
 */
function getUrl($path = '', $params = []) {
    $base = BASE_URL;
    
    // Remove leading slash if present
    $path = ltrim($path, '/');
    
    // Handle different types of paths
    if (empty($path)) {
        // Home page
        return $base . '/';
    } elseif (strpos($path, 'assets/') === 0 || 
              strpos($path, 'controllers/') === 0 || 
              strpos($path, 'admin/') === 0) {
        // Assets, controllers, admin - direct path
        return $base . '/' . $path;
    } elseif (strpos($path, 'pages/') === 0) {
        // Already has pages/ prefix
        return $base . '/' . $path;
    } else {
        // Default: assume it's a page in pages directory
        return $base . '/pages/' . $path;
    }
}

/**
 * Alias for getUrl for cleaner syntax
 */
function url($path = '', $params = []) {
    // If path is already a full URL, return as-is
    if (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0) {
        $url = $path;
    } else {
        // Use getUrl function to get the base URL
        $url = getUrl($path);
    }
    
    // Add query parameters if provided
    if (!empty($params)) {
        $query = http_build_query($params);
        $url .= (strpos($url, '?') === false ? '?' : '&') . $query;
    }
    
    return $url;
}

/**
 * Asset URL helper
 */
function asset_url($path) {
    $base = BASE_URL;
    
    // Remove leading slash if present
    $path = ltrim($path, '/');
    
    // Ensure assets/ prefix
    if (strpos($path, 'assets/') !== 0) {
        $path = 'assets/' . $path;
    }
    
    return $base . '/' . $path;
}

/**
 * Redirect helper
 */
function redirect($path, $params = [], $statusCode = 302) {
    $url = url($path, $params);
    header("Location: $url", true, $statusCode);
    exit;
}

/**
 * Check if user can sell (has seller or admin user_type)
 */
function canSell($user_id = null) {
    if (!$user_id && isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
    }
    
    if (!$user_id) {
        return false;
    }
    
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT user_type FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_type = $stmt->fetchColumn();
    
    // Allow sellers and admins to sell
    return in_array($user_type, ['seller', 'admin']);
}

/**
 * Get user type
 */
function getUserType($user_id = null) {
    if (!$user_id && isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
    }
    
    if (!$user_id) {
        return 'guest';
    }
    
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT user_type FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetchColumn() ?: 'buyer';
}

/**
 * Check if current page is active (for navigation highlighting)
 */
function isActivePage($pageName) {
    $currentPage = basename($_SERVER['PHP_SELF']);
    return ($currentPage === $pageName);
}

// If SimpleLogger doesn't exist, create a basic version
if (!function_exists('app_log')) {
    /**
     * Main logging function - logs to both file and database if available
     */
    function app_log(string $message, string $type = 'INFO', array $context = []): void {
        // Always log to file using SimpleLogger
        if (class_exists('SimpleLogger')) {
            $logMessage = $message;
            if (!empty($context)) {
                $logMessage .= ' | Context: ' . json_encode($context);
            }
            SimpleLogger::log($logMessage, $type);
        }
        
        // Also try to log to database if Logger class exists
        if (class_exists('Logger')) {
            try {
                $userId = $_SESSION['user_id'] ?? null;
                
                // Map our log types to Logger methods
                switch (strtoupper($type)) {
                    case 'ERROR':
                        Logger::getInstance()->error($message, $context, $userId);
                        break;
                    case 'WARNING':
                        Logger::getInstance()->warning($message, $context, $userId);
                        break;
                    case 'DEBUG':
                        Logger::getInstance()->debug($message, $context, $userId);
                        break;
                    case 'SECURITY':
                        Logger::getInstance()->security($message, $context, $userId);
                        break;
                    default:
                        Logger::getInstance()->info($message, $context, $userId);
                        break;
                }
            } catch (Exception $e) {
                // If database logging fails, we still have file logging
                // No need to do anything here
            }
        }
    }
    
    /**
     * Quick function to log errors
     */
    function log_error(string $message, array $context = []): void {
        app_log($message, 'ERROR', $context);
    }
    
    /**
     * Quick function to log warnings
     */
    function log_warning(string $message, array $context = []): void {
        app_log($message, 'WARNING', $context);
    }
    
    /**
     * Quick function for info logs
     */
    function log_info(string $message, array $context = []): void {
        app_log($message, 'INFO', $context);
    }
    
    /**
     * Quick function for debug logs
     */
    function log_debug(string $message, array $context = []): void {
        app_log($message, 'DEBUG', $context);
    }
    
    /**
     * Quick function for security-related logs
     */
    function log_security(string $message, array $context = []): void {
        app_log($message, 'SECURITY', $context);
    }
}

// ============================================================================
// SESSION & AUTHENTICATION FUNCTIONS
// ============================================================================

/**
 * Check if user is currently logged in
 */
function isLoggedIn(): bool {
    // Make sure session is started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Check if user_id exists in session
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    // Optional: Check last activity for session timeout
    if (isset($_SESSION['last_activity'])) {
        $sessionTimeout = 3600; // 1 hour
        if ((time() - $_SESSION['last_activity']) > $sessionTimeout) {
            secureLogout();
            return false;
        }
        
        // Update last activity
        $_SESSION['last_activity'] = time();
    }
    
    return true;
}

/**
 * Get guest cart items with listing details
 */
function getGuestCartItems() {
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        // Check if guest cart exists and is an array
        if (!isset($_SESSION['guest_cart']) || !is_array($_SESSION['guest_cart']) || empty($_SESSION['guest_cart'])) {
            return [];
        }
        
        $ids = array_keys($_SESSION['guest_cart']);
        if (empty($ids)) {
            return [];
        }
        
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        
        $stmt = $conn->prepare("
            SELECT 
                l.id as listing_id,
                l.name as listing_name,
                l.description,
                l.price,
                l.image,
                l.quantity as stock_quantity,
                l.status as listing_status,
                l.seller_id,
                u.name as seller_name,
                u.city as seller_city
            FROM listings l
            LEFT JOIN users u ON l.seller_id = u.id
            WHERE l.id IN ($placeholders) 
              AND l.status = 'active'
              AND l.is_active = 1
            ORDER BY l.seller_id, l.created_at DESC
        ");
        
        $stmt->execute($ids);
        $listings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Add quantity from session - FIXED: session stores quantity as integer, not array
        foreach ($listings as &$listing) {
            $listingId = $listing['listing_id'];
            // Get the quantity (should be an integer)
            $listing['quantity'] = isset($_SESSION['guest_cart'][$listingId]) ? 
                (int)$_SESSION['guest_cart'][$listingId] : 0;
        }
        
        return $listings;
    } catch (Exception $e) {
        error_log("Error getting guest cart items: " . $e->getMessage());
        return [];
    }
}

/**
 * Check if current user is an admin
 */
function isAdmin(): bool {
    return isLoggedIn() && ($_SESSION['user_type'] ?? '') === 'admin';
}

/**
 * Check if current user is a seller
 */
function isSeller(): bool {
    return isLoggedIn() && ($_SESSION['user_type'] ?? '') === 'seller';
}

/**
 * Check if current user is a buyer
 */
function isBuyer(): bool {
    return isLoggedIn() && ($_SESSION['user_type'] ?? '') === 'buyer';
}

/**
 * Securely destroy user session
 */
function secureLogout(): void {
    // Clear all session variables
    $_SESSION = [];
    
    // Delete session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(), 
            '', 
            time() - 42000,
            $params["path"], 
            $params["domain"], 
            $params["secure"], 
            $params["httponly"]
        );
    }
    
    // Destroy the session
    session_destroy();
}

/**
 * Create a secure user session after successful login
 */
function createUserSession(array $user): void {
    // Regenerate session ID to prevent session fixation
    session_regenerate_id(true);
    
    // Set session variables
    $_SESSION = [
        'user_id'       => (int)$user['id'],
        'user_name'     => htmlspecialchars($user['name'] ?? 'User'),
        'user_surname'  => htmlspecialchars($user['surname'] ?? ''),
        'user_email'    => filter_var($user['email'], FILTER_SANITIZE_EMAIL),
        'user_type'     => htmlspecialchars($user['user_type'] ?? 'buyer'),
        'last_activity' => time(),
        'ip_address'    => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent'    => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'login_time'    => time()
    ];

    // Update last login in database
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);
    } catch (Exception $e) {
        // Log error but don't break the login process
        error_log("Failed to update last login: " . $e->getMessage());
    }
}

// ============================================================================
// CART HELPER FUNCTIONS
// ============================================================================

/**
 * Remove item from cart for logged-in or guest user
 */
function removeFromCart($user_id, $listing_id) {
    if ($user_id) {
        // Logged-in user: remove from database
        try {
            $db = Database::getInstance();
            $conn = $db->getConnection();
            $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = :user_id AND listing_id = :listing_id");
            return $stmt->execute([':user_id' => $user_id, ':listing_id' => $listing_id]);
        } catch (Exception $e) {
            error_log("Error removing from cart: " . $e->getMessage());
            return false;
        }
    } else {
        // Guest user: remove from session
        if (isset($_SESSION['guest_cart'][$listing_id])) {
            unset($_SESSION['guest_cart'][$listing_id]);
            return true;
        }
        return false;
    }
}

/**
 * Update cart quantity for logged-in or guest user
 */
function updateCartQuantity($user_id, $listing_id, $quantity) {
    $quantity = (int)$quantity; // Ensure quantity is integer
    
    if ($quantity <= 0) {
        return removeFromCart($user_id, $listing_id);
    }
    
    if ($user_id) {
        // Logged-in user: update in database
        try {
            $db = Database::getInstance();
            $conn = $db->getConnection();
            
            // Check if item already exists in cart
            $stmt = $conn->prepare("SELECT id FROM cart WHERE user_id = :user_id AND listing_id = :listing_id");
            $stmt->execute([':user_id' => $user_id, ':listing_id' => $listing_id]);
            
            if ($stmt->fetch()) {
                // Update existing item
                $stmt = $conn->prepare("UPDATE cart SET quantity = :quantity, updated_at = NOW() WHERE user_id = :user_id AND listing_id = :listing_id");
            } else {
                // Add new item
                $stmt = $conn->prepare("INSERT INTO cart (user_id, listing_id, quantity, created_at, updated_at) VALUES (:user_id, :listing_id, :quantity, NOW(), NOW())");
            }
            
            return $stmt->execute([':user_id' => $user_id, ':listing_id' => $listing_id, ':quantity' => $quantity]);
        } catch (Exception $e) {
            error_log("Error updating cart quantity: " . $e->getMessage());
            return false;
        }
    } else {
        // Guest user: update in session - store as integer
        if (!isset($_SESSION['guest_cart']) || !is_array($_SESSION['guest_cart'])) {
            $_SESSION['guest_cart'] = [];
        }
        $_SESSION['guest_cart'][$listing_id] = $quantity;
        return true;
    }
}

// Add to functions.php
function supportLink($tab = 'help-center', $text = 'Support', $icon = 'fa-headset') {
    return '<a href="javascript:void(0);" onclick="openSupportModal(\'' . $tab . '\')" class="support-link">
                <i class="fas ' . $icon . '"></i> ' . $text . '
            </a>';
}

/**
 * Get total quantity of items in cart
 * @return int Total quantity
 */
function getCartTotalQuantity() {
    if (isLoggedIn()) {
        $user_id = $_SESSION['user_id'];
        try {
            $db = Database::getInstance();
            $conn = $db->getConnection();
            $stmt = $conn->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = :user_id");
            $stmt->execute([':user_id' => $user_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($result['total'] ?? 0);
        } catch (Exception $e) {
            error_log("Error getting cart total quantity: " . $e->getMessage());
            return 0;
        }
    } else {
        // Guest user - FIXED: quantity is stored as integer, not array
        if (isset($_SESSION['guest_cart']) && is_array($_SESSION['guest_cart'])) {
            $total = 0;
            foreach ($_SESSION['guest_cart'] as $quantity) {
                $total += (int)$quantity;
            }
            return $total;
        }
        return 0;
    }
}

/**
 * Clear entire cart for logged-in or guest user
 */
function clearCart($user_id) {
    if ($user_id) {
        // Logged-in user: clear from database
        try {
            $db = Database::getInstance();
            $conn = $db->getConnection();
            $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = :user_id");
            return $stmt->execute([':user_id' => $user_id]);
        } catch (Exception $e) {
            error_log("Error clearing cart: " . $e->getMessage());
            return false;
        }
    } else {
        // Guest user: clear from session
        unset($_SESSION['guest_cart']);
        return true;
    }
}

// ============================================================================
// INPUT VALIDATION & SANITIZATION
// ============================================================================

/**
 * Clean user input to prevent XSS attacks
 */
function sanitizeInput($data, bool $allow_html = false) {
    // Handle arrays recursively
    if (is_array($data)) {
        return array_map(fn($item) => sanitizeInput($item, $allow_html), $data);
    }
    
    // Convert to string and trim
    $data = trim((string)$data);
    
    // Remove control characters
    $data = str_replace(["\0", "\r", "\n", "\t"], '', $data);
    
    // Remove script tags
    $data = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $data);
    
    // Apply appropriate sanitization
    if ($allow_html) {
        // Allow some HTML but sanitize it
        return filter_var($data, FILTER_SANITIZE_SPECIAL_CHARS);
    } else {
        // Convert special characters to HTML entities
        return htmlspecialchars(strip_tags($data), ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Validate password strength
 */
function validatePassword(string $password): array {
    $errors = [];
    
    // Minimum length check
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters";
    }
    
    // Uppercase letter check
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    }
    
    // Lowercase letter check
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter";
    }
    
    // Number check
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    }
    
    // Special character check
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = "Password must contain at least one special character";
    }
    
    return $errors;
}

/**
 * Validate user registration input
 */
function validateRegistrationInput(array $data): array {
    $errors = [];
    
    // Name validation (2-50 characters, letters, spaces, hyphens only)
    if (empty($data['name']) || strlen($data['name']) < 2 || !preg_match('/^[a-zA-Z\s\-]{2,50}$/', $data['name'])) {
        $errors[] = "Valid first name (2-50 characters) is required";
    }
    
    // Surname validation
    if (empty($data['surname']) || strlen($data['surname']) < 2 || !preg_match('/^[a-zA-Z\s\-]{2,50}$/', $data['surname'])) {
        $errors[] = "Valid last name (2-50 characters) is required";
    }
    
    // Email validation
    if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email address is required";
    } elseif (strlen($data['email']) > 100) {
        $errors[] = "Email address is too long";
    }
    
    // Password validation
    if (empty($data['password'])) {
        $errors[] = "Password is required";
    } else {
        $passwordErrors = validatePassword($data['password']);
        $errors = array_merge($errors, $passwordErrors);
    }
    
    // Password confirmation
    if (($data['password'] ?? '') !== ($data['confirm_password'] ?? '')) {
        $errors[] = "Passwords do not match";
    }
    
    // User type validation - FIXED: only 'buyer' or 'seller' allowed
    if (empty($data['user_type']) || !in_array($data['user_type'], ['buyer', 'seller'])) {
        $errors[] = "Please select a valid user type";
    }
    
    return $errors;
}

// ============================================================================
// DATABASE FUNCTIONS
// ============================================================================

/**
 * Get PDO database connection
 */
function getDBConnection(): PDO {
    try {
        return Database::getInstance()->getConnection();
    } catch (PDOException $e) {
        // Log the error
        error_log("Database connection failed: " . $e->getMessage());
        throw new RuntimeException('Database connection unavailable. Please try again later.');
    }
}

/**
 * Execute a database query safely
 */
function executeQuery(string $sql, array $params = []): PDOStatement {
    $db = getDBConnection();
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

/**
 * Check if email already exists in database
 */
function emailExists(string $email): bool {
    try {
        $stmt = executeQuery("SELECT id FROM users WHERE email = ?", [$email]);
        return $stmt->fetch() !== false;
    } catch (Exception $e) {
        error_log("Email check failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Authenticate user with email and password
 */
function authenticateUser(string $email, string $password): ?array {
    try {
        $db = getDBConnection();
        $stmt = $db->prepare("
            SELECT id, name, surname, email, password, user_type, is_active 
            FROM users 
            WHERE email = :email AND is_active = 1
            LIMIT 1
        ");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return null;
        }

        // Verify password
        if (password_verify($password, $user['password'])) {
            // Remove password from returned array
            unset($user['password']);
            return $user;
        }
        
        return null;

    } catch (Exception $e) {
        error_log("Authentication error: " . $e->getMessage());
        return null;
    }
}

function time_elapsed_string($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    $intervals = array(
        31536000 => 'year',
        2592000 => 'month',
        604800 => 'week',
        86400 => 'day',
        3600 => 'hour',
        60 => 'minute',
        1 => 'second'
    );
    
    if ($diff < 60) {
        return 'just now';
    }
    
    foreach ($intervals as $seconds => $label) {
        if ($diff >= $seconds) {
            $value = floor($diff / $seconds);
            return $value . ' ' . $label . ($value > 1 ? 's' : '') . ' ago';
        }
    }
    
    return date('M d, Y', $time);
}

// ============================================================================
// REDIRECTION FUNCTIONS
// ============================================================================

/**
 * Redirect user based on their role after login
 */
function redirectBasedOnRole(): string {
    $userType = $_SESSION['user_type'] ?? 'buyer';
    
    switch($userType) {
        case 'seller':
            return url('pages/dashboard.php');
        case 'admin':
            return url('admin/dashboard.php');
        default:
            return url('pages/home.php');
    }
}

// ============================================================================
// FLASH MESSAGE SYSTEM
// ============================================================================

/**
 * Store a flash message to display on next page load
 */
function setFlashMessage(string $message, string $type = 'info'): void {
    // Initialize flash messages array if needed
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    
    // Add the message
    $_SESSION['flash_messages'][] = [
        'message'   => $message,
        'type'      => $type,
        'timestamp' => time()
    ];
}

/**
 * Display all flash messages and clear them
 */
function displayFlashMessage(): void {
    if (empty($_SESSION['flash_messages'])) {
        return;
    }

    // Clean up old messages (older than 5 minutes)
    $now = time();
    $_SESSION['flash_messages'] = array_filter(
        $_SESSION['flash_messages'], 
        function($msg) use ($now) {
            return ($now - $msg['timestamp']) < 300;
        }
    );

    // Display each message
    foreach ($_SESSION['flash_messages'] as $i => $flash) {
        // Determine CSS class based on type
        $class = 'alert-info';
        switch(strtolower($flash['type'])) {
            case 'success':
                $class = 'alert-success';
                $icon = 'check-circle';
                break;
            case 'error':
            case 'danger':
                $class = 'alert-danger';
                $icon = 'exclamation-triangle';
                break;
            case 'warning':
                $class = 'alert-warning';
                $icon = 'exclamation-circle';
                break;
            default:
                $class = 'alert-info';
                $icon = 'info-circle';
        }
        
        // Output the message
        echo '<div class="alert ' . $class . ' alert-dismissible fade show" role="alert">';
        echo '<i class="fas fa-' . $icon . ' me-2"></i>';
        echo htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8');
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
        
        // Remove displayed message
        unset($_SESSION['flash_messages'][$i]);
    }
    
    // Clean up empty array
    if (empty($_SESSION['flash_messages'])) {
        unset($_SESSION['flash_messages']);
    }
}

// ============================================================================
// CATEGORY FUNCTIONS (MOVED TO TOP FOR ACCESSIBILITY)
// ============================================================================

function getCategories(?int $limit = null): array {
    try {
        $sql = "
            SELECT 
                c.id, 
                c.name, 
                c.slug, 
                c.icon, 
                COUNT(l.id) AS total_listings 
            FROM categories c 
            LEFT JOIN listings l ON c.id = l.category_id AND l.is_active = 1 
            WHERE c.is_active = 1 
            GROUP BY c.id, c.name, c.slug, c.icon 
            ORDER BY 
                CASE WHEN c.name = 'Other' THEN 2 ELSE 1 END, 
                total_listings DESC 
        ";
        
        if ($limit !== null) {
            $sql .= " LIMIT " . (int)$limit;
        }
        
        $stmt = executeQuery($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Failed to fetch categories: " . $e->getMessage());
        return [];
    }
}

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Fetch promotions - FIXED QUERY
 * The 'discount_price' doesn't exist, using original_price instead
 */
function getPromotions($limit = 8) {
    try {
        $db = Database::getInstance();
        return $db->getRows("
            SELECT 
                l.id, 
                l.name, 
                l.price, 
                l.original_price,
                -- Calculate discount percentage based on original_price
                CASE 
                    WHEN l.original_price IS NOT NULL AND l.original_price > 0 
                    THEN ROUND((1 - l.price / l.original_price) * 100)
                    ELSE 0 
                END AS discount_percent,
                u.name AS seller_name, 
                l.image
            FROM listings l
            JOIN users u ON l.seller_id = u.id
            WHERE l.original_price IS NOT NULL 
              AND l.original_price > l.price
              AND l.is_active = 1
              AND l.status = 'active'
            ORDER BY discount_percent DESC, l.created_at DESC
            LIMIT ?
        ", [$limit]);
    } catch (PDOException $e) {
        error_log("Promotions load error: " . $e->getMessage());
        return [];
    }
}

/**
 * Fetch recent listings - FIXED QUERY
 */
function getRecentListings($limit = 20) {
    try {
        $db = Database::getInstance();
        return $db->getRows("
            SELECT 
                l.id, 
                l.name AS title, 
                l.price, 
                l.image, 
                l.created_at,
                u.name AS seller_name, 
                u.city AS seller_city
            FROM listings l
            LEFT JOIN users u ON l.seller_id = u.id
            WHERE l.is_active = 1
              AND l.status = 'active'
            ORDER BY l.created_at DESC
            LIMIT ?
        ", [$limit]);
    } catch (PDOException $e) {
        error_log("Recent listings error: " . $e->getMessage());
        return [];
    }
}

/**
 * Fetch testimonials - FIXED QUERY
 */
function getTestimonials($limit = 6) {
    try {
        $db = Database::getInstance();
        return $db->getRows("
            SELECT 
                t.id, 
                u.name AS user_name, 
                t.testimonial_text, 
                t.rating, 
                t.created_at
            FROM testimonials t
            INNER JOIN users u ON t.user_id = u.id
            WHERE t.status = 'approved'
            ORDER BY t.created_at DESC
            LIMIT ?
        ", [$limit]);
    } catch (PDOException $e) {
        error_log("Testimonials error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get listing image with fallback
 */
function getListingImage($imagePath = null) {
    $defaultImage = BASE_URL . '/assets/images/products/default-product.png';
    
    if (empty($imagePath)) {
        return $defaultImage;
    }
    
    // Check if it's already a full URL
    if (strpos($imagePath, 'http://') === 0 || strpos($imagePath, 'https://') === 0) {
        return $imagePath;
    }
    
    // Define root path if not defined
    if (!defined('ROOT_PATH')) {
        define('ROOT_PATH', realpath(dirname(__DIR__)));
    }
    
    // Check if file exists locally
    $localPath = ROOT_PATH . '/' . ltrim($imagePath, '/');
    if (file_exists($localPath)) {
        return BASE_URL . '/' . ltrim($imagePath, '/');
    }
    
    // Try in uploads directory
    $uploadsPath = ROOT_PATH . '/uploads/listings/' . basename($imagePath);
    if (file_exists($uploadsPath)) {
        return BASE_URL . '/uploads/listings/' . basename($imagePath);
    }
    
    // Try in assets/uploads directory
    $assetsUploadsPath = ROOT_PATH . '/assets/uploads/' . basename($imagePath);
    if (file_exists($assetsUploadsPath)) {
        return BASE_URL . '/assets/uploads/' . basename($imagePath);
    }
    
    return $defaultImage;
}

/**
 * Get popular cities from user data
 */
function getCities(?int $limit = 8): array {
    try {
        $sql = "SELECT DISTINCT city FROM users WHERE city IS NOT NULL AND city != '' ORDER BY city";
        
        if ($limit !== null) {
            $sql .= " LIMIT " . (int)$limit;
        }
        
        $stmt = executeQuery($sql);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        error_log("Error fetching cities: " . $e->getMessage());
        return [];
    }
}

// ============================================================================
// FORMATTING FUNCTIONS
// ============================================================================

/**
 * Format price with South African Rand symbol (VAT INCLUSIVE)
 */
function format_zar(float $amount): string {
    return 'R ' . number_format($amount, 2, ',', ' ');
}

/**
 * Calculate the VAT portion from a VAT-inclusive price
 */
function calculate_vat_from_inclusive(float $amount_incl_vat, float $vat_rate = 0.15): float {
    $vat_amount = $amount_incl_vat * ($vat_rate / (1 + $vat_rate));
    return round($vat_amount, 2);
}

/**
 * Calculate price excluding VAT from a VAT-inclusive price
 */
function price_excluding_vat(float $amount_incl_vat, float $vat_rate = 0.15): float {
    $amount_excl_vat = $amount_incl_vat / (1 + $vat_rate);
    return round($amount_excl_vat, 2);
}

/**
 * Calculate price including VAT
 */
function price_including_vat(float $amount_excl_vat, float $vat_rate = 0.15): float {
    return round($amount_excl_vat * (1 + $vat_rate), 2);
}

/**
 * Format price breakdown for invoices/receipts
 */
function get_price_breakdown(float $amount_incl_vat, float $vat_rate = 0.15): array {
    $vat_amount = calculate_vat_from_inclusive($amount_incl_vat, $vat_rate);
    $amount_excl_vat = price_excluding_vat($amount_incl_vat, $vat_rate);
    
    return [
        'amount_excl_vat' => $amount_excl_vat,
        'vat_amount' => $vat_amount,
        'amount_incl_vat' => $amount_incl_vat,
        'vat_rate_percent' => $vat_rate * 100
    ];
}

/**
 * Display price with VAT info for product listings
 */
function display_price_with_vat(float $amount_incl_vat, bool $show_vat_text = true): string {
    $formatted = format_zar($amount_incl_vat);
    if ($show_vat_text) {
        $formatted .= ' <small class="text-muted">(incl. VAT)</small>';
    }
    return $formatted;
}

/**
 * Calculate total with VAT for multiple items
 */
function calculate_cart_total(array $prices, float $vat_rate = 0.15): array {
    $total_incl_vat = array_sum($prices);
    $total_excl_vat = 0;
    $total_vat = 0;
    
    foreach ($prices as $price) {
        $total_excl_vat += price_excluding_vat($price, $vat_rate);
        $total_vat += calculate_vat_from_inclusive($price, $vat_rate);
    }
    
    // Round to avoid floating point issues
    $total_excl_vat = round($total_excl_vat, 2);
    $total_vat = round($total_vat, 2);
    $total_incl_vat = round($total_incl_vat, 2);
    
    return [
        'subtotal_excl_vat' => $total_excl_vat,
        'vat_total' => $total_vat,
        'total_incl_vat' => $total_incl_vat
    ];
}

// ============================================================================
// STATISTICS FUNCTIONS
// ============================================================================

/**
 * Get total number of users (for footer stats)
 */
function getTotalUsers(): int {
    try {
        $stmt = executeQuery("SELECT COUNT(*) as count FROM users WHERE is_active = 1");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['count'] ?? 0);
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Get total number of active listings (for footer stats)
 */
function getTotalListings(): int {
    try {
        $stmt = executeQuery("SELECT COUNT(*) as count FROM listings WHERE is_active = 1 AND status = 'active'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['count'] ?? 0);
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Get total number of completed transactions (for footer stats)
 */
function getTotalTransactions(): int {
    try {
        $stmt = executeQuery("SELECT COUNT(*) as count FROM orders WHERE status IN ('delivered', 'completed')");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['count'] ?? 0);
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Get cart item count for current user
 */
function getCartCount($user_id = null): int {
    // Use provided user_id or get from session
    $user_id = $user_id ?? ($_SESSION['user_id'] ?? null);
    
    if ($user_id) {
        // Logged-in user: count from database
        try {
            $db = getDBConnection();
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM cart WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($result['count'] ?? 0);
        } catch (Exception $e) {
            return 0;
        }
    } else {
        // Guest user: count from session
        return isset($_SESSION['guest_cart']) ? count($_SESSION['guest_cart']) : 0;
    }
}

// ============================================================================
// HELPER FUNCTIONS FOR TEMPLATES
// ============================================================================

/**
 * Include a partial template file
 */
function includePartial($name, $data = []) {
    // Look for partial in includes directory
    $file = __DIR__ . '/' . $name . '.php';
    
    if (!file_exists($file)) {
        // Try in includes/partials directory
        $file = __DIR__ . '/partials/' . $name . '.php';
        if (!file_exists($file)) {
            // Try in parent includes directory
            $file = __DIR__ . '/../includes/' . $name . '.php';
            if (!file_exists($file)) {
                throw new Exception("Partial '{$name}' not found");
            }
        }
    }
    
    // Extract variables for use in partial
    extract($data);
    
    // Include the partial file
    include $file;
}

// ============================================================================
// CSRF TOKEN FUNCTIONS (Simplified Version)
// ============================================================================

/**
 * Generate a CSRF token for form protection
 */
function generateCSRFToken($purpose = 'default') {
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $token = bin2hex(random_bytes(32));
    
    // Debug logging
    error_log("DEBUG: Generating CSRF token for purpose: '$purpose'");
    error_log("DEBUG: Token generated: " . substr($token, 0, 20) . '...');
    
    // Initialize tokens array if not exists
    if (!isset($_SESSION['csrf_tokens'])) {
        $_SESSION['csrf_tokens'] = [];
    }
    
    // Store token with purpose
    $_SESSION['csrf_tokens'][$purpose] = $token;
    
    // Debug logging of session state
    error_log("DEBUG: Session ID: " . session_id());
    error_log("DEBUG: CSRF tokens in session: " . print_r($_SESSION['csrf_tokens'], true));
    
    return $token;
}

function validateCSRFToken($token, $purpose = 'default') {
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Debug logging
    error_log("DEBUG: Validating CSRF token for purpose: '$purpose'");
    error_log("DEBUG: Token received: " . ($token ? substr($token, 0, 20) . '...' : 'EMPTY'));
    error_log("DEBUG: Session ID: " . session_id());
    error_log("DEBUG: CSRF tokens in session: " . print_r($_SESSION['csrf_tokens'] ?? 'No tokens', true));
    
    if (!isset($_SESSION['csrf_tokens']) || !isset($_SESSION['csrf_tokens'][$purpose])) {
        error_log("DEBUG: No token found for purpose '$purpose' in session");
        return false;
    }
    
    $storedToken = $_SESSION['csrf_tokens'][$purpose];
    $isValid = hash_equals($storedToken, $token);
    
    error_log("DEBUG: Stored token: " . substr($storedToken, 0, 20) . '...');
    error_log("DEBUG: Tokens match: " . ($isValid ? 'YES' : 'NO'));
    
    // Remove token after validation (one-time use)
    unset($_SESSION['csrf_tokens'][$purpose]);
    
    return $isValid;
}

?>