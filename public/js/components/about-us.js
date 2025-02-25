document.addEventListener('DOMContentLoaded', function() {
    // Initialize GSAP ScrollTrigger
    gsap.registerPlugin(ScrollTrigger);

    // Function to check if an element is in viewport
    function isInViewport(element) {
        const rect = element.getBoundingClientRect();
        return (
            rect.top >= 0 &&
            rect.left >= 0 &&
            rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
            rect.right <= (window.innerWidth || document.documentElement.clientWidth)
        );
    }

    // Function to animate counter with GSAP
    function animateCounter(element) {
        const target = parseInt(element.textContent);
        gsap.fromTo(element, 
            { textContent: 0 },
            {
                duration: 2,
                textContent: target,
                roundProps: "textContent",
                ease: "power1.inOut",
                onComplete: () => {
                    element.textContent = target + '+';
                    // Add particle effect on completion
                    createParticles(element);
                }
            }
        );
    }

    // Particle effect function
    function createParticles(element) {
        const rect = element.getBoundingClientRect();
        const particleCount = 20;

        for (let i = 0; i < particleCount; i++) {
            const particle = document.createElement('div');
            particle.className = 'absolute w-1 h-1 bg-primary rounded-full';
            particle.style.left = rect.left + rect.width / 2 + 'px';
            particle.style.top = rect.top + rect.height / 2 + 'px';
            document.body.appendChild(particle);

            const angle = (i / particleCount) * 360;
            const velocity = 2;
            const rad = angle * Math.PI / 180;
            const duration = 0.5 + Math.random() * 0.5;

            gsap.to(particle, {
                x: Math.cos(rad) * 50,
                y: Math.sin(rad) * 50,
                opacity: 0,
                duration: duration,
                ease: "power1.out",
                onComplete: () => particle.remove()
            });
        }
    }

    // Initialize counters with ScrollTrigger
    const counters = document.querySelectorAll('.counter-value');
    let animated = new Set();

    counters.forEach(counter => {
        ScrollTrigger.create({
            trigger: counter,
            start: "top 80%",
            onEnter: () => {
                if (!animated.has(counter)) {
                    animateCounter(counter);
                    animated.add(counter);
                }
            }
        });
    });

    // Add parallax effect to hero images
    const heroImages = document.querySelectorAll('.lg\\:w-1/2.relative img');
    heroImages.forEach(img => {
        gsap.to(img, {
            y: 50,
            ease: "none",
            scrollTrigger: {
                trigger: img,
                start: "top bottom",
                end: "bottom top",
                scrub: 1
            }
        });
    });

    // Add hover effect for service cards
    const serviceCards = document.querySelectorAll('.card');
    serviceCards.forEach(card => {
        card.addEventListener('mouseenter', (e) => {
            const cardBody = card.querySelector('.card-body');
            gsap.to(cardBody, {
                y: -10,
                duration: 0.3,
                ease: "power2.out"
            });
        });

        card.addEventListener('mouseleave', (e) => {
            const cardBody = card.querySelector('.card-body');
            gsap.to(cardBody, {
                y: 0,
                duration: 0.3,
                ease: "power2.out"
            });
        });
    });

    // Add smooth scroll for navigation
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                gsap.to(window, {
                    duration: 1,
                    scrollTo: {
                        y: target,
                        offsetY: 50
                    },
                    ease: "power2.inOut"
                });
            }
        });
    });

    // Initialize VanillaTilt for 3D card effect
    VanillaTilt.init(document.querySelectorAll("[data-tilt]"), {
        max: 5,
        speed: 400,
        glare: true,
        "max-glare": 0.2,
    });

    // Add scroll-triggered animations for sections
    gsap.utils.toArray('.card').forEach((card, i) => {
        gsap.from(card, {
            scrollTrigger: {
                trigger: card,
                start: "top 80%",
                toggleActions: "play none none reverse"
            },
            opacity: 0,
            y: 50,
            duration: 0.6,
            delay: i * 0.1
        });
    });

    // Animate icons on scroll
    gsap.utils.toArray('.w-16.h-16').forEach(icon => {
        gsap.from(icon, {
            scrollTrigger: {
                trigger: icon,
                start: "top 80%",
                toggleActions: "play none none reverse"
            },
            rotate: 360,
            opacity: 0,
            duration: 1,
            ease: "back.out(1.7)"
        });
    });
}); 