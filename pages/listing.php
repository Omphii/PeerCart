<?php
// listing.php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ .'/../includes/functions.php';

// Define ROOT_PATH if not defined
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', realpath(dirname(__DIR__)));
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    header('Location: ' . BASE_URL . '/pages/404.php');
    exit;
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Fetch listing with seller info
    $stmt = $conn->prepare("
        SELECT 
            l.*,
            u.name as seller_name,
            u.email as seller_email,
            u.phone as seller_phone,
            u.city as seller_city,
            u.surname as seller_surname,
            u.province as seller_province,
            u.created_at as seller_since,
            c.name as category_name
        FROM listings l
        LEFT JOIN users u ON l.seller_id = u.id
        LEFT JOIN categories c ON l.category_id = c.id
        WHERE l.id = :id 
          AND l.is_active = 1 
          AND l.status = 'active'
    ");
    $stmt->execute([':id' => $id]);
    $listing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$listing) {
        header('Location: ' . BASE_URL . '/pages/404.php');
        exit;
    }
    
    // Initialize variables
    $error = null;
    $isInStock = (!empty($listing['quantity']) && $listing['quantity'] > 0);
    $conditionText = ucfirst($listing['condition'] ?? 'New');
    $stockText = $isInStock ? 'In Stock' : 'Out of Stock';
    $conditionClass = in_array($listing['condition'] ?? 'new', ['new', 'like-new']) ? 'bg-success' : 'bg-warning';
    $csrfToken = $_SESSION['csrf_token'] ?? '';
    
    // Update view count
    $updateStmt = $conn->prepare("UPDATE listings SET views = views + 1 WHERE id = :id");
    $updateStmt->execute([':id' => $id]);
    
    // Handle images - FIXED VERSION
    $defaultImage = BASE_URL . '/assets/images/products/default-product.png';
    $images = [];
    
    // Check main image
    if (!empty($listing['image'])) {
        $mainImage = $listing['image'];
        
        // If it's already a full URL
        if (filter_var($mainImage, FILTER_VALIDATE_URL)) {
            $images[] = $mainImage;
        } else {
            // Try multiple possible locations
            $possiblePaths = [
                ROOT_PATH . '/uploads/listings/' . $mainImage,
                ROOT_PATH . '/assets/uploads/' . $mainImage,
                ROOT_PATH . '/assets/images/products/' . $mainImage,
                ROOT_PATH . '/' . ltrim($mainImage, '/')
            ];
            
            $found = false;
            foreach ($possiblePaths as $localPath) {
                if (file_exists($localPath)) {
                    // Convert to URL
                    $relativePath = str_replace(ROOT_PATH, '', $localPath);
                    $images[] = BASE_URL . $relativePath;
                    $found = true;
                    break;
                }
            }
            
            // If not found, use default
            if (!$found) {
                $images[] = $defaultImage;
            }
        }
    } else {
        $images[] = $defaultImage;
    }
    
    // Fetch related listings
    $relatedStmt = $conn->prepare("
        SELECT l.*, u.name as seller_name, u.city as seller_city
        FROM listings l
        LEFT JOIN users u ON l.seller_id = u.id
        WHERE l.category_id = :category_id 
          AND l.id != :listing_id
          AND l.is_active = 1
          AND l.status = 'active'
        ORDER BY RAND()
        LIMIT 6
    ");
    $relatedStmt->execute([
        ':category_id' => $listing['category_id'],
        ':listing_id' => $id
    ]);
    $relatedListings = $relatedStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch seller's other listings
    $sellerListingsStmt = $conn->prepare("
        SELECT l.*, u.name as seller_name
        FROM listings l
        LEFT JOIN users u ON l.seller_id = u.id
        WHERE l.seller_id = :seller_id 
          AND l.id != :listing_id
          AND l.is_active = 1
          AND l.status = 'active'
        ORDER BY l.created_at DESC
        LIMIT 4
    ");
    $sellerListingsStmt->execute([
        ':seller_id' => $listing['seller_id'],
        ':listing_id' => $id
    ]);
    $sellerListings = $sellerListingsStmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Listing page error: " . $e->getMessage());
    header('Location: ' . BASE_URL . '/pages/404.php');
    exit;
}

$title = htmlspecialchars($listing['name']) . ' - PeerCart';
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
    
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/pages/listing.css">
    
    <style>
        /* Fallback styles in case CSS doesn't load */
        body { 
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #f8f9fa;
            color: #333;
        }
        
        .listing-container {
            margin: 0 auto;
            padding: 0 20px;
        }
    </style>
</head>
<body class="listing-page">
    <!-- Animated Background -->
    <div class="background-elements">
        <div class="bg-circle"></div>
        <div class="bg-circle"></div>
        <div class="bg-circle"></div>
    </div>
    
    <!-- Include navbar - ONLY ONCE -->
    <?php 
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
    <!-- Left Column: Images & Description (First on mobile) -->
    <div class="col-lg-7 order-lg-1">
        <!-- Product Gallery -->
        <div class="product-gallery-modern slide-up">
            <div class="gallery-main" onclick="openLightbox(0)">
                <img src="<?= htmlspecialchars($images[0]) ?>" 
                     id="mainImage"
                     alt="<?= htmlspecialchars($listing['name']) ?>"
                     class="gallery-main-img">
                <div class="image-zoom-controls">
                    <button class="zoom-btn" onclick="zoomIn(event)" title="Zoom In">
                        <i class="fas fa-search-plus"></i>
                    </button>
                    <button class="zoom-btn" onclick="zoomOut(event)" title="Zoom Out">
                        <i class="fas fa-search-minus"></i>
                    </button>
                    <button class="zoom-btn" onclick="openLightbox(0)" title="Fullscreen">
                        <i class="fas fa-expand"></i>
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
    </div>
    
    <!-- Right Column: Details & Actions (Second on mobile) -->
    <div class="col-lg-5 order-lg-2">
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
                    
                    <!-- Seller Information - Compact Version -->
                    <div class="seller-compact">
                        <div class="d-flex align-items-center mb-2">
                            <div class="me-3">
                                <img src="https://ui-avatars.com/api/?name=<?= urlencode($listing['seller_name'] . ' ' . $listing['seller_surname']) ?>&background=4361ee&color=fff" 
                                     alt="<?= htmlspecialchars($listing['seller_name']) ?>"
                                     class="rounded-circle border seller-avatar-compact" 
                                     width="45" 
                                     height="45">
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-0 seller-name"><?= htmlspecialchars($listing['seller_name'] . ' ' . $listing['seller_surname']) ?></h6>
                                <small class="text-muted seller-location">
                                    <i class="fas fa-map-marker-alt me-1"></i>
                                    <?= htmlspecialchars($listing['seller_city'] . ', ' . $listing['seller_province']) ?>
                                </small>
                            </div>
                        </div>
                        
                        <div class="seller-actions-compact mt-2">
                            <a href="mailto:?subject=Inquiry about <?= urlencode($listing['name']) ?>" 
                               class="btn btn-sm btn-outline-primary me-2 seller-action-btn">
                                <i class="fas fa-envelope"></i>
                                <span class="d-none d-sm-inline">Message</span>
                            </a>
                            <a href="<?= BASE_URL ?>/pages/listings.php?seller=<?= $listing['seller_id'] ?>" 
                               class="btn btn-sm btn-outline-secondary seller-action-btn">
                                <i class="fas fa-store"></i>
                                <span class="d-none d-sm-inline">Store</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Full Width Column: Additional Images & Links (Last on mobile) -->
    <div class="col-12 order-last">
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
                                     onclick="openLightbox(<?= $index + 1 ?>)"
                                     style="cursor: pointer;">
                                <div class="image-overlay" onclick="openLightbox(<?= $index + 1 ?>)">
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
                <div class="card h-100 shadow-sm related-card">
                    <a href="<?= BASE_URL ?>/pages/listing.php?id=<?= $related['id'] ?>" 
                       class="text-decoration-none text-dark">
                        <div class="card-img-top" style="height: 160px; overflow: hidden;">
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
const lightboxImages = <?= json_encode($images) ?>;

// Lightbox functionality
let currentLightboxIndex = 0;
const lightboxModal = document.createElement('div');
lightboxModal.className = 'lightbox-modal';
lightboxModal.innerHTML = `
    <button class="lightbox-close" onclick="closeLightbox()">
        <i class="fas fa-times"></i>
    </button>
    <div class="lightbox-content">
        <button class="lightbox-nav lightbox-prev" onclick="prevImage()">
            <i class="fas fa-chevron-left"></i>
        </button>
        <img class="lightbox-img" src="" alt="">
        <button class="lightbox-nav lightbox-next" onclick="nextImage()">
            <i class="fas fa-chevron-right"></i>
        </button>
        <div class="lightbox-counter"></div>
        <div class="lightbox-thumbnails"></div>
    </div>
`;

// Initialize lightbox
function initLightbox() {
    document.body.appendChild(lightboxModal);
    
    // Add click handlers to all gallery images
    const galleryImages = [
        document.getElementById('mainImage'),
        ...document.querySelectorAll('.additional-image'),
        ...document.querySelectorAll('.thumbnail-img')
    ];
    
    galleryImages.forEach((img, index) => {
        if (img) {
            img.style.cursor = 'pointer';
            img.addEventListener('click', (e) => {
                e.stopPropagation();
                openLightbox(index % lightboxImages.length);
            });
        }
    });
    
    // Close lightbox on ESC key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeLightbox();
        if (e.key === 'ArrowLeft') prevImage();
        if (e.key === 'ArrowRight') nextImage();
    });
    
    // Close lightbox when clicking outside image
    lightboxModal.addEventListener('click', (e) => {
        if (e.target === lightboxModal) {
            closeLightbox();
        }
    });
}

function openLightbox(index) {
    currentLightboxIndex = index;
    updateLightbox();
    lightboxModal.style.display = 'block';
    document.body.style.overflow = 'hidden';
    
    // Add keyboard navigation
    document.addEventListener('keydown', handleLightboxKeys);
}

function closeLightbox() {
    lightboxModal.style.display = 'none';
    document.body.style.overflow = '';
    document.removeEventListener('keydown', handleLightboxKeys);
}

function updateLightbox() {
    const img = lightboxModal.querySelector('.lightbox-img');
    const counter = lightboxModal.querySelector('.lightbox-counter');
    const thumbnails = lightboxModal.querySelector('.lightbox-thumbnails');
    
    img.src = lightboxImages[currentLightboxIndex];
    img.alt = `Image ${currentLightboxIndex + 1} of ${lightboxImages.length}`;
    
    counter.textContent = `${currentLightboxIndex + 1} / ${lightboxImages.length}`;
    
    // Update thumbnails
    thumbnails.innerHTML = '';
    lightboxImages.forEach((image, index) => {
        const thumb = document.createElement('div');
        thumb.className = `lightbox-thumb ${index === currentLightboxIndex ? 'active' : ''}`;
        thumb.innerHTML = `<img src="${image}" alt="Thumbnail ${index + 1}">`;
        thumb.onclick = () => {
            currentLightboxIndex = index;
            updateLightbox();
        };
        thumbnails.appendChild(thumb);
    });
}

function nextImage() {
    currentLightboxIndex = (currentLightboxIndex + 1) % lightboxImages.length;
    updateLightbox();
}

function prevImage() {
    currentLightboxIndex = (currentLightboxIndex - 1 + lightboxImages.length) % lightboxImages.length;
    updateLightbox();
}

function handleLightboxKeys(e) {
    if (e.key === 'ArrowLeft') prevImage();
    if (e.key === 'ArrowRight') nextImage();
}

// Change main image
function changeImage(imageUrl, element) {
    document.getElementById('mainImage').src = imageUrl;
    
    // Update active thumbnail
    document.querySelectorAll('.thumbnail-item').forEach(thumb => {
        thumb.classList.remove('active');
    });
    
    element.classList.add('active');
    
    // Find index for lightbox
    const index = lightboxImages.indexOf(imageUrl);
    if (index !== -1) {
        currentLightboxIndex = index;
    }
}

// Change quantity
function changeQuantity(change) {
    const input = document.getElementById('quantityInput');
    let newValue = parseInt(input.value) + change;
    
    if (newValue < 1) newValue = 1;
    if (newValue > maxQuantity) newValue = maxQuantity;
    
    input.value = newValue;
}

// Zoom functions
function zoomIn(event) {
    if (event) event.stopPropagation();
    const img = document.getElementById('mainImage');
    img.style.transform = img.style.transform === 'scale(1.5)' ? 'scale(2)' : 'scale(1.5)';
}

function zoomOut(event) {
    if (event) event.stopPropagation();
    const img = document.getElementById('mainImage');
    img.style.transform = 'scale(1)';
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
        
        const response = await fetch(baseUrl + '/controllers/update-cart.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast(data.message, 'success');
            
            // Update cart count in navbar
            const cartCountElements = document.querySelectorAll('.cart-count');
            cartCountElements.forEach(el => {
                if (data.cart_count !== undefined) {
                    el.textContent = data.cart_count;
                }
            });
        } else {
            showToast(data.message, 'danger');
            if (data.redirect) {
                window.location.href = data.redirect;
            }
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Failed to add item to cart. Please try again.', 'danger');
    } finally {
        addBtn.innerHTML = originalText;
        addBtn.disabled = false;
    }
}

// Buy now
async function buyNow() {
    if (!isInStock) {
        showToast('This item is out of stock.', 'warning');
        return;
    }
    
    const quantity = parseInt(document.getElementById('quantityInput').value);
    
    if (quantity < 1 || quantity > maxQuantity) {
        showToast('Please select a valid quantity.', 'warning');
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
        
        const response = await fetch(baseUrl + '/controllers/update-cart.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Redirect to checkout
            window.location.href = baseUrl + '/pages/checkout.php';
        } else {
            showToast(data.message, 'danger');
            if (data.redirect) {
                window.location.href = data.redirect;
            }
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Failed to process. Please try again.', 'danger');
    } finally {
        buyBtn.innerHTML = originalText;
        buyBtn.disabled = false;
    }
}

// Mobile-specific optimizations
function initMobileOptimizations() {
    if ('ontouchstart' in window) {
        // Mobile device detected
        
        // Increase quantity button touch area
        const quantityButtons = document.querySelectorAll('.quantity-btn');
        quantityButtons.forEach(btn => {
            btn.style.minWidth = '44px';
            btn.style.minHeight = '44px';
        });
        
        // Make thumbnails more touch-friendly
        const thumbnails = document.querySelectorAll('.thumbnail-item');
        thumbnails.forEach(thumb => {
            thumb.style.padding = '4px';
        });
        
        // Adjust main image height based on screen size
        const screenHeight = window.innerHeight;
        const galleryMain = document.querySelector('.gallery-main');
        if (galleryMain && screenHeight < 700) {
            galleryMain.style.height = '250px';
        }
        
        // Show zoom controls always on mobile
        const zoomControls = document.querySelector('.image-zoom-controls');
        if (zoomControls) {
            zoomControls.style.opacity = '1';
        }
    }
}

// Add these new functions
function notifyWhenAvailable() {
    <?php if (!isset($_SESSION['user_id'])): ?>
        window.location.href = baseUrl + '/pages/auth.php?redirect=' + encodeURIComponent(window.location.href);
        return;
    <?php endif; ?>
    
    showToast('We will notify you when this item is back in stock!', 'info');
}

function addToWishlist() {
    <?php if (!isset($_SESSION['user_id'])): ?>
        window.location.href = baseUrl + '/pages/auth.php?redirect=' + encodeURIComponent(window.location.href);
        return;
    <?php endif; ?>
    
    showToast('Added to wishlist!', 'success');
}

function shareListing() {
    if (navigator.share) {
        navigator.share({
            title: '<?= addslashes($listing["name"]) ?>',
            text: 'Check out this listing on PeerCart',
            url: window.location.href
        });
    } else {
        // Fallback: copy to clipboard
        navigator.clipboard.writeText(window.location.href);
        showToast('Link copied to clipboard!', 'success');
    }
}

function compareListing() {
    showToast('Added to compare list!', 'success');
}

function saveListing() {
    <?php if (!isset($_SESSION['user_id'])): ?>
        window.location.href = baseUrl + '/pages/auth.php?redirect=' + encodeURIComponent(window.location.href);
        return;
    <?php endif; ?>
    
    showToast('Listing saved to your collection!', 'success');
}

// Enhanced toast function
function showToast(message, type = 'info') {
    // Remove any existing toast
    const existingToast = document.querySelector('.toast-notification');
    if (existingToast) {
        existingToast.remove();
    }
    
    const toast = document.createElement('div');
    toast.className = `toast-notification ${type}`;
    toast.innerHTML = `
        <div class="toast-content">
            <i class="fas fa-${type === 'success' ? 'check-circle' : 
                               type === 'danger' ? 'exclamation-circle' : 
                               type === 'warning' ? 'exclamation-triangle' : 
                               'info-circle'} fa-lg"></i>
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

// Add CSS for slideOutRight if not already added
if (!document.querySelector('#slideOutRightStyle')) {
    const style = document.createElement('style');
    style.id = 'slideOutRightStyle';
    style.textContent = `
        @keyframes slideOutRight {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
    `;
    document.head.appendChild(style);
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    console.log('Listing page loaded for ID:', listingId);
    
    // Initialize lightbox
    initLightbox();
    
    // Initialize mobile optimizations
    initMobileOptimizations();
    
    // Adjust layout on window resize
    window.addEventListener('resize', function() {
        initMobileOptimizations();
    });
    
    // Quantity input validation
    const quantityInput = document.getElementById('quantityInput');
    if (quantityInput) {
        quantityInput.addEventListener('change', function() {
            let value = parseInt(this.value);
            if (isNaN(value) || value < 1) {
                this.value = 1;
            } else if (value > maxQuantity) {
                this.value = maxQuantity;
                showToast(`Maximum quantity is ${maxQuantity}`, 'warning');
            }
        });
        
        // Add touch event for mobile
        quantityInput.addEventListener('touchstart', function(e) {
            e.stopPropagation();
        });
    }
    
    // Initialize tooltips for action buttons
    const actionButtons = document.querySelectorAll('.action-icon-btn');
    actionButtons.forEach(btn => {
        btn.addEventListener('touchstart', function(e) {
            const tooltip = this.querySelector('.tooltip');
            if (tooltip) {
                tooltip.style.opacity = '1';
                tooltip.style.visibility = 'visible';
                
                // Hide tooltip after 2 seconds
                setTimeout(() => {
                    tooltip.style.opacity = '0';
                    tooltip.style.visibility = 'hidden';
                }, 2000);
            }
        });
    });
});
</script>

<!-- Include footer -->
<?php include __DIR__ . '/../includes/footer.php'; ?>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>