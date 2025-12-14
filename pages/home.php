<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__.'/../includes/bootstrap.php';
http_response_code(200);

$title = 'PeerCart - Discover Amazing Deals';
$currentPage = 'home';
includePartial('header', compact('title', 'currentPage'));

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
} catch (PDOException $e) {
    echo "<div class='container mt-4'><div class='alert alert-danger'>We're experiencing technical difficulties. Please try again later.</div></div>";
    includePartial('footer');
    exit;
}

// --- Promotions ---
try {
    $promotions = $db->getRows("
        SELECT 
            p.id, p.name, p.price, p.original_price,
            ROUND((1 - p.price / NULLIF(p.original_price, 0)) * 100) AS discount_percent,
            u.name AS seller_name,
            p.image
        FROM listings p
        JOIN users u ON p.seller_id = u.id
        WHERE p.original_price IS NOT NULL 
          AND p.original_price > p.price
          AND p.is_active = 1
        ORDER BY discount_percent DESC, p.created_at DESC
        LIMIT 8
    ");
} catch (PDOException $e) {
    $promotions = [];
}

// --- Recent Listings ---
try {
    $recentListings = $db->getRows("
        SELECT 
            l.id, l.name AS title, l.price, l.image, l.created_at, u.name AS seller_name, u.city AS seller_city
        FROM listings l
        LEFT JOIN users u ON l.seller_id = u.id
        WHERE l.is_active = 1
        ORDER BY l.created_at DESC
        LIMIT 20
    ");
} catch (PDOException $e) {
    $recentListings = [];
}

// --- Testimonials ---
try {
    $testimonials = $db->getRows("
        SELECT t.id, u.name as user_name, t.testimonial_text, t.rating, t.created_at
        FROM testimonials t
        INNER JOIN users u ON t.user_id = u.id
        WHERE t.status = 'approved'
        ORDER BY t.created_at DESC
        LIMIT 6
    ");
} catch (PDOException $e) {
    $testimonials = [];
}

// --- Categories ---
try {
    $categories = $db->getRows("
        SELECT c.id, c.name, c.icon, c.slug, COUNT(l.id) as listing_count
        FROM categories c
        LEFT JOIN listings l ON c.id = l.category_id AND l.is_active = 1
        WHERE c.is_active = 1
        GROUP BY c.id, c.name, c.icon, c.slug
        ORDER BY listing_count DESC, c.name ASC
        LIMIT 8
    ");
} catch (PDOException $e) {
    $categories = [];
}

// Helper function to get image with fallback
function getImagePath($image, $default='assets/images/products/default-product.png') {
    $fullPath = __DIR__ . '/../' . $image;
    if (!empty($image) && file_exists($fullPath)) {
        return asset($image);
    }
    return asset($default);
}
?>

<!-- Hero Section (unchanged) -->
<section class="hero-section">
    <div class="container hero-flex">
        <div class="hero-content">
            <h1>Discover Amazing Deals in Your Community</h1>
            <p>Buy and sell directly with people near you. No middlemen, no fees.</p>
            <div class="hero-buttons">
                <a href="<?= url('pages/listings.php') ?>" class="btn btn-primary">Shop Now</a>
                <a href="<?= url('pages/sell.php') ?>" class="btn btn-outline">Sell Item</a>
            </div>
        </div>
        <div class="hero-slideshow">
            <div class="slide active">🚚 Get it delivered to your door</div>
            <div class="slide">✅ Trade safely online by verifying your account</div>
            <div class="slide">💡 Discover exclusive deals every day</div>
        </div>
    </div>
</section>

<!-- Promotions -->
<section class="promotional-products">
    <div class="container">
        <div class="section-header">
            <h2>Hot Deals & Promotions</h2>
            <a href="<?= url('pages/listings.php') ?>" class="view-all">View All Promotions</a>
        </div>
        <div class="promotions-container">
            <div class="promotions-scroller">
                <?php if (!empty($promotions)): ?>
                    <?php foreach ($promotions as $promo):
                        $price = number_format($promo['price'], 2);
                        $originalPrice = number_format($promo['original_price'], 2);
                        $discountPercent = $promo['discount_percent'];
                        $sellerName = $promo['seller_name'] ?? 'Unknown Seller';
                        $imagePath = getImagePath($promo['image']);
                    ?>
                    <div class="promotion-card">
                        <a href="<?= url('pages/listing.php?id=' . $promo['id']) ?>">
                            <div class="promotion-image">
                                <img src="<?= $imagePath ?>" alt="<?= htmlspecialchars($promo['name']) ?>" loading="lazy" onerror="this.src='<?= asset('assets/images/products/default-product.png') ?>'">
                                <div class="promotion-badge">-<?= $discountPercent ?>%</div>
                                <div class="promotion-price">R<?= $price ?></div>
                            </div>
                            <div class="promotion-details">
                                <h3><?= htmlspecialchars($promo['name']) ?></h3>
                                <p class="promotion-seller"><i class="fas fa-user"></i> <?= htmlspecialchars($sellerName) ?></p>
                                <div class="promotion-footer">
                                    <span class="original-price">Was R<?= $originalPrice ?></span>
                                    <span class="discount-text">Save <?= $discountPercent ?>%</span>
                                </div>
                            </div>
                        </a>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-promotions">
                        <p>No promotions available at the moment. Check back later!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- Featured Listings -->
<section class="featured-listings">
    <div class="container">
        <div class="section-header">
            <h2>Recently Added</h2>
            <a href="<?= url('pages/listings.php') ?>" class="view-all">View All</a>
        </div>
        <div class="listings-grid">
            <?php if (!empty($recentListings)):
                foreach ($recentListings as $listing):
                    $price = number_format($listing['price'], 2);
                    $image = getImagePath($listing['image']);
                    $sellerName = $listing['seller_name'] ?: 'Unknown Seller';
                    $city = !empty($listing['seller_city']) ? htmlspecialchars($listing['seller_city']) : 'City not specified';
                    
                    $timeDiff = time() - strtotime($listing['created_at']);
                    $timeAgo = ($timeDiff < 60) ? "Just now" : (($timeDiff < 3600) ? floor($timeDiff/60)." mins ago" : (($timeDiff < 86400) ? floor($timeDiff/3600)." hours ago" : floor($timeDiff/86400)." days ago"));
            ?>
            <div class="listing-card">
                <a href="<?= url('pages/listing.php?id=' . $listing['id']) ?>">
                    <div class="listing-image">
                        <img src="<?= $image ?>" alt="<?= htmlspecialchars($listing['title']) ?>" onerror="this.src='<?= asset('assets/images/products/default-product.png') ?>'">
                        <span class="price">R<?= $price ?></span>
                    </div>
                    <div class="listing-details">
                        <h3><?= htmlspecialchars($listing['title']) ?></h3>
                        <p class="city"><i class="fas fa-map-marker-alt"></i> <?= $city ?></p>
                        <div class="listing-footer">
                            <span class="seller"><i class="fas fa-user"></i> <?= htmlspecialchars($sellerName) ?></span>
                            <span class="time"><?= $timeAgo ?></span>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; else: ?>
            <div class="no-listings">
                <p>No recent listings found. <a href="<?= url('pages/sell.php') ?>">Be the first to list an item!</a></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>


<section class="testimonials">
    <div class="container">
        <h2>What Our Users Say</h2>
        <p class="subtitle">Discover why thousands of people love our platform</p>
        <?php if (!empty($testimonials)): ?>
        <div class="testimonial-scroll-container">
            <div class="testimonial-row">
                <?php foreach ($testimonials as $testimonial): ?>
                <div class="testimonial-card">
                    <div class="quote-icon"><i class="fas fa-quote-left"></i></div>
                    <div class="rating">
                        <?php for($i=1; $i<=5; $i++): ?>
                            <i class="fas fa-star <?= $i <= $testimonial['rating'] ? 'filled' : '' ?>"></i>
                        <?php endfor; ?>
                    </div>
                    <p class="testimonial-text">"<?= htmlspecialchars($testimonial['testimonial_text']) ?>"</p>
                    <div class="author">
                        <img src="<?= asset('images/users/default-user.png') ?>" alt="<?= htmlspecialchars($testimonial['user_name']) ?>">
                        <div class="author-info">
                            <h4><?= htmlspecialchars($testimonial['user_name']) ?></h4>
                            <p>Member since <?= date('Y', strtotime($testimonial['created_at'])) ?></p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php else: ?>
        <div class="no-testimonials"><p>No testimonials yet. Check back soon!</p></div>
        <?php endif; ?>
        <div class="testimonial-cta">
            <p>Share your experience with us!</p>
            <a href="<?= url('pages/testimonial.php') ?>" class="cta-button">Write a Testimonial</a>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Hero Slideshow
    const slides = document.querySelectorAll('.hero-slideshow .slide');
    let current = 0;
    function showSlide(i){ slides.forEach((s,j)=>s.classList.toggle('active',i===j)); }
    function nextSlide(){ current=(current+1)%slides.length; showSlide(current); }
    if(slides.length){ showSlide(current); setInterval(nextSlide,5000); }

    // Testimonial scroll
    const testimonialRow = document.querySelector('.testimonial-row');
    if(testimonialRow){ 
        let scrollAmount = 0;
        setInterval(()=>{ 
            scrollAmount += 1; 
            if(scrollAmount >= testimonialRow.scrollWidth - testimonialRow.clientWidth) scrollAmount = 0;
            testimonialRow.scrollTo({left:scrollAmount, behavior:'smooth'});
        },50);
    }
});
</script>

<?php includePartial('footer'); ?>
