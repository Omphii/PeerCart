<?php
// pages/support.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ .'/../includes/functions.php';
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if it's a modal request
$is_modal = isset($_GET['modal']) && $_GET['modal'] == 'true';
$active_tab = $_GET['tab'] ?? 'help-center';

// If modal request, only output modal content
if ($is_modal) {
    $title = 'Support Center - PeerCart';
    $support_content = getSupportContent($active_tab);
    echo $support_content;
    exit;
}

// Full page request
$title = 'Support Center - PeerCart';
$currentPage = 'support';

// Include header
includePartial('header', ['title' => $title]);

// Function to get support content
function getSupportContent($tab = 'help-center') {
    ob_start();
    ?>
    <div class="support-modal-content">
        <!-- Support Modal Header -->
        <div class="support-modal-header">
            <div class="support-header-left">
                <div class="support-header-icon">
                    <i class="fas fa-headset"></i>
                </div>
                <div class="support-header-text">
                    <h3 class="support-modal-title">PeerCart Support Center</h3>
                    <p class="support-modal-subtitle">Get help with your peer-to-peer shopping experience</p>
                </div>
            </div>
            <button type="button" class="support-modal-close" onclick="closeSupportModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <!-- Support Tabs Navigation -->
        <div class="support-tabs-nav">
            <div class="support-tabs-container">
                <button class="support-tab <?= $tab === 'help-center' ? 'active' : '' ?>" 
                        onclick="switchSupportTab('help-center')" data-tab="help-center">
                    <i class="fas fa-home"></i>
                    <span>Help Center</span>
                </button>
                <button class="support-tab <?= $tab === 'faq' ? 'active' : '' ?>" 
                        onclick="switchSupportTab('faq')" data-tab="faq">
                    <i class="fas fa-question-circle"></i>
                    <span>FAQ</span>
                </button>
                <button class="support-tab <?= $tab === 'contact' ? 'active' : '' ?>" 
                        onclick="switchSupportTab('contact')" data-tab="contact">
                    <i class="fas fa-envelope"></i>
                    <span>Contact Us</span>
                </button>
                <button class="support-tab <?= $tab === 'shipping' ? 'active' : '' ?>" 
                        onclick="switchSupportTab('shipping')" data-tab="shipping">
                    <i class="fas fa-shipping-fast"></i>
                    <span>Shipping</span>
                </button>
                <button class="support-tab <?= $tab === 'returns' ? 'active' : '' ?>" 
                        onclick="switchSupportTab('returns')" data-tab="returns">
                    <i class="fas fa-undo"></i>
                    <span>Returns</span>
                </button>
                <button class="support-tab <?= $tab === 'privacy' ? 'active' : '' ?>" 
                        onclick="switchSupportTab('privacy')" data-tab="privacy">
                    <i class="fas fa-shield-alt"></i>
                    <span>Privacy</span>
                </button>
                <button class="support-tab <?= $tab === 'terms' ? 'active' : '' ?>" 
                        onclick="switchSupportTab('terms')" data-tab="terms">
                    <i class="fas fa-file-contract"></i>
                    <span>Terms</span>
                </button>
            </div>
        </div>
        
        <!-- Support Content -->
        <div class="support-content">
            <?php if ($tab === 'help-center'): ?>
                <!-- Help Center -->
                <div class="support-tab-content active" id="help-center-content">
                    <div class="help-center-hero">
                        <div class="help-center-icon-large">
                            <i class="fas fa-headset"></i>
                        </div>
                        <h2>How can we help you today?</h2>
                        <p class="help-center-subtitle">Find answers to common questions or contact our support team</p>
                        
                        <!-- Quick Search -->
                        <div class="help-search-container">
                            <div class="help-search-input">
                                <i class="fas fa-search"></i>
                                <input type="text" placeholder="Search for help..." id="help-search">
                                <button class="search-btn">Search</button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Links -->
                    <div class="quick-links-grid">
                        <div class="quick-link-card" onclick="switchSupportTab('faq')">
                            <div class="quick-link-icon">
                                <i class="fas fa-question-circle"></i>
                            </div>
                            <h4>Frequently Asked Questions</h4>
                            <p>Find answers to common questions</p>
                        </div>
                        
                        <div class="quick-link-card" onclick="switchSupportTab('contact')">
                            <div class="quick-link-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <h4>Contact Support</h4>
                            <p>Get in touch with our team</p>
                        </div>
                        
                        <div class="quick-link-card" onclick="switchSupportTab('shipping')">
                            <div class="quick-link-icon">
                                <i class="fas fa-shipping-fast"></i>
                            </div>
                            <h4>Shipping & Delivery</h4>
                            <p>Track orders & delivery info</p>
                        </div>
                        
                        <div class="quick-link-card" onclick="switchSupportTab('returns')">
                            <div class="quick-link-icon">
                                <i class="fas fa-undo"></i>
                            </div>
                            <h4>Returns & Refunds</h4>
                            <p>Return policy & refund process</p>
                        </div>
                    </div>
                    
                    <!-- Popular Topics -->
                    <div class="popular-topics">
                        <h3>Popular Topics</h3>
                        <div class="topics-grid">
                            <button class="topic-tag" onclick="showTopic('account')">
                                <i class="fas fa-user-circle"></i> Account Issues
                            </button>
                            <button class="topic-tag" onclick="showTopic('payment')">
                                <i class="fas fa-credit-card"></i> Payment Problems
                            </button>
                            <button class="topic-tag" onclick="showTopic('tracking')">
                                <i class="fas fa-map-marker-alt"></i> Order Tracking
                            </button>
                            <button class="topic-tag" onclick="showTopic('seller')">
                                <i class="fas fa-store"></i> Seller Support
                            </button>
                            <button class="topic-tag" onclick="showTopic('safety')">
                                <i class="fas fa-shield-alt"></i> Safety Guidelines
                            </button>
                            <button class="topic-tag" onclick="showTopic('technical')">
                                <i class="fas fa-laptop"></i> Technical Support
                            </button>
                        </div>
                    </div>
                    
                    <!-- Getting Started -->
                    <div class="getting-started">
                        <h3>Getting Started with PeerCart</h3>
                        <div class="getting-started-steps">
                            <div class="step">
                                <div class="step-number">1</div>
                                <div class="step-content">
                                    <h4>Create Your Account</h4>
                                    <p>Sign up in under 2 minutes to start buying and selling in your community</p>
                                </div>
                            </div>
                            <div class="step">
                                <div class="step-number">2</div>
                                <div class="step-content">
                                    <h4>Verify Your Profile</h4>
                                    <p>Build trust by verifying your account with email and phone number</p>
                                </div>
                            </div>
                            <div class="step">
                                <div class="step-number">3</div>
                                <div class="step-content">
                                    <h4>Start Trading</h4>
                                    <p>Browse listings or create your own. Connect directly with other users</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
            <?php elseif ($tab === 'faq'): ?>
                <!-- FAQ Content -->
                <div class="support-tab-content active" id="faq-content">
                    <h2>Frequently Asked Questions</h2>
                    <p class="tab-subtitle">Find quick answers to common questions about PeerCart</p>
                    
                    <div class="faq-categories">
                        <div class="faq-category active" data-category="general">
                            <h4><i class="fas fa-home"></i> General</h4>
                        </div>
                        <div class="faq-category" data-category="account">
                            <h4><i class="fas fa-user"></i> Account</h4>
                        </div>
                        <div class="faq-category" data-category="buying">
                            <h4><i class="fas fa-shopping-cart"></i> Buying</h4>
                        </div>
                        <div class="faq-category" data-category="selling">
                            <h4><i class="fas fa-store"></i> Selling</h4>
                        </div>
                        <div class="faq-category" data-category="payment">
                            <h4><i class="fas fa-credit-card"></i> Payment</h4>
                        </div>
                        <div class="faq-category" data-category="safety">
                            <h4><i class="fas fa-shield-alt"></i> Safety</h4>
                        </div>
                    </div>
                    
                    <div class="faq-items">
                        <!-- General FAQ -->
                        <div class="faq-category-content active" id="faq-general">
                            <div class="faq-item active">
                                <div class="faq-question">
                                    <h4>What is PeerCart?</h4>
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                                <div class="faq-answer">
                                    <p>PeerCart is South Africa's premier peer-to-peer marketplace where you can buy and sell items directly with people in your community. We connect buyers and sellers locally and nationally without middleman fees.</p>
                                </div>
                            </div>
                            
                            <div class="faq-item">
                                <div class="faq-question">
                                    <h4>Is PeerCart free to use?</h4>
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                                <div class="faq-answer">
                                    <p>Yes, PeerCart is completely free for buyers. Sellers only pay a 5% commission on successful sales (capped at R100). There are no listing fees, subscription charges, or hidden costs.</p>
                                </div>
                            </div>
                            
                            <div class="faq-item">
                                <div class="faq-question">
                                    <h4>How do I create an account?</h4>
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                                <div class="faq-answer">
                                    <p>Click on "Register" at the top right of any page. You can sign up using your email address or connect with Google or Facebook. It only takes 2 minutes! After registration, verify your email to unlock full functionality.</p>
                                </div>
                            </div>
                            
                            <div class="faq-item">
                                <div class="faq-question">
                                    <h4>Is PeerCart available nationwide?</h4>
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                                <div class="faq-answer">
                                    <p>Yes! PeerCart operates across all 9 provinces of South Africa. You can filter listings by city or region to find items near you, or enable national shipping for broader selection.</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Account FAQ -->
                        <div class="faq-category-content" id="faq-account">
                            <div class="faq-item">
                                <div class="faq-question">
                                    <h4>How do I reset my password?</h4>
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                                <div class="faq-answer">
                                    <p>Click "Forgot Password" on the login page. Enter your email address and we'll send you a password reset link. The link expires in 24 hours for security. If you don't receive the email, check your spam folder.</p>
                                </div>
                            </div>
                            
                            <div class="faq-item">
                                <div class="faq-question">
                                    <h4>Can I change my username?</h4>
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                                <div class="faq-answer">
                                    <p>Yes, you can change your username once every 30 days. Go to Account Settings → Profile and click "Edit" next to your username. Note that changing username doesn't affect your transaction history.</p>
                                </div>
                            </div>
                            
                            <div class="faq-item">
                                <div class="faq-question">
                                    <h4>How do I verify my account?</h4>
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                                <div class="faq-answer">
                                    <p>Account verification increases trust. Go to Settings → Verification. You can verify your email, phone number, and ID document. Verified accounts get priority support and higher visibility.</p>
                                </div>
                            </div>
                            
                            <div class="faq-item">
                                <div class="faq-question">
                                    <h4>Can I have multiple accounts?</h4>
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                                <div class="faq-answer">
                                    <p>No, each individual or business should have only one account. Multiple accounts may be suspended. If you need a business account, contact support for special arrangements.</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Buying FAQ -->
                        <div class="faq-category-content" id="faq-buying">
                            <div class="faq-item">
                                <div class="faq-question">
                                    <h4>How do I make a purchase?</h4>
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                                <div class="faq-answer">
                                    <p>1. Browse listings or use search<br>
                                       2. Click on an item to view details<br>
                                       3. Click "Add to Cart" or "Buy Now"<br>
                                       4. Proceed to checkout<br>
                                       5. Select payment method<br>
                                       6. Confirm purchase<br>
                                       You'll receive order confirmation and tracking details.</p>
                                </div>
                            </div>
                            
                            <div class="faq-item">
                                <div class="faq-question">
                                    <h4>What payment methods are accepted?</h4>
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                                <div class="faq-answer">
                                    <p>We accept:<br>
                                       • Credit/Debit Cards (Visa, MasterCard)<br>
                                       • EFT/Bank Transfer<br>
                                       • Cash on Delivery (selected areas)<br>
                                       • Mobile Payments (selected providers)<br>
                                       All payments are secured through our payment gateway.</p>
                                </div>
                            </div>
                            
                            <div class="faq-item">
                                <div class="faq-question">
                                    <h4>How do I know if a seller is trustworthy?</h4>
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                                <div class="faq-answer">
                                    <p>Check the seller's profile for:<br>
                                       • Verification badges (email, phone, ID)<br>
                                       • User rating and reviews<br>
                                       • Response rate and time<br>
                                       • Number of successful transactions<br>
                                       • Account age<br>
                                       Always read recent reviews before purchasing.</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Selling FAQ -->
                        <div class="faq-category-content" id="faq-selling">
                            <div class="faq-item">
                                <div class="faq-question">
                                    <h4>How do I list an item for sale?</h4>
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                                <div class="faq-answer">
                                    <p>1. Click "Sell" in the main navigation<br>
                                       2. Fill in item details (title, description, price)<br>
                                       3. Upload clear photos (minimum 3)<br>
                                       4. Set shipping options<br>
                                       5. Choose category<br>
                                       6. Review and publish<br>
                                       Your listing goes live immediately!</p>
                                </div>
                            </div>
                            
                            <div class="faq-item">
                                <div class="faq-question">
                                    <h4>What fees do sellers pay?</h4>
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                                <div class="faq-answer">
                                    <p>PeerCart charges:<br>
                                       • 5% commission on successful sales<br>
                                       • Maximum fee: R100 per transaction<br>
                                       • No listing fees<br>
                                       • No subscription fees<br>
                                       • Payment processing fees: 2.9% + R2<br>
                                       Example: Sell R1000 item → R50 commission + R31 processing = R81 total fee.</p>
                                </div>
                            </div>
                            
                            <div class="faq-item">
                                <div class="faq-question">
                                    <h4>When do I get paid?</h4>
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                                <div class="faq-answer">
                                    <p>Payments are released to sellers:<br>
                                       • 24 hours after delivery confirmation<br>
                                       • Or 7 days after delivery if no issues reported<br>
                                       • Paid directly to your bank account<br>
                                       • Processing time: 1-3 business days<br>
                                       All payments are protected until buyer confirms receipt.</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Payment FAQ -->
                        <div class="faq-category-content" id="faq-payment">
                            <div class="faq-item">
                                <div class="faq-question">
                                    <h4>Is my payment information secure?</h4>
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                                <div class="faq-answer">
                                    <p>Yes! We use industry-standard security:<br>
                                       • PCI-DSS compliant payment processing<br>
                                       • SSL encryption for all transactions<br>
                                       • No storage of full card details<br>
                                       • 3D Secure authentication<br>
                                       • Regular security audits<br>
                                       Your financial information is always protected.</p>
                                </div>
                            </div>
                            
                            <div class="faq-item">
                                <div class="faq-question">
                                    <h4>What is PeerCart's buyer protection?</h4>
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                                <div class="faq-answer">
                                    <p>Our buyer protection covers:<br>
                                       • Items not as described<br>
                                       • Items not received<br>
                                       • Counterfeit goods<br>
                                       • Major damage during shipping<br>
                                       • Wrong item received<br>
                                       File a dispute within 30 days of delivery. We mediate between buyer and seller.</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Safety FAQ -->
                        <div class="faq-category-content" id="faq-safety">
                            <div class="faq-item">
                                <div class="faq-question">
                                    <h4>How do I stay safe on PeerCart?</h4>
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                                <div class="faq-answer">
                                    <p>Safety tips:<br>
                                       • Only communicate through PeerCart messaging<br>
                                       • Never share personal contact details<br>
                                       • Meet in public places for local pickup<br>
                                       • Use secure payment methods<br>
                                       • Check user ratings and reviews<br>
                                       • Report suspicious activity immediately<br>
                                       • Trust your instincts</p>
                                </div>
                            </div>
                            
                            <div class="faq-item">
                                <div class="faq-question">
                                    <h4>What should I do if I encounter a scam?</h4>
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                                <div class="faq-answer">
                                    <p>If you suspect a scam:<br>
                                       1. Stop communication immediately<br>
                                       2. Do NOT send any payment<br>
                                       3. Report the user through their profile<br>
                                       4. Contact support with details<br>
                                       5. Save all conversation screenshots<br>
                                       We investigate all reports within 24 hours.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Still Need Help -->
                    <div class="still-need-help">
                        <div class="help-cta">
                            <i class="fas fa-comments"></i>
                            <h4>Still need help?</h4>
                            <p>Can't find the answer you're looking for? Our support team is ready to assist you.</p>
                            <button class="cta-button" onclick="switchSupportTab('contact')">
                                <i class="fas fa-envelope"></i> Contact Support
                            </button>
                        </div>
                    </div>
                </div>
                
            <?php elseif ($tab === 'contact'): ?>
                <!-- Contact Form -->
                <div class="support-tab-content active" id="contact-content">
                    <h2>Contact Our Support Team</h2>
                    <p class="tab-subtitle">We're here to help! Reach out to us and we'll respond within 24 hours.</p>
                    
                    <div class="contact-methods">
                        <div class="contact-method-card">
                            <div class="contact-method-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <h4>Email Support</h4>
                            <p>support@peercart.co.za</p>
                            <small>Response time: 24 hours</small>
                        </div>
                        
                        <div class="contact-method-card">
                            <div class="contact-method-icon">
                                <i class="fas fa-phone"></i>
                            </div>
                            <h4>Phone Support</h4>
                            <p>0861 PEERCART (733 722)</p>
                            <small>Mon-Fri, 8AM-6PM SAST</small>
                        </div>
                        
                        <div class="contact-method-card">
                            <div class="contact-method-icon">
                                <i class="fas fa-comments"></i>
                            </div>
                            <h4>Live Chat</h4>
                            <p>Available now</p>
                            <button class="chat-btn" onclick="startLiveChat()">Start Chat</button>
                        </div>
                    </div>
                    
                    <div class="contact-form-container">
                        <h3>Send us a message</h3>
                        <p class="form-subtitle">Fill out the form below and our team will get back to you as soon as possible.</p>
                        
                        <form id="support-contact-form">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="contact-name">Full Name *</label>
                                    <input type="text" id="contact-name" placeholder="Enter your full name" required>
                                </div>
                                <div class="form-group">
                                    <label for="contact-email">Email Address *</label>
                                    <input type="email" id="contact-email" placeholder="Enter your email address" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="contact-subject">What do you need help with? *</label>
                                <select id="contact-subject" required>
                                    <option value="">Select a topic</option>
                                    <option value="account">Account Issues</option>
                                    <option value="payment">Payment Problems</option>
                                    <option value="order">Order Issues</option>
                                    <option value="seller">Seller Support</option>
                                    <option value="technical">Technical Support</option>
                                    <option value="refund">Refund Request</option>
                                    <option value="safety">Safety Concern</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="contact-order">Order/Listing Number (if applicable)</label>
                                <input type="text" id="contact-order" placeholder="e.g., ORD-12345 or LST-78901">
                            </div>
                            
                            <div class="form-group">
                                <label for="contact-message">Message Details *</label>
                                <textarea id="contact-message" placeholder="Please describe your issue in detail. Include relevant information like dates, amounts, and usernames involved." rows="6" required></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" required>
                                    <span>I agree to the <a href="javascript:void(0);" onclick="switchSupportTab('privacy')">Privacy Policy</a> and allow PeerCart to contact me regarding this inquiry.</span>
                                </label>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="submit-btn">
                                    <i class="fas fa-paper-plane"></i> Send Message
                                </button>
                                <button type="reset" class="reset-btn">
                                    <i class="fas fa-redo"></i> Clear Form
                                </button>
                            </div>
                        </form>
                        
                        <div class="contact-info-note">
                            <p><i class="fas fa-info-circle"></i> <strong>Note:</strong> For urgent matters, please call our support line. Include your order number for faster resolution.</p>
                        </div>
                    </div>
                </div>
                
            <?php elseif ($tab === 'shipping'): ?>
                <!-- Shipping Information -->
                <div class="support-tab-content active" id="shipping-content">
                    <h2>Shipping & Delivery Information</h2>
                    <p class="tab-subtitle">Everything you need to know about shipping on PeerCart</p>
                    
                    <div class="shipping-overview">
                        <div class="shipping-card">
                            <div class="shipping-card-icon">
                                <i class="fas fa-truck"></i>
                            </div>
                            <h3>How Shipping Works</h3>
                            <p>Each seller manages their own shipping. You'll see available shipping options and costs before checkout.</p>
                        </div>
                        
                        <div class="shipping-card">
                            <div class="shipping-card-icon">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <h3>Local Pickup</h3>
                            <p>Many sellers offer local pickup. Arrange safe meeting in public places. Never share home addresses.</p>
                        </div>
                        
                        <div class="shipping-card">
                            <div class="shipping-card-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <h3>Delivery Times</h3>
                            <p>Standard delivery: 3-7 business days<br>
                               Express delivery: 1-3 business days<br>
                               Remote areas: Up to 10 business days</p>
                        </div>
                    </div>
                    
                    <div class="shipping-timeline">
                        <div class="timeline-item active">
                            <div class="timeline-icon">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                            <div class="timeline-content">
                                <h4>Order Placed</h4>
                                <p>Once you place an order, the seller has 48 hours to confirm and prepare your item for shipping. You'll receive an order confirmation email.</p>
                            </div>
                        </div>
                        
                        <div class="timeline-item">
                            <div class="timeline-icon">
                                <i class="fas fa-box"></i>
                            </div>
                            <div class="timeline-content">
                                <h4>Item Shipped</h4>
                                <p>Seller ships the item within 1-3 business days. You'll receive tracking information via email once the item is dispatched.</p>
                            </div>
                        </div>
                        
                        <div class="timeline-item">
                            <div class="timeline-icon">
                                <i class="fas fa-truck"></i>
                            </div>
                            <div class="timeline-content">
                                <h4>In Transit</h4>
                                <p>Delivery typically takes 3-7 business days within South Africa, depending on your location and chosen shipping method.</p>
                            </div>
                        </div>
                        
                        <div class="timeline-item">
                            <div class="timeline-icon">
                                <i class="fas fa-home"></i>
                            </div>
                            <div class="timeline-content">
                                <h4>Delivered</h4>
                                <p>You'll receive delivery notification. Please inspect your item within 24 hours of delivery. Report any issues immediately.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="shipping-faq">
                        <h3>Shipping FAQ</h3>
                        
                        <div class="shipping-faq-item">
                            <div class="faq-question">
                                <h4>How much does shipping cost?</h4>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="faq-answer">
                                <p>Shipping costs vary by seller, item size/weight, and your location. Each seller sets their own shipping rates which are clearly displayed during checkout. You'll see the exact shipping cost before completing your purchase.</p>
                            </div>
                        </div>
                        
                        <div class="shipping-faq-item">
                            <div class="faq-question">
                                <h4>How do I track my order?</h4>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="faq-answer">
                                <p>Once your item is shipped, you'll receive a tracking number via email. You can also view tracking information in your Orders page. Click the tracking number to see real-time updates from the courier.</p>
                            </div>
                        </div>
                        
                        <div class="shipping-faq-item">
                            <div class="faq-question">
                                <h4>What if my package is delayed?</h4>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="faq-answer">
                                <p>If your package is significantly delayed:<br>
                                   1. Check tracking for updates<br>
                                   2. Contact the seller through PeerCart messaging<br>
                                   3. If no response within 48 hours, contact support<br>
                                   We'll help facilitate communication with the seller and courier.</p>
                            </div>
                        </div>
                        
                        <div class="shipping-faq-item">
                            <div class="faq-question">
                                <h4>Do you ship internationally?</h4>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="faq-answer">
                                <p>Currently, PeerCart focuses on South African buyers and sellers. Some sellers may offer international shipping at additional cost. Check the listing details or contact the seller directly for international shipping options.</p>
                            </div>
                        </div>
                        
                        <div class="shipping-faq-item">
                            <div class="faq-question">
                                <h4>What happens if my package is damaged?</h4>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="faq-answer">
                                <p>If your package arrives damaged:<br>
                                   1. Take photos immediately<br>
                                   2. Do not discard packaging<br>
                                   3. Contact the seller within 24 hours<br>
                                   4. File a dispute if needed<br>
                                   Sellers are responsible for proper packaging. PeerCart will mediate if necessary.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="courier-partners">
                        <h3>Our Courier Partners</h3>
                        <div class="courier-logos">
                            <div class="courier-logo">The Courier Guy</div>
                            <div class="courier-logo">Fastway</div>
                            <div class="courier-logo">Pargo</div>
                            <div class="courier-logo">PostNet</div>
                            <div class="courier-logo">Dawn Wing</div>
                        </div>
                        <p class="courier-note">Sellers may use any reputable courier service. Always confirm shipping method with the seller.</p>
                    </div>
                </div>
                
            <?php elseif ($tab === 'returns'): ?>
                <!-- Returns & Refunds -->
                <div class="support-tab-content active" id="returns-content">
                    <h2>Returns & Refunds Policy</h2>
                    <p class="tab-subtitle">Our simple and fair return process for peer-to-peer transactions</p>
                    
                    <div class="returns-overview">
                        <div class="return-policy-card">
                            <div class="policy-icon">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <h4>Return Window</h4>
                            <p>30 days from delivery date</p>
                        </div>
                        
                        <div class="return-policy-card">
                            <div class="policy-icon">
                                <i class="fas fa-box-open"></i>
                            </div>
                            <h4>Condition Required</h4>
                            <p>Unused with original packaging</p>
                        </div>
                        
                        <div class="return-policy-card">
                            <div class="policy-icon">
                                <i class="fas fa-money-bill-wave"></i>
                            </div>
                            <h4>Refund Processing</h4>
                            <p>5-10 business days</p>
                        </div>
                        
                        <div class="return-policy-card">
                            <div class="policy-icon">
                                <i class="fas fa-shipping-fast"></i>
                            </div>
                            <h4>Return Shipping</h4>
                            <p>Buyer pays unless item faulty</p>
                        </div>
                    </div>
                    
                    <div class="return-steps">
                        <div class="return-step">
                            <div class="step-number">1</div>
                            <div class="step-content">
                                <h4>Request Return</h4>
                                <p>Submit a return request within 30 days of delivery through your Orders page. Include clear photos and detailed reason.</p>
                            </div>
                        </div>
                        
                        <div class="return-step">
                            <div class="step-number">2</div>
                            <div class="step-content">
                                <h4>Get Approval</h4>
                                <p>Seller reviews your request and provides a return authorization within 48 hours. PeerCart mediates if needed.</p>
                            </div>
                        </div>
                        
                        <div class="return-step">
                            <div class="step-number">3</div>
                                <div class="step-content">
                                <h4>Ship Item Back</h4>
                                <p>Ship the item back using the provided return label within 7 days of approval. Use trackable shipping and keep receipt.</p>
                            </div>
                        </div>
                        
                        <div class="return-step">
                            <div class="step-number">4</div>
                            <div class="step-content">
                                <h4>Receive Refund</h4>
                                <p>Once we receive and verify the return, your refund is processed within 5-10 business days to original payment method.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="return-conditions">
                        <h3>Return Conditions</h3>
                        <div class="conditions-grid">
                            <div class="condition-card valid">
                                <i class="fas fa-check-circle"></i>
                                <h4>Valid Reasons for Return</h4>
                                <ul>
                                    <li><strong>Not as described:</strong> Item differs significantly from listing</li>
                                    <li><strong>Wrong item:</strong> Received different item than ordered</li>
                                    <li><strong>Damaged:</strong> Item arrived broken or damaged</li>
                                    <li><strong>Defective:</strong> Item doesn't work as intended</li>
                                    <li><strong>Missing parts:</strong> Incomplete item received</li>
                                </ul>
                            </div>
                            
                            <div class="condition-card invalid">
                                <i class="fas fa-times-circle"></i>
                                <h4>Not Valid for Return</h4>
                                <ul>
                                    <li><strong>Changed mind:</strong> Simply no longer want the item</li>
                                    <li><strong>Found cheaper:</strong> Found same item for less elsewhere</li>
                                    <li><strong>Used item:</strong> Item has been used or installed</li>
                                    <li><strong>Missing packaging:</strong> Original packaging discarded</li>
                                    <li><strong>Buyer's error:</strong> Ordered wrong size/color/model</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <div class="refund-details">
                        <h3>Refund Details</h3>
                        <div class="refund-info">
                            <div class="refund-item">
                                <h4>Refund Amount</h4>
                                <p>You'll receive the item price plus original shipping cost (if applicable). Return shipping is only covered if the return is due to seller error.</p>
                            </div>
                            
                            <div class="refund-item">
                                <h4>Processing Time</h4>
                                <p>Refunds typically process within 5-10 business days after we receive the return. Bank processing may add additional 2-3 days.</p>
                            </div>
                            
                            <div class="refund-item">
                                <h4>Payment Method</h4>
                                <p>Refunds go back to your original payment method. Credit/debit card refunds appear on your next statement.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="seller-returns">
                        <h3>For Sellers: Handling Returns</h3>
                        <div class="seller-tips">
                            <div class="seller-tip">
                                <i class="fas fa-camera"></i>
                                <h4>Document Everything</h4>
                                <p>Take photos/videos when packing items. This protects against false damage claims.</p>
                            </div>
                            
                            <div class="seller-tip">
                                <i class="fas fa-comments"></i>
                                <h4>Communicate Promptly</h4>
                                <p>Respond to return requests within 24 hours. Good communication prevents disputes.</p>
                            </div>
                            
                            <div class="seller-tip">
                                <i class="fas fa-shield-alt"></i>
                                <h4>Use Tracking</h4>
                                <p>Always use trackable shipping for returns. This provides proof of delivery.</p>
                            </div>
                        </div>
                    </div>
                </div>
                
            <?php elseif ($tab === 'privacy'): ?>
                <!-- Privacy Policy -->
                <div class="support-tab-content active" id="privacy-content">
                    <h2>Privacy Policy</h2>
                    <div class="privacy-content">
                        <p class="last-updated">Last updated: <?= date('F j, Y') ?></p>
                        
                        <div class="privacy-section">
                            <h3>1. Introduction</h3>
                            <p>Welcome to PeerCart ("we," "our," or "us"). We respect your privacy and are committed to protecting your personal data. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you use our peer-to-peer marketplace platform.</p>
                        </div>
                        
                        <div class="privacy-section">
                            <h3>2. Information We Collect</h3>
                            <h4>Personal Information</h4>
                            <ul>
                                <li><strong>Account Information:</strong> Name, email address, phone number, profile picture</li>
                                <li><strong>Identity Verification:</strong> ID documents (for verified sellers)</li>
                                <li><strong>Contact Information:</strong> Shipping/billing addresses</li>
                                <li><strong>Payment Information:</strong> Encrypted payment details (handled by our payment processor)</li>
                            </ul>
                            
                            <h4>Transaction Information</h4>
                            <ul>
                                <li>Purchase and sales history</li>
                                <li>Payment amounts and methods</li>
                                <li>Shipping details and tracking information</li>
                                <li>Communications between buyers and sellers</li>
                            </ul>
                            
                            <h4>Usage Information</h4>
                            <ul>
                                <li>Device information (IP address, browser type)</li>
                                <li>Location data (for local listings)</li>
                                <li>Activity logs and search queries</li>
                                <li>Cookies and similar technologies</li>
                            </ul>
                        </div>
                        
                        <div class="privacy-section">
                            <h3>3. How We Use Your Information</h3>
                            <ul>
                                <li><strong>To provide our services:</strong> Facilitate transactions between buyers and sellers</li>
                                <li><strong>To verify identities:</strong> Prevent fraud and ensure platform safety</li>
                                <li><strong>To process payments:</strong> Handle secure payment transactions</li>
                                <li><strong>To communicate:</strong> Send order updates, security alerts, and support responses</li>
                                <li><strong>To improve our platform:</strong> Analyze usage patterns and enhance user experience</li>
                                <li><strong>To ensure security:</strong> Detect and prevent fraudulent activities</li>
                                <li><strong>To comply with laws:</strong> Meet legal obligations and respond to legal requests</li>
                            </ul>
                        </div>
                        
                        <div class="privacy-section">
                            <h3>4. Information Sharing</h3>
                            <p>We only share your information in these specific circumstances:</p>
                            
                            <h4>With Other Users</h4>
                            <ul>
                                <li>Buyers see seller's username, rating, and city</li>
                                <li>Sellers see buyer's username and shipping address (for delivery)</li>
                                <li>Public profile information (what you choose to share)</li>
                            </ul>
                            
                            <h4>With Service Providers</h4>
                            <ul>
                                <li>Payment processors (for transaction handling)</li>
                                <li>Courier services (for shipping labels)</li>
                                <li>Cloud hosting providers (for data storage)</li>
                                <li>Customer support platforms</li>
                            </ul>
                            
                            <h4>Legal Requirements</h4>
                            <ul>
                                <li>When required by law or legal process</li>
                                <li>To protect our rights or prevent fraud</li>
                                <li>In connection with a business transfer (merger or acquisition)</li>
                            </ul>
                        </div>
                        
                        <div class="privacy-section">
                            <h3>5. Data Security</h3>
                            <p>We implement appropriate technical and organizational measures to protect your personal data:</p>
                            <ul>
                                <li>SSL encryption for all data transmissions</li>
                                <li>Regular security audits and vulnerability testing</li>
                                <li>Secure payment processing (PCI DSS compliant)</li>
                                <li>Access controls and authentication measures</li>
                                <li>Regular employee security training</li>
                            </ul>
                        </div>
                        
                        <div class="privacy-section">
                            <h3>6. Your Rights (POPIA Compliance)</h3>
                            <p>Under South Africa's Protection of Personal Information Act (POPIA), you have the right to:</p>
                            <ul>
                                <li><strong>Access:</strong> Request copies of your personal data</li>
                                <li><strong>Correction:</strong> Request correction of inaccurate data</li>
                                <li><strong>Deletion:</strong> Request deletion of your personal data</li>
                                <li><strong>Objection:</strong> Object to our processing of your data</li>
                                <li><strong>Restriction:</strong> Request restriction of processing</li>
                                <li><strong>Data Portability:</strong> Request transfer of your data</li>
                            </ul>
                            <p>To exercise these rights, contact our Information Officer at <a href="mailto:privacy@peercart.co.za">privacy@peercart.co.za</a>.</p>
                        </div>
                        
                        <div class="privacy-section">
                            <h3>7. Data Retention</h3>
                            <p>We retain your personal data only as long as necessary:</p>
                            <ul>
                                <li><strong>Active accounts:</strong> Until account deletion request</li>
                                <li><strong>Transaction records:</strong> 5 years (for tax and legal compliance)</li>
                                <li><strong>Inactive accounts:</strong> 3 years of inactivity</li>
                                <li><strong>Marketing data:</strong> Until consent withdrawal</li>
                            </ul>
                        </div>
                        
                        <div class="privacy-section">
                            <h3>8. Cookies and Tracking</h3>
                            <p>We use cookies and similar tracking technologies:</p>
                            <ul>
                                <li><strong>Essential cookies:</strong> Required for platform functionality</li>
                                <li><strong>Preference cookies:</strong> Remember your settings</li>
                                <li><strong>Analytics cookies:</strong> Understand how you use our platform</li>
                                <li><strong>Marketing cookies:</strong> Show relevant advertisements</li>
                            </ul>
                            <p>You can control cookies through your browser settings. Note that disabling cookies may affect platform functionality.</p>
                        </div>
                        
                        <div class="privacy-section">
                            <h3>9. Children's Privacy</h3>
                            <p>PeerCart is not intended for users under 18 years of age. We do not knowingly collect personal data from children. If you believe we have collected data from a child, please contact us immediately.</p>
                        </div>
                        
                        <div class="privacy-section">
                            <h3>10. Changes to This Policy</h3>
                            <p>We may update this Privacy Policy periodically. We will notify you of significant changes by email or platform notification. Continued use after changes constitutes acceptance of the updated policy.</p>
                        </div>
                        
                        <div class="privacy-section">
                            <h3>11. Contact Us</h3>
                            <p>If you have questions about this Privacy Policy or our data practices:</p>
                            <ul>
                                <li><strong>Email:</strong> privacy@peercart.co.za</li>
                                <li><strong>Address:</strong> Information Officer, PeerCart (Pty) Ltd, Cape Town, South Africa</li>
                                <li><strong>Phone:</strong> 0861 PEERCART (733 722)</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
            <?php elseif ($tab === 'terms'): ?>
                <!-- Terms of Service -->
                <div class="support-tab-content active" id="terms-content">
                    <h2>Terms of Service</h2>
                    <div class="terms-content">
                        <p class="last-updated">Last updated: <?= date('F j, Y') ?></p>
                        
                        <div class="terms-section">
                            <h3>1. Acceptance of Terms</h3>
                            <p>By accessing or using the PeerCart platform, you agree to be bound by these Terms of Service ("Terms"). If you disagree with any part, you may not access our platform.</p>
                        </div>
                        
                        <div class="terms-section">
                            <h3>2. User Accounts</h3>
                            <h4>Eligibility</h4>
                            <ul>
                                <li>You must be at least 18 years old</li>
                                <li>You must provide accurate and complete information</li>
                                <li>You are responsible for maintaining account security</li>
                                <li>One account per individual or business</li>
                            </ul>
                            
                            <h4>Account Responsibilities</h4>
                            <ul>
                                <li>Keep login credentials confidential</li>
                                <li>Notify us immediately of unauthorized access</li>
                                <li>You are responsible for all activity under your account</li>
                                <li>Accounts may be suspended for violations</li>
                            </ul>
                        </div>
                        
                        <div class="terms-section">
                            <h3>3. Platform Usage</h3>
                            <h4>Permitted Use</h4>
                            <ul>
                                <li>Buying and selling legal items</li>
                                <li>Communicating with other users for transactions</li>
                                <li>Leaving honest reviews and ratings</li>
                                <li>Using platform features as intended</li>
                            </ul>
                            
                            <h4>Prohibited Activities</h4>
                            <ul>
                                <li><strong>Illegal items:</strong> Drugs, weapons, stolen goods</li>
                                <li><strong>Counterfeit goods:</strong> Fake or replica items</li>
                                <li><strong>Hate speech:</strong> Discriminatory or abusive content</li>
                                <li><strong>Fraud:</strong> Scams, misleading listings, fake reviews</li>
                                <li><strong>Spam:</strong> Unsolicited commercial messages</li>
                                <li><strong>Circumvention:</strong> Attempting to avoid fees or rules</li>
                                <li><strong>Multiple accounts:</strong> Creating duplicate accounts</li>
                            </ul>
                        </div>
                        
                        <div class="terms-section">
                            <h3>4. Buying and Selling</h3>
                            <h4>For Buyers</h4>
                            <ul>
                                <li>You agree to pay the listed price plus applicable fees</li>
                                <li>Payment must be made through approved methods</li>
                                <li>You must provide accurate shipping information</li>
                                <li>You agree to inspect items upon delivery</li>
                                <li>Disputes must be filed within 30 days of delivery</li>
                            </ul>
                            
                            <h4>For Sellers</h4>
                            <ul>
                                <li>You must accurately describe items and condition</li>
                                <li>You must ship items within agreed timeframe</li>
                                <li>You are responsible for proper packaging</li>
                                <li>You agree to our commission structure (5%, max R100)</li>
                                <li>You must comply with all applicable laws</li>
                            </ul>
                        </div>
                        
                        <div class="terms-section">
                            <h3>5. Fees and Payments</h3>
                            <ul>
                                <li><strong>Buyers:</strong> No fees for purchasing</li>
                                <li><strong>Sellers:</strong> 5% commission on successful sales (maximum R100 per transaction)</li>
                                <li><strong>Payment processing:</strong> Additional fees may apply (shown at checkout)</li>
                                <li><strong>Taxes:</strong> You are responsible for applicable taxes</li>
                                <li><strong>Refunds:</strong> Fees may be refunded in case of returns</li>
                            </ul>
                        </div>
                        
                        <div class="terms-section">
                            <h3>6. Dispute Resolution</h3>
                            <h4>PeerCart's Role</h4>
                            <ul>
                                <li>We provide a platform for transactions</li>
                                <li>We are not party to buyer-seller agreements</li>
                                <li>We may mediate disputes when requested</li>
                                <li>Our decisions in disputes are final</li>
                            </ul>
                            
                            <h4>Dispute Process</h4>
                            <ul>
                                <li>Attempt direct resolution with the other party first</li>
                                <li>If unsuccessful, file a dispute through our platform</li>
                                <li>Provide evidence (photos, messages, tracking)</li>
                                <li>We will review and make a decision within 7 days</li>
                                <li>Decisions may include refunds, returns, or account actions</li>
                            </ul>
                        </div>
                        
                        <div class="terms-section">
                            <h3>7. Intellectual Property</h3>
                            <ul>
                                <li><strong>Our content:</strong> Platform design, logos, software are our property</li>
                                <li><strong>Your content:</strong> You retain rights to your listings and photos</li>
                                <li><strong>License:</strong> You grant us license to display your content on our platform</li>
                                <li><strong>Prohibited:</strong> Copying, modifying, or distributing our content without permission</li>
                            </ul>
                        </div>
                        
                        <div class="terms-section">
                            <h3>8. Limitation of Liability</h3>
                            <p>To the maximum extent permitted by law:</p>
                            <ul>
                                <li>We are not liable for user conduct or content</li>
                                <li>We are not liable for transaction disputes between users</li>
                                <li>We are not liable for shipping delays or damages</li>
                                <li>Our total liability is limited to fees paid in last 6 months</li>
                                <li>We are not liable for indirect, incidental, or consequential damages</li>
                            </ul>
                        </div>
                        
                        <div class="terms-section">
                            <h3>9. Termination</h3>
                            <ul>
                                <li>You may delete your account at any time</li>
                                <li>We may suspend or terminate accounts for violations</li>
                                <li>Termination doesn't relieve outstanding obligations</li>
                                <li>Provisions surviving termination: Fees, Liability, Disputes</li>
                            </ul>
                        </div>
                        
                        <div class="terms-section">
                            <h3>10. Changes to Terms</h3>
                            <p>We may modify these Terms at any time. We will notify you of significant changes. Continued use after changes constitutes acceptance. If you disagree with changes, you must stop using our platform.</p>
                        </div>
                        
                        <div class="terms-section">
                            <h3>11. Governing Law</h3>
                            <p>These Terms are governed by the laws of South Africa. Any disputes shall be resolved in the courts of South Africa.</p>
                        </div>
                        
                        <div class="terms-section">
                            <h3>12. Contact Information</h3>
                            <p>For questions about these Terms:</p>
                            <ul>
                                <li><strong>Email:</strong> legal@peercart.co.za</li>
                                <li><strong>Address:</strong> PeerCart (Pty) Ltd, Cape Town, South Africa</li>
                                <li><strong>Phone:</strong> 0861 PEERCART (733 722)</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
            <?php endif; ?>
        </div>
    </div>
    
    <style>
        /* ============ SUPPORT MODAL STYLES ============ */
        :root {
            /* Use same variables as cart.php */
            --primary: #4361ee;
            --primary-light: #4895ef;
            --primary-dark: #3a0ca3;
            --secondary: #7209b7;
            --accent: #f72585;
            --success: #4cc9f0;
            --warning: #f8961e;
            --danger: #e63946;
            --dark: #1a1a2e;
            --dark-gray: #2d3047;
            --gray: #6c757d;
            --light-gray: #f8f9fa;
            --white: #ffffff;
            --bg-primary: #ffffff;
            --bg-secondary: #f8f9fa;
            --text-primary: #1a1a2e;
            --text-secondary: #6c757d;
            --border-color: rgba(0, 0, 0, 0.1);
            --glass-bg: rgba(255, 255, 255, 0.98);
            --glass-border: rgba(255, 255, 255, 0.3);
            --glass-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
            --space-xs: 0.5rem;
            --space-sm: 1rem;
            --space-md: 1.5rem;
            --space-lg: 2rem;
            --space-xl: 3rem;
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --radius-xl: 24px;
            --radius-full: 100px;
            --transition-fast: 0.2s ease;
            --transition-normal: 0.3s ease;
            --transition-slow: 0.5s ease;
        }

        [data-theme="dark"] {
            --glass-bg: rgba(26, 26, 46, 0.98);
            --glass-border: rgba(255, 255, 255, 0.15);
            --glass-shadow: 0 25px 50px rgba(0, 0, 0, 0.5);
        }

        /* Support Modal Container */
        .support-modal-container {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(10px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 99999;
            opacity: 0;
            visibility: hidden;
            transition: all var(--transition-normal);
            padding: var(--space-sm);
        }

        .support-modal-container.active {
            opacity: 1;
            visibility: visible;
        }

        .support-modal-content {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-lg);
            box-shadow: var(--glass-shadow);
            width: 100%;
            max-width: 1200px;
            max-height: 90vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            animation: modalSlideIn 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            transform-origin: center center;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: scale(0.95) translateY(20px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        /* Support Modal Header */
        .support-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: var(--space-md);
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            position: relative;
            overflow: hidden;
        }

        .support-modal-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, 
                rgba(255, 255, 255, 0.1) 0%, 
                rgba(255, 255, 255, 0) 100%);
            pointer-events: none;
        }

        .support-header-left {
            display: flex;
            align-items: center;
            gap: var(--space-md);
        }

        .support-header-icon {
            width: 48px;
            height: 48px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .support-header-text {
            flex: 1;
        }

        .support-modal-title {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 700;
        }

        .support-modal-subtitle {
            margin: 0.25rem 0 0 0;
            opacity: 0.9;
            font-size: 0.9rem;
        }

        .support-modal-close {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            width: 40px;
            height: 40px;
            border-radius: var(--radius-md);
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            transition: all var(--transition-fast);
            position: relative;
            z-index: 1;
        }

        .support-modal-close:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: rotate(90deg);
        }

        /* Tabs Navigation */
        .support-tabs-nav {
            background: var(--bg-secondary);
            border-bottom: 1px solid var(--border-color);
            padding: var(--space-sm);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .support-tabs-container {
            display: flex;
            gap: var(--space-xs);
            overflow-x: auto;
            padding: 0.25rem;
            scrollbar-width: thin;
            scrollbar-color: var(--primary) transparent;
        }

        .support-tabs-container::-webkit-scrollbar {
            height: 4px;
        }

        .support-tabs-container::-webkit-scrollbar-track {
            background: transparent;
        }

        .support-tabs-container::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: var(--radius-full);
        }

        .support-tab {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.25rem;
            background: transparent;
            border: none;
            border-radius: var(--radius-full);
            color: var(--text-secondary);
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all var(--transition-fast);
            white-space: nowrap;
            flex-shrink: 0;
        }

        .support-tab:hover {
            background: rgba(67, 97, 238, 0.1);
            color: var(--primary);
            transform: translateY(-1px);
        }

        .support-tab.active {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            box-shadow: 0 4px 12px rgba(67, 97, 238, 0.3);
        }

        .support-tab i {
            font-size: 1rem;
        }

        /* Support Content Area */
        .support-content {
            flex: 1;
            overflow-y: auto;
            padding: var(--space-md);
            min-height: 400px;
        }

        /* Tab Content */
        .support-tab-content {
            display: none;
            animation: fadeIn 0.3s ease;
        }

        .support-tab-content.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .support-tab-content h2 {
            margin: 0 0 var(--space-sm) 0;
            font-size: 1.75rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .tab-subtitle {
            color: var(--text-secondary);
            margin-bottom: var(--space-lg);
            font-size: 1rem;
            max-width: 600px;
        }

        /* Help Center Styles */
        .help-center-hero {
            text-align: center;
            padding: var(--space-lg) 0;
            margin-bottom: var(--space-lg);
        }

        .help-center-icon-large {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto var(--space-md);
            color: white;
            font-size: 2rem;
            box-shadow: 0 8px 25px rgba(67, 97, 238, 0.3);
        }

        .help-center-hero h2 {
            margin-bottom: var(--space-sm);
        }

        .help-search-container {
            max-width: 600px;
            margin: var(--space-lg) auto;
        }

        .help-search-input {
            display: flex;
            align-items: center;
            background: var(--bg-primary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-full);
            padding: 0.5rem 0.5rem 0.5rem 1rem;
            box-shadow: var(--shadow-md);
            transition: all var(--transition-normal);
        }

        .help-search-input:focus-within {
            border-color: var(--primary);
            box-shadow: 0 8px 25px rgba(67, 97, 238, 0.2);
            transform: translateY(-2px);
        }

        .help-search-input i {
            color: var(--text-secondary);
            margin-right: 0.5rem;
        }

        .help-search-input input {
            flex: 1;
            border: none;
            background: transparent;
            padding: 0.5rem;
            font-size: 1rem;
            color: var(--text-primary);
            outline: none;
        }

        .search-btn {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: var(--radius-full);
            font-weight: 600;
            cursor: pointer;
            transition: all var(--transition-normal);
        }

        .search-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(67, 97, 238, 0.3);
        }

        /* Quick Links */
        .quick-links-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: var(--space-md);
            margin-bottom: var(--space-lg);
        }

        .quick-link-card {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-lg);
            padding: var(--space-md);
            text-align: center;
            cursor: pointer;
            transition: all var(--transition-normal);
            text-decoration: none;
            color: inherit;
        }

        .quick-link-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary);
        }

        .quick-link-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, 
                rgba(67, 97, 238, 0.1), 
                rgba(114, 9, 183, 0.1));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto var(--space-sm);
            color: var(--primary);
            font-size: 1.5rem;
            transition: all var(--transition-normal);
        }

        .quick-link-card:hover .quick-link-icon {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            transform: scale(1.1) rotate(5deg);
        }

        .quick-link-card h4 {
            margin: 0 0 0.5rem 0;
            font-size: 1.1rem;
            font-weight: 700;
        }

        .quick-link-card p {
            margin: 0;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        /* Getting Started */
        .getting-started {
            margin-top: var(--space-xl);
            padding: var(--space-lg);
            background: linear-gradient(135deg, 
                rgba(67, 97, 238, 0.05), 
                rgba(114, 9, 183, 0.05));
            border-radius: var(--radius-lg);
        }

        .getting-started h3 {
            text-align: center;
            margin-bottom: var(--space-lg);
            color: var(--text-primary);
        }

        .getting-started-steps {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: var(--space-lg);
        }

        .step {
            text-align: center;
        }

        .step-number {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto var(--space-sm);
            color: white;
            font-weight: 800;
            font-size: 1.25rem;
        }

        .step-content h4 {
            margin: 0 0 0.5rem 0;
            font-size: 1.1rem;
            color: var(--text-primary);
        }

        .step-content p {
            margin: 0;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        /* Popular Topics */
        .popular-topics {
            margin-top: var(--space-xl);
        }

        .popular-topics h3 {
            margin-bottom: var(--space-md);
            font-size: 1.3rem;
            color: var(--text-primary);
        }

        .topics-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .topic-tag {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.25rem;
            background: var(--glass-bg);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-full);
            color: var(--text-secondary);
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all var(--transition-fast);
        }

        .topic-tag:hover {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(67, 97, 238, 0.3);
        }

        /* FAQ Styles */
        .faq-categories {
            display: flex;
            gap: var(--space-sm);
            margin-bottom: var(--space-lg);
            padding: 0.5rem;
            background: var(--bg-secondary);
            border-radius: var(--radius-md);
            overflow-x: auto;
        }

        .faq-category {
            padding: 0.75rem 1.5rem;
            background: transparent;
            border: none;
            border-radius: var(--radius-md);
            cursor: pointer;
            transition: all var(--transition-fast);
            flex-shrink: 0;
            text-align: center;
        }

        .faq-category:hover {
            background: rgba(67, 97, 238, 0.1);
        }

        .faq-category.active {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            box-shadow: 0 4px 12px rgba(67, 97, 238, 0.3);
        }

        .faq-category h4 {
            margin: 0;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .faq-items {
            max-width: 800px;
            margin: 0 auto;
        }

        .faq-item {
            background: var(--glass-bg);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            margin-bottom: var(--space-sm);
            overflow: hidden;
            transition: all var(--transition-normal);
        }

        .faq-item:hover {
            border-color: var(--primary);
            box-shadow: var(--shadow-md);
        }

        .faq-question {
            padding: var(--space-md);
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
        }

        .faq-question h4 {
            margin: 0;
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-primary);
            flex: 1;
        }

        .faq-question i {
            color: var(--text-secondary);
            transition: transform var(--transition-normal);
        }

        .faq-item.active .faq-question i {
            transform: rotate(180deg);
        }

        .faq-answer {
            padding: 0 var(--space-md);
            max-height: 0;
            overflow: hidden;
            transition: all var(--transition-normal);
        }

        .faq-item.active .faq-answer {
            padding: 0 var(--space-md) var(--space-md);
            max-height: 500px;
        }

        .faq-answer p {
            margin: 0;
            color: var(--text-secondary);
            line-height: 1.6;
        }

        .faq-category-content {
            display: none;
        }

        .faq-category-content.active {
            display: block;
        }

        /* Still Need Help */
        .still-need-help {
            margin-top: var(--space-xl);
            padding: var(--space-lg);
            background: linear-gradient(135deg, 
                rgba(67, 97, 238, 0.1), 
                rgba(114, 9, 183, 0.1));
            border-radius: var(--radius-lg);
            text-align: center;
        }

        .help-cta i {
            font-size: 3rem;
            color: var(--primary);
            margin-bottom: var(--space-md);
        }

        .help-cta h4 {
            margin: 0 0 var(--space-sm);
            font-size: 1.5rem;
            color: var(--text-primary);
        }

        .help-cta p {
            margin: 0 0 var(--space-lg);
            color: var(--text-secondary);
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Contact Styles */
        .contact-methods {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: var(--space-md);
            margin-bottom: var(--space-lg);
        }

        .contact-method-card {
            background: var(--glass-bg);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: var(--space-md);
            text-align: center;
            transition: all var(--transition-normal);
        }

        .contact-method-card:hover {
            transform: translateY(-5px);
            border-color: var(--primary);
            box-shadow: var(--shadow-lg);
        }

        .contact-method-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, 
                rgba(67, 97, 238, 0.1), 
                rgba(114, 9, 183, 0.1));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto var(--space-sm);
            color: var(--primary);
            font-size: 1.5rem;
        }

        .contact-method-card h4 {
            margin: 0 0 0.5rem 0;
            font-size: 1.1rem;
            font-weight: 700;
        }

        .contact-method-card p {
            margin: 0;
            font-size: 1rem;
            font-weight: 600;
            color: var(--primary);
        }

        .contact-method-card small {
            color: var(--text-secondary);
            font-size: 0.85rem;
            display: block;
            margin-top: 0.5rem;
        }

        .chat-btn {
            margin-top: var(--space-sm);
            background: linear-gradient(135deg, var(--success), #38b000);
            color: white;
            border: none;
            padding: 0.5rem 1.5rem;
            border-radius: var(--radius-full);
            font-weight: 600;
            cursor: pointer;
            transition: all var(--transition-normal);
        }

        .chat-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(76, 201, 240, 0.3);
        }

        /* Contact Form */
        .contact-form-container {
            background: var(--glass-bg);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: var(--space-lg);
            margin-top: var(--space-lg);
        }

        .contact-form-container h3 {
            margin: 0 0 var(--space-sm) 0;
            font-size: 1.3rem;
            color: var(--text-primary);
        }

        .form-subtitle {
            color: var(--text-secondary);
            margin-bottom: var(--space-lg);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--space-md);
            margin-bottom: var(--space-md);
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
                gap: var(--space-sm);
            }
        }

        .form-group {
            margin-bottom: var(--space-md);
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-primary);
            font-size: 0.95rem;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            background: var(--bg-primary);
            color: var(--text-primary);
            font-size: 1rem;
            transition: all var(--transition-fast);
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }

        .checkbox-label {
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            cursor: pointer;
        }

        .checkbox-label input {
            margin-top: 0.25rem;
        }

        .checkbox-label span {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .checkbox-label a {
            color: var(--primary);
            text-decoration: none;
        }

        .checkbox-label a:hover {
            text-decoration: underline;
        }

        .form-actions {
            display: flex;
            gap: var(--space-md);
            margin-top: var(--space-lg);
        }

        .submit-btn {
            flex: 1;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: var(--radius-full);
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: all var(--transition-normal);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(67, 97, 238, 0.3);
        }

        .reset-btn {
            flex: 0 0 auto;
            background: transparent;
            color: var(--text-secondary);
            border: 1px solid var(--border-color);
            padding: 1rem 1.5rem;
            border-radius: var(--radius-full);
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all var(--transition-normal);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .reset-btn:hover {
            background: var(--bg-secondary);
            border-color: var(--primary);
            color: var(--primary);
        }

        .contact-info-note {
            margin-top: var(--space-lg);
            padding: var(--space-sm);
            background: rgba(67, 97, 238, 0.05);
            border-radius: var(--radius-md);
            border-left: 4px solid var(--primary);
        }

        .contact-info-note p {
            margin: 0;
            color: var(--text-secondary);
            font-size: 0.9rem;
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
        }

        .contact-info-note i {
            color: var(--primary);
            margin-top: 0.125rem;
        }

        /* Shipping Styles */
        .shipping-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: var(--space-md);
            margin-bottom: var(--space-lg);
        }

        .shipping-card {
            background: var(--glass-bg);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: var(--space-md);
            text-align: center;
        }

        .shipping-card-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, 
                rgba(67, 97, 238, 0.1), 
                rgba(114, 9, 183, 0.1));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto var(--space-sm);
            color: var(--primary);
            font-size: 1.5rem;
        }

        .shipping-card h3 {
            margin: 0 0 0.5rem 0;
            font-size: 1.1rem;
            color: var(--text-primary);
        }

        .shipping-card p {
            margin: 0;
            color: var(--text-secondary);
            font-size: 0.9rem;
            line-height: 1.5;
        }

        /* Shipping Timeline */
        .shipping-timeline {
            max-width: 800px;
            margin: var(--space-lg) auto;
            position: relative;
        }

        .shipping-timeline::before {
            content: '';
            position: absolute;
            left: 30px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: linear-gradient(to bottom, var(--primary), var(--secondary));
        }

        .timeline-item {
            display: flex;
            gap: var(--space-md);
            margin-bottom: var(--space-lg);
            position: relative;
            opacity: 0.5;
            transition: all var(--transition-normal);
        }

        .timeline-item.active {
            opacity: 1;
        }

        .timeline-item:last-child {
            margin-bottom: 0;
        }

        .timeline-icon {
            width: 60px;
            height: 60px;
            background: var(--bg-primary);
            border: 2px solid var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            font-size: 1.25rem;
            flex-shrink: 0;
            position: relative;
            z-index: 1;
        }

        .timeline-content {
            flex: 1;
            padding-top: 0.5rem;
        }

        .timeline-content h4 {
            margin: 0 0 0.5rem 0;
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .timeline-content p {
            margin: 0;
            color: var(--text-secondary);
            line-height: 1.6;
        }

        /* Shipping FAQ */
        .shipping-faq {
            margin-top: var(--space-xl);
        }

        .shipping-faq h3 {
            margin-bottom: var(--space-lg);
            color: var(--text-primary);
        }

        .shipping-faq-item {
            background: var(--glass-bg);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            margin-bottom: var(--space-sm);
            overflow: hidden;
            transition: all var(--transition-normal);
        }

        .shipping-faq-item:hover {
            border-color: var(--primary);
            box-shadow: var(--shadow-md);
        }

        .shipping-faq-item .faq-question {
            padding: var(--space-md);
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
        }

        .shipping-faq-item .faq-question h4 {
            margin: 0;
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-primary);
            flex: 1;
        }

        .shipping-faq-item .faq-question i {
            color: var(--text-secondary);
            transition: transform var(--transition-normal);
        }

        .shipping-faq-item.active .faq-question i {
            transform: rotate(180deg);
        }

        .shipping-faq-item .faq-answer {
            padding: 0 var(--space-md);
            max-height: 0;
            overflow: hidden;
            transition: all var(--transition-normal);
        }

        .shipping-faq-item.active .faq-answer {
            padding: 0 var(--space-md) var(--space-md);
            max-height: 500px;
        }

        .shipping-faq-item .faq-answer p {
            margin: 0;
            color: var(--text-secondary);
            line-height: 1.6;
        }

        /* Courier Partners */
        .courier-partners {
            margin-top: var(--space-xl);
            padding: var(--space-lg);
            background: linear-gradient(135deg, 
                rgba(67, 97, 238, 0.05), 
                rgba(114, 9, 183, 0.05));
            border-radius: var(--radius-lg);
            text-align: center;
        }

        .courier-partners h3 {
            margin-bottom: var(--space-md);
            color: var(--text-primary);
        }

        .courier-logos {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: var(--space-sm);
            margin: var(--space-md) 0;
        }

        .courier-logo {
            padding: 0.5rem 1rem;
            background: var(--glass-bg);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            color: var(--text-secondary);
            font-weight: 600;
            font-size: 0.9rem;
        }

        .courier-note {
            margin: 0;
            color: var(--text-secondary);
            font-size: 0.9rem;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Returns Styles */
        .returns-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--space-md);
            margin-bottom: var(--space-lg);
        }

        .return-policy-card {
            background: var(--glass-bg);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: var(--space-md);
            text-align: center;
            transition: all var(--transition-normal);
        }

        .return-policy-card:hover {
            transform: translateY(-5px);
            border-color: var(--primary);
            box-shadow: var(--shadow-md);
        }

        .policy-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, 
                rgba(67, 97, 238, 0.1), 
                rgba(114, 9, 183, 0.1));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto var(--space-sm);
            color: var(--primary);
            font-size: 1.25rem;
        }

        .return-policy-card h4 {
            margin: 0 0 0.5rem 0;
            font-size: 1rem;
            color: var(--text-primary);
        }

        .return-policy-card p {
            margin: 0;
            color: var(--text-secondary);
            font-size: 0.9rem;
            font-weight: 600;
        }

        /* Return Steps */
        .return-steps {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--space-lg);
            margin: var(--space-lg) 0;
        }

        .return-step {
            text-align: center;
            position: relative;
        }

        .step-number {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 800;
            font-size: 1.25rem;
            margin: 0 auto var(--space-sm);
            box-shadow: 0 4px 12px rgba(67, 97, 238, 0.3);
        }

        .step-content h4 {
            margin: 0 0 0.5rem 0;
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .step-content p {
            margin: 0;
            color: var(--text-secondary);
            font-size: 0.9rem;
            line-height: 1.5;
        }

        /* Return Conditions */
        .return-conditions {
            margin: var(--space-xl) 0;
        }

        .return-conditions h3 {
            margin-bottom: var(--space-lg);
            color: var(--text-primary);
        }

        .conditions-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--space-lg);
        }

        @media (max-width: 768px) {
            .conditions-grid {
                grid-template-columns: 1fr;
            }
        }

        .condition-card {
            background: var(--glass-bg);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: var(--space-lg);
        }

        .condition-card.valid {
            border-top: 4px solid var(--success);
        }

        .condition-card.invalid {
            border-top: 4px solid var(--danger);
        }

        .condition-card i {
            font-size: 2rem;
            margin-bottom: var(--space-sm);
        }

        .condition-card.valid i {
            color: var(--success);
        }

        .condition-card.invalid i {
            color: var(--danger);
        }

        .condition-card h4 {
            margin: 0 0 var(--space-md) 0;
            font-size: 1.1rem;
            font-weight: 700;
        }

        .condition-card ul {
            margin: 0;
            padding-left: 1.25rem;
        }

        .condition-card li {
            margin-bottom: 0.75rem;
            color: var(--text-secondary);
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .condition-card li strong {
            color: var(--text-primary);
        }

        /* Refund Details */
        .refund-details {
            margin: var(--space-xl) 0;
        }

        .refund-details h3 {
            margin-bottom: var(--space-lg);
            color: var(--text-primary);
        }

        .refund-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: var(--space-lg);
        }

        .refund-item {
            background: var(--glass-bg);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: var(--space-md);
        }

        .refund-item h4 {
            margin: 0 0 0.5rem 0;
            font-size: 1.1rem;
            color: var(--primary);
        }

        .refund-item p {
            margin: 0;
            color: var(--text-secondary);
            line-height: 1.6;
        }

        /* Seller Returns */
        .seller-returns {
            margin: var(--space-xl) 0;
            padding: var(--space-lg);
            background: linear-gradient(135deg, 
                rgba(67, 97, 238, 0.05), 
                rgba(114, 9, 183, 0.05));
            border-radius: var(--radius-lg);
        }

        .seller-returns h3 {
            margin-bottom: var(--space-lg);
            color: var(--text-primary);
            text-align: center;
        }

        .seller-tips {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: var(--space-lg);
        }

        .seller-tip {
            text-align: center;
        }

        .seller-tip i {
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: var(--space-sm);
        }

        .seller-tip h4 {
            margin: 0 0 0.5rem 0;
            font-size: 1.1rem;
            color: var(--text-primary);
        }

        .seller-tip p {
            margin: 0;
            color: var(--text-secondary);
            font-size: 0.9rem;
            line-height: 1.5;
        }

        /* Privacy & Terms Content */
        .privacy-content,
        .terms-content {
            max-width: 800px;
            margin: 0 auto;
        }

        .last-updated {
            color: var(--text-secondary);
            font-style: italic;
            margin-bottom: var(--space-lg);
        }

        .privacy-section,
        .terms-section {
            margin-bottom: var(--space-xl);
        }

        .privacy-section h3,
        .terms-section h3 {
            margin: 0 0 var(--space-md) 0;
            color: var(--text-primary);
            font-size: 1.3rem;
        }

        .privacy-section h4,
        .terms-section h4 {
            margin: var(--space-md) 0 var(--space-sm);
            color: var(--text-primary);
            font-size: 1.1rem;
        }

        .privacy-section p,
        .terms-section p {
            margin: 0 0 var(--space-sm);
            color: var(--text-secondary);
            line-height: 1.8;
        }

        .privacy-section ul,
        .terms-section ul {
            margin: var(--space-sm) 0 var(--space-sm) 1.5rem;
            padding: 0;
        }

        .privacy-section li,
        .terms-section li {
            margin-bottom: 0.75rem;
            color: var(--text-secondary);
            line-height: 1.6;
        }

        .privacy-section li strong,
        .terms-section li strong {
            color: var(--text-primary);
        }

        /* CTA Button */
        .cta-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: var(--radius-full);
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all var(--transition-normal);
        }

        .cta-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(67, 97, 238, 0.3);
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .support-modal-content {
                max-height: 85vh;
            }
            
            .support-tabs-container {
                padding-bottom: 0.5rem;
            }
            
            .support-tab {
                padding: 0.5rem 1rem;
                font-size: 0.85rem;
            }
        }

        @media (max-width: 768px) {
            .support-modal-container {
                padding: var(--space-xs);
            }
            
            .support-modal-content {
                max-height: 80vh;
            }
            
            .support-modal-header {
                padding: var(--space-sm);
            }
            
            .support-header-left {
                gap: var(--space-sm);
            }
            
            .support-header-icon {
                width: 40px;
                height: 40px;
                font-size: 1.25rem;
            }
            
            .support-modal-title {
                font-size: 1.25rem;
            }
            
            .support-modal-subtitle {
                font-size: 0.85rem;
            }
            
            .support-content {
                padding: var(--space-sm);
            }
            
            .help-center-hero {
                padding: var(--space-md) 0;
            }
            
            .help-center-icon-large {
                width: 60px;
                height: 60px;
                font-size: 1.5rem;
            }
            
            .help-center-hero h2 {
                font-size: 1.5rem;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .submit-btn,
            .reset-btn {
                width: 100%;
            }
        }

        @media (max-width: 480px) {
            .support-modal-container {
                padding: 0;
            }
            
            .support-modal-content {
                border-radius: 0;
                max-height: 100vh;
                height: 100vh;
            }
            
            .support-tabs-container {
                gap: 0.25rem;
            }
            
            .support-tab {
                padding: 0.5rem 0.75rem;
                font-size: 0.8rem;
            }
            
            .support-tab i {
                display: none;
            }
            
            .quick-links-grid {
                grid-template-columns: 1fr;
            }
            
            .contact-methods {
                grid-template-columns: 1fr;
            }
            
            .shipping-overview,
            .return-steps,
            .refund-info,
            .seller-tips {
                grid-template-columns: 1fr;
            }
            
            .getting-started-steps {
                grid-template-columns: 1fr;
            }
        }

        /* Dark Mode Adjustments */
        [data-theme="dark"] .support-tabs-nav {
            background: var(--bg-primary);
        }
        
        [data-theme="dark"] .help-search-input,
        [data-theme="dark"] .contact-form-container,
        [data-theme="dark"] .faq-item,
        [data-theme="dark"] .shipping-faq-item,
        [data-theme="dark"] .condition-card,
        [data-theme="dark"] .refund-item,
        [data-theme="dark"] .shipping-card,
        [data-theme="dark"] .return-policy-card,
        [data-theme="dark"] .quick-link-card,
        [data-theme="dark"] .contact-method-card {
            background: rgba(42, 42, 62, 0.8);
        }
        
        [data-theme="dark"] .quick-link-card:hover,
        [data-theme="dark"] .contact-method-card:hover {
            background: rgba(42, 42, 62, 0.9);
        }
        
        [data-theme="dark"] .timeline-icon {
            background: var(--bg-secondary);
        }
        
        [data-theme="dark"] .getting-started,
        [data-theme="dark"] .still-need-help,
        [data-theme="dark"] .courier-partners,
        [data-theme="dark"] .seller-returns {
            background: linear-gradient(135deg, 
                rgba(90, 118, 255, 0.1), 
                rgba(141, 43, 212, 0.1));
        }
        
        [data-theme="dark"] .contact-info-note {
            background: rgba(90, 118, 255, 0.1);
        }

        /* Print Styles */
        @media print {
            .support-modal-container {
                position: static;
                background: white;
            }
            
            .support-modal-content {
                box-shadow: none;
                border: 1px solid #ddd;
                max-height: none;
            }
            
            .support-modal-close {
                display: none;
            }
        }

        /* Reduced Motion */
        @media (prefers-reduced-motion: reduce) {
            .support-modal-content,
            .support-tab,
            .quick-link-card,
            .contact-method-card,
            .faq-item,
            .shipping-faq-item,
            .submit-btn,
            .reset-btn,
            .timeline-item,
            .support-modal-close,
            .faq-question i,
            .shipping-faq-item .faq-question i {
                transition: none;
            }
            
            .support-modal-content {
                animation: none;
            }
        }
    </style>
    
    <script>
        // Support Modal JavaScript
        let currentSupportTab = '<?= $tab ?>';
        
        function openSupportModal(tab = 'help-center') {
            // Check if modal already exists
            let modal = document.getElementById('support-modal-container');
            
            if (!modal) {
                // Create modal container
                modal = document.createElement('div');
                modal.id = 'support-modal-container';
                modal.className = 'support-modal-container';
                document.body.appendChild(modal);
            }
            
            // Load support content via AJAX
            fetch(`<?= BASE_URL ?>/pages/support.php?modal=true&tab=${tab}`)
                .then(response => response.text())
                .then(html => {
                    modal.innerHTML = html;
                    modal.classList.add('active');
                    document.body.style.overflow = 'hidden'; // Prevent scrolling
                    
                    // Initialize FAQ accordions
                    initFAQAccordions();
                    
                    // Initialize contact form
                    initContactForm();
                    
                    // Initialize shipping FAQ accordions
                    initShippingFAQAccordions();
                    
                    // Initialize search
                    initHelpSearch();
                })
                .catch(error => {
                    console.error('Error loading support modal:', error);
                    modal.innerHTML = '<div class="error-message">Failed to load support content. Please try again.</div>';
                });
        }
        
        function closeSupportModal() {
            const modal = document.getElementById('support-modal-container');
            if (modal) {
                modal.classList.remove('active');
                setTimeout(() => {
                    modal.remove();
                }, 300);
            }
            document.body.style.overflow = ''; // Restore scrolling
        }
        
        function switchSupportTab(tab) {
            currentSupportTab = tab;
            
            // Update active tab
            document.querySelectorAll('.support-tab').forEach(tabEl => {
                tabEl.classList.remove('active');
            });
            
            document.querySelector(`.support-tab[data-tab="${tab}"]`).classList.add('active');
            
            // Hide all tab content
            document.querySelectorAll('.support-tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Show selected tab content
            document.getElementById(`${tab}-content`).classList.add('active');
            
            // Update URL without page reload (for direct linking)
            const url = new URL(window.location);
            url.searchParams.set('tab', tab);
            window.history.replaceState({}, '', url);
            
            // Re-initialize components based on tab
            if (tab === 'faq') {
                initFAQAccordions();
            }
            
            if (tab === 'contact') {
                initContactForm();
            }
            
            if (tab === 'shipping') {
                initShippingFAQAccordions();
            }
            
            // Scroll to top of content
            document.querySelector('.support-content').scrollTop = 0;
        }
        
        function initFAQAccordions() {
            // Main FAQ accordions
            const faqItems = document.querySelectorAll('.faq-item');
            faqItems.forEach(item => {
                const question = item.querySelector('.faq-question');
                question.addEventListener('click', () => {
                    // Close other items in same category
                    const categoryContent = item.closest('.faq-category-content');
                    if (categoryContent) {
                        categoryContent.querySelectorAll('.faq-item').forEach(otherItem => {
                            if (otherItem !== item && otherItem.classList.contains('active')) {
                                otherItem.classList.remove('active');
                            }
                        });
                    }
                    
                    // Toggle current item
                    item.classList.toggle('active');
                });
            });
            
            // FAQ category switching
            const faqCategories = document.querySelectorAll('.faq-category');
            const faqContents = document.querySelectorAll('.faq-category-content');
            
            faqCategories.forEach(category => {
                category.addEventListener('click', () => {
                    const categoryName = category.dataset.category;
                    
                    // Update active category
                    faqCategories.forEach(cat => cat.classList.remove('active'));
                    category.classList.add('active');
                    
                    // Show corresponding content
                    faqContents.forEach(content => {
                        content.classList.remove('active');
                    });
                    document.getElementById(`faq-${categoryName}`).classList.add('active');
                });
            });
        }
        
        function initShippingFAQAccordions() {
            const shippingFaqItems = document.querySelectorAll('.shipping-faq-item');
            shippingFaqItems.forEach(item => {
                const question = item.querySelector('.faq-question');
                question.addEventListener('click', () => {
                    // Close other items
                    shippingFaqItems.forEach(otherItem => {
                        if (otherItem !== item && otherItem.classList.contains('active')) {
                            otherItem.classList.remove('active');
                        }
                    });
                    
                    // Toggle current item
                    item.classList.toggle('active');
                });
            });
        }
        
        function initContactForm() {
            const contactForm = document.getElementById('support-contact-form');
            if (contactForm) {
                // Reset form button
                const resetBtn = contactForm.querySelector('.reset-btn');
                if (resetBtn) {
                    resetBtn.addEventListener('click', () => {
                        contactForm.reset();
                    });
                }
                
                // Form submission
                contactForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = {
                        name: document.getElementById('contact-name').value,
                        email: document.getElementById('contact-email').value,
                        subject: document.getElementById('contact-subject').value,
                        order: document.getElementById('contact-order').value,
                        message: document.getElementById('contact-message').value
                    };
                    
                    // Simple validation
                    if (!formData.name || !formData.email || !formData.subject || !formData.message) {
                        alert('Please fill in all required fields (marked with *)');
                        return;
                    }
                    
                    // Show loading state
                    const submitBtn = contactForm.querySelector('.submit-btn');
                    const originalText = submitBtn.innerHTML;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
                    submitBtn.disabled = true;
                    
                    // Simulate API call
                    setTimeout(() => {
                        // In a real app, you would send this to your server
                        alert('Thank you for your message! We\'ll get back to you within 24 hours. A confirmation email has been sent to ' + formData.email);
                        contactForm.reset();
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    }, 1500);
                });
            }
        }
        
        function initHelpSearch() {
            const searchInput = document.getElementById('help-search');
            const searchBtn = document.querySelector('.search-btn');
            
            if (searchInput && searchBtn) {
                const performSearch = () => {
                    const query = searchInput.value.trim().toLowerCase();
                    if (!query) return;
                    
                    // Show search results
                    alert(`Searching for: "${query}"\n\nIn a real implementation, this would show relevant help articles.`);
                    
                    // Example: If query contains specific keywords, switch to relevant tab
                    if (query.includes('return') || query.includes('refund')) {
                        switchSupportTab('returns');
                    } else if (query.includes('ship') || query.includes('deliver')) {
                        switchSupportTab('shipping');
                    } else if (query.includes('pay') || query.includes('money')) {
                        switchSupportTab('faq');
                        // Could also filter FAQ to payment section
                    }
                };
                
                searchBtn.addEventListener('click', performSearch);
                searchInput.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') {
                        performSearch();
                    }
                });
            }
        }
        
        function showTopic(topic) {
            // Switch to FAQ tab and relevant category
            switchSupportTab('faq');
            
            // Map topics to FAQ categories
            const topicMap = {
                'account': 'account',
                'payment': 'payment',
                'tracking': 'shipping',
                'seller': 'selling',
                'safety': 'safety',
                'technical': 'general'
            };
            
            setTimeout(() => {
                const category = topicMap[topic] || 'general';
                const categoryElement = document.querySelector(`.faq-category[data-category="${category}"]`);
                if (categoryElement) {
                    categoryElement.click();
                    
                    // Scroll to first FAQ in category
                    const firstFaq = document.querySelector(`#faq-${category} .faq-item:first-child`);
                    if (firstFaq) {
                        firstFaq.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        firstFaq.classList.add('active');
                    }
                }
            }, 500);
        }
        
        function startLiveChat() {
            // In a real implementation, this would open a chat widget
            alert('Live chat would open here. In production, this would connect to a chat service like Intercom, Zendesk, or a custom solution.\n\nFor now, please use the contact form or email support@peercart.co.za');
        }
        
        // Close modal when clicking outside content
        document.addEventListener('click', function(e) {
            const modal = document.getElementById('support-modal-container');
            if (modal && modal.classList.contains('active')) {
                if (e.target === modal) {
                    closeSupportModal();
                }
            }
        });
        
        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeSupportModal();
            }
        });
        
        // Make functions available globally
        window.openSupportModal = openSupportModal;
        window.closeSupportModal = closeSupportModal;
        window.switchSupportTab = switchSupportTab;
        window.showTopic = showTopic;
        window.startLiveChat = startLiveChat;
    </script>
    <?php
    return ob_get_clean();
}
?>
</div>

<!-- Full page content (when not in modal) -->
<div class="support-page-container">
    <div class="support-page-header">
        <h1>Support Center</h1>
        <p>Get help with your PeerCart experience</p>
    </div>
    
    <div class="support-page-content">
        <?= getSupportContent($active_tab) ?>
    </div>
</div>

<style>
    /* Full page styles */
    .support-page-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: var(--space-xl) var(--space-md);
    }
    
    .support-page-header {
        text-align: center;
        margin-bottom: var(--space-xl);
        padding: var(--space-lg);
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        border-radius: var(--radius-lg);
        color: white;
    }
    
    .support-page-header h1 {
        margin: 0 0 var(--space-sm);
        font-size: 2.5rem;
        font-weight: 800;
    }
    
    .support-page-header p {
        margin: 0;
        opacity: 0.9;
        font-size: 1.1rem;
    }
    
    .support-page-content {
        background: var(--glass-bg);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-lg);
        overflow: hidden;
        box-shadow: var(--shadow-lg);
    }
</style>

<script>
    // Support Modal JavaScript
    let currentSupportTab = '<?= $tab ?>';
    
    function openSupportModal(tab = 'help-center') {
        // Function for opening modal from within support content
        if (window.parent && window.parent.openSupportModal) {
            window.parent.openSupportModal(tab);
        } else if (window.opener && window.opener.openSupportModal) {
            window.opener.openSupportModal(tab);
        }
    }
    
    function closeSupportModal() {
        if (window.parent && window.parent.closeSupportModal) {
            window.parent.closeSupportModal();
        } else if (window.opener && window.opener.closeSupportModal) {
            window.opener.closeSupportModal();
        } else {
            // Fallback for standalone view
            window.history.back();
        }
    }
    
    function switchSupportTab(tab) {
        currentSupportTab = tab;
        
        // Update active tab
        document.querySelectorAll('.support-tab').forEach(tabEl => {
            tabEl.classList.remove('active');
        });
        
        const targetTab = document.querySelector(`.support-tab[data-tab="${tab}"]`);
        if (targetTab) {
            targetTab.classList.add('active');
        }
        
        // Hide all tab content
        document.querySelectorAll('.support-tab-content').forEach(content => {
            content.classList.remove('active');
        });
        
        // Show selected tab content
        const targetContent = document.getElementById(`${tab}-content`);
        if (targetContent) {
            targetContent.classList.add('active');
        }
        
        // Update URL without page reload (for direct linking)
        const url = new URL(window.location);
        url.searchParams.set('tab', tab);
        window.history.replaceState({}, '', url);
        
        // Re-initialize components based on tab
        if (tab === 'faq') {
            initFAQAccordions();
        }
        
        if (tab === 'contact') {
            initContactForm();
        }
        
        if (tab === 'shipping') {
            initShippingFAQAccordions();
        }
        
        // Scroll to top of content
        const supportContent = document.querySelector('.support-content');
        if (supportContent) {
            supportContent.scrollTop = 0;
        }
    }
    
    function initFAQAccordions() {
        // Main FAQ accordions
        const faqItems = document.querySelectorAll('.faq-item');
        faqItems.forEach(item => {
            const question = item.querySelector('.faq-question');
            if (question) {
                question.addEventListener('click', () => {
                    // Close other items in same category
                    const categoryContent = item.closest('.faq-category-content');
                    if (categoryContent) {
                        categoryContent.querySelectorAll('.faq-item').forEach(otherItem => {
                            if (otherItem !== item && otherItem.classList.contains('active')) {
                                otherItem.classList.remove('active');
                            }
                        });
                    }
                    
                    // Toggle current item
                    item.classList.toggle('active');
                });
            }
        });
        
        // FAQ category switching
        const faqCategories = document.querySelectorAll('.faq-category');
        const faqContents = document.querySelectorAll('.faq-category-content');
        
        faqCategories.forEach(category => {
            category.addEventListener('click', () => {
                const categoryName = category.dataset.category;
                
                // Update active category
                faqCategories.forEach(cat => cat.classList.remove('active'));
                category.classList.add('active');
                
                // Show corresponding content
                faqContents.forEach(content => {
                    content.classList.remove('active');
                });
                
                const targetContent = document.getElementById(`faq-${categoryName}`);
                if (targetContent) {
                    targetContent.classList.add('active');
                }
            });
        });
    }
    
    function initShippingFAQAccordions() {
        const shippingFaqItems = document.querySelectorAll('.shipping-faq-item');
        shippingFaqItems.forEach(item => {
            const question = item.querySelector('.faq-question');
            if (question) {
                question.addEventListener('click', () => {
                    // Close other items
                    shippingFaqItems.forEach(otherItem => {
                        if (otherItem !== item && otherItem.classList.contains('active')) {
                            otherItem.classList.remove('active');
                        }
                    });
                    
                    // Toggle current item
                    item.classList.toggle('active');
                });
            }
        });
    }
    
    function initContactForm() {
        const contactForm = document.getElementById('support-contact-form');
        if (contactForm) {
            // Reset form button
            const resetBtn = contactForm.querySelector('.reset-btn');
            if (resetBtn) {
                resetBtn.addEventListener('click', () => {
                    contactForm.reset();
                });
            }
            
            // Form submission
            contactForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = {
                    name: document.getElementById('contact-name')?.value || '',
                    email: document.getElementById('contact-email')?.value || '',
                    subject: document.getElementById('contact-subject')?.value || '',
                    order: document.getElementById('contact-order')?.value || '',
                    message: document.getElementById('contact-message')?.value || ''
                };
                
                // Simple validation
                if (!formData.name || !formData.email || !formData.subject || !formData.message) {
                    alert('Please fill in all required fields (marked with *)');
                    return;
                }
                
                // Show loading state
                const submitBtn = contactForm.querySelector('.submit-btn');
                if (submitBtn) {
                    const originalText = submitBtn.innerHTML;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
                    submitBtn.disabled = true;
                    
                    // Simulate API call
                    setTimeout(() => {
                        alert('Thank you for your message! We\'ll get back to you within 24 hours. A confirmation email has been sent to ' + formData.email);
                        contactForm.reset();
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    }, 1500);
                }
            });
        }
    }
    
    function initHelpSearch() {
        const searchInput = document.getElementById('help-search');
        const searchBtn = document.querySelector('.search-btn');
        
        if (searchInput && searchBtn) {
            const performSearch = () => {
                const query = searchInput.value.trim().toLowerCase();
                if (!query) return;
                
                // Show search results
                alert(`Searching for: "${query}"\n\nIn a real implementation, this would show relevant help articles.`);
                
                // Example: If query contains specific keywords, switch to relevant tab
                if (query.includes('return') || query.includes('refund')) {
                    switchSupportTab('returns');
                } else if (query.includes('ship') || query.includes('deliver')) {
                    switchSupportTab('shipping');
                } else if (query.includes('pay') || query.includes('money')) {
                    switchSupportTab('faq');
                }
            };
            
            searchBtn.addEventListener('click', performSearch);
            searchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    performSearch();
                }
            });
        }
    }
    
    function showTopic(topic) {
        // Switch to FAQ tab and relevant category
        switchSupportTab('faq');
        
        // Map topics to FAQ categories
        const topicMap = {
            'account': 'account',
            'payment': 'payment',
            'tracking': 'shipping',
            'seller': 'selling',
            'safety': 'safety',
            'technical': 'general'
        };
        
        setTimeout(() => {
            const category = topicMap[topic] || 'general';
            const categoryElement = document.querySelector(`.faq-category[data-category="${category}"]`);
            if (categoryElement) {
                categoryElement.click();
                
                // Scroll to first FAQ in category
                const firstFaq = document.querySelector(`#faq-${category} .faq-item:first-child`);
                if (firstFaq) {
                    firstFaq.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    firstFaq.classList.add('active');
                }
            }
        }, 500);
    }
    
    function startLiveChat() {
        // In a real implementation, this would open a chat widget
        alert('Live chat would open here. In production, this would connect to a chat service like Intercom, Zendesk, or a custom solution.\n\nFor now, please use the contact form or email support@peercart.co.za');
    }
    
    // Initialize everything when DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize based on current tab
        switchSupportTab(currentSupportTab);
        
        // Initialize components based on active tab
        if (currentSupportTab === 'faq') {
            initFAQAccordions();
        }
        
        if (currentSupportTab === 'contact') {
            initContactForm();
        }
        
        if (currentSupportTab === 'shipping') {
            initShippingFAQAccordions();
        }
        
        if (currentSupportTab === 'help-center') {
            initHelpSearch();
        }
    });
    
    // Make functions available globally
    window.openSupportModal = openSupportModal;
    window.closeSupportModal = closeSupportModal;
    window.switchSupportTab = switchSupportTab;
    window.showTopic = showTopic;
    window.startLiveChat = startLiveChat;
    window.initFAQAccordions = initFAQAccordions;
    window.initContactForm = initContactForm;
    window.initShippingFAQAccordions = initShippingFAQAccordions;
    window.initHelpSearch = initHelpSearch;
</script>

<?php
// If full page request, include footer
if (!$is_modal) {
    includePartial('footer');
}


?>