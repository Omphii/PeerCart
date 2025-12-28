<?php
// ==============================
// InfinityFree Database Config
// ==============================

define('DB_HOST', 'sql102.infinityfree.com');
define('DB_NAME', 'if0_40682740_peercart');
define('DB_USER', 'if0_40682740');
define('DB_PASS', 'SBAvsNvwtF');
define('DB_PORT', 3306);

// Enable debug mode only for development
define('DEBUG_MODE', false);

// Site configuration
define('SITE_NAME', 'PeerCart');
define('SITE_EMAIL', 'noreply@peercart.com');
define('ITEMS_PER_PAGE', 12);

// File upload configuration
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// Security settings
define('SESSION_TIMEOUT', 3600); // 1 hour
define('CSRF_TOKEN_LIFETIME', 1800); // 30 minutes
?>