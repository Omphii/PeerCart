<?php
// includes/footer.php
?>
    
    <!-- Back to Top Button -->
    <button id="backToTop" class="back-to-top">
        <i class="fas fa-chevron-up"></i>
    </button>

    <!-- Site Footer -->
    <footer class="site-footer">
        <div class="container">
            <div class="footer-grid">
                <!-- About Section -->
                <div class="footer-about">
                    <div class="footer-logo">
                        <a href="<?= BASE_URL ?>/">Peer<span>Cart</span></a>
                    </div>
                    <p class="footer-description">
                        Peer-to-peer marketplace connecting buyers and sellers directly. 
                        Buy, sell, and trade items in your local community safely and easily.
                    </p>
                    <div class="footer-social">
                        <h4>Follow Us</h4>
                        <div class="social-links">
                            <a href="#" class="social-link" title="Facebook">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <a href="#" class="social-link" title="Twitter">
                                <i class="fab fa-twitter"></i>
                            </a>
                            <a href="#" class="social-link" title="Instagram">
                                <i class="fab fa-instagram"></i>
                            </a>
                            <a href="#" class="social-link" title="LinkedIn">
                                <i class="fab fa-linkedin-in"></i>
                            </a>
                            <a href="#" class="social-link" title="YouTube">
                                <i class="fab fa-youtube"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="footer-links">
                    <h4>Quick Links</h4>
                    <ul class="footer-menu">
                        <li><a href="<?= BASE_URL ?>/"><i class="fas fa-home"></i> Home</a></li>
                        <li><a href="<?= BASE_URL ?>/pages/listings.php"><i class="fas fa-shopping-bag"></i> Shop</a></li>
                        <li><a href="<?= BASE_URL ?>/pages/sell.php"><i class="fas fa-plus-circle"></i> Sell</a></li>
                        <li><a href="<?= BASE_URL ?>/pages/categories.php"><i class="fas fa-tags"></i> Categories</a></li>
                        <li><a href="<?= BASE_URL ?>/pages/about.php"><i class="fas fa-info-circle"></i> About Us</a></li>
                    </ul>
                </div>

                <!-- Help & Support -->
                <div class="footer-links">
                    <h4>Help & Support</h4>
                    <ul class="footer-menu">
                        <li><a href="<?= BASE_URL ?>/pages/faq.php"><i class="fas fa-question-circle"></i> FAQ</a></li>
                        <li><a href="<?= BASE_URL ?>/pages/contact.php"><i class="fas fa-envelope"></i> Contact Us</a></li>
                        <li><a href="<?= BASE_URL ?>/pages/shipping.php"><i class="fas fa-shipping-fast"></i> Shipping Info</a></li>
                        <li><a href="<?= BASE_URL ?>/pages/returns.php"><i class="fas fa-undo"></i> Returns Policy</a></li>
                        <li><a href="<?= BASE_URL ?>/pages/safety.php"><i class="fas fa-shield-alt"></i> Safety Tips</a></li>
                    </ul>
                </div>

                <!-- Newsletter -->
                <div class="footer-newsletter">
                    <h4>Stay Updated</h4>
                    <p class="newsletter-description">
                        Subscribe to our newsletter for the latest deals, promotions, and updates.
                    </p>
                    <form class="newsletter-form" id="newsletterForm">
                        <div class="input-group">
                            <input type="email" 
                                   name="newsletter_email" 
                                   placeholder="Your email address" 
                                   required
                                   class="newsletter-input">
                            <button type="submit" class="newsletter-btn">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                        <div class="form-note">
                            <small>We respect your privacy. Unsubscribe at any time.</small>
                        </div>
                    </form>
                    <div class="payment-methods">
                        <h5>We Accept</h5>
                        <div class="payment-icons">
                            <i class="fab fa-cc-visa" title="Visa"></i>
                            <i class="fab fa-cc-mastercard" title="Mastercard"></i>
                            <i class="fab fa-cc-amex" title="American Express"></i>
                            <i class="fab fa-cc-paypal" title="PayPal"></i>
                            <i class="fab fa-cc-apple-pay" title="Apple Pay"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Bottom -->
            <div class="footer-bottom">
                <div class="footer-bottom-left">
                    <p>&copy; <?= date('Y') ?> PeerCart. All rights reserved.</p>
                    <div class="footer-stats">
                        <span><i class="fas fa-users"></i> <?= getTotalUsers() ?> Members</span>
                        <span><i class="fas fa-shopping-cart"></i> <?= getTotalListings() ?> Listings</span>
                        <span><i class="fas fa-handshake"></i> <?= getTotalTransactions() ?> Transactions</span>
                    </div>
                </div>
                
                <div class="footer-bottom-right">
                    <div class="legal-links">
                        <a href="<?= BASE_URL ?>/pages/terms.php">Terms of Service</a>
                        <a href="<?= BASE_URL ?>/pages/privacy.php">Privacy Policy</a>
                        <a href="<?= BASE_URL ?>/pages/cookies.php">Cookie Policy</a>
                        <a href="<?= BASE_URL ?>/pages/sitemap.php">Sitemap</a>
                    </div>
                    <div class="footer-badges">
                        <span class="badge badge-success"><i class="fas fa-check-circle"></i> Secure Checkout</span>
                        <span class="badge badge-info"><i class="fas fa-shield-alt"></i> Buyer Protection</span>
                        <span class="badge badge-warning"><i class="fas fa-star"></i> Trusted Sellers</span>
                    </div>
                </div>
            </div>

            <!-- Trust Seals -->
            <div class="trust-seals">
                <div class="trust-seal">
                    <i class="fas fa-lock fa-2x"></i>
                    <span>SSL Secure</span>
                </div>
                <div class="trust-seal">
                    <i class="fas fa-shield-alt fa-2x"></i>
                    <span>100% Protected</span>
                </div>
                <div class="trust-seal">
                    <i class="fas fa-truck fa-2x"></i>
                    <span>Fast Shipping</span>
                </div>
                <div class="trust-seal">
                    <i class="fas fa-headset fa-2x"></i>
                    <span>24/7 Support</span>
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
            backToTop.style.display = 'block';
        } else {
            backToTop.style.display = 'none';
        }
    });
    
    backToTop.addEventListener('click', () => {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
    
    // Newsletter Form Submission
    document.getElementById('newsletterForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const email = this.querySelector('input[name="newsletter_email"]').value;
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        // Show loading state
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        submitBtn.disabled = true;
        
        // Simulate API call
        setTimeout(() => {
            // In a real app, you would make an AJAX request here
            alert('Thank you for subscribing to our newsletter!');
            this.reset();
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }, 1500);
    });
    
    // Add hover effects to social links
    document.querySelectorAll('.social-link').forEach(link => {
        link.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-3px)';
        });
        
        link.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
    
    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href !== '#') {
                e.preventDefault();
                const target = document.querySelector(href);
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            }
        });
    });
    </script>

    <!-- Optional: Add these helper functions if not already in functions.php -->
    <?php
    // Add these to your functions.php file if they don't exist:
    
    /**
    
    */
    ?>
</body>
</html>