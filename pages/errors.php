<?php
// pages/error.php - Single Error Page Handler
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__.'/../includes/bootstrap.php';

// Get error code from URL parameter, default to 404
$error_code = isset($_GET['code']) ? (int)$_GET['code'] : 404;

// Define all error details
$errors = [
    400 => [
        'title' => 'Bad Request',
        'message' => 'The server couldn\'t understand your request. Please check the URL or the information you provided and try again.',
        'icon' => 'fa-exclamation-triangle',
        'help_link' => page('contact.php'),
        'help_text' => 'contact our support team',
        'buttons' => [
            ['text' => 'Go Home', 'icon' => 'fa-home', 'url' => page('index.php'), 'primary' => true],
            ['text' => 'Go Back', 'icon' => 'fa-arrow-left', 'url' => 'javascript:history.back()', 'primary' => false]
        ]
    ],
    401 => [
        'title' => 'Unauthorized Access',
        'message' => 'You need to be logged in to access this page. Please sign in to continue.',
        'icon' => 'fa-user-lock',
        'help_link' => page('register.php'),
        'help_text' => 'Create one now',
        'buttons' => [
            ['text' => 'Sign In', 'icon' => 'fa-sign-in-alt', 'url' => page('login.php'), 'primary' => true],
            ['text' => 'Go Home', 'icon' => 'fa-home', 'url' => page('index.php'), 'primary' => false]
        ]
    ],
    403 => [
        'title' => 'Access Forbidden',
        'message' => 'You don\'t have permission to access this page or resource. If you believe this is an error, please contact the administrator.',
        'icon' => 'fa-ban',
        'help_link' => page('contact.php'),
        'help_text' => 'Contact our support team',
        'buttons' => [
            ['text' => 'Go Home', 'icon' => 'fa-home', 'url' => page('index.php'), 'primary' => true],
            ['text' => 'Go Back', 'icon' => 'fa-arrow-left', 'url' => 'javascript:history.back()', 'primary' => false]
        ]
    ],
    404 => [
        'title' => 'Page Not Found',
        'message' => 'The page you\'re looking for doesn\'t exist or has been moved. Let\'s help you find what you\'re looking for.',
        'icon' => 'fa-map-marker-alt',
        'help_link' => page('categories.php'),
        'help_text' => 'categories',
        'buttons' => [
            ['text' => 'Go Home', 'icon' => 'fa-home', 'url' => page('index.php'), 'primary' => true],
            ['text' => 'Browse Listings', 'icon' => 'fa-search', 'url' => page('listings.php'), 'primary' => false]
        ]
    ],
    500 => [
        'title' => 'Internal Server Error',
        'message' => 'Something went wrong on our servers. Our team has been notified and we\'re working to fix it. Please try again later.',
        'icon' => 'fa-server',
        'help_link' => page('contact.php'),
        'help_text' => 'contact our support team',
        'buttons' => [
            ['text' => 'Go Home', 'icon' => 'fa-home', 'url' => page('index.php'), 'primary' => true],
            ['text' => 'Try Again', 'icon' => 'fa-redo', 'url' => 'javascript:window.location.reload()', 'primary' => false]
        ]
    ],
    503 => [
        'title' => 'Service Unavailable',
        'message' => 'We\'re currently performing maintenance or experiencing temporary high traffic. Please check back soon.',
        'icon' => 'fa-tools',
        'help_link' => '#',
        'help_text' => 'Twitter',
        'buttons' => [
            ['text' => 'Go Home', 'icon' => 'fa-home', 'url' => page('index.php'), 'primary' => true],
            ['text' => 'Refresh Page', 'icon' => 'fa-redo', 'url' => 'javascript:window.location.reload()', 'primary' => false]
        ],
        'maintenance_info' => true
    ]
];

// If error code doesn't exist in our array, default to 404
if (!isset($errors[$error_code])) {
    $error_code = 404;
}

// Get error details
$error = $errors[$error_code];

// Set page title and HTTP response code
$title = 'PeerCart - ' . $error['title'] . ' (' . $error_code . ')';
$currentPage = 'error';
http_response_code($error_code);

// Include header
require_once __DIR__ . '/../includes/header.php';

// Add the external CSS file
echo '<link rel="stylesheet" href="' . asset('css/pages/errors.css') . '">';
?>

<div class="error-page error-<?php echo $error_code; ?>">
    <div class="error-container">
        <div class="error-card">
            <div class="error-icon">
                <i class="fas <?php echo $error['icon']; ?>"></i>
            </div>
            <h1 class="error-code"><?php echo $error_code; ?></h1>
            <h2 class="error-title"><?php echo $error['title']; ?></h2>
            <p class="error-message">
                <?php echo $error['message']; ?>
            </p>
            <div class="error-actions">
                <?php foreach ($error['buttons'] as $button): ?>
                    <a href="<?php echo $button['url']; ?>" 
                       class="error-btn <?php echo $button['primary'] ? 'error-btn-primary' : 'error-btn-secondary'; ?>">
                        <i class="fas <?php echo $button['icon']; ?>"></i> <?php echo $button['text']; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        
        <?php if ($error_code === 404): ?>
        <!-- Search box only for 404 -->
        <div class="error-search">
            <p style="margin-bottom: var(--space-sm);">Try searching for what you need:</p>
            <form action="<?php echo page('listings.php'); ?>" method="GET" class="search-form">
                <input type="text" name="search" placeholder="Search for products, categories..." class="search-input">
                <button type="submit" class="search-btn">
                    <i class="fas fa-search"></i> Search
                </button>
            </form>
        </div>
        <?php endif; ?>
        
        <?php if (isset($error['maintenance_info']) && $error['maintenance_info']): ?>
        <!-- Maintenance info for 503 -->
        <div class="maintenance-info">
            <h4><i class="fas fa-info-circle"></i> Maintenance Information</h4>
            <p>We're working hard to improve your experience. During this time:</p>
            <ul>
                <li>New listings and purchases may be temporarily unavailable</li>
                <li>Profile updates might not be saved</li>
                <li>Some features may be limited</li>
            </ul>
            <p>Expected completion: Within the next few hours</p>
        </div>
        <?php endif; ?>
        
        <div class="error-links">
            <?php if ($error_code === 404): ?>
                <p>Or explore our <a href="<?php echo page('categories.php'); ?>">categories</a> or <a href="<?php echo page('help.php'); ?>">help center</a>.</p>
            <?php elseif ($error_code === 401): ?>
                <p>Don't have an account? <a href="<?php echo page('register.php'); ?>">Create one now</a>.</p>
            <?php elseif ($error_code === 503): ?>
                <p>Follow us on <a href="#">Twitter</a> for real-time updates.</p>
            <?php else: ?>
                <p>If the problem persists, please <a href="<?php echo $error['help_link']; ?>"><?php echo $error['help_text']; ?></a>.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php 
require_once __DIR__ . '/../includes/footer.php'; 
?>