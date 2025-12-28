<?php
// pages/about-us.php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/functions.php';

// Page title
$title = 'About Us - PeerCart';
includePartial('header', ['title' => $title, 'currentPage' => 'about']);

// Add the external CSS file
echo '<link rel="stylesheet" href="' . asset('css/pages/about-us.css') . '">';
?>

<div class="about-page">
    <!-- Hero Section -->
    <section class="about-hero">
        <div class="hero-particles" id="heroParticles"></div>
        <div class="about-hero-content">
            <span class="hero-badge">Our Story</span>
            <h1>Revolutionizing Local Commerce</h1>
            <p class="hero-subtitle">
                PeerCart is South Africa's fastest-growing peer-to-peer marketplace, 
                connecting buyers and sellers in communities across the country. 
                We're building a trusted platform where commerce meets community.
            </p>
            <div class="hero-buttons">
                <a href="#mission" class="btn-primary-outline">
                    <i class="fas fa-arrow-down"></i> Explore Our Mission
                </a>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <span class="stat-number" data-count="10000">0</span>
                <span class="stat-label">Active Members</span>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <span class="stat-number" data-count="25000">0</span>
                <span class="stat-label">Listings</span>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-handshake"></i>
                </div>
                <span class="stat-number" data-count="5000">0</span>
                <span class="stat-label">Successful Transactions</span>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-map-marker-alt"></i>
                </div>
                <span class="stat-number" data-count="50">0</span>
                <span class="stat-label">Cities Nationwide</span>
            </div>
        </div>
    </section>

    <!-- Mission & Values Section -->
    <section class="mission-section" id="mission">
        <div class="mission-container">
            <div class="mission-content">
                <h2>Our Mission & Values</h2>
                <p>
                    We believe in creating economic opportunities for everyone by making 
                    buying and selling accessible, safe, and rewarding. Our platform 
                    empowers individuals to turn their unused items into income and 
                    find great deals within their communities.
                </p>
                <p>
                    At PeerCart, we're more than just a marketplace—we're building 
                    connections that strengthen local economies and foster sustainable 
                    consumption.
                </p>
            </div>
            <div class="mission-values">
                <div class="value-item">
                    <div class="value-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div class="value-content">
                        <h4>Trust & Safety</h4>
                        <p>Verified users, secure payments, and buyer protection</p>
                    </div>
                </div>
                <div class="value-item">
                    <div class="value-icon">
                        <i class="fas fa-heart"></i>
                    </div>
                    <div class="value-content">
                        <h4>Community First</h4>
                        <p>Building connections that go beyond transactions</p>
                    </div>
                </div>
                <div class="value-item">
                    <div class="value-icon">
                        <i class="fas fa-leaf"></i>
                    </div>
                    <div class="value-content">
                        <h4>Sustainability</h4>
                        <p>Promoting reuse and reducing waste through circular economy</p>
                    </div>
                </div>
                <div class="value-item">
                    <div class="value-icon">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <div class="value-content">
                        <h4>Innovation</h4>
                        <p>Continuously improving our platform for better user experience</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Team Section -->
    <section class="team-section">
        <div class="section-header">
            <h2 class="section-title">Meet Our Leadership</h2>
            <p class="section-subtitle">
                A diverse team of innovators, entrepreneurs, and community builders 
                dedicated to transforming local commerce in South Africa.
            </p>
        </div>
        
        <div class="team-grid">
            <div class="team-card">
                <div class="team-image">
                    <img src="<?= asset('images/team/ceo.jpg') ?>" alt="CEO" onerror="this.src='https://ui-avatars.com/api/?name=John+Doe&background=4361ee&color=fff&size=400'">
                </div>
                <div class="team-info">
                    <h3 class="team-name">John Doe</h3>
                    <span class="team-role">CEO & Founder</span>
                    <p class="team-bio">
                        Serial entrepreneur with 10+ years in e-commerce and 
                        marketplace platforms. Passionate about community-driven innovation.
                    </p>
                    <div class="team-social">
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-github"></i></a>
                    </div>
                </div>
            </div>
            
            <div class="team-card">
                <div class="team-image">
                    <img src="<?= asset('images/team/cto.jpg') ?>" alt="CTO" onerror="this.src='https://ui-avatars.com/api/?name=Jane+Smith&background=3a0ca3&color=fff&size=400'">
                </div>
                <div class="team-info">
                    <h3 class="team-name">Jane Smith</h3>
                    <span class="team-role">CTO</span>
                    <p class="team-bio">
                        Tech visionary with expertise in scalable platforms and 
                        secure payment systems. Former lead engineer at major fintech.
                    </p>
                    <div class="team-social">
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-github"></i></a>
                    </div>
                </div>
            </div>
            
            <div class="team-card">
                <div class="team-image">
                    <img src="<?= asset('images/team/cmo.jpg') ?>" alt="CMO" onerror="this.src='https://ui-avatars.com/api/?name=Sarah+Johnson&background=7209b7&color=fff&size=400'">
                </div>
                <div class="team-info">
                    <h3 class="team-name">Sarah Johnson</h3>
                    <span class="team-role">CMO</span>
                    <p class="team-bio">
                        Marketing strategist specializing in community growth and 
                        brand development. Previously headed marketing at tech startups.
                    </p>
                    <div class="team-social">
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Timeline Section -->
    <section class="timeline-section">
        <div class="section-header">
            <h2 class="section-title">Our Journey</h2>
            <p class="section-subtitle">
                From a small startup idea to South Africa's leading peer-to-peer marketplace
            </p>
        </div>
        
        <div class="timeline">
            <div class="timeline-item">
                <div class="timeline-dot"></div>
                <div class="timeline-content">
                    <div class="timeline-year">2021</div>
                    <h3 class="timeline-title">The Beginning</h3>
                    <p class="timeline-description">
                        Founded in Cape Town with a vision to create a trusted 
                        local marketplace. Launched beta version with 100 users.
                    </p>
                </div>
            </div>
            
            <div class="timeline-item">
                <div class="timeline-dot"></div>
                <div class="timeline-content">
                    <div class="timeline-year">2022</div>
                    <h3 class="timeline-title">Growth & Expansion</h3>
                    <p class="timeline-description">
                        Expanded to 5 major cities. Introduced secure payment system 
                        and mobile app. Reached 10,000 active users milestone.
                    </p>
                </div>
            </div>
            
            <div class="timeline-item">
                <div class="timeline-dot"></div>
                <div class="timeline-content">
                    <div class="timeline-year">2023</div>
                    <h3 class="timeline-title">National Launch</h3>
                    <p class="timeline-description">
                        Officially launched nationwide. Partnered with major delivery 
                        services. Introduced buyer protection program.
                    </p>
                </div>
            </div>
            
            <div class="timeline-item">
                <div class="timeline-dot"></div>
                <div class="timeline-content">
                    <div class="timeline-year">2024</div>
                    <h3 class="timeline-title">Innovation & Awards</h3>
                    <p class="timeline-description">
                        Won "Best E-commerce Startup" award. Launched AI-powered 
                        recommendations. Expanded to 50+ cities nationwide.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="cta-content">
            <h2 class="cta-title">Join Our Growing Community</h2>
            <p class="cta-description">
                Whether you're looking to sell unused items, find great deals, 
                or build a small business, PeerCart provides the tools and 
                community you need to succeed.
            </p>
            <div class="cta-buttons">
                <a href="<?= BASE_URL ?>/pages/auth.php" class="btn-primary-outline">
                    <i class="fas fa-user-plus"></i> Sign Up Free
                </a>
                <a href="<?= BASE_URL ?>/pages/contact.php" class="btn-primary-outline">
                    <i class="fas fa-envelope"></i> Contact Us
                </a>
                <a href="<?= BASE_URL ?>/pages/sell.php" class="btn-primary-outline">
                    <i class="fas fa-plus-circle"></i> Start Selling
                </a>
            </div>
        </div>
    </section>
</div>

<!-- JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Animate stats counter
    const counters = document.querySelectorAll('.stat-number');
    const speed = 200;
    
    counters.forEach(counter => {
        const animate = () => {
            const target = +counter.getAttribute('data-count');
            const count = +counter.innerText;
            const increment = target / speed;
            
            if (count < target) {
                counter.innerText = Math.ceil(count + increment);
                setTimeout(animate, 1);
            } else {
                counter.innerText = target.toLocaleString();
            }
        };
        
        // Start animation when element is in viewport
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    animate();
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });
        
        observer.observe(counter);
    });
    
    // Create floating particles
    const heroParticles = document.getElementById('heroParticles');
    const particleCount = 30;
    
    for (let i = 0; i < particleCount; i++) {
        const particle = document.createElement('div');
        particle.classList.add('particle');
        
        // Random properties
        const size = Math.random() * 10 + 5;
        const x = Math.random() * 100;
        const y = Math.random() * 100;
        const duration = Math.random() * 20 + 10;
        const delay = Math.random() * 5;
        const opacity = Math.random() * 0.3 + 0.1;
        
        particle.style.width = `${size}px`;
        particle.style.height = `${size}px`;
        particle.style.left = `${x}%`;
        particle.style.top = `${y}%`;
        particle.style.animationDuration = `${duration}s`;
        particle.style.animationDelay = `${delay}s`;
        particle.style.opacity = opacity;
        
        heroParticles.appendChild(particle);
    }
    
    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href !== '#' && href.startsWith('#')) {
                e.preventDefault();
                const target = document.querySelector(href);
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            }
        });
    });
    
    // Add hover effects to team cards
    const teamCards = document.querySelectorAll('.team-card');
    teamCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-15px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
    
    // Timeline animation
    const timelineItems = document.querySelectorAll('.timeline-item');
    const timelineObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
                timelineObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.3 });
    
    timelineItems.forEach((item, index) => {
        item.style.opacity = '0';
        item.style.transform = 'translateY(30px)';
        item.style.transition = `opacity 0.5s ease ${index * 0.2}s, transform 0.5s ease ${index * 0.2}s`;
        timelineObserver.observe(item);
    });
});
</script>

<?php includePartial('footer'); ?>