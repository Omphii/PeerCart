<?php
/**
 * PeerCart2 Bootstrap File
 * Initializes application environment, constants, and core functionality
 */

// ==================== ERROR REPORTING ====================
if (getenv('APP_ENV') === 'production') {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// ==================== PATH CONSTANTS ====================
// Define these FIRST before using them
define('ROOT_PATH', realpath(dirname(__DIR__)));
define('ASSETS_PATH', ROOT_PATH . '/assets');
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('PAGES_PATH', ROOT_PATH . '/pages');
define('CONTROLLERS_PATH', ROOT_PATH . '/controllers');
define('UPLOADS_PATH', ROOT_PATH . '/uploads');

// ==================== URL CONSTANTS ====================
// Get the current URL structure
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

// Calculate base URL - handle subdirectory if present
$scriptDir = dirname($_SERVER['SCRIPT_NAME']);
$basePath = ($scriptDir === '/' || $scriptDir === '\\') ? '' : $scriptDir;
$baseUrl = $protocol . '://' . $host . $basePath;

define('BASE_URL', rtrim($baseUrl, '/'));
define('ASSETS_URL', BASE_URL . '/assets');
define('BASE_PATH', $basePath);

// ==================== LOGGER INITIALIZATION ====================
// First, define a simple logger that works even if database fails
if (!class_exists('SimpleLogger')) {
    class SimpleLogger {
        private static $logFile;
        
        public static function init() {
            $logDir = ROOT_PATH . '/logs';
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0755, true);
            }
            self::$logFile = $logDir . '/app.log';
        }
        
        public static function log($message, $type = 'INFO') {
            $timestamp = date('Y-m-d H:i:s');
            $logLine = "[$timestamp] [$type] $message\n";
            
            if (self::$logFile && is_writable(dirname(self::$logFile))) {
                @file_put_contents(self::$logFile, $logLine, FILE_APPEND | LOCK_EX);
            } else {
                error_log($logLine);
            }
        }
    }
}

// Initialize simple logger
SimpleLogger::init();

// ==================== START SESSION ====================
if (session_status() === PHP_SESSION_NONE) {
    session_name('PEERCART_SESSION');
    session_start([
        'cookie_lifetime' => 86400,        // 24 hours
        'cookie_secure'   => isset($_SERVER['HTTPS']), // secure if HTTPS
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict',
        'use_strict_mode' => true,
        'use_only_cookies' => true,
        'gc_maxlifetime'  => 86400
    ]);
}

// ==================== CSRF TOKEN CLEANUP ====================
if (isset($_SESSION['csrf_tokens']) && is_array($_SESSION['csrf_tokens'])) {
    $now = time();
    foreach ($_SESSION['csrf_tokens'] as $purpose => $tokenData) {
        if (isset($tokenData['expires']) && $now > $tokenData['expires']) {
            unset($_SESSION['csrf_tokens'][$purpose]);
        }
    }
}

// Initialize cart if not present
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Initialize flash messages if not present
if (!isset($_SESSION['flash_messages']) || !is_array($_SESSION['flash_messages'])) {
    $_SESSION['flash_messages'] = [];
}

// ==================== CORE INCLUDES ====================
// Load core files
$coreFiles = [
    'database.php',
    'functions.php',
    'validation.php',
    'helpers.php'
];

foreach ($coreFiles as $file) {
    $filePath = __DIR__ . '/' . $file;
    if (file_exists($filePath)) {
        require_once $filePath;
    } else {
        SimpleLogger::log("Core file not found: $file", 'WARNING');
    }
}

// Try to load Logger class
$loggerFile = __DIR__ . '/logger.php';
if (file_exists($loggerFile)) {
    require_once $loggerFile;
}

// ==================== TIMEZONE ====================
date_default_timezone_set('Africa/Johannesburg');

// ==================== SECURITY HEADERS ====================
if (!headers_sent()) {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    // CORS headers for API if needed
    if (isset($_SERVER['HTTP_ORIGIN'])) {
        header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400');
    }
}

// ==================== SANITIZE GLOBALS ====================
// Sanitize GET, POST, REQUEST arrays
function sanitize_global_array(&$array) {
    if (is_array($array)) {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                sanitize_global_array($value);
            } else {
                // Basic sanitization - prevent XSS
                $array[$key] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            }
        }
    }
}

// Only sanitize if not already sanitized
if (!isset($_SESSION['globals_sanitized'])) {
    sanitize_global_array($_GET);
    sanitize_global_array($_POST);
    sanitize_global_array($_REQUEST);
    $_SESSION['globals_sanitized'] = true;
}

// Log script start
SimpleLogger::log("Script started: " . ($_SERVER['REQUEST_URI'] ?? 'unknown') . 
                  " | IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 'INFO');
?>