<?php
/**
 * PeerCart - Core Functions
 * This file contains essential helper functions used throughout the site.
 * Keep functions organized and add proper documentation.
 */

// ============================================================================
// LOGGING FUNCTIONS
// ============================================================================

// If SimpleLogger doesn't exist, create a basic version
if (!function_exists('app_log')) {
    /**
     * Main logging function - logs to both file and database if available
     * 
     * @param string $message The message to log
     * @param string $type Log type: INFO, ERROR, WARNING, DEBUG, SECURITY
     * @param array $context Additional data to include with the log
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
 * Validates session timeout and security checks
 * 
 * @return bool True if user is logged in and session is valid
 */
function isLoggedIn(): bool {
    // Check if session variables exist
    if (!isset($_SESSION['user_id'], $_SESSION['last_activity'])) {
        return false;
    }

    // Session timeout check - 1 hour (3600 seconds)
    $sessionTimeout = 3600;
    if ((time() - $_SESSION['last_activity']) > $sessionTimeout) {
        secureLogout();
        return false;
    }

    // Update last activity timestamp
    $_SESSION['last_activity'] = time();
    return true;
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
 * Clears all session data and removes session cookie
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
 * 
 * @param array $user User data from database
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
        $db = getDBConnection();
        $stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);
    } catch (Exception $e) {
        // Log error but don't break the login process
        error_log("Failed to update last login: " . $e->getMessage());
    }
}

// ============================================================================
// INPUT VALIDATION & SANITIZATION
// ============================================================================

/**
 * Clean user input to prevent XSS attacks
 * 
 * @param mixed $data The data to sanitize
 * @param bool $allow_html Whether to allow HTML (use carefully!)
 * @return mixed Sanitized data
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
 * 
 * @param string $password Password to validate
 * @return array Array of error messages (empty if password is valid)
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
 * 
 * @param array $data Registration form data
 * @return array Array of validation errors
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
    
    // User type validation
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
 * 
 * @return PDO Database connection object
 * @throws RuntimeException If connection fails
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
 * 
 * @param string $sql SQL query with placeholders
 * @param array $params Parameters for the query
 * @return PDOStatement The executed statement
 * @throws PDOException If query fails
 */
function executeQuery(string $sql, array $params = []): PDOStatement {
    $db = getDBConnection();
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

/**
 * Check if email already exists in database
 * 
 * @param string $email Email to check
 * @return bool True if email exists, false otherwise
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
 * 
 * @param string $email User email
 * @param string $password User password
 * @return array|null User data if authenticated, null otherwise
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

        // If user not found, still verify password to prevent timing attacks
        if (!$user) {
            // Use a dummy password hash with same length as our real hashes
            password_verify($password, '$2y$10$' . str_repeat('a', 53));
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

// ============================================================================
// URL & ASSET HELPER FUNCTIONS
// ============================================================================

/**
 * Generate URL for assets (CSS, JS, images)
 * Handles different types of asset paths
 * 
 * @param string $path Relative path to asset
 * @return string Full URL to the asset
 */
function asset($path) {
    // Remove leading slash if present
    $path = ltrim($path, '/');
    
    // Check if it's already a full URL
    if (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0) {
        return $path;
    }
    
    // Check for different asset types
    if (strpos($path, 'assets/') === 0) {
        // Already in assets folder
        return BASE_URL . '/' . $path;
    } elseif (strpos($path, 'uploads/') === 0) {
        // Uploaded file
        return BASE_URL . '/' . $path;
    } elseif (strpos($path, 'css/') === 0) {
        // CSS file
        return BASE_URL . '/assets/css/' . substr($path, 4);
    } elseif (strpos($path, 'js/') === 0) {
        // JS file
        return BASE_URL . '/assets/js/' . substr($path, 3);
    } elseif (strpos($path, 'images/') === 0) {
        // Image file
        return BASE_URL . '/assets/images/' . substr($path, 7);
    }
    
    // Default to assets folder
    return BASE_URL . '/assets/' . $path;
}

/**
 * Generate full URL for site pages
 * 
 * @param string $path Page path
 * @return string Full URL
 */
function url($path) {
    $path = ltrim($path, '/');
    return BASE_URL . '/' . $path;
}

// ============================================================================
// REDIRECTION FUNCTIONS
// ============================================================================

/**
 * Redirect user based on their role after login
 * 
 * @return string Redirect URL
 */
function redirectBasedOnRole(): string {
    $userType = $_SESSION['user_type'] ?? 'buyer';
    
    switch($userType) {
        case 'seller':
            return url('pages/dashboard.php');
        case 'admin':
            return url('admin/dashboard.php');
        default:
            return url('');
    }
}

// ============================================================================
// FLASH MESSAGE SYSTEM
// ============================================================================

/**
 * Store a flash message to display on next page load
 * 
 * @param string $message The message to display
 * @param string $type Message type: success, error, warning, info
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
 * Call this function in your template where you want messages to appear
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
            return ($now - $msg['timestamp']) < 300; // 5 minutes
        }
    );

    // Display each message
    foreach ($_SESSION['flash_messages'] as $i => $flash) {
        // Determine CSS class based on type
        $class = 'alert-info'; // default
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
// CATEGORY & LISTING FUNCTIONS
// ============================================================================

/**
 * Get all active categories with listing counts
 * 
 * @param int|null $limit Maximum number of categories to return
 * @return array List of categories
 */
function getCategories(?int $limit = null): array {
    try {
        $sql = "
            SELECT c.id, c.name, c.slug, c.icon, COUNT(l.id) AS total_listings
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

function getListingImage($imagePath = null) {
    $defaultImage = BASE_URL . '/assets/images/products/default-product.png';
    
    if (empty($imagePath)) {
        return $defaultImage;
    }
    
    // Check if it's already a full URL
    if (strpos($imagePath, 'http://') === 0 || strpos($imagePath, 'https://') === 0) {
        return $imagePath;
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
 * 
 * @param int|null $limit Maximum number of cities to return
 * @return array List of cities
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
 * All prices displayed to customers should be VAT inclusive
 * 
 * @param float $amount Price amount (VAT inclusive)
 * @return string Formatted price string with R symbol
 */
function format_zar(float $amount): string {
    return 'R ' . number_format($amount, 2, ',', ' ');
}

/**
 * Calculate the VAT portion from a VAT-inclusive price
 * Use this when you need to show the VAT amount separately on invoices
 * 
 * @param float $amount_incl_vat VAT inclusive price
 * @param float $vat_rate VAT rate (default 15% = 0.15)
 * @return float VAT amount contained in the price
 */
function calculate_vat_from_inclusive(float $amount_incl_vat, float $vat_rate = 0.15): float {
    // Formula: VAT = PriceInclusive × (VAT Rate / (1 + VAT Rate))
    $vat_amount = $amount_incl_vat * ($vat_rate / (1 + $vat_rate));
    return round($vat_amount, 2);
}

/**
 * Calculate price excluding VAT from a VAT-inclusive price
 * Use this for accounting or when storing prices in database
 * 
 * @param float $amount_incl_vat VAT inclusive price
 * @param float $vat_rate VAT rate (default 15%)
 * @return float Price excluding VAT
 */
function price_excluding_vat(float $amount_incl_vat, float $vat_rate = 0.15): float {
    // Formula: PriceExcluding = PriceInclusive / (1 + VAT Rate)
    $amount_excl_vat = $amount_incl_vat / (1 + $vat_rate);
    return round($amount_excl_vat, 2);
}

/**
 * Calculate price including VAT (old function - keep for backwards compatibility)
 * But note: In South Africa, all displayed prices SHOULD be VAT inclusive
 * 
 * @param float $amount_excl_vat Price excluding VAT
 * @param float $vat_rate VAT rate (default 15%)
 * @return float Price including VAT
 */
function price_including_vat(float $amount_excl_vat, float $vat_rate = 0.15): float {
    return round($amount_excl_vat * (1 + $vat_rate), 2);
}

/**
 * Format price breakdown for invoices/receipts
 * Shows both VAT inclusive and exclusive amounts
 * 
 * @param float $amount_incl_vat VAT inclusive price
 * @param float $vat_rate VAT rate (default 15%)
 * @return array Price breakdown array
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
 * Shows "R XX.XX (incl. VAT)" for clarity
 * 
 * @param float $amount_incl_vat VAT inclusive price
 * @param bool $show_vat_text Whether to show "(incl. VAT)" text
 * @return string Formatted price with optional VAT text
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
 * Use this for shopping cart totals
 * 
 * @param array $prices Array of VAT inclusive prices
 * @param float $vat_rate VAT rate (default 15%)
 * @return array Total breakdown
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
// CART FUNCTIONS
// ============================================================================

/**
 * Get total number of users (for footer stats)
 * 
 * @return int Total active users
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
 * 
 * @return int Total active listings
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
 * 
 * @return int Total completed transactions
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
 * Works for both logged-in users (database) and guests (session)
 * 
 * @param int|null $user_id User ID (null for current user)
 * @return int Number of items in cart
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
 * 
 * @param string $name Partial file name (without .php)
 * @param array $data Variables to extract for the partial
 * @throws Exception If partial file not found
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
 * This is a simplified version - you can enhance it later
 * 
 * @param string $purpose Purpose of the token (e.g., 'login', 'register')
 * @return string The generated token
 */
function generateCSRFToken(string $purpose = 'general'): string {
    // Initialize token array if needed
    if (!isset($_SESSION['csrf_tokens'])) {
        $_SESSION['csrf_tokens'] = [];
    }
    
    // Generate random token
    $token = bin2hex(random_bytes(32));
    
    // Store token with expiration (1 hour)
    $_SESSION['csrf_tokens'][$purpose] = [
        'token' => $token,
        'expires' => time() + 3600
    ];
    
    return $token;
}

/**
 * Validate a CSRF token
 * 
 * @param string $token The token to validate
 * @param string $purpose Purpose the token was generated for
 * @return bool True if token is valid, false otherwise
 */
function validateCSRFToken(string $token, string $purpose = 'general'): bool {
    // Check if token exists
    if (!isset($_SESSION['csrf_tokens'][$purpose])) {
        return false;
    }
    
    $storedToken = $_SESSION['csrf_tokens'][$purpose];
    
    // Check if token expired
    if (time() > $storedToken['expires']) {
        unset($_SESSION['csrf_tokens'][$purpose]);
        return false;
    }
    
    // Compare tokens securely (timing-attack safe)
    if (hash_equals($storedToken['token'], $token)) {
        // Remove token after successful validation (one-time use)
        unset($_SESSION['csrf_tokens'][$purpose]);
        return true;
    }
    
    return false;
}
?>