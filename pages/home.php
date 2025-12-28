<?php
// pages/home.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__.'/../includes/bootstrap.php';

$title = 'PeerCart - Discover Amazing Deals';
$currentPage = 'home';

// Include header directly
require_once __DIR__ . '/../includes/header.php';

// Add CSS files
echo '<link rel="stylesheet" href="' . asset('css/pages/home.css') . '">';
echo '<link rel="stylesheet" href="' . asset('css/main.css') . '">';
echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">';
?>

<!-- SIMPLE POPUP - Light Theme -->
<div class="welcome-popup" id="welcomePopup">
    <div class="popup-content">
        <button class="popup-close" id="popupClose">&times;</button>
        
        <div class="popup-header">
            <h2>👋 Welcome to PeerCart!</h2>
            <p>A demonstration marketplace by Omphile Modiba</p>
        </div>
        
        <div class="popup-body">
            <div class="popup-point">
                <i class="fas fa-code"></i>
                <p>This is a <strong>demo project</strong> showcasing my development skills. Everything works like a real application!</p>
            </div>
            
            <div class="popup-point">
                <i class="fas fa-shield-alt"></i>
                <p><strong>No real transactions:</strong> Feel free to explore, register, and test features with any email address.</p>
            </div>
            
            <div class="popup-point">
                <i class="fas fa-star"></i>
                <p><strong>Leave feedback:</strong> I'd appreciate it if you could try the platform and leave a review in Testimonials.</p>
            </div>
            
            <div class="popup-point">
                <i class="fas fa-info-circle"></i>
                <p>All displayed data is simulated to <strong>mimic real-world scenarios</strong> for demonstration purposes.</p>
            </div>
        </div>
        
        <div class="popup-footer">
            <button class="popup-btn" id="popupAction">Let's Explore! 🚀</button>
            <div class="remember-check">
                <input type="checkbox" id="dontShowAgain">
                <label for="dontShowAgain">Don't show this again</label>
            </div>
        </div>
    </div>
</div>

<!-- POPUP STYLES - Light Theme -->
<!-- POPUP STYLES - Glassmorphism Theme -->
<!-- POPUP STYLES - Glassmorphism Theme -->
<style>
/* Reset and base styles for popup */
.welcome-popup {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.85);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    padding: 20px;
    box-sizing: border-box;
}

.welcome-popup.active {
    display: flex;
}

.popup-content {
    background: rgba(255, 255, 255, 0.15);
    border-radius: 24px;
    padding: 40px;
    max-width: 600px;
    width: 100%;
    position: relative;
    border: 1px solid rgba(255, 255, 255, 0.2);
    box-shadow: 
        0 25px 50px -12px rgba(0, 0, 0, 0.25),
        0 0 0 1px rgba(255, 255, 255, 0.1),
        inset 0 1px 0 0 rgba(255, 255, 255, 0.1),
        inset 0 0 0 1px rgba(255, 255, 255, 0.05);
    transform: translateY(20px);
    transition: transform 0.4s ease;
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    overflow: hidden;
}

/* Glass overlay effect */
.popup-content::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 1px;
    background: linear-gradient(90deg, 
        transparent 0%, 
        rgba(255, 255, 255, 0.4) 50%, 
        transparent 100%);
    z-index: 1;
}

.popup-content::after {
    content: "";
    position: absolute;
    inset: 0;
    background: linear-gradient(
        135deg,
        rgba(255, 255, 255, 0.1) 0%,
        rgba(255, 255, 255, 0.05) 100%
    );
    border-radius: 24px;
    pointer-events: none;
    z-index: -1;
}

.welcome-popup.active .popup-content {
    transform: translateY(0);
}

.popup-close {
    position: absolute;
    top: 20px;
    right: 20px;
    background: rgba(255, 255, 255, 0.15);
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: white;
    width: 44px;
    height: 44px;
    border-radius: 12px;
    font-size: 24px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    z-index: 2;
    font-weight: 300;
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
}

.popup-close:hover {
    background: rgba(255, 255, 255, 0.25);
    border-color: rgba(255, 255, 255, 0.3);
    transform: rotate(90deg);
}

.popup-header {
    margin-bottom: 30px;
    text-align: center;
    position: relative;
    z-index: 1;
}

.popup-header h2 {
    color: white;
    font-size: 2.5rem;
    margin-bottom: 15px;
    font-weight: 800;
    background: linear-gradient(45deg, #ffffff, #d1d5db);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
    text-shadow: 0 2px 10px rgba(255, 255, 255, 0.1);
}

.popup-header p {
    color: rgba(255, 255, 255, 0.85);
    font-size: 1.1rem;
    font-weight: 400;
}

.popup-body {
    margin-bottom: 30px;
    position: relative;
    z-index: 1;
}

.popup-point {
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 16px;
    padding: 20px;
    margin-bottom: 15px;
    display: flex;
    align-items: flex-start;
    gap: 18px;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
}

.popup-point:hover {
    background: rgba(255, 255, 255, 0.12);
    border-color: rgba(255, 255, 255, 0.2);
    transform: translateX(5px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
}

.popup-point::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
    background: linear-gradient(180deg, #667eea, #764ba2);
    border-radius: 4px 0 0 4px;
}

.popup-point i {
    color: #667eea;
    font-size: 1.4rem;
    margin-top: 2px;
    min-width: 24px;
    text-align: center;
    filter: drop-shadow(0 2px 4px rgba(102, 126, 234, 0.4));
}

.popup-point p {
    color: rgba(255, 255, 255, 0.95);
    margin: 0;
    line-height: 1.7;
    font-size: 1.05rem;
}

/* BOLD WHITE TEXT FOR HIGHLIGHTED TERMS */
.popup-point strong {
    color: #ffffff !important;
    font-weight: 700 !important;
    background: none !important;
    -webkit-background-clip: initial !important;
    background-clip: initial !important;
    color: white !important;
    -webkit-text-fill-color: white !important;
    text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
}

.popup-footer {
    text-align: center;
    position: relative;
    z-index: 1;
}

.popup-btn {
    background: linear-gradient(45deg, rgba(102, 126, 234, 0.9), rgba(118, 75, 162, 0.9));
    color: white;
    border: none;
    padding: 16px 45px;
    border-radius: 14px;
    font-size: 1.1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    border: 1px solid rgba(255, 255, 255, 0.2);
    display: inline-block;
    min-width: 200px;
    box-shadow: 
        0 10px 30px rgba(102, 126, 234, 0.3),
        inset 0 1px 0 rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    position: relative;
    overflow: hidden;
}

.popup-btn:hover {
    transform: translateY(-3px);
    box-shadow: 
        0 15px 40px rgba(102, 126, 234, 0.4),
        inset 0 1px 0 rgba(255, 255, 255, 0.2);
    background: linear-gradient(45deg, rgba(102, 126, 234, 1), rgba(118, 75, 162, 1));
}

.popup-btn::after {
    content: "";
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(
        90deg,
        transparent,
        rgba(255, 255, 255, 0.2),
        transparent
    );
    transition: left 0.7s ease;
}

.popup-btn:hover::after {
    left: 100%;
}

.remember-check {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-top: 25px;
    justify-content: center;
    padding: 15px;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 12px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(5px);
    -webkit-backdrop-filter: blur(5px);
    max-width: 300px;
    margin-left: auto;
    margin-right: auto;
}

.remember-check input {
    width: 20px;
    height: 20px;
    cursor: pointer;
    accent-color: #667eea;
}

.remember-check label {
    color: rgba(255, 255, 255, 0.85);
    font-size: 0.95rem;
    cursor: pointer;
}

/* Mobile responsive */
@media (max-width: 768px) {
    .welcome-popup {
        padding: 15px;
    }
    
    .popup-content {
        padding: 30px 20px;
        margin: 0;
        width: calc(100% - 30px);
        max-height: 85vh;
        overflow-y: auto;
    }
    
    .popup-header h2 {
        font-size: 2.2rem;
    }
    
    .popup-header p {
        font-size: 1.1rem;
    }
    
    .popup-btn {
        width: 100%;
        max-width: 300px;
        padding: 14px 35px;
    }
    
    .popup-close {
        width: 40px;
        height: 40px;
        font-size: 22px;
        top: 15px;
        right: 15px;
        background: rgba(0, 0, 0, 0.5);
    }
}

@media (max-width: 480px) {
    .popup-content {
        padding: 25px 16px;
        border-radius: 20px;
    }
    
    .popup-header h2 {
        font-size: 1.8rem;
    }
    
    .popup-point {
        flex-direction: column;
        gap: 10px;
        padding: 16px;
    }
    
    .popup-point i {
        margin-top: 0;
        align-self: center;
    }
    
    .popup-btn {
        padding: 16px 30px;
        font-size: 1rem;
    }
}

/* Fallback for browsers without backdrop-filter support */
@supports not (backdrop-filter: blur(1px)) {
    .popup-content {
        background: rgba(30, 30, 40, 0.95);
    }
    
    .popup-point,
    .popup-btn,
    .popup-close,
    .remember-check {
        background: rgba(40, 40, 50, 0.9);
    }
}
</style>

<?php
try {
    $db = db();
    
    // --- Promotions ---
    try {
        $promotions = $db->getRows("
        SELECT 
            l.id, l.name, l.price, l.original_price,
            ROUND((1 - l.price / NULLIF(l.original_price, 0)) * 100) AS discount_percent,
            u.name AS seller_name,
            l.image
        FROM listings l
        JOIN users u ON l.seller_id = u.id
        WHERE l.original_price IS NOT NULL 
          AND l.original_price > l.price
          AND l.is_active = 1
          AND l.status = 'active'
        ORDER BY discount_percent DESC, l.created_at DESC
        LIMIT 8
        ");
    } catch (PDOException $e) {
        $promotions = [];
        error_log("Promotions query error: " . $e->getMessage());
    }

    // --- Recent Listings ---
    try {
        $recentListings = $db->getRows("
            SELECT 
                l.id, l.name AS title, l.price, l.image, l.created_at, 
                u.name AS seller_name, u.city AS seller_city
            FROM listings l
            LEFT JOIN users u ON l.seller_id = u.id
            WHERE l.is_active = 1 AND l.status = 'active'
            ORDER BY l.created_at DESC
            LIMIT 20
        ");
    } catch (PDOException $e) {
        $recentListings = [];
        error_log("Recent listings query error: " . $e->getMessage());
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
        error_log("Testimonials query error: " . $e->getMessage());
    }

    // --- Categories ---
    try {
        $categories = $db->getRows("
            SELECT c.id, c.name, c.icon, c.slug, COUNT(l.id) as listing_count
            FROM categories c
            LEFT JOIN listings l ON c.id = l.category_id AND l.is_active = 1 AND l.status = 'active'
            WHERE c.is_active = 1
            GROUP BY c.id, c.name, c.icon, c.slug
            ORDER BY listing_count DESC, c.name ASC
            LIMIT 8
        ");
    } catch (PDOException $e) {
        $categories = [];
        error_log("Categories query error: " . $e->getMessage());
    }

} catch (PDOException $e) {
    echo "<div class='container mt-4'><div class='alert alert-danger'>We're experiencing technical difficulties. Please try again later.</div></div>";
    error_log("Database connection error in home.php: " . $e->getMessage());
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}
?>

<!-- Floating glass elements -->
<div class="floating-element"></div>
<div class="floating-element"></div>
<div class="floating-element"></div>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="hero-flex">
            <!-- Left Column: Text Content -->
            <div class="hero-text-column">
                <h1>Discover Amazing Deals in Your Community</h1>
                <p>Buy and sell directly with people near you. No middlemen, no fees.</p>
            </div>
            
            <!-- Right Column: Slideshow with Buttons -->
            <div class="hero-slideshow-column">
                <div class="hero-slideshow">
                    <!-- Slideshow content -->
                    <div class="slides-container">
                        <div class="slide active">🚚 Get it delivered to your door</div>
                        <div class="slide">✅ Trade safely online by verifying your account</div>
                        <div class="slide">💡 Discover exclusive deals every day</div>
                    </div>
                    
                    <!-- Buttons inside slideshow at the bottom -->
                    <div class="slideshow-buttons">
                        <a href="<?php echo page('listings.php'); ?>" class="btn btn-primary">Shop Now</a>
                        <a href="<?php echo page('sell.php'); ?>" class="btn btn-outline">Sell Item</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Glassmorphism Background -->
<div class="home-background"></div>
<div class="floating-circles">
    <div class="circle"></div>
    <div class="circle"></div>
    <div class="circle"></div>
    <div class="circle"></div>
</div>
<div class="grid-pattern"></div>

<!-- Promotions -->
<section class="promotional-products">
    <div class="container">
        <div class="section-header">
            <h2>Hot Deals & Promotions</h2>
            <a href="<?php echo page('listings.php?filter=discounted'); ?>" class="view-all">View All Promotions</a>
        </div>
        <div class="promotions-scroller">
            <?php if (!empty($promotions)): ?>
                <?php foreach ($promotions as $promo):
                    $price = number_format($promo['price'], 2);
                    $originalPrice = number_format($promo['original_price'], 2);
                    $discountPercent = $promo['discount_percent'];
                    $sellerName = $promo['seller_name'] ?? 'Unknown Seller';
                    $imageUrl = getListingImage($promo['image']);
                ?>
                <div class="promotion-card">
                    <a href="<?php echo page('listing.php?id=' . $promo['id']); ?>">
                        <div class="promotion-image">
                            <img src="<?php echo $imageUrl; ?>" alt="<?php echo htmlspecialchars($promo['name']); ?>" 
                                 onerror="this.onerror=null;this.src='<?php echo asset('images/products/default-product.png'); ?>'">
                            <?php if ($discountPercent > 0): ?>
                            <div class="promotion-badge">-<?php echo $discountPercent; ?>%</div>
                            <?php endif; ?>
                            <div class="promotion-price">R<?php echo $price; ?></div>
                        </div>
                        <div class="promotion-details">
                            <h3 class="promotion-title"><?php echo htmlspecialchars($promo['name']); ?></h3>
                            <p class="promotion-seller"><i class="fas fa-user"></i> <?php echo htmlspecialchars($sellerName); ?></p>
                            <?php if ($originalPrice > $price): ?>
                            <div class="promotion-footer">
                                <span class="original-price">Was R<?php echo $originalPrice; ?></span>
                                <span class="discount-text">Save <?php echo $discountPercent; ?>%</span>
                            </div>
                            <?php endif; ?>
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
</section>

<!-- Categories Section -->
<section class="featured-categories">
    <div class="container">
        <h2>Browse Categories</h2>
        <div class="categories-container">
            <div class="categories-grid">
                <?php if (!empty($categories)): ?>
                    <?php foreach ($categories as $category): ?>
                    <a href="<?php echo page('listings.php?category_id=' . $category['id']); ?>" class="category-card">
                        <div class="category-icon">
                            <i class="fas <?php echo htmlspecialchars($category['icon'] ?? 'fa-tag'); ?>"></i>
                        </div>
                        <h3><?php echo htmlspecialchars($category['name']); ?></h3>
                    </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-categories">
                        <p>No categories available yet.</p>
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
            <a href="<?php echo page('listings.php'); ?>" class="view-all">View All</a>
        </div>
        <div class="listings-grid">
            <?php if (!empty($recentListings)):
                foreach ($recentListings as $listing):
                    $price = number_format($listing['price'], 2);
                    $image = getListingImage($listing['image']);
                    $city = !empty($listing['seller_city']) ? htmlspecialchars($listing['seller_city']) : 'City not specified';
                    $sellerName = $listing['seller_name'] ?? 'Unknown Seller';
                    
                    $timeDiff = time() - strtotime($listing['created_at']);
                    $timeAgo = ($timeDiff < 60) ? "Just now" : (($timeDiff < 3600) ? floor($timeDiff/60)." mins ago" : (($timeDiff < 86400) ? floor($timeDiff/3600)." hours ago" : floor($timeDiff/86400)." days ago"));
            ?>
            <div class="listing-card">
                <a href="<?php echo page('listing.php?id=' . $listing['id']); ?>">
                    <div class="listing-image">
                        <img src="<?php echo $image; ?>" alt="<?php echo htmlspecialchars($listing['title']); ?>" 
                             onerror="this.onerror=null;this.src='<?php echo asset('images/products/default-product.png'); ?>'">
                        <span class="price">R<?php echo $price; ?></span>
                    </div>
                    <div class="listing-details">
                        <h3><?php echo htmlspecialchars($listing['title']); ?></h3>
                        <p class="city"><i class="fas fa-map-marker-alt"></i> <?php echo $city; ?></p>
                        <div class="listing-footer">
                            <span class="seller"><i class="fas fa-user"></i> <?php echo htmlspecialchars($sellerName); ?></span>
                            <span class="time"><?php echo $timeAgo; ?></span>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; else: ?>
            <div class="no-listings">
                <p>No recent listings found. <a href="<?php echo page('sell.php'); ?>">Be the first to list an item!</a></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Testimonials -->
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
                            <i class="fas fa-star <?php echo ($i <= $testimonial['rating']) ? 'filled' : ''; ?>"></i>
                        <?php endfor; ?>
                    </div>
                    <p class="testimonial-text">"<?php echo htmlspecialchars($testimonial['testimonial_text']); ?>"</p>
                    <div class="author">
                        <img src="<?php echo avatar(); ?>" alt="<?php echo htmlspecialchars($testimonial['user_name']); ?>" 
                             onerror="this.onerror=null;this.src='<?php echo avatar(); ?>'">
                        <div class="author-info">
                            <h4><?php echo htmlspecialchars($testimonial['user_name']); ?></h4>
                            <p>Member since <?php echo date('Y', strtotime($testimonial['created_at'])); ?></p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php else: ?>
        <div class="no-testimonials">
            <p>No testimonials yet. <a href="<?php echo page('testimonial.php'); ?>" style="color: white; text-decoration: underline;">Be the first to share your experience!</a></p>
        </div>
        <?php endif; ?>
        <div class="testimonial-cta">
            <p>Share your experience with us!</p>
            <a href="<?php echo page('testimonial.php'); ?>" class="cta-button">Write a Testimonial</a>
        </div>
    </div>
</section>

<script>
// SIMPLE POPUP JAVSCRIPT - Safari Compatible
document.addEventListener('DOMContentLoaded', function() {
    const popup = document.getElementById('welcomePopup');
    const closeBtn = document.getElementById('popupClose');
    const actionBtn = document.getElementById('popupAction');
    const dontShowCheckbox = document.getElementById('dontShowAgain');
    
    // Check if user has already closed the popup
    const hasSeenPopup = localStorage.getItem('peerCartWelcomeSeen');
    
    // Show popup only if not seen before
    if (!hasSeenPopup) {
        // Show popup after a short delay
        setTimeout(() => {
            popup.classList.add('active');
            document.body.style.overflow = 'hidden';
        }, 800);
    }
    
    // Close popup function
    function closePopup() {
        popup.classList.remove('active');
        setTimeout(() => {
            document.body.style.overflow = 'auto';
        }, 300);
        
        // If checkbox is checked, remember in localStorage
        if (dontShowCheckbox.checked) {
            localStorage.setItem('peerCartWelcomeSeen', 'true');
        }
    }
    
    // Close button click
    if (closeBtn) {
        closeBtn.addEventListener('click', closePopup);
    }
    
    // Action button click
    if (actionBtn) {
        actionBtn.addEventListener('click', closePopup);
    }
    
    // Close on ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && popup.classList.contains('active')) {
            closePopup();
        }
    });
    
    // Close on outside click
    if (popup) {
        popup.addEventListener('click', function(e) {
            if (e.target === popup) {
                closePopup();
            }
        });
    }
    
    // Hero Slideshow
    const slides = document.querySelectorAll('.hero-slideshow .slide');
    if (slides.length > 0) {
        let current = 0;
        function showSlide(i) { 
            slides.forEach((s, j) => s.classList.toggle('active', i === j)); 
        }
        function nextSlide() { 
            current = (current + 1) % slides.length; 
            showSlide(current); 
        }
        showSlide(current);
        setInterval(nextSlide, 5000);
    }
    
    // Auto-scroll for Promotions
    const promotionsScroller = document.querySelector('.promotions-scroller');
    if (promotionsScroller && promotionsScroller.children.length > 1) {
        let autoScrollInterval;
        let isPaused = false;
        const scrollSpeed = 2;
        
        function startAutoScroll() {
            autoScrollInterval = setInterval(() => {
                if (!isPaused) {
                    promotionsScroller.scrollLeft += scrollSpeed;
                    
                    // Reset when reaching end
                    if (promotionsScroller.scrollLeft >= promotionsScroller.scrollWidth - promotionsScroller.clientWidth) {
                        promotionsScroller.scrollLeft = 0;
                    }
                }
            }, 30);
        }
        
        // Pause on hover
        promotionsScroller.addEventListener('mouseenter', () => {
            isPaused = true;
        });
        
        promotionsScroller.addEventListener('mouseleave', () => {
            isPaused = false;
        });
        
        // Start auto-scroll
        setTimeout(() => {
            startAutoScroll();
        }, 1000);
    }
    
    // Manual wheel scrolling for all containers
    const scrollContainers = document.querySelectorAll('.promotions-scroller, .categories-container, .testimonial-scroll-container');
    
    scrollContainers.forEach(container => {
        container.addEventListener('wheel', (e) => {
            e.preventDefault();
            container.scrollLeft += e.deltaY * 0.5;
        });
    });
});
</script>
<?php 
require_once __DIR__ . '/../includes/footer.php'; 
?>