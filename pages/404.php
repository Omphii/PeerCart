<?php
// 404.php - Custom 404 Error Page
require_once __DIR__ . '/../includes/bootstrap.php';

// Set HTTP response code
http_response_code(404);

// Get base URL for assets
$base_url = dirname(dirname($_SERVER['SCRIPT_NAME']));
if ($base_url === '/' || $base_url === '\\') {
    $base_url = '';
}

// Page title
$title = 'Page Not Found | PeerCart';

// Include header with minimal CSS
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    
    <!-- Bootstrap CSS for quick styling -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom 404 Styles -->
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3a0ca3;
            --dark-color: #2b2d42;
            --light-color: #f8f9fa;
            --gray-color: #6c757d;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Segoe UI', system-ui, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            margin: 0;
        }
        
        .error-container {
            background: white;
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 800px;
            width: 100%;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .error-icon {
            font-size: 8rem;
            color: var(--primary-color);
            margin-bottom: 2rem;
            animation: bounce 2s infinite;
        }
        
        .error-code {
            font-size: 6rem;
            font-weight: 800;
            color: var(--secondary-color);
            line-height: 1;
            margin-bottom: 1rem;
        }
        
        .error-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 1rem;
        }
        
        .error-message {
            font-size: 1.2rem;
            color: var(--gray-color);
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        
        .error-details {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin: 2rem 0;
            text-align: left;
            font-family: 'Courier New', monospace;
            border-left: 4px solid var(--primary-color);
        }
        
        .error-details h5 {
            color: var(--dark-color);
            margin-bottom: 1rem;
        }
        
        .error-details p {
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        
        .error-details code {
            background: #e9ecef;
            padding: 2px 6px;
            border-radius: 4px;
            color: #d63384;
        }
        
        .action-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
        }
        
        .btn {
            padding: 0.75rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(67, 97, 238, 0.3);
            color: white;
        }
        
        .btn-outline {
            background: transparent;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
        }
        
        .btn-outline:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
        }
        
        .search-box {
            max-width: 500px;
            margin: 2rem auto;
        }
        
        .popular-links {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            justify-content: center;
            margin-top: 2rem;
        }
        
        .popular-links a {
            background: #e9ecef;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            text-decoration: none;
            color: var(--gray-color);
            font-size: 0.9rem;
            transition: all 0.2s ease;
        }
        
        .popular-links a:hover {
            background: var(--primary-color);
            color: white;
        }
        
        .decoration {
            position: absolute;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(67, 97, 238, 0.1), rgba(58, 12, 163, 0.1));
            z-index: 0;
        }
        
        .decoration-1 {
            top: -150px;
            right: -150px;
        }
        
        .decoration-2 {
            bottom: -150px;
            left: -150px;
        }
        
        .content {
            position: relative;
            z-index: 1;
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }
        
        @media (max-width: 768px) {
            .error-container {
                padding: 2rem 1.5rem;
            }
            
            .error-icon {
                font-size: 6rem;
            }
            
            .error-code {
                font-size: 4rem;
            }
            
            .error-title {
                font-size: 2rem;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="decoration decoration-1"></div>
        <div class="decoration decoration-2"></div>
        
        <div class="content">
            <!-- Error Icon -->
            <div class="error-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            
            <!-- Error Code -->
            <h1 class="error-code">404</h1>
            
            <!-- Error Title -->
            <h2 class="error-title">Page Not Found</h2>
            
            <!-- Error Message -->
            <div class="error-message">
                <p>Oops! The page you're looking for seems to have wandered off.</p>
                <p>Don't worry, let's get you back on track.</p>
            </div>
            
            <!-- Search Box -->
            <div class="search-box">
                <div class="input-group mb-3">
                    <input type="text" class="form-control" placeholder="What are you looking for?" id="searchInput">
                    <button class="btn btn-primary" type="button" id="searchBtn">
                        <i class="fas fa-search"></i> Search
                    </button>
                </div>
            </div>
            
            <!-- Error Details (visible in development) -->
            <?php if (getenv('APP_ENV') !== 'production'): ?>
            <div class="error-details">
                <h5>Debug Information:</h5>
                <p><strong>Requested URL:</strong> <code><?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? 'Unknown') ?></code></p>
                <p><strong>Request Method:</strong> <code><?= htmlspecialchars($_SERVER['REQUEST_METHOD'] ?? 'Unknown') ?></code></p>
                <p><strong>Timestamp:</strong> <code><?= date('Y-m-d H:i:s') ?></code></p>
                <p><strong>User Agent:</strong> <code><?= htmlspecialchars($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown') ?></code></p>
            </div>
            <?php endif; ?>
            
            <!-- Action Buttons -->
            <div class="action-buttons">
                <a href="<?= BASE_URL ?>/" class="btn btn-primary">
                    <i class="fas fa-home"></i> Go to Homepage
                </a>
                
                <a href="<?= BASE_URL ?>/pages/listings.php" class="btn btn-outline">
                    <i class="fas fa-shopping-bag"></i> Browse Listings
                </a>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                <a href="<?= BASE_URL ?>/pages/dashboard.php" class="btn btn-outline">
                    <i class="fas fa-tachometer-alt"></i> Go to Dashboard
                </a>
                <?php else: ?>
                <a href="<?= BASE_URL ?>/includes/auth.php" class="btn btn-outline">
                    <i class="fas fa-sign-in-alt"></i> Login/register
                </a>
                <?php endif; ?>
            </div>
            
            <!-- Popular Links -->
            <div class="popular-links">
                <p class="w-100 text-muted mb-2">Popular pages:</p>
                <a href="<?= BASE_URL ?>/pages/listings.php">Browse All</a>
                <a href="<?= BASE_URL ?>/pages/categories.php">Categories</a>
                <a href="<?= BASE_URL ?>/pages/sell.php">Sell Item</a>
                <a href="<?= BASE_URL ?>/pages/orders.php">My Orders</a>
                <a href="<?= BASE_URL ?>/pages/cart.php">Shopping Cart</a>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Search functionality
            const searchBtn = document.getElementById('searchBtn');
            const searchInput = document.getElementById('searchInput');
            
            if (searchBtn && searchInput) {
                searchBtn.addEventListener('click', function() {
                    performSearch();
                });
                
                searchInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        performSearch();
                    }
                });
            }
            
            function performSearch() {
                const query = searchInput.value.trim();
                if (query) {
                    window.location.href = '<?= BASE_URL ?>/pages/listings.php?search=' + encodeURIComponent(query);
                } else {
                    window.location.href = '<?= BASE_URL ?>/pages/listings.php';
                }
            }
            
            // Add some fun animations
            const errorIcon = document.querySelector('.error-icon');
            if (errorIcon) {
                errorIcon.addEventListener('mouseover', function() {
                    this.style.transform = 'scale(1.1) rotate(10deg)';
                });
                
                errorIcon.addEventListener('mouseout', function() {
                    this.style.transform = 'scale(1) rotate(0deg)';
                });
            }
            
            // Log 404 error for analytics (optional)
            console.log('404 Error: Page not found - <?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? 'Unknown') ?>');
            
            // Show helpful message if URL looks like it might be mistyped
            const currentUrl = '<?= $_SERVER['REQUEST_URI'] ?? '' ?>';
            const commonTypos = [
                { wrong: 'cartt', correct: 'cart' },
                { wrong: 'lisitngs', correct: 'listings' },
                { wrong: 'dashbord', correct: 'dashboard' },
                { wrong: 'catagories', correct: 'categories' }
            ];
            
            for (const typo of commonTypos) {
                if (currentUrl.includes(typo.wrong)) {
                    const suggestion = document.createElement('div');
                    suggestion.className = 'alert alert-info mt-3';
                    suggestion.innerHTML = `
                        <i class="fas fa-lightbulb"></i> 
                        Did you mean <a href="<?= BASE_URL ?>/${typo.correct}" class="alert-link">${typo.correct}</a>?
                    `;
                    document.querySelector('.error-message').appendChild(suggestion);
                    break;
                }
            }
        });
        
        // Service Worker registration for offline support (future enhancement)
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/sw.js').catch(function(error) {
                    console.log('ServiceWorker registration failed:', error);
                });
            });
        }
    </script>
</body>
</html>