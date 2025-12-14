// C:\laragon\www\PeerCart2\assets\js\script.js

/**
 * PeerCart Main JavaScript File
 * Modular approach with clear separation of concerns
 */

// ==================== UTILITY FUNCTIONS ====================
(() => {
if (typeof window.Utils === 'undefined') {
    window.Utils = {
        /**
         * Show a temporary alert message
         * @param {string} type - alert type (success, danger, etc.)
         * @param {string} message - message to display
         */
        showAlert: (type, message) => {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
            alertDiv.role = 'alert';
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
            
            const container = document.querySelector('.alert-container') || document.body;
            container.prepend(alertDiv);
            
            setTimeout(() => {
                alertDiv.remove();
            }, 5000);
        },

        /**
         * Debounce function to limit how often a function executes
         * @param {Function} func - function to debounce
         * @param {number} delay - delay in milliseconds
         */
        debounce: (func, delay) => {
            let timeoutId;
            return function(...args) {
                clearTimeout(timeoutId);
                timeoutId = setTimeout(() => func.apply(this, args), delay);
            };
        }
    };
}

// ==================== MODULES ====================
const MobileMenu = {
    init: () => {
        const menuBtn = document.querySelector('.mobile-menu-btn');
        const mobileMenu = document.querySelector('.mobile-menu');

        if (menuBtn && mobileMenu) {
            menuBtn.addEventListener('click', () => {
                mobileMenu.classList.toggle('active');
                menuBtn.setAttribute('aria-expanded', mobileMenu.classList.contains('active'));
            });
        } else {
            console.warn('Mobile menu elements not found');
        }
    }
};

const DropdownHandler = {
    init: () => {
        const dropdowns = document.querySelectorAll('.dropdown');
        
        dropdowns.forEach(dropdown => {
            const toggle = dropdown.querySelector('a');
            
            // Desktop behavior (hover)
            dropdown.addEventListener('mouseenter', () => {
                if (window.innerWidth > 768) {
                    dropdown.classList.add('active');
                }
            });
            
            dropdown.addEventListener('mouseleave', () => {
                if (window.innerWidth > 768) {
                    dropdown.classList.remove('active');
                }
            });
            
            // Mobile behavior (click)
            toggle.addEventListener('click', (e) => {
                if (window.innerWidth <= 768) {
                    e.preventDefault();
                    e.stopPropagation();
                    dropdown.classList.toggle('active');
                }
            });
        });
        
        // Close dropdowns when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.dropdown')) {
                document.querySelectorAll('.dropdown').forEach(dropdown => {
                    dropdown.classList.remove('active');
                });
            }
        });
        
        // Close when resizing from mobile to desktop
        window.addEventListener('resize', () => {
            if (window.innerWidth > 768) {
                document.querySelectorAll('.dropdown').forEach(dropdown => {
                    dropdown.classList.remove('active');
                });
            }
        });
    }
};

const TestimonialSlider = {
    currentIndex: 0,
    intervalId: null,
    intervalTime: 5000,

    init: () => {
        const testimonials = document.querySelectorAll('.testimonial');
        if (testimonials.length === 0) return;

        TestimonialSlider.showCurrent();
        TestimonialSlider.startAutoRotation();

        // Pause on hover
        const container = document.querySelector('.testimonial-slider');
        if (container) {
            container.addEventListener('mouseenter', TestimonialSlider.pause);
            container.addEventListener('mouseleave', TestimonialSlider.resume);
        }
    },

    showCurrent: () => {
        const testimonials = document.querySelectorAll('.testimonial');
        testimonials.forEach((testimonial, i) => {
            testimonial.style.display = i === TestimonialSlider.currentIndex ? 'block' : 'none';
        });
    },

    next: () => {
        const testimonials = document.querySelectorAll('.testimonial');
        TestimonialSlider.currentIndex = (TestimonialSlider.currentIndex + 1) % testimonials.length;
        TestimonialSlider.showCurrent();
    },

    startAutoRotation: () => {
        if (TestimonialSlider.intervalId) {
            clearInterval(TestimonialSlider.intervalId);
        }
        TestimonialSlider.intervalId = setInterval(TestimonialSlider.next, TestimonialSlider.intervalTime);
    },

    pause: () => {
        clearInterval(TestimonialSlider.intervalId);
    },

    resume: () => {
        TestimonialSlider.startAutoRotation();
    }
};

const PromotionsCarousel = {
    // Properties
    scrollPos: 0,
    scrollSpeed: 2,
    isPaused: false,
    animationId: null,
    container: null,
    scroller: null,
    cards: [],

    // Initialize with proper null checks
    init: function() {
    this.container = document.querySelector('.promotions-container');
    this.scroller = document.querySelector('.promotions-scroller');

    if (!this.container || !this.scroller) {
        // silently skip if carousel elements are missing
        return false;
    }

    this.cards = document.querySelectorAll('.promotion-card');
    if (this.cards.length === 0) return false;

        // Clone cards for seamless looping
        const cloneCount = Math.min(4, this.cards.length);
        for (let i = 0; i < cloneCount; i++) {
            const clone = this.cards[i].cloneNode(true);
            this.scroller.appendChild(clone);
        }

        // Event listeners
        this.container.addEventListener('mouseenter', () => this.pause());
        this.container.addEventListener('mouseleave', () => this.resume());

        // Start animation
        return this.startAnimation();
    },

    // Animation loop with safety checks
    animate: function() {
        if (!this.scroller) {
            console.error('Scroller element missing');
            return this.stopAnimation();
        }

        if (!this.isPaused) {
            this.scrollPos += this.scrollSpeed;
            
            // Reset position when halfway
            if (this.scrollPos >= this.scroller.scrollWidth / 2) {
                this.scrollPos = 0;
            }
            
            this.scroller.style.transform = `translateX(-${this.scrollPos}px)`;
        }
        
        this.animationId = requestAnimationFrame(this.animate.bind(this));
    },

    // Start/stop with validation
    startAnimation: function() {
        if (!this.scroller) return false;
        if (this.animationId) this.stopAnimation();
        this.animate();
        return true;
    },

    stopAnimation: function() {
        if (this.animationId) {
            cancelAnimationFrame(this.animationId);
            this.animationId = null;
        }
    },

    // Pause/resume controls
    pause: function() {
        this.isPaused = true;
    },

    resume: function() {
        this.isPaused = false;
    }
};

// ==================== INITIALIZATION ====================
document.addEventListener('DOMContentLoaded', () => {
    MobileMenu.init();
    DropdownHandler.init(); 
    // FormHandler.init(); // Removed - not defined
    TestimonialSlider.init();
    PromotionsCarousel.init();
    
    // Hero slideshow
    const slides = document.querySelectorAll(".hero-slideshow .slide");
    if (slides.length > 0) {
        let current = 0;

        function showNextSlide() {
            // Hide current slide
            slides[current].classList.remove("active");
            
            // Move to next slide
            current = (current + 1) % slides.length;
            
            // Show next slide
            slides[current].classList.add("active");
        }

        // Start the slideshow
        setInterval(showNextSlide, 2500); // change every 2.5 seconds
    }
});

// Clean up when page is hidden
document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
        if (typeof PromotionsCarousel !== 'undefined' && PromotionsCarousel.stopAnimation) {
            PromotionsCarousel.stopAnimation();
        }
        if (typeof TestimonialSlider !== 'undefined' && TestimonialSlider.pause) {
            TestimonialSlider.pause();
        }
    } else {
        if (typeof PromotionsCarousel !== 'undefined' && PromotionsCarousel.startAnimation) {
            PromotionsCarousel.startAnimation();
        }
        if (typeof TestimonialSlider !== 'undefined' && TestimonialSlider.resume) {
            TestimonialSlider.resume();
        }
    }
});

// Dropdown section toggle
document.querySelectorAll('.dropdown-section .section-title').forEach(title => {
    title.addEventListener('click', () => {
        const section = title.parentElement;
        section.classList.toggle('active');
    });
});
})();