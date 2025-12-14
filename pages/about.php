<?php
// pages/about-us.php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/functions.php';

// Page title
$title = 'About Us - PeerCart';
includePartial('header', ['title' => $title, 'currentPage' => 'about']);
?>

<!-- About Us Page Specific CSS -->
<style>
.about-page {
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    min-height: 100vh;
}

/* Hero Section */
.about-hero {
    background: linear-gradient(rgba(26, 26, 46, 0.9), rgba(22, 33, 62, 0.9)), 
                url('<?= asset('images/about-hero.jpg') ?>');
    background-size: cover;
    background-position: center;
    color: white;
    padding: 100px 20px;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.hero-particles {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
}

.particle {
    position: absolute;
    background: rgba(67, 97, 238, 0.3);
    border-radius: 50%;
    animation: float 15s infinite linear;
}

@keyframes float {
    0%, 100% {
        transform: translateY(0) rotate(0deg);
    }
    50% {
        transform: translateY(-20px) rotate(180deg);
    }
}

.about-hero-content {
    max-width: 800px;
    margin: 0 auto;
    position: relative;
    z-index: 2;
}

.hero-badge {
    background: rgba(67, 97, 238, 0.2);
    border: 2px solid rgba(67, 97, 238, 0.5);
    color: #4361ee;
    padding: 8px 20px;
    border-radius: 30px;
    display: inline-block;
    margin-bottom: 20px;
    font-weight: 600;
    backdrop-filter: blur(10px);
}

.about-hero h1 {
    font-size: 3.5rem;
    margin-bottom: 20px;
    background: linear-gradient(90deg, #4361ee, #3a0ca3);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.hero-subtitle {
    font-size: 1.2rem;
    color: #b0b7c3;
    margin-bottom: 40px;
    line-height: 1.6;
}

/* Stats Section */
.stats-section {
    background: white;
    padding: 80px 20px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 30px;
    max-width: 1200px;
    margin: 0 auto;
}

.stat-card {
    text-align: center;
    padding: 40px 20px;
    border-radius: 15px;
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    transition: all 0.3s ease;
    border: 1px solid rgba(67, 97, 238, 0.1);
    position: relative;
    overflow: hidden;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 4px;
    background: linear-gradient(90deg, #4361ee, #3a0ca3);
}

.stat-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 20px 40px rgba(67, 97, 238, 0.15);
}

.stat-icon {
    font-size: 3rem;
    color: #4361ee;
    margin-bottom: 20px;
}

.stat-number {
    font-size: 3rem;
    font-weight: 700;
    color: #1a1a2e;
    margin-bottom: 10px;
    display: block;
}

.stat-label {
    color: #64748b;
    font-size: 1.1rem;
    font-weight: 500;
}

/* Mission Section */
.mission-section {
    padding: 100px 20px;
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
    color: white;
    position: relative;
}

.mission-container {
    max-width: 1200px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 60px;
    align-items: center;
}

.mission-content h2 {
    font-size: 2.5rem;
    margin-bottom: 30px;
    color: white;
}

.mission-content p {
    color: #b0b7c3;
    font-size: 1.1rem;
    line-height: 1.8;
    margin-bottom: 30px;
}

.mission-values {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.value-item {
    display: flex;
    align-items: flex-start;
    gap: 15px;
    padding: 20px;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 10px;
    border-left: 4px solid #4361ee;
    transition: all 0.3s ease;
}

.value-item:hover {
    background: rgba(255, 255, 255, 0.1);
    transform: translateX(10px);
}

.value-icon {
    color: #4361ee;
    font-size: 1.5rem;
}

.value-content h4 {
    margin: 0 0 5px 0;
    color: white;
}

.value-content p {
    margin: 0;
    font-size: 0.95rem;
}

/* Team Section */
.team-section {
    padding: 100px 20px;
    background: white;
}

.section-header {
    text-align: center;
    max-width: 800px;
    margin: 0 auto 60px;
}

.section-title {
    font-size: 2.5rem;
    color: #1a1a2e;
    margin-bottom: 20px;
}

.section-subtitle {
    color: #64748b;
    font-size: 1.1rem;
    line-height: 1.6;
}

.team-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 40px;
    max-width: 1200px;
    margin: 0 auto;
}

.team-card {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    position: relative;
}

.team-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 20px 40px rgba(67, 97, 238, 0.15);
}

.team-image {
    height: 300px;
    overflow: hidden;
}

.team-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.team-card:hover .team-image img {
    transform: scale(1.1);
}

.team-info {
    padding: 30px;
    text-align: center;
}

.team-name {
    font-size: 1.5rem;
    color: #1a1a2e;
    margin-bottom: 5px;
}

.team-role {
    color: #4361ee;
    font-weight: 600;
    margin-bottom: 15px;
    display: block;
}

.team-bio {
    color: #64748b;
    font-size: 0.95rem;
    line-height: 1.6;
    margin-bottom: 20px;
}

.team-social {
    display: flex;
    justify-content: center;
    gap: 15px;
}

.team-social a {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    background: #f8fafc;
    border-radius: 50%;
    color: #64748b;
    text-decoration: none;
    transition: all 0.3s ease;
}

.team-social a:hover {
    background: #4361ee;
    color: white;
    transform: translateY(-3px);
}

/* Timeline Section */
.timeline-section {
    padding: 100px 20px;
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
}

.timeline {
    max-width: 800px;
    margin: 0 auto;
    position: relative;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 50%;
    top: 0;
    bottom: 0;
    width: 4px;
    background: #4361ee;
    transform: translateX(-50%);
}

.timeline-item {
    margin-bottom: 50px;
    position: relative;
    width: 50%;
    padding-right: 50px;
}

.timeline-item:nth-child(even) {
    margin-left: 50%;
    padding-left: 50px;
    padding-right: 0;
}

.timeline-dot {
    position: absolute;
    right: -12px;
    top: 0;
    width: 24px;
    height: 24px;
    background: #4361ee;
    border-radius: 50%;
    border: 4px solid white;
    box-shadow: 0 0 0 4px rgba(67, 97, 238, 0.2);
}

.timeline-item:nth-child(even) .timeline-dot {
    left: -12px;
    right: auto;
}

.timeline-content {
    background: white;
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
}

.timeline-year {
    font-size: 1.5rem;
    color: #4361ee;
    font-weight: 700;
    margin-bottom: 10px;
}

.timeline-title {
    font-size: 1.3rem;
    color: #1a1a2e;
    margin-bottom: 15px;
}

.timeline-description {
    color: #64748b;
    line-height: 1.6;
}

/* CTA Section */
.cta-section {
    padding: 100px 20px;
    background: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%);
    color: white;
    text-align: center;
}

.cta-content {
    max-width: 800px;
    margin: 0 auto;
}

.cta-title {
    font-size: 2.5rem;
    margin-bottom: 20px;
}

.cta-description {
    font-size: 1.1rem;
    margin-bottom: 40px;
    color: rgba(255, 255, 255, 0.9);
    line-height: 1.6;
}

.cta-buttons {
    display: flex;
    gap: 20px;
    justify-content: center;
    flex-wrap: wrap;
}

.btn-primary-outline {
    background: transparent;
    color: white;
    border: 2px solid white;
    padding: 15px 40px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 10px;
}

.btn-primary-outline:hover {
    background: white;
    color: #4361ee;
    transform: translateY(-3px);
}

/* Responsive Design */
@media (max-width: 992px) {
    .about-hero h1 {
        font-size: 2.5rem;
    }
    
    .mission-container {
        grid-template-columns: 1fr;
        gap: 40px;
    }
    
    .timeline::before {
        left: 30px;
    }
    
    .timeline-item,
    .timeline-item:nth-child(even) {
        width: 100%;
        margin-left: 0;
        padding-left: 70px;
        padding-right: 0;
    }
    
    .timeline-dot {
        left: 18px !important;
        right: auto !important;
    }
}

@media (max-width: 768px) {
    .about-hero {
        padding: 60px 20px;
    }
    
    .about-hero h1 {
        font-size: 2rem;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .section-title {
        font-size: 2rem;
    }
    
    .team-grid {
        grid-template-columns: 1fr;
    }
    
    .cta-buttons {
        flex-direction: column;
        align-items: center;
    }
    
    .btn-primary-outline {
        width: 100%;
        justify-content: center;
    }
}

@media (max-width: 576px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .stat-number {
        font-size: 2.5rem;
    }
}
</style>

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
                <a href="<?= BASE_URL ?>/includes/auth.php" class="btn-primary-outline">
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
            this.style.transform = 'translateY(-10px)';
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