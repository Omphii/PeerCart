<?php
// listing.php - Modern Design (WORKING VERSION)
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/functions.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get listing ID
$id = $_GET['id'] ?? null;
if (!$id || !is_numeric($id)) {
    header('Location: ' . BASE_URL . '/pages/404.php');
    exit;
}

$listing = null;
$relatedListings = [];
$images = [];
$error = null;

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Increment view count
    $conn->prepare("UPDATE listings SET views = views + 1 WHERE id = ?")->execute([$id]);
    
    // Fetch listing with all details
    $stmt = $conn->prepare("
        SELECT 
            l.*,
            c.name AS category_name,
            c.slug AS category_slug,
            u.id AS seller_id,
            u.name AS seller_name,
            u.surname AS seller_surname,
            u.city AS seller_city,
            u.province AS seller_province,
            u.created_at AS seller_since,
            u.profile_image AS seller_image,
            (SELECT COUNT(*) FROM listings WHERE seller_id = u.id AND status = 'active') AS seller_listing_count
        FROM listings l
        LEFT JOIN categories c ON l.category_id = c.id
        LEFT JOIN users u ON l.seller_id = u.id
        WHERE l.id = ? AND l.is_active = 1
        LIMIT 1
    ");
    
    $stmt->execute([$id]);
    $listing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$listing) {
        header('Location: ' . BASE_URL . '/pages/404.php');
        exit;
    }
    
    // Handle images
    $defaultImage = BASE_URL . '/assets/images/products/default-product.png';
    $images = [];
    
    // Check main image
    if (!empty($listing['image'])) {
        $mainImage = $listing['image'];
        if (!filter_var($mainImage, FILTER_VALIDATE_URL)) {
            // Check if image exists locally
            $localPath = ROOT_PATH . '/' . ltrim($mainImage, '/');
            if (file_exists($localPath)) {
                $images[] = BASE_URL . '/' . ltrim($mainImage, '/');
            } else {
                $images[] = $defaultImage;
            }
        } else {
            $images[] = $mainImage;
        }
    } else {
        $images[] = $defaultImage;
    }
    
    // Get related listings
    $stmt = $conn->prepare("
        SELECT 
            l.id,
            l.name,
            l.price,
            l.image,
            l.item_condition,
            u.name AS seller_name,
            u.city AS seller_city
        FROM listings l
        LEFT JOIN users u ON l.seller_id = u.id
        WHERE l.category_id = ? 
          AND l.id != ? 
          AND l.status = 'active' 
          AND l.is_active = 1
          AND l.quantity > 0
        ORDER BY RAND()
        LIMIT 4
    ");
    $stmt->execute([$listing['category_id'] ?? 0, $id]);
    $relatedListings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fix image paths for related listings
    foreach ($relatedListings as &$related) {
        if (!empty($related['image']) && !filter_var($related['image'], FILTER_VALIDATE_URL)) {
            $localPath = ROOT_PATH . '/' . ltrim($related['image'], '/');
            if (file_exists($localPath)) {
                $related['image'] = BASE_URL . '/' . ltrim($related['image'], '/');
            } else {
                $related['image'] = $defaultImage;
            }
        } elseif (empty($related['image'])) {
            $related['image'] = $defaultImage;
        }
    }
    
    // Determine condition text and class
    $conditionText = '';
    $conditionClass = '';
    switch ($listing['item_condition'] ?? 'new') {
        case 'new':
            $conditionText = 'New';
            $conditionClass = 'condition-new';
            break;
        case 'used_like_new':
            $conditionText = 'Used - Like New';
            $conditionClass = 'condition-used-like-new';
            break;
        case 'used_good':
            $conditionText = 'Used - Good';
            $conditionClass = 'condition-used-good';
            break;
        case 'used_fair':
            $conditionText = 'Used - Fair';
            $conditionClass = 'condition-used-fair';
            break;
        default:
            $conditionText = 'New';
            $conditionClass = 'condition-new';
    }
    
    // Check stock status
    $isInStock = (($listing['status'] ?? 'active') === 'active' && ($listing['quantity'] ?? 0) > 0);
    $stockText = $isInStock ? 'In Stock' : 'Out of Stock';
    
} catch (Exception $e) {
    $error = "Error loading listing: " . $e->getMessage();
    error_log($error);
}

// Generate CSRF token for cart actions
$csrfToken = generateCSRFToken('add-to-cart');

// Page title
$title = htmlspecialchars($listing['name'] ?? 'Listing') . ' | PeerCart';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/listing.css">
    
    <style>
        /* Fallback styles in case CSS doesn't load */
        body { 
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #f8f9fa;
            color: #333;
        }
        
        .listing-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .test-alert {
            background: #4361ee;
            color: white;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
            text-align: center;
        }
    </style>
</head>
<body>
    <body class="listing-page">
    <!-- Animated Background -->
    <div class="background-elements">
        <div class="bg-circle"></div>
        <div class="bg-circle"></div>
        <div class="bg-circle"></div>
    </div>
    
    <!-- Include navbar -->
    <?php 
    // Simple navbar include
    $currentPage = 'listing';
    include __DIR__ . '/../includes/header.php'; 
    ?>

<div class="listing-container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="breadcrumb-modern mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/">Home</a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/pages/listings.php">Browse</a></li>
            <?php if ($listing && isset($listing['category_name'])): ?>
                <li class="breadcrumb-item">
                    <a href="<?= BASE_URL ?>/pages/listings.php?category=<?= $listing['category_id'] ?>">
                        <?= htmlspecialchars($listing['category_name']) ?>
                    </a>
                </li>
            <?php endif; ?>
            <li class="breadcrumb-item active" aria-current="page">
                <?= htmlspecialchars(substr($listing['name'] ?? 'Listing', 0, 30)) ?>...
            </li>
        </ol>
    </nav>
    
    <!-- Error Message -->
    <?php if ($error): ?>
        <div class="alert alert-danger fade-in">
            <i class="fas fa-exclamation-circle me-2"></i> <?= htmlspecialchars($error) ?>
            <a href="<?= BASE_URL ?>/pages/listings.php" class="alert-link">Browse other listings</a>
        </div>
    <?php endif; ?>
    
    <?php if ($listing): ?>
<div class="row g-4">
    <!-- Left Column: Images -->
    <div class="col-lg-7 order-lg-1 order-2">
        <!-- Product Gallery -->
        <div class="product-gallery-modern slide-up">
            <div class="gallery-main">
                <img src="<?= htmlspecialchars($images[0]) ?>" 
                     id="mainImage"
                     alt="<?= htmlspecialchars($listing['name']) ?>"
                     class="gallery-main-img">
                <div class="image-zoom-controls">
                    <button class="zoom-btn" onclick="zoomIn()" title="Zoom In">
                        <i class="fas fa-search-plus"></i>
                    </button>
                    <button class="zoom-btn" onclick="zoomOut()" title="Zoom Out">
                        <i class="fas fa-search-minus"></i>
                    </button>
                </div>
            </div>
            
            <?php if (count($images) > 1): ?>
            <div class="thumbnails-modern">
                <?php foreach ($images as $index => $image): ?>
                    <div class="thumbnail-item <?= $index === 0 ? 'active' : '' ?>" 
                         onclick="changeImage('<?= htmlspecialchars($image) ?>', this)">
                        <img src="<?= htmlspecialchars($image) ?>" 
                             alt="Thumbnail <?= $index + 1 ?>"
                             class="thumbnail-img">
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Product Description -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h3 class="card-title mb-3">
                    <i class="fas fa-align-left text-primary me-2"></i> Description
                </h3>
                <div class="card-text">
                    <?php if (!empty($listing['description'])): ?>
                        <?= nl2br(htmlspecialchars($listing['description'])) ?>
                    <?php else: ?>
                        <p class="text-muted">No description provided.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Additional Images (3 columns) -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h3 class="card-title mb-3">
                    <i class="fas fa-images text-primary me-2"></i> Additional Images
                </h3>
                <div class="row g-3">
                    <?php
                    // Create array of images (use default image if no additional images)
                    $additionalImages = $images;
                    // Remove the first image (main image) from additional images
                    if (count($additionalImages) > 1) {
                        array_shift($additionalImages);
                    }
                    
                    // If no additional images, use default image 3 times
                    if (empty($additionalImages)) {
                        $additionalImages = array_fill(0, 3, $defaultImage);
                    }
                    
                    // Ensure we have exactly 3 images for 3 columns
                    while (count($additionalImages) < 3) {
                        $additionalImages[] = $defaultImage;
                    }
                    if (count($additionalImages) > 3) {
                        $additionalImages = array_slice($additionalImages, 0, 3);
                    }
                    
                    foreach ($additionalImages as $index => $image): 
                    ?>
                        <div class="col-md-4 col-sm-6">
                            <div class="additional-image-wrapper">
                                <img src="<?= htmlspecialchars($image) ?>" 
                                     alt="Additional Image <?= $index + 1 ?>"
                                     class="additional-image img-fluid rounded"
                                     onclick="changeImage('<?= htmlspecialchars($image) ?>')"
                                     style="cursor: pointer;">
                                <div class="image-overlay" onclick="changeImage('<?= htmlspecialchars($image) ?>')">
                                    <i class="fas fa-search-plus"></i>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions mb-4">
            <button class="action-icon-btn" onclick="addToWishlist()">
                <i class="far fa-heart"></i>
                <span class="tooltip">Add to Wishlist</span>
            </button>
            <button class="action-icon-btn" onclick="shareListing()">
                <i class="fas fa-share-alt"></i>
                <span class="tooltip">Share</span>
            </button>
            <button class="action-icon-btn" onclick="compareListing()">
                <i class="fas fa-balance-scale"></i>
                <span class="tooltip">Compare</span>
            </button>
            <button class="action-icon-btn" onclick="saveListing()">
                <i class="far fa-bookmark"></i>
                <span class="tooltip">Save</span>
            </button>
        </div>
    </div>
    
    <!-- Right Column: Details & Actions -->
    <div class="col-lg-5 order-lg-2 order-1">
        <div class="sticky-lg-top" style="top: 20px;">
            <!-- Product Info Card -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <!-- Condition Badges -->
                    <div class="d-flex flex-wrap align-items-center mb-3">
                        <span class="badge <?= $conditionClass ?> me-2 mb-2">
                            <?= $conditionText ?>
                        </span>
                        <span class="badge <?= $isInStock ? 'bg-success' : 'bg-danger' ?> mb-2">
                            <i class="fas fa-<?= $isInStock ? 'check' : 'times' ?> me-1"></i>
                            <?= $stockText ?>
                        </span>
                    </div>
                    
                    <!-- Product Title -->
                    <h1 class="h2 mb-3"><?= htmlspecialchars($listing['name']) ?></h1>
                    
                    <!-- Price Section -->
                    <div class="mb-4">
                        <div class="d-flex align-items-center mb-2">
                            <span class="h3 text-primary fw-bold me-3">
                                R<?= number_format($listing['price'], 2) ?>
                            </span>
                            <?php if (!empty($listing['original_price']) && $listing['original_price'] > $listing['price']): ?>
                                <div>
                                    <span class="text-muted text-decoration-line-through me-2">
                                        R<?= number_format($listing['original_price'], 2) ?>
                                    </span>
                                    <?php
                                    $discountPercent = round((($listing['original_price'] - $listing['price']) / $listing['original_price']) * 100);
                                    ?>
                                    <span class="badge bg-danger">
                                        <?= $discountPercent ?>% OFF
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Quantity Selection -->
                    <div class="quantity-selector-modern mb-4">
                        <label class="mb-3">
                            <i class="fas fa-boxes text-primary me-2"></i> Select Quantity
                        </label>
                        <div class="quantity-controls">
                            <button class="quantity-btn" type="button" onclick="changeQuantity(-1)" title="Decrease">
                                <i class="fas fa-minus"></i>
                            </button>
                            <input type="number" 
                                   id="quantityInput"
                                   class="quantity-input-modern" 
                                   value="1" 
                                   min="1" 
                                   max="<?= $isInStock ? $listing['quantity'] : 0 ?>"
                                   <?= !$isInStock ? 'disabled' : '' ?>>
                            <button class="quantity-btn" type="button" onclick="changeQuantity(1)" title="Increase">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                        <?php if ($isInStock): ?>
                            <div class="stock-info mt-3">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                <?= $listing['quantity'] ?> units available in stock
                            </div>
                        <?php else: ?>
                            <div class="stock-info mt-3">
                                <i class="fas fa-exclamation-triangle text-danger me-2"></i>
                                Currently out of stock
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="action-buttons-modern mb-4">
                        <?php if ($isInStock): ?>
                            <button class="btn-modern btn-primary-modern" onclick="addToCart()">
                                <i class="fas fa-shopping-cart"></i>
                                Add to Cart
                            </button>
                            
                            <button class="btn-modern btn-success-modern" onclick="buyNow()">
                                <i class="fas fa-bolt"></i>
                                Buy Now
                            </button>
                        <?php else: ?>
                            <button class="btn-modern btn-secondary-modern" onclick="notifyWhenAvailable()">
                                <i class="fas fa-bell"></i>
                                Notify When Available
                            </button>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Product Details -->
                    <div class="row mb-4">
                        <div class="col-6">
                            <div class="mb-3">
                                <small class="text-muted d-block">Category</small>
                                <strong><?= htmlspecialchars($listing['category_name'] ?? 'Not specified') ?></strong>
                            </div>
                            <div class="mb-3">
                                <small class="text-muted d-block">Listed</small>
                                <strong><?= date('d M Y', strtotime($listing['created_at'])) ?></strong>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mb-3">
                                <small class="text-muted d-block">Views</small>
                                <strong><?= number_format($listing['views']) ?></strong>
                            </div>
                            <div class="mb-3">
                                <small class="text-muted d-block">Condition</small>
                                <strong><?= $conditionText ?></strong>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Seller Information -->
                    <div class="border-top pt-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="me-3">
                                <img src="https://ui-avatars.com/api/?name=<?= urlencode($listing['seller_name'] . ' ' . $listing['seller_surname']) ?>&background=4361ee&color=fff" 
                                     alt="<?= htmlspecialchars($listing['seller_name']) ?>"
                                     class="rounded-circle border" 
                                     width="50" 
                                     height="50">
                            </div>
                            <div>
                                <h6 class="mb-1"><?= htmlspecialchars($listing['seller_name'] . ' ' . $listing['seller_surname']) ?></h6>
                                <small class="text-muted">
                                    <i class="fas fa-map-marker-alt me-1"></i>
                                    <?= htmlspecialchars($listing['seller_city'] . ', ' . $listing['seller_province']) ?>
                                </small>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <a href="mailto:?subject=Inquiry about <?= urlencode($listing['name']) ?>" 
                               class="btn btn-outline-primary">
                                <i class="fas fa-envelope me-2"></i> Message Seller
                            </a>
                            <a href="<?= BASE_URL ?>/pages/listings.php?seller=<?= $listing['seller_id'] ?>" 
                               class="btn btn-outline-secondary">
                                <i class="fas fa-store me-2"></i> View Store
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
    
    <!-- Related Listings -->
    <?php if (!empty($relatedListings)): ?>
    <div class="mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="mb-0">Related Listings</h3>
            <a href="<?= BASE_URL ?>/pages/listings.php?category=<?= $listing['category_id'] ?>" 
               class="btn btn-outline-primary">
                View All <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>
        
        <div class="row g-4">
            <?php foreach ($relatedListings as $related): ?>
            <div class="col-md-3 col-sm-6">
                <div class="card h-100 shadow-sm">
                    <a href="<?= BASE_URL ?>/pages/listing.php?id=<?= $related['id'] ?>" 
                       class="text-decoration-none text-dark">
                        <div class="card-img-top" style="height: 200px; overflow: hidden;">
                            <img src="<?= htmlspecialchars($related['image']) ?>" 
                                 alt="<?= htmlspecialchars($related['name']) ?>"
                                 class="img-fluid w-100 h-100"
                                 style="object-fit: cover;">
                        </div>
                        <div class="card-body">
                            <h6 class="card-title"><?= htmlspecialchars($related['name']) ?></h6>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-primary fw-bold">R<?= number_format($related['price'], 2) ?></span>
                                <small class="text-muted">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <?= htmlspecialchars($related['seller_city']) ?>
                                </small>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <?php else: ?>
    <!-- No Listing Found -->
    <div class="text-center py-5">
        <div class="mb-4">
            <i class="fas fa-search fa-4x text-muted"></i>
        </div>
        <h3>Listing Not Found</h3>
        <p class="text-muted mb-4">The listing you're looking for doesn't exist or has been removed.</p>
        <div class="d-flex justify-content-center gap-3">
            <a href="<?= BASE_URL ?>/pages/listings.php" class="btn btn-primary">
                <i class="fas fa-shopping-bag me-2"></i> Browse Listings
            </a>
            <a href="<?= BASE_URL ?>/" class="btn btn-outline-primary">
                <i class="fas fa-home me-2"></i> Go Home
            </a>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- JavaScript -->
<script>
// CSRF Token
const csrfToken = '<?= $csrfToken ?>';
const listingId = <?= $id ?>;
const maxQuantity = <?= $listing['quantity'] ?? 0 ?>;
const isInStock = <?= $isInStock ? 'true' : 'false' ?>;
const baseUrl = '<?= BASE_URL ?>';

// Change main image
function changeImage(imageUrl, element) {
    document.getElementById('mainImage').src = imageUrl;
    
    // Update active thumbnail
    document.querySelectorAll('.thumbnail-item').forEach(thumb => {
        thumb.classList.remove('active');
    });
    
    element.classList.add('active');
}

// Change quantity
function changeQuantity(change) {
    const input = document.getElementById('quantityInput');
    let newValue = parseInt(input.value) + change;
    
    if (newValue < 1) newValue = 1;
    if (newValue > maxQuantity) newValue = maxQuantity;
    
    input.value = newValue;
}

// Add to cart
async function addToCart() {
    if (!isInStock) {
        alert('This item is out of stock.');
        return;
    }
    
    const quantity = parseInt(document.getElementById('quantityInput').value);
    
    if (quantity < 1 || quantity > maxQuantity) {
        alert('Please select a valid quantity.');
        return;
    }
    
    // Show loading
    const addBtn = document.querySelector('button[onclick="addToCart()"]');
    const originalText = addBtn.innerHTML;
    addBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Adding...';
    addBtn.disabled = true;
    
    try {
        const formData = new FormData();
        formData.append('csrf_token', csrfToken);
        formData.append('action', 'add');
        formData.append('listing_id', listingId);
        formData.append('quantity', quantity);
        
        const response = await fetch(baseUrl + '/pages/update-cart.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert(data.message);
            
            // Update cart count in navbar
            const cartCountElements = document.querySelectorAll('.cart-count');
            cartCountElements.forEach(el => {
                if (data.cart_count !== undefined) {
                    el.textContent = data.cart_count;
                }
            });
        } else {
            alert(data.message);
            if (data.redirect) {
                window.location.href = data.redirect;
            }
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to add item to cart. Please try again.');
    } finally {
        addBtn.innerHTML = originalText;
        addBtn.disabled = false;
    }
}

// Buy now
async function buyNow() {
    if (!isInStock) {
        alert('This item is out of stock.');
        return;
    }
    
    const quantity = parseInt(document.getElementById('quantityInput').value);
    
    if (quantity < 1 || quantity > maxQuantity) {
        alert('Please select a valid quantity.');
        return;
    }
    
    // Show loading
    const buyBtn = document.querySelector('button[onclick="buyNow()"]');
    const originalText = buyBtn.innerHTML;
    buyBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Processing...';
    buyBtn.disabled = true;
    
    try {
        const formData = new FormData();
        formData.append('csrf_token', csrfToken);
        formData.append('action', 'add');
        formData.append('listing_id', listingId);
        formData.append('quantity', quantity);
        
        const response = await fetch(baseUrl + '/pages/update-cart.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Redirect to checkout
            window.location.href = baseUrl + '/pages/checkout.php';
        } else {
            alert(data.message);
            if (data.redirect) {
                window.location.href = data.redirect;
            }
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to process. Please try again.');
    } finally {
        buyBtn.innerHTML = originalText;
        buyBtn.disabled = false;
    }
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    console.log('Listing page loaded for ID:', listingId);
    
    // Quantity input validation
    const quantityInput = document.getElementById('quantityInput');
    if (quantityInput) {
        quantityInput.addEventListener('change', function() {
            let value = parseInt(this.value);
            if (isNaN(value) || value < 1) {
                this.value = 1;
            } else if (value > maxQuantity) {
                this.value = maxQuantity;
                alert(`Maximum quantity is ${maxQuantity}`);
            }
        });
    }
});
// Add these new functions
function notifyWhenAvailable() {
    <?php if (!isset($_SESSION['user_id'])): ?>
        window.location.href = baseUrl + '/includes/auth/login.php?redirect=' + encodeURIComponent(window.location.href);
        return;
    <?php endif; ?>
    
    showToast('We will notify you when this item is back in stock!', 'info');
}

function compareListing() {
    showToast('Added to compare list!', 'success');
}

function saveListing() {
    <?php if (!isset($_SESSION['user_id'])): ?>
        window.location.href = baseUrl + '/includes/auth/login.php?redirect=' + encodeURIComponent(window.location.href);
        return;
    <?php endif; ?>
    
    showToast('Listing saved to your collection!', 'success');
}

// Enhanced toast function
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast-notification ${type}`;
    toast.innerHTML = `
        <div class="toast-content">
            <i class="fas fa-${type === 'success' ? 'check-circle' : 
                               type === 'danger' ? 'exclamation-circle' : 
                               type === 'warning' ? 'exclamation-triangle' : 
                               'info-circle'} fa-2x"></i>
            <div>
                <strong class="d-block mb-1">${type.charAt(0).toUpperCase() + type.slice(1)}</strong>
                <span>${message}</span>
            </div>
        </div>
        <button class="toast-close" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    document.body.appendChild(toast);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (toast.parentElement) {
            toast.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }
    }, 5000);
}

// Add CSS for slideOutRight
const style = document.createElement('style');
style.textContent = `
    @keyframes slideOutRight {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
`;
document.head.appendChild(style);
</script>

<!-- Include footer -->
<?php include __DIR__ . '/../includes/footer.php'; ?>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>