<?php
/**
 * PeerCart - Core Functions
 * This file contains essential helper functions used throughout the site.
 */

// ============================================================================
// LOGGING FUNCTIONS
// ============================================================================

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
                
                switch (strtoupper($type)) {
                    case 'ERROR':   Logger::getInstance()->error($message, $context, $userId); break;
                    case 'WARNING': Logger::getInstance()->warning($message, $context, $userId); break;
                    case 'DEBUG':   Logger::getInstance()->debug($message, $context, $userId); break;
                    case 'SECURITY':Logger::getInstance()->security($message, $context, $userId); break;
                    default:        Logger::getInstance()->info($message, $context, $userId); break;
                }
            } catch (Exception $e) {
                error_log("Database logging failed: " . $e->getMessage());
            }
        }
    }
    
    function log_error(string $message, array $context = []): void {
        app_log($message, 'ERROR', $context);
    }
    
    function log_warning(string $message, array $context = []): void {
        app_log($message, 'WARNING', $context);
    }
    
    function log_info(string $message, array $context = []): void {
        app_log($message, 'INFO', $context);
    }
    
    function log_debug(string $message, array $context = []): void {
        app_log($message, 'DEBUG', $context);
    }
    
    function log_security(string $message, array $context = []): void {
        app_log($message, 'SECURITY', $context);
    }
}

// ============================================================================
// SESSION & AUTHENTICATION FUNCTIONS
// ============================================================================

function isLoggedIn(): bool {
    if (!isset($_SESSION['user_id'], $_SESSION['last_activity'])) {
        return false;
    }

    $sessionTimeout = 3600; // 1 hour
    if ((time() - $_SESSION['last_activity']) > $sessionTimeout) {
        secureLogout();
        return false;
    }

    $_SESSION['last_activity'] = time();
    return true;
}

function isAdmin(): bool {
    return isLoggedIn() && ($_SESSION['user_type'] ?? '') === 'admin';
}

function isSeller(): bool {
    return isLoggedIn() && ($_SESSION['user_type'] ?? '') === 'seller';
}

function isBuyer(): bool {
    return isLoggedIn() && ($_SESSION['user_type'] ?? '') === 'buyer';
}

function secureLogout(): void {
    $_SESSION = [];
    
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
    
    session_destroy();
}

function createUserSession(array $user): void {
    session_regenerate_id(true);
    
    $_SESSION = [
        'user_id'       => (int)$user['id'],
        'user_name'     => htmlspecialchars($user['name'] ?? 'User'),
        'user_surname'  => htmlspecialchars($user['surname'] ?? ''),
        'user_email'    => filter_var($user['email'], FILTER_SANITIZE_EMAIL),
        'user_type'     => htmlspecialchars($user['user_type'] ?? 'buyer'),
        'user_avatar'   => $user['profile_image'] ?? null,
        'last_activity' => time(),
        'ip_address'    => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent'    => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'login_time'    => time()
    ];

    // Update last login
    try {
        $db = getDBConnection();
        $stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);
    } catch (Exception $e) {
        log_error("Failed to update last login: " . $e->getMessage());
    }
}

// ============================================================================
// INPUT VALIDATION & SANITIZATION
// ============================================================================

function sanitizeInput($data, bool $allow_html = false) {
    if (is_array($data)) {
        return array_map(fn($item) => sanitizeInput($item, $allow_html), $data);
    }
    
    $data = trim((string)$data);
    $data = str_replace(["\0", "\r", "\n", "\t"], '', $data);
    $data = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $data);
    
    if ($allow_html) {
        return filter_var($data, FILTER_SANITIZE_SPECIAL_CHARS);
    } else {
        return htmlspecialchars(strip_tags($data), ENT_QUOTES, 'UTF-8');
    }
}

function validatePassword(string $password): array {
    $errors = [];
    
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters";
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter";
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    }
    
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = "Password must contain at least one special character";
    }
    
    return $errors;
}

function validateRegistrationInput(array $data): array {
    $errors = [];
    
    // Name validation
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

function getDBConnection(): PDO {
    try {
        return Database::getInstance()->getConnection();
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        throw new RuntimeException('Database connection unavailable. Please try again later.');
    }
}

function executeQuery(string $sql, array $params = []): PDOStatement {
    $db = getDBConnection();
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

function emailExists(string $email): bool {
    try {
        $stmt = executeQuery("SELECT id FROM users WHERE email = ?", [$email]);
        return $stmt->fetch() !== false;
    } catch (Exception $e) {
        error_log("Email check failed: " . $e->getMessage());
        return false;
    }
}

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
            password_verify($password, '$2y$10$' . str_repeat('a', 53));
            return null;
        }

        if (password_verify($password, $user['password'])) {
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
// URL & ASSET HELPER FUNCTIONS (IMPROVED VERSION)
// ============================================================================

/**
 * Generate URL for assets (CSS, JS, images)
 * Handles different types of asset paths with cache busting
 */
function asset($path, $version = false): string {
    if (empty($path)) {
        return BASE_URL . '/assets/images/products/default-product.png';
    }
    
    // Remove leading slash if present
    $path = ltrim($path, '/');
    
    // Check if it's already a full URL
    if (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0) {
        return $path;
    }
    
    // Check if file exists locally
    $localPath = ROOT_PATH . '/' . $path;
    
    // Common asset prefixes mapping
    $assetTypes = [
        'css/' => 'assets/css/',
        'js/' => 'assets/js/',
        'images/' => 'assets/images/',
        'fonts/' => 'assets/fonts/',
        'uploads/' => 'uploads/',
        'assets/' => 'assets/'
    ];
    
    // Check if path starts with any known prefix
    foreach ($assetTypes as $prefix => $folder) {
        if (strpos($path, $prefix) === 0) {
            // Already has correct prefix
            break;
        }
    }
    
    // If no known prefix, assume it's in assets folder
    if (!preg_match('/^(assets\/|uploads\/|css\/|js\/|images\/|fonts\/)/', $path)) {
        $path = 'assets/' . $path;
    }
    
    // Build full URL
    $assetUrl = BASE_URL . '/' . $path;
    
    // Add cache busting version if requested and file exists
    if ($version && file_exists(ROOT_PATH . '/' . $path)) {
        $filemtime = @filemtime(ROOT_PATH . '/' . $path);
        $assetUrl .= '?v=' . ($filemtime ?: time());
    }
    
    return $assetUrl;
}

/**
 * Get image URL with fallback
 */
function getImageUrl(?string $imagePath, string $type = 'listing'): string {
    if (empty($imagePath)) {
        switch ($type) {
            case 'profile':
                return asset('images/users/default-user.png');
            case 'product':
            case 'listing':
            default:
                return asset('images/products/default-product.png');
        }
    }
    
    // Check if it's a full URL
    if (filter_var($imagePath, FILTER_VALIDATE_URL)) {
        return $imagePath;
    }
    
    // Check common locations
    $possiblePaths = [
        'uploads/listings/' . basename($imagePath),
        'uploads/profile/' . basename($imagePath),
        'assets/uploads/' . basename($imagePath),
        'uploads/' . basename($imagePath),
        $imagePath
    ];
    
    foreach ($possiblePaths as $path) {
        $fullPath = ROOT_PATH . '/' . $path;
        if (file_exists($fullPath) && is_file($fullPath)) {
            return asset($path);
        }
    }
    
    // Return default if not found
    return asset('images/products/default-product.png');
}

/**
 * Generate full URL for site pages
 */
function url($path, $query = []): string {
    $path = ltrim($path, '/');
    $url = BASE_URL . '/' . $path;
    
    if (!empty($query)) {
        $url .= '?' . http_build_query($query);
    }
    
    return $url;
}

// ============================================================================
// REDIRECTION FUNCTIONS
// ============================================================================

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

function setFlashMessage(string $message, string $type = 'info'): void {
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    
    $_SESSION['flash_messages'][] = [
        'message'   => $message,
        'type'      => $type,
        'timestamp' => time()
    ];
}

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

function format_zar(float $amount): string {
    return 'R ' . number_format($amount, 2, ',', ' ');
}

function calculate_vat_from_inclusive(float $amount_incl_vat, float $vat_rate = 0.15): float {
    $vat_amount = $amount_incl_vat * ($vat_rate / (1 + $vat_rate));
    return round($vat_amount, 2);
}

function price_excluding_vat(float $amount_incl_vat, float $vat_rate = 0.15): float {
    $amount_excl_vat = $amount_incl_vat / (1 + $vat_rate);
    return round($amount_excl_vat, 2);
}

function price_including_vat(float $amount_excl_vat, float $vat_rate = 0.15): float {
    return round($amount_excl_vat * (1 + $vat_rate), 2);
}

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

function display_price_with_vat(float $amount_incl_vat, bool $show_vat_text = true): string {
    $formatted = format_zar($amount_incl_vat);
    if ($show_vat_text) {
        $formatted .= ' <small class="text-muted">(incl. VAT)</small>';
    }
    return $formatted;
}

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

function getTotalUsers(): int {
    try {
        $stmt = executeQuery("SELECT COUNT(*) as count FROM users WHERE is_active = 1");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['count'] ?? 0);
    } catch (Exception $e) {
        return 0;
    }
}

function getTotalListings(): int {
    try {
        $stmt = executeQuery("SELECT COUNT(*) as count FROM listings WHERE is_active = 1 AND status = 'active'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['count'] ?? 0);
    } catch (Exception $e) {
        return 0;
    }
}

function getTotalTransactions(): int {
    try {
        $stmt = executeQuery("SELECT COUNT(*) as count FROM orders WHERE status IN ('delivered', 'completed')");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['count'] ?? 0);
    } catch (Exception $e) {
        return 0;
    }
}

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
// CSRF TOKEN FUNCTIONS
// ============================================================================

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

// ============================================================================
// ADDITIONAL HELPER FUNCTIONS (NEW)
// ============================================================================

/**
 * Time ago formatting
 */
function time_elapsed_string($datetime, $full = false): string {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = [
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    ];
    
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}

/**
 * Get user's full name
 */
function getUserFullName(?int $userId = null): string {
    if ($userId === null && isset($_SESSION['user_id'])) {
        $firstName = $_SESSION['user_name'] ?? '';
        $lastName = $_SESSION['user_surname'] ?? '';
        return trim($firstName . ' ' . $lastName) ?: 'User';
    }
    
    // If user ID provided, fetch from database
    try {
        $db = getDBConnection();
        $stmt = $db->prepare("SELECT name, surname FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if ($user) {
            return trim($user['name'] . ' ' . $user['surname']) ?: 'User';
        }
    } catch (Exception $e) {
        log_error("Failed to get user name: " . $e->getMessage());
    }
    
    return 'User';
}

/**
 * Get user's profile image URL
 */
function getUserProfileImage(?int $userId = null): string {
    $defaultImage = asset('images/users/default-user.png');
    
    if ($userId === null && isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
    }
    
    if (!$userId) {
        return $defaultImage;
    }
    
    try {
        $db = getDBConnection();
        $stmt = $db->prepare("SELECT profile_image FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if ($user && !empty($user['profile_image'])) {
            return getImageUrl($user['profile_image'], 'profile');
        }
    } catch (Exception $e) {
        log_error("Failed to get user profile image: " . $e->getMessage());
    }
    
    return $defaultImage;
}

/**
 * Sanitize filename for upload
 */
function sanitizeFilename(string $filename): string {
    $filename = preg_replace('/[^a-zA-Z0-9\.\-\_]/', '_', $filename);
    $filename = preg_replace('/_{2,}/', '_', $filename);
    return time() . '_' . $filename;
}

/**
 * Generate referral code
 */
function generateReferralCode(): string {
    return strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
}

/**
 * Validate email address
 */
function isValidEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate South African phone number
 */
function isValidPhone(string $phone): bool {
    $clean = preg_replace('/\D/', '', $phone);
    return preg_match('/^0[0-9]{9,10}$/', $clean);
}

/**
 * Format phone number for display
 */
function formatPhone(string $phone): string {
    $clean = preg_replace('/\D/', '', $phone);
    
    if (strlen($clean) === 10) {
        return preg_replace('/(\d{3})(\d{3})(\d{4})/', '$1 $2 $3', $clean);
    } elseif (strlen($clean) === 11) {
        return preg_replace('/(\d{3})(\d{4})(\d{4})/', '$1 $2 $3', $clean);
    }
    
    return $phone;
}

/**
 * Redirect with message
 */
function redirectWithMessage(string $url, string $message, string $type = 'success'): void {
    setFlashMessage($message, $type);
    header("Location: $url");
    exit;
}

/**
 * Check if string is JSON
 */
function isJson(string $string): bool {
    json_decode($string);
    return json_last_error() === JSON_ERROR_NONE;
}

/**
 * Get current page URL
 */
function currentUrl(): string {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    return $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

/**
 * Get base URL without query string
 */
function baseUrl(): string {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $script = $_SERVER['SCRIPT_NAME'];
    
    return $protocol . '://' . $host . dirname($script);
}

/**
 * Generate pagination links
 */
function paginate(int $totalItems, int $perPage, int $currentPage, string $baseUrl): array {
    $totalPages = ceil($totalItems / $perPage);
    
    $pagination = [
        'total_items' => $totalItems,
        'total_pages' => $totalPages,
        'current_page' => $currentPage,
        'per_page' => $perPage,
        'has_previous' => $currentPage > 1,
        'has_next' => $currentPage < $totalPages,
        'pages' => []
    ];
    
    // Calculate page range
    $start = max(1, $currentPage - 2);
    $end = min($totalPages, $currentPage + 2);
    
    for ($i = $start; $i <= $end; $i++) {
        $pagination['pages'][] = [
            'number' => $i,
            'url' => $baseUrl . '?page=' . $i,
            'is_current' => $i === $currentPage
        ];
    }
    
    return $pagination;
}

/**
 * Truncate text with ellipsis
 */
function truncate(string $text, int $length = 100, string $ellipsis = '...'): string {
    if (strlen($text) <= $length) {
        return $text;
    }
    
    $text = substr($text, 0, $length);
    $text = substr($text, 0, strrpos($text, ' '));
    return $text . $ellipsis;
}

/**
 * Generate slug from string
 */
function slugify(string $text): string {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    
    if (empty($text)) {
        return 'n-a';
    }
    
    return $text;
}

/**
 * Check if request is AJAX
 */
function isAjax(): bool {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Send JSON response
 */
function jsonResponse(array $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Get client IP address
 */
function getClientIp(): string {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    
    return $ip;
}

/**
 * Generate order number
 */
function generateOrderNumber(): string {
    return 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}
?>