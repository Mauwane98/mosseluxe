// Interactive Features for Mossé Luxe

document.addEventListener('DOMContentLoaded', function() {
    // initializeInteractiveFeatures();
});

function initializeInteractiveFeatures() {
    setupCustomCursor();
    setupScrollAnimations();
    setupHoverEffects();
    setupCountdownTimers();
    setupBrandStorytelling();
}

// Custom Cursor Implementation
function setupCustomCursor() {
    // Create custom cursor elements
    const cursor = document.createElement('div');
    cursor.className = 'custom-cursor';
    cursor.innerHTML = '<div class="cursor-dot"></div>';

    const cursorFollower = document.createElement('div');
    cursorFollower.className = 'cursor-follower';

    document.body.appendChild(cursor);
    document.body.appendChild(cursorFollower);

    let mouseX = 0, mouseY = 0;
    let followerX = 0, followerY = 0;

    // Track mouse movement
    document.addEventListener('mousemove', function(e) {
        mouseX = e.clientX;
        mouseY = e.clientY;

        cursor.style.left = mouseX + 'px';
        cursor.style.top = mouseY + 'px';
    });

    // Smooth follower animation
    function animateFollower() {
        followerX += (mouseX - followerX) * 0.1;
        followerY += (mouseY - followerY) * 0.1;

        cursorFollower.style.left = followerX + 'px';
        cursorFollower.style.top = followerY + 'px';

        requestAnimationFrame(animateFollower);
    }
    animateFollower();

    // Add hover effects for interactive elements
    const interactiveElements = document.querySelectorAll('a, button, [role="button"], [data-hover="interactive"]');
    interactiveElements.forEach(element => {
        element.addEventListener('mouseenter', function() {
            cursor.classList.add('cursor-hover');
            cursorFollower.classList.add('cursor-hover');
        });

        element.addEventListener('mouseleave', function() {
            cursor.classList.remove('cursor-hover');
            cursorFollower.classList.remove('cursor-hover');
        });
    });

    // Hide on mobile/touch devices
    if ('ontouchstart' in window) {
        cursor.style.display = 'none';
        cursorFollower.style.display = 'none';
    }
}

// Scroll-triggered animations
function setupScrollAnimations() {
    const animatedElements = document.querySelectorAll('[data-scroll-animation]');
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const element = entry.target;
                const animationType = element.dataset.scrollAnimation;
                const delay = element.dataset.animationDelay || 0;

                setTimeout(() => {
                    element.classList.add('animate-in-view');
                    element.style.animationDelay = '0ms';
                }, delay);

                observer.unobserve(element); // Only animate once
            }
        });
    }, observerOptions);

    animatedElements.forEach(element => {
        observer.observe(element);
    });
}

// Enhanced hover effects
function setupHoverEffects() {
    // Image overlay effects
    const images = document.querySelectorAll('.interactive-image');
    images.forEach(img => {
        const overlay = document.createElement('div');
        overlay.className = 'image-overlay';
        overlay.innerHTML = '<div class="overlay-content"><span class="overlay-text">View Details</span></div>';
        img.parentNode.appendChild(overlay);

        img.parentNode.addEventListener('mouseenter', function() {
            overlay.classList.add('active');
        });

        img.parentNode.addEventListener('mouseleave', function() {
            overlay.classList.remove('active');
        });
    });

    // Button ripple effects
    const buttons = document.querySelectorAll('.btn-ripple');
    buttons.forEach(button => {
        button.addEventListener('click', function(e) {
            const ripple = document.createElement('span');
            ripple.className = 'ripple-effect';

            const rect = button.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;

            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';

            button.appendChild(ripple);

            setTimeout(() => {
                ripple.remove();
            }, 600);
        });
    });

    // Product card tilt effects
    const tiltCards = document.querySelectorAll('.tilt-card');
    tiltCards.forEach(card => {
        card.addEventListener('mousemove', function(e) {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;

            const centerX = rect.width / 2;
            const centerY = rect.height / 2;

            const rotateX = (y - centerY) / 10;
            const rotateY = (centerX - x) / 10;

            card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale(1.05)`;
        });

        card.addEventListener('mouseleave', function() {
            card.style.transform = 'perspective(1000px) rotateX(0deg) rotateY(0deg) scale(1)';
        });
    });
}

// Countdown timer for limited editions
function setupCountdownTimers() {
    const countdownElements = document.querySelectorAll('[data-countdown]');

    countdownElements.forEach(element => {
        const endDate = new Date(element.dataset.countdown).getTime();
        const timerElement = element.querySelector('.countdown-timer') || element;
        const labelElement = element.querySelector('.countdown-label');

        function updateCountdown() {
            const now = new Date().getTime();
            const distance = endDate - now;

            if (distance < 0) {
                timerElement.innerHTML = '<span class="countdown-ended">DROPS NOW!</span>';
                labelElement.textContent = 'Limited Edition Available';
                return;
            }

            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);

            timerElement.innerHTML = `
                <span class="time-unit"><span class="time-value">${days.toString().padStart(2, '0')}</span><span class="time-label">Days</span></span>
                <span class="time-separator">:</span>
                <span class="time-unit"><span class="time-value">${hours.toString().padStart(2, '0')}</span><span class="time-label">Hrs</span></span>
                <span class="time-separator">:</span>
                <span class="time-unit"><span class="time-value">${minutes.toString().padStart(2, '0')}</span><span class="time-label">Min</span></span>
                <span class="time-separator">:</span>
                <span class="time-unit"><span class="time-value">${seconds.toString().padStart(2, '0')}</span><span class="time-label">Sec</span></span>
            `;

            // Add urgency animation when less than 1 hour
            if (distance < 3600000) {
                timerElement.classList.add('urgent-countdown');
            }
        }

        updateCountdown();
        setInterval(updateCountdown, 1000);
    });
}

// Brand storytelling sections
function setupBrandStorytelling() {
    const storySections = document.querySelectorAll('.story-section');

    storySections.forEach(section => {
        const progressBar = section.querySelector('.story-progress');
        const storyContent = section.querySelectorAll('.story-slide');

        if (!progressBar || storyContent.length === 0) return;

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    animateStorySection(entry.target);
                }
            });
        }, { threshold: 0.3 });

        observer.observe(section);

        function animateStorySection(section) {
            storyContent.forEach((slide, index) => {
                const delay = index * 200;
                setTimeout(() => {
                    slide.classList.add('story-visible');
                }, delay);
            });

            // Animate progress bar
            let progress = 0;
            const progressInterval = setInterval(() => {
                progress += 2;
                progressBar.style.width = progress + '%';

                if (progress >= 100) {
                    clearInterval(progressInterval);
                }
            }, 50);
        }
    });

    // Parallax effects for storytelling
    window.addEventListener('scroll', function() {
        const parallaxElements = document.querySelectorAll('.parallax-element');

        parallaxElements.forEach(element => {
            const scrolled = window.pageYOffset;
            const rate = element.dataset.parallaxRate || 0.5;
            const yPos = -(scrolled * rate);

            element.style.transform = `translateY(${yPos}px)`;
        });
    });
}

// Typing animation for text reveals
function createTypingAnimation(element, text, speed = 100) {
    let i = 0;
    element.textContent = '';

    function typeWriter() {
        if (i < text.length) {
            element.textContent += text.charAt(i);
            i++;
            setTimeout(typeWriter, speed);
        }
    }

    typeWriter();
}

// Initialize typing animations
document.addEventListener('DOMContentLoaded', function() {
    const typingElements = document.querySelectorAll('[data-typing-text]');
    typingElements.forEach(element => {
        const text = element.dataset.typingText;
        const speed = parseInt(element.dataset.typingSpeed) || 100;

        createTypingAnimation(element, text, speed);
    });
});

// Magnetic button effect
function setupMagneticButtons() {
    const magneticButtons = document.querySelectorAll('.magnetic-btn');

    magneticButtons.forEach(button => {
        button.addEventListener('mousemove', function(e) {
            const rect = button.getBoundingClientRect();
            const x = e.clientX - rect.left - rect.width / 2;
            const y = e.clientY - rect.top - rect.height / 2;

            button.style.transform = `translate(${x * 0.3}px, ${y * 0.3}px)`;
        });

        button.addEventListener('mouseleave', function() {
            button.style.transform = 'translate(0px, 0px)';
        });
    });
}

// Particle effects for premium brands
function createParticleEffect(container) {
    for (let i = 0; i < 20; i++) {
        const particle = document.createElement('div');
        particle.className = 'floating-particle';
        particle.style.left = Math.random() * 100 + '%';
        particle.style.animationDelay = Math.random() * 3 + 's';
        particle.style.animationDuration = (Math.random() * 3 + 2) + 's';
        container.appendChild(particle);
    }
}

// Initialize particle effects
document.addEventListener('DOMContentLoaded', function() {
    const particleContainers = document.querySelectorAll('.particle-container');
    particleContainers.forEach(container => {
        createParticleEffect(container);
    });

    setupMagneticButtons();
});

// Add smooth scroll behavior for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));

        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});
