<?php
/**
 * PeerCart Configuration
 * Store all your app settings here
 */

// ==================== ENVIRONMENT ====================
// Set environment: 'development' or 'production'
define('APP_ENV', 'development');

// ==================== DEBUG MODE ====================
define('DEBUG_MODE', APP_ENV === 'development');

// ==================== DATABASE CONFIGURATION ====================
// Development Database Configuration
if (APP_ENV === 'development') {
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'peercart_db');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_CHARSET', 'utf8mb4');
    define('DB_PORT', '3306');
} else {
    // Production Database Configuration
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'peercart_prod');
    define('DB_USER', 'peercart_user');
    define('DB_PASS', 'secure_password_here');
    define('DB_CHARSET', 'utf8mb4');
    define('DB_PORT', '3306');
}

// ==================== SITE CONFIGURATION ====================
define('SITE_NAME', 'PeerCart');
define('SITE_TAGLINE', 'Peer-to-Peer Marketplace');
define('SITE_EMAIL', 'noreply@peercart.com');
define('ADMIN_EMAIL', 'admin@peercart.com');
define('SUPPORT_EMAIL', 'support@peercart.com');

// ==================== PATH CONFIGURATION ====================
// Already defined in bootstrap.php, but re-define for clarity
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', realpath(dirname(__DIR__)));
}

if (!defined('UPLOADS_PATH')) {
    define('UPLOADS_PATH', ROOT_PATH . '/uploads');
    define('UPLOADS_LISTINGS_PATH', UPLOADS_PATH . '/listings');
    define('UPLOADS_PROFILE_PATH', UPLOADS_PATH . '/profile');
    define('UPLOADS_TEMP_PATH', UPLOADS_PATH . '/temp');
}

if (!defined('ASSETS_PATH')) {
    define('ASSETS_PATH', ROOT_PATH . '/assets');
    define('ASSETS_CSS_PATH', ASSETS_PATH . '/css');
    define('ASSETS_JS_PATH', ASSETS_PATH . '/js');
    define('ASSETS_IMAGES_PATH', ASSETS_PATH . '/images');
    define('ASSETS_FONTS_PATH', ASSETS_PATH . '/fonts');
}

// ==================== URL CONFIGURATION ====================
// Already defined in bootstrap.php
if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    define('BASE_URL', $protocol . '://' . $host . '/PeerCart2');
}

// ==================== SECURITY CONFIGURATION ====================
define('SESSION_TIMEOUT', 3600); // 1 hour
define('SESSION_REGEN_INTERVAL', 300); // Regenerate ID every 5 minutes
define('CSRF_TOKEN_LIFETIME', 3600); // 1 hour
define('PASSWORD_MIN_LENGTH', 8);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes in seconds

// ==================== FILE UPLOAD CONFIGURATION ====================
define('MAX_UPLOAD_SIZE', 5242880); // 5MB
define('MAX_UPLOAD_SIZE_MB', 5); // 5MB (for display)
define('ALLOWED_IMAGE_TYPES', [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/gif' => 'gif',
    'image/webp' => 'webp'
]);
define('ALLOWED_FILE_TYPES', [
    'application/pdf' => 'pdf',
    'application/msword' => 'doc',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx'
]);

// ==================== VAT CONFIGURATION (South Africa) ====================
define('VAT_RATE', 0.15); // 15%
define('VAT_RATE_PERCENT', 15); // 15%

// ==================== FEATURE FLAGS ====================
define('EMAIL_VERIFICATION_REQUIRED', false);
define('ALLOW_GUEST_CHECKOUT', true);
define('MAINTENANCE_MODE', false);
define('ALLOW_REGISTRATION', true);
define('ALLOW_SELLER_REGISTRATION', true);
define('REQUIRE_PHONE_VERIFICATION', false);

// ==================== PAYMENT CONFIGURATION ====================
define('CURRENCY', 'ZAR');
define('CURRENCY_SYMBOL', 'R');
define('CURRENCY_CODE', 'ZAR');
define('CURRENCY_DECIMALS', 2);
define('CURRENCY_THOUSAND_SEPARATOR', ' ');
define('CURRENCY_DECIMAL_SEPARATOR', ',');

// ==================== SHIPPING CONFIGURATION ====================
define('BASE_SHIPPING_COST', 50.00);
define('ADDITIONAL_ITEM_SHIPPING', 10.00);
define('FREE_SHIPPING_THRESHOLD', 500.00);
define('SHIPPING_PROVIDERS', ['The Courier Guy', 'Fastway', 'Pargo', 'PostNet']);
define('SHIPPING_DAYS', [
    'standard' => 3,
    'express' => 1,
    'overnight' => 1
]);

// ==================== EMAIL CONFIGURATION ====================
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-password');
define('SMTP_ENCRYPTION', 'tls');
define('EMAIL_FROM_NAME', SITE_NAME);
define('EMAIL_FROM_ADDRESS', SITE_EMAIL);

// ==================== API CONFIGURATION ====================
define('GOOGLE_MAPS_API_KEY', 'your-google-maps-api-key');
define('RECAPTCHA_SITE_KEY', 'your-recaptcha-site-key');
define('RECAPTCHA_SECRET_KEY', 'your-recaptcha-secret-key');

// ==================== CACHE CONFIGURATION ====================
define('CACHE_ENABLED', false);
define('CACHE_LIFETIME', 3600); // 1 hour
define('CACHE_DIR', ROOT_PATH . '/cache');

// ==================== LOGGING CONFIGURATION ====================
define('LOG_LEVEL', APP_ENV === 'development' ? 'DEBUG' : 'ERROR');
define('LOG_FILE', ROOT_PATH . '/logs/app.log');
define('LOG_MAX_SIZE', 10485760); // 10MB
define('LOG_BACKUP_COUNT', 5);

// ==================== PAGINATION CONFIGURATION ====================
define('ITEMS_PER_PAGE', 20);
define('ITEMS_PER_PAGE_MOBILE', 10);
define('PAGINATION_MAX_LINKS', 5);

// ==================== RATING CONFIGURATION ====================
define('MIN_RATING', 1);
define('MAX_RATING', 5);
define('RATING_DECIMALS', 1);

// ==================== NOTIFICATION CONFIGURATION ====================
define('NOTIFICATION_LIFETIME', 604800); // 7 days in seconds
define('MAX_NOTIFICATIONS', 50);
define('PUSH_NOTIFICATIONS_ENABLED', false);

// ==================== SEO CONFIGURATION ====================
define('META_DESCRIPTION_LENGTH', 160);
define('META_TITLE_LENGTH', 60);
define('SEO_FRIENDLY_URLS', true);
define('CANONICAL_URLS', true);

// ==================== PERFORMANCE CONFIGURATION ====================
define('COMPRESS_OUTPUT', true);
define('MINIFY_CSS', APP_ENV === 'production');
define('MINIFY_JS', APP_ENV === 'production');
define('BROWSER_CACHE', APP_ENV === 'production');
define('GZIP_COMPRESSION', APP_ENV === 'production');

// ==================== SOCIAL MEDIA CONFIGURATION ====================
define('FACEBOOK_URL', 'https://facebook.com/peercart');
define('TWITTER_URL', 'https://twitter.com/peercart');
define('INSTAGRAM_URL', 'https://instagram.com/peercart');
define('LINKEDIN_URL', 'https://linkedin.com/company/peercart');
define('YOUTUBE_URL', 'https://youtube.com/peercart');

// ==================== ANALYTICS CONFIGURATION ====================
define('GOOGLE_ANALYTICS_ID', 'UA-XXXXX-Y');
define('FACEBOOK_PIXEL_ID', 'XXXXXXXXXXXXXXX');

// ==================== LEGAL CONFIGURATION ====================
define('TERMS_VERSION', '1.0');
define('PRIVACY_VERSION', '1.0');
define('COOKIE_VERSION', '1.0');
define('RETURN_DAYS', 14);
define('WARRANTY_MONTHS', 6);
?>