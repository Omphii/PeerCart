<?php
// includes/footer.php
// Ensure functions are available
if (!function_exists('asset')) {
    require_once __DIR__ . '/bootstrap.php';
}
?>

<link rel="stylesheet" href="<?= asset('css/pages/footer.css') ?>?v=<?= time() ?>">

<!-- Back to Top Button -->
<button id="backToTop" class="back-to-top">
    <i class="fas fa-chevron-up"></i>
</button>

<!-- Site Footer - Modern Glassmorphism Version -->
<footer class="site-footer">
    <div class="footer-container">
        
        <!-- Main Content -->
        <div class="footer-content">
            
            <!-- Brand & Social -->
            <div class="footer-brand">
                <div class="footer-logo">
                    <a href="<?= BASE_URL ?>/">Peer<span>Cart</span></a>
                </div>
                <p class="footer-tagline">
                    Peer-to-peer marketplace. Buy, sell, trade safely.
                </p>
                <div class="footer-social">
                    <div class="social-links">
                        <a href="#" class="social-link" title="Facebook" aria-label="Facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="social-link" title="Twitter" aria-label="Twitter">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="social-link" title="Instagram" aria-label="Instagram">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="social-link" title="LinkedIn" aria-label="LinkedIn">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="footer-section">
                <h4>Quick Links</h4>
                <ul class="footer-links">
                    <li><a href="<?= BASE_URL ?>/">Home</a></li>
                    <li><a href="<?= page('listings.php') ?>">Shop</a></li>
                    <li><a href="<?= page('sell.php') ?>">Sell</a></li>
                    <li><a href="<?= page('auth.php') ?>">About Us</a></li>
                </ul>
            </div>

            <!-- Support -->
            <div class="footer-section">
                <h4>Support</h4>
                <ul class="footer-links">
                    <li><a href="<?= page('support.php') ?>">FAQ</a></li>
                    <li><a href="<?= page('support.php') ?>">Contact</a></li>
                    <li><a href="<?= page('support.php') ?>">Shipping</a></li>
                    <li><a href="<?= page('support.php') ?>">Returns</a></li>
                </ul>
            </div>

            <!-- Newsletter -->
            <div class="footer-newsletter">
                <h4 class="newsletter-title">Newsletter</h4>
                <p class="newsletter-description">
                    Get updates on deals & promotions.
                </p>
                <form class="newsletter-form" id="newsletterForm">
                    <input type="email" 
                           name="newsletter_email" 
                           placeholder="Your email" 
                           required
                           aria-label="Email for newsletter"
                           class="newsletter-input">
                    <button type="submit" class="newsletter-btn" aria-label="Subscribe to newsletter">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </form>
                
                <div class="payment-methods">
                    <h5>Payments</h5>
                    <div class="payment-icons">
                        <i class="fab fa-cc-visa" title="Visa" aria-label="Visa"></i>
                        <i class="fab fa-cc-mastercard" title="Mastercard" aria-label="Mastercard"></i>
                        <i class="fab fa-cc-paypal" title="PayPal" aria-label="PayPal"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom Section -->
        <div class="footer-bottom">
            
            <!-- Trust Badges -->
            <div class="trust-badges">
                <span class="trust-badge">
                    <i class="fas fa-lock"></i> Secure
                </span>
                <span class="trust-badge">
                    <i class="fas fa-shield-alt"></i> Protected
                </span>
                <span class="trust-badge">
                    <i class="fas fa-check-circle"></i> Verified
                </span>
            </div>

            <!-- Copyright -->
            <div class="footer-copyright">
                <p>&copy; <?php echo date('Y'); ?> PeerCart. All rights reserved.</p>
            </div>

            <!-- Legal Links -->
            <div class="footer-legal">
                <a href="<?= page('terms.php') ?>">Terms</a>
                <a href="<?= page('privacy.php') ?>">Privacy</a>
                <a href="<?= page('cookies.php') ?>">Cookies</a>
            </div>
        </div>
    </div>
</footer>

<!-- JavaScript -->
<script>
// Back to Top Button
const backToTop = document.getElementById('backToTop');

window.addEventListener('scroll', () => {
    if (window.pageYOffset > 300) {
        backToTop.classList.add('show');
    } else {
        backToTop.classList.remove('show');
    }
});

backToTop.addEventListener('click', () => {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
});

// Newsletter Form
document.getElementById('newsletterForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const email = this.querySelector('input[name="newsletter_email"]').value;
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalHTML = submitBtn.innerHTML;
    
    // Show loading state
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    submitBtn.disabled = true;
    
    // Simulate API call
    setTimeout(() => {
        // Simple success notification
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--primary);
            color: white;
            padding: 12px 24px;
            border-radius: var(--radius-md);
            font-size: 14px;
            font-weight: 600;
            z-index: 1001;
            box-shadow: var(--shadow-lg);
            display: flex;
            align-items: center;
            gap: 8px;
            animation: slideUp 0.3s ease;
        `;
        notification.innerHTML = '<i class="fas fa-check-circle"></i> Subscribed successfully!';
        document.body.appendChild(notification);
        
        // Remove notification after 3 seconds
        setTimeout(() => {
            notification.style.animation = 'slideDown 0.3s ease';
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 3000);
        
        // Reset form
        this.reset();
        submitBtn.innerHTML = originalHTML;
        submitBtn.disabled = false;
    }, 1000);
});

// Hover effects
document.querySelectorAll('.social-link, .footer-links a').forEach(link => {
    link.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-3px)';
    });
    
    link.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0)';
    });
});

// Highlight current page in footer
document.querySelectorAll('.footer-links a').forEach(link => {
    const currentPath = window.location.pathname;
    const linkPath = new URL(link.href).pathname;
    
    if (currentPath === linkPath || 
        (currentPath.includes('listings') && linkPath.includes('listings')) ||
        (currentPath.includes('sell') && linkPath.includes('sell'))) {
        link.style.color = 'var(--primary)';
        link.style.fontWeight = '600';
        link.style.position = 'relative';
    }
});

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translate(-50%, 20px);
        }
        to {
            opacity: 1;
            transform: translate(-50%, 0);
        }
    }
    
    @keyframes slideDown {
        from {
            opacity: 1;
            transform: translate(-50%, 0);
        }
        to {
            opacity: 0;
            transform: translate(-50%, 20px);
        }
    }
`;
document.head.appendChild(style);
</script>