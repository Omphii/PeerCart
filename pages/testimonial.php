<?php
// testimonials.php - Enhanced with database integration
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/functions.php';

// Set page title
$title = 'Customer Testimonials - PeerCart';
$currentPage = 'testimonials';

// Get testimonials from database
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Get approved testimonials
    $stmt = $conn->prepare("
        SELECT 
            t.*,
            u.name as user_name,
            u.profile_image,
            u.city,
            u.created_at as member_since,
            COUNT(DISTINCT l.id) as total_listings,
            COUNT(DISTINCT o.id) as total_orders
        FROM testimonials t
        INNER JOIN users u ON t.user_id = u.id
        LEFT JOIN listings l ON u.id = l.seller_id AND l.status = 'active'
        LEFT JOIN orders o ON u.id = o.user_id AND o.status = 'completed'
        WHERE t.status = 'approved'
        GROUP BY t.id
        ORDER BY 
            CASE WHEN t.featured = 1 THEN 0 ELSE 1 END,
            t.rating DESC,
            t.created_at DESC
    ");
    $stmt->execute();
    $testimonials = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get statistics
    $stats_stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_testimonials,
            AVG(rating) as average_rating,
            COUNT(CASE WHEN featured = 1 THEN 1 END) as featured_count
        FROM testimonials 
        WHERE status = 'approved'
    ");
    $stats_stmt->execute();
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $testimonials = [];
    $stats = ['total_testimonials' => 0, 'average_rating' => 0, 'featured_count' => 0];
    error_log("Testimonials error: " . $e->getMessage());
}

// Handle testimonial submission
$submission_success = false;
$submission_error = null;
$form_data = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isLoggedIn()) {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (validateCSRFToken($csrf_token, 'testimonial_submit')) {
        $rating = intval($_POST['rating'] ?? 0);
        $testimonial_text = trim($_POST['testimonial_text'] ?? '');
        $user_id = $_SESSION['user_id'];
        
        // Validation
        if ($rating < 1 || $rating > 5) {
            $submission_error = "Please select a rating between 1 and 5 stars.";
        } elseif (strlen($testimonial_text) < 10) {
            $submission_error = "Testimonial must be at least 10 characters long.";
        } elseif (strlen($testimonial_text) > 1000) {
            $submission_error = "Testimonial cannot exceed 1000 characters.";
        } else {
            try {
                // Check if user already submitted a testimonial
                $check_stmt = $conn->prepare("
                    SELECT id FROM testimonials 
                    WHERE user_id = ? AND status != 'rejected'
                ");
                $check_stmt->execute([$user_id]);
                
                if ($check_stmt->rowCount() > 0) {
                    $submission_error = "You have already submitted a testimonial.";
                } else {
                    // Insert new testimonial
                    $insert_stmt = $conn->prepare("
                        INSERT INTO testimonials 
                        (user_id, rating, testimonial_text, status, created_at) 
                        VALUES (?, ?, ?, 'pending', NOW())
                    ");
                    $insert_stmt->execute([$user_id, $rating, $testimonial_text]);
                    
                    $submission_success = true;
                    $form_data = []; // Clear form
                    
                    // Log activity
                    log_activity("Testimonial submitted", [
                        'user_id' => $user_id,
                        'rating' => $rating
                    ]);
                }
            } catch (Exception $e) {
                $submission_error = "Failed to submit testimonial. Please try again later.";
                error_log("Testimonial submission error: " . $e->getMessage());
            }
        }
        
        if ($submission_error) {
            $form_data = [
                'rating' => $rating,
                'testimonial_text' => $testimonial_text
            ];
        }
    } else {
        $submission_error = "Invalid security token. Please try again.";
    }
}

// Generate CSRF token for form
$csrf_token = generateCSRFToken('testimonial_submit');

// Include header
includePartial('header', compact('title', 'currentPage'));
?>

<style>
.testimonials-page {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    min-height: 100vh;
    padding: 40px 0;
}

.page-header {
    text-align: center;
    margin-bottom: 40px;
    padding: 0 20px;
}

.page-header h1 {
    font-size: 2.5rem;
    color: #1a1a2e;
    margin-bottom: 15px;
    background: linear-gradient(90deg, #4361ee, #3a0ca3);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.page-subtitle {
    color: #666;
    font-size: 1.1rem;
    max-width: 600px;
    margin: 0 auto;
    line-height: 1.6;
}

/* Stats Section */
.stats-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
    padding: 0 20px;
    
    margin: 0 auto 40px;
}

.stat-card {
    background: white;
    border-radius: 10px;
    padding: 25px;
    text-align: center;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    border: 1px solid #e0e0e0;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
}

.stat-value {
    font-size: 2.5rem;
    font-weight: 700;
    color: #4361ee;
    margin-bottom: 10px;
    display: block;
}

.stat-label {
    color: #666;
    font-size: 1rem;
}

/* Testimonials Grid */
.testimonials-container {
    
    margin: 0 auto;
    padding: 0 20px;
}

.testimonials-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 30px;
    margin-bottom: 50px;
}

.testimonial-card {
    background: white;
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    border: 1px solid #e0e0e0;
    position: relative;
    overflow: hidden;
}

.testimonial-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(0,0,0,0.12);
}

.testimonial-card.featured {
    border: 2px solid #ffd700;
    background: linear-gradient(135deg, #fff9e6 0%, #ffffff 100%);
}

.featured-badge {
    position: absolute;
    top: 15px;
    right: 15px;
    background: #ffd700;
    color: #333;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 5px;
}

/* Rating Stars */
.rating {
    margin-bottom: 15px;
}

.rating-stars {
    display: flex;
    gap: 2px;
}

.star {
    color: #ddd;
    font-size: 1.2rem;
}

.star.filled {
    color: #ffc107;
}

.star.half-filled {
    position: relative;
    color: #ddd;
}

.star.half-filled::after {
    content: '★';
    position: absolute;
    left: 0;
    width: 50%;
    overflow: hidden;
    color: #ffc107;
}

/* User Info */
.user-info {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #eee;
}

.user-avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    overflow: hidden;
    border: 3px solid #4361ee;
    background: #f8f9fa;
}

.user-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.user-details h4 {
    margin: 0 0 5px 0;
    color: #333;
    font-size: 1.1rem;
}

.user-meta {
    display: flex;
    gap: 10px;
    font-size: 0.9rem;
    color: #666;
}

.user-meta span {
    display: flex;
    align-items: center;
    gap: 3px;
}

/* Testimonial Text */
.testimonial-text {
    color: #555;
    line-height: 1.6;
    margin-bottom: 20px;
    font-style: italic;
    position: relative;
    padding-left: 20px;
}

.testimonial-text::before {
    content: '"';
    position: absolute;
    left: 0;
    top: -10px;
    font-size: 3rem;
    color: #4361ee;
    opacity: 0.2;
    font-family: Georgia, serif;
}

/* Testimonial Footer */
.testimonial-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 15px;
    border-top: 1px solid #eee;
    font-size: 0.9rem;
    color: #888;
}

.testimonial-date {
    display: flex;
    align-items: center;
    gap: 5px;
}

/* Submission Form - Fixed Star Rating */
.submission-section {
    background: white;
    border-radius: 15px;
    padding: 40px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.08);
    margin-top: 50px;
    border: 1px solid #e0e0e0;
    
    margin: 50px auto 0;
}

.submission-section h2 {
    text-align: center;
    color: #1a1a2e;
    margin-bottom: 30px;
}

.submission-form {
    max-width: 600px;
    margin: 0 auto;
}

.form-group {
    margin-bottom: 25px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #555;
}

.rating-input {
    display: flex;
    gap: 5px;
    justify-content: center;
    margin: 10px 0;
}

.rating-input input {
    display: none;
}

.rating-input label {
    font-size: 2rem;
    color: #ddd;
    cursor: pointer;
    transition: color 0.2s;
}

/* Fix for star rating - highlight selected and previous stars */
.rating-input input:checked ~ label,
.rating-input label:hover,
.rating-input label:hover ~ label {
    color: #ddd; /* Reset to default first */
}

.rating-input label:hover,
.rating-input label:hover ~ label {
    color: #ddd; /* Keep all gray on hover to right */
}

.rating-input label:hover {
    color: #ffc107; /* Only current hovered star */
}

.rating-input input:checked ~ label {
    color: #ddd; /* Reset all to gray */
}

/* Highlight all stars up to and including the checked one */
.rating-input input:checked + label ~ label,
.rating-input input:checked + label {
    color: #ffc107;
}

/* Alternative approach - simpler */
.star-rating {
    display: flex;
    flex-direction: row-reverse;
    justify-content: center;
    gap: 5px;
    margin: 10px 0;
}

.star-rating input {
    display: none;
}

.star-rating label {
    font-size: 2rem;
    color: #ddd;
    cursor: pointer;
    transition: color 0.2s;
}

.star-rating label:hover,
.star-rating label:hover ~ label {
    color: #ffc107;
}

.star-rating input:checked ~ label {
    color: #ffc107;
}

.star-rating:not(:hover) input:checked ~ label {
    color: #ffc107;
}

.form-control {
    width: 100%;
    padding: 12px 15px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 1rem;
    transition: border 0.3s;
    font-family: inherit;
}

.form-control:focus {
    outline: none;
    border-color: #4361ee;
    box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
}

textarea.form-control {
    min-height: 150px;
    resize: vertical;
}

.submit-btn {
    background: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%);
    color: white;
    border: none;
    padding: 15px 40px;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 0 auto;
}

.submit-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
}

.submit-btn:disabled {
    background: #ccc;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

/* Alerts */
.alert {
    padding: 15px;
    border-radius: 6px;
    margin-bottom: 20px;
    animation: slideInDown 0.3s ease;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-danger {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.08);
}

.empty-state-icon {
    font-size: 4rem;
    color: #ddd;
    margin-bottom: 20px;
}

.empty-state h3 {
    color: #666;
    margin-bottom: 10px;
}

.empty-state p {
    color: #888;
    margin-bottom: 30px;
}

/* Animations */
@keyframes slideInDown {
    from {
        transform: translateY(-20px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

/* Responsive Design */
@media (max-width: 768px) {
    .stats-container {
        grid-template-columns: 1fr;
    }
    
    .testimonials-grid {
        grid-template-columns: 1fr;
    }
    
    .testimonial-card {
        padding: 20px;
    }
    
    .submission-section {
        padding: 25px;
    }
    
    .page-header h1 {
        font-size: 2rem;
    }
}

@media (max-width: 480px) {
    .user-info {
        flex-direction: column;
        text-align: center;
    }
    
    .user-meta {
        flex-direction: column;
        gap: 5px;
    }
    
    .rating-input label,
    .star-rating label {
        font-size: 1.5rem;
    }
}
</style>

<div class="testimonials-page">
    <div class="page-header">
        <h1>What Our Customers Say</h1>
        <p class="page-subtitle">
            Discover why thousands of people trust PeerCart for their buying and selling needs. 
            Real stories from real users.
        </p>
    </div>
    
    <!-- Statistics -->
    <div class="stats-container">
        <div class="stat-card">
            <span class="stat-value"><?= number_format($stats['total_testimonials']) ?></span>
            <span class="stat-label">Total Testimonials</span>
        </div>
        
        <div class="stat-card">
            <span class="stat-value"><?= number_format($stats['average_rating'], 1) ?></span>
            <span class="stat-label">Average Rating</span>
        </div>
        
        <div class="stat-card">
            <span class="stat-value"><?= number_format($stats['featured_count']) ?></span>
            <span class="stat-label">Featured Reviews</span>
        </div>
    </div>
    
    <!-- Testimonials Grid -->
    <div class="testimonials-container">
        <?php if (!empty($testimonials)): ?>
            <div class="testimonials-grid">
                <?php foreach ($testimonials as $testimonial): ?>
                    <div class="testimonial-card <?= $testimonial['featured'] ? 'featured' : '' ?>">
                        <?php if ($testimonial['featured']): ?>
                            <div class="featured-badge">
                                <i class="fas fa-star"></i> Featured
                            </div>
                        <?php endif; ?>
                        
                        <!-- Rating -->
                        <div class="rating">
                            <div class="rating-stars">
                                <?php 
                                $rating = $testimonial['rating'];
                                $full_stars = floor($rating);
                                $has_half_star = ($rating - $full_stars) >= 0.5;
                                
                                for ($i = 1; $i <= 5; $i++): 
                                    if ($i <= $full_stars): ?>
                                        <span class="star filled">★</span>
                                    <?php elseif ($has_half_star && $i == $full_stars + 1): ?>
                                        <span class="star half-filled">★</span>
                                    <?php else: ?>
                                        <span class="star">★</span>
                                    <?php endif; ?>
                                <?php endfor; ?>
                            </div>
                        </div>
                        
                        <!-- User Info -->
                        <div class="user-info">
                            <div class="user-avatar">
                                <?php if (!empty($testimonial['profile_image'])): ?>
                                    <img src="<?= BASE_URL ?>/assets/uploads/<?= htmlspecialchars($testimonial['profile_image']) ?>" 
                                         alt="<?= htmlspecialchars($testimonial['user_name']) ?>"
                                         onerror="this.src='<?= BASE_URL ?>/assets/images/users/default-user.png'">
                                <?php else: ?>
                                    <img src="<?= BASE_URL ?>/assets/images/users/default-user.png" 
                                         alt="<?= htmlspecialchars($testimonial['user_name']) ?>">
                                <?php endif; ?>
                            </div>
                            <div class="user-details">
                                <h4><?= htmlspecialchars($testimonial['user_name']) ?></h4>
                                <div class="user-meta">
                                    <?php if (!empty($testimonial['city'])): ?>
                                        <span>
                                            <i class="fas fa-map-marker-alt"></i>
                                            <?= htmlspecialchars($testimonial['city']) ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($testimonial['total_listings'] > 0): ?>
                                        <span>
                                            <i class="fas fa-box"></i>
                                            <?= $testimonial['total_listings'] ?> listings
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($testimonial['total_orders'] > 0): ?>
                                        <span>
                                            <i class="fas fa-shopping-bag"></i>
                                            <?= $testimonial['total_orders'] ?> orders
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Testimonial Text -->
                        <div class="testimonial-text">
                            <?= nl2br(htmlspecialchars($testimonial['testimonial_text'])) ?>
                        </div>
                        
                        <!-- Footer -->
                        <div class="testimonial-footer">
                            <span class="testimonial-date">
                                <i class="far fa-calendar-alt"></i>
                                <?= date('F j, Y', strtotime($testimonial['created_at'])) ?>
                            </span>
                            <span>
                                <i class="fas fa-user-check"></i>
                                Verified User
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="far fa-comment-dots"></i>
                </div>
                <h3>No Testimonials Yet</h3>
                <p>Be the first to share your experience with PeerCart!</p>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Testimonial Submission Form -->
    <?php if (isLoggedIn()): ?>
        <div class="submission-section">
            <h2>Share Your Experience</h2>
            
            <?php if ($submission_success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    Thank you for your testimonial! It's pending approval and will appear here soon.
                </div>
            <?php elseif ($submission_error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?= htmlspecialchars($submission_error) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="submission-form" id="testimonialForm">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                
                <div class="form-group">
                    <label>How would you rate your experience?</label>
                    <!-- Use the simpler star-rating class -->
                    <div class="star-rating">
                        <input type="radio" id="star5" name="rating" value="5" 
                               <?= ($form_data['rating'] ?? 0) == 5 ? 'checked' : '' ?> required>
                        <label for="star5" title="5 stars">★</label>
                        
                        <input type="radio" id="star4" name="rating" value="4"
                               <?= ($form_data['rating'] ?? 0) == 4 ? 'checked' : '' ?>>
                        <label for="star4" title="4 stars">★</label>
                        
                        <input type="radio" id="star3" name="rating" value="3"
                               <?= ($form_data['rating'] ?? 0) == 3 ? 'checked' : '' ?>>
                        <label for="star3" title="3 stars">★</label>
                        
                        <input type="radio" id="star2" name="rating" value="2"
                               <?= ($form_data['rating'] ?? 0) == 2 ? 'checked' : '' ?>>
                        <label for="star2" title="2 stars">★</label>
                        
                        <input type="radio" id="star1" name="rating" value="1"
                               <?= ($form_data['rating'] ?? 0) == 1 ? 'checked' : '' ?>>
                        <label for="star1" title="1 star">★</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="testimonial_text">Your Testimonial</label>
                    <textarea 
                        id="testimonial_text" 
                        name="testimonial_text" 
                        class="form-control" 
                        placeholder="Tell us about your experience with PeerCart..." 
                        required
                        maxlength="1000"
                        rows="5"
                    ><?= htmlspecialchars($form_data['testimonial_text'] ?? '') ?></textarea>
                    <div style="font-size: 0.9rem; color: #666; margin-top: 5px;">
                        <span id="charCount">0</span> / 1000 characters
                    </div>
                </div>
                
                <div class="form-group" style="text-align: center;">
                    <button type="submit" class="submit-btn" id="submitBtn">
                        <i class="fas fa-paper-plane"></i> Submit Testimonial
                    </button>
                    <p style="font-size: 0.9rem; color: #888; margin-top: 10px;">
                        All testimonials are reviewed before publishing.
                    </p>
                </div>
            </form>
        </div>
    <?php else: ?>
        <div class="submission-section" style="text-align: center;">
            <h2>Share Your Experience</h2>
            <p style="color: #666; margin-bottom: 30px;">
                Login to share your experience with PeerCart and help others make better decisions.
            </p>
            <a href="<?= BASE_URL ?>/pages/auth.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" 
               class="submit-btn" style="text-decoration: none;">
                <i class="fas fa-sign-in-alt"></i> Login to Submit
            </a>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Character counter for testimonial text
    const testimonialText = document.getElementById('testimonial_text');
    const charCount = document.getElementById('charCount');
    
    if (testimonialText && charCount) {
        testimonialText.addEventListener('input', function() {
            charCount.textContent = this.value.length;
            
            // Add warning for approaching limit
            if (this.value.length > 900) {
                charCount.style.color = '#ff6b6b';
            } else if (this.value.length > 800) {
                charCount.style.color = '#ffa726';
            } else {
                charCount.style.color = '#666';
            }
        });
        
        // Initialize character count
        charCount.textContent = testimonialText.value.length;
    }
    
    // Form validation
    const testimonialForm = document.getElementById('testimonialForm');
    const submitBtn = document.getElementById('submitBtn');
    
    if (testimonialForm) {
        testimonialForm.addEventListener('submit', function(e) {
            const rating = document.querySelector('input[name="rating"]:checked');
            const text = testimonialText.value.trim();
            
            if (!rating) {
                e.preventDefault();
                alert('Please select a rating.');
                return;
            }
            
            if (text.length < 10) {
                e.preventDefault();
                alert('Please write a testimonial of at least 10 characters.');
                testimonialText.focus();
                return;
            }
            
            // Disable button to prevent double submission
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Submitting...';
            }
        });
    }
    
    // Animate testimonial cards on scroll
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.animation = 'fadeIn 0.6s ease forwards';
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);
    
    // Observe testimonial cards
    document.querySelectorAll('.testimonial-card').forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.animationDelay = (index * 0.1) + 's';
        observer.observe(card);
    });
    
    // Star rating interaction - simplified
    const starLabels = document.querySelectorAll('.star-rating label');
    const starInputs = document.querySelectorAll('.star-rating input');
    
    // Add click listeners to highlight appropriate stars
    starLabels.forEach(label => {
        label.addEventListener('click', function() {
            const starValue = this.getAttribute('for').replace('star', '');
            highlightStars(starValue);
        });
    });
    
    // Add hover effect
    starLabels.forEach(label => {
        label.addEventListener('mouseenter', function() {
            const starValue = this.getAttribute('for').replace('star', '');
            highlightStars(starValue);
        });
        
        label.addEventListener('mouseleave', function() {
            const checked = document.querySelector('.star-rating input:checked');
            if (checked) {
                highlightStars(checked.value);
            } else {
                resetStars();
            }
        });
    });
    
    // Initialize stars based on checked input
    const checkedStar = document.querySelector('.star-rating input:checked');
    if (checkedStar) {
        highlightStars(checkedStar.value);
    }
    
    function highlightStars(value) {
        starLabels.forEach(label => {
            const starValue = label.getAttribute('for').replace('star', '');
            if (starValue <= value) {
                label.style.color = '#ffc107';
            } else {
                label.style.color = '#ddd';
            }
        });
    }
    
    function resetStars() {
        starLabels.forEach(label => {
            label.style.color = '#ddd';
        });
    }
});
</script>

<?php includePartial('footer'); ?>