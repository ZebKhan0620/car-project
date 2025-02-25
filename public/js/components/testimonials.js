document.addEventListener('DOMContentLoaded', function() {
    const testimonials = document.querySelector('#testimonials');
    if (!testimonials) return;

    const slides = testimonials.querySelectorAll('.testimonial-image');
    const textBox = testimonials.querySelector('.testimonial-text');
    const prevBtn = testimonials.querySelector('.testimonial-prev');
    const nextBtn = testimonials.querySelector('.testimonial-next');

    let currentIndex = 0;
    let slideInterval = null;
    let isAnimating = false;
    const SLIDE_INTERVAL_TIME = 5000;

    // Set initial state with fade in
    const initialData = JSON.parse(slides[0].dataset.testimonial);
    textBox.querySelector('h3').textContent = initialData.author;
    textBox.querySelector('p').textContent = initialData.role;
    textBox.querySelector('div').textContent = initialData.content;

    // Initial animations
    slides.forEach((slide, index) => {
        gsap.set(slide, {
            opacity: index === 0 ? 1 : 0,
            display: index === 0 ? 'block' : 'none',
            scale: index === 0 ? 1 : 0.95
        });
    });

    function animateText(elements, direction) {
        const yOffset = direction === 'in' ? 30 : -30;
        
        return gsap.timeline()
            .to(elements, {
                opacity: direction === 'in' ? 1 : 0,
                y: direction === 'in' ? 0 : yOffset,
                duration: 0.6,
                stagger: 0.1,
                ease: "power2.out"
            });
    }

    function animateSlide(currentSlide, nextSlide, direction) {
        const xOffset = direction === 'next' ? 20 : -20;
        
        return gsap.timeline()
            .to(currentSlide, {
                opacity: 0,
                scale: 0.95,
                x: -xOffset,
                duration: 0.5,
                ease: "power2.inOut"
            })
            .set(currentSlide, { display: 'none' })
            .set(nextSlide, { 
                display: 'block',
                opacity: 0,
                scale: 0.95,
                x: xOffset
            })
            .to(nextSlide, {
                opacity: 1,
                scale: 1,
                x: 0,
                duration: 0.5,
                ease: "power2.out"
            });
    }

    function changeSlide(direction) {
        if (isAnimating) return;
        isAnimating = true;

        stopAutoSlide(); // Stop current interval

        const textElements = [
            textBox.querySelector('h3'),
            textBox.querySelector('p'),
            textBox.querySelector('div')
        ];

        const nextIndex = direction === 'next' 
            ? (currentIndex + 1) % slides.length 
            : (currentIndex - 1 + slides.length) % slides.length;

        const timeline = gsap.timeline({
            onComplete: () => {
                isAnimating = false;
                currentIndex = nextIndex;
                if (!testimonials.matches(':hover')) { // Only restart if not hovering
                    startAutoSlide();
                }
            }
        });

        // Fade out current text
        timeline.add(animateText(textElements, 'out'))
            // Animate slides
            .add(animateSlide(slides[currentIndex], slides[nextIndex], direction), "-=0.3")
            // Update text content
            .call(() => {
                const data = JSON.parse(slides[nextIndex].dataset.testimonial);
                textBox.querySelector('h3').textContent = data.author;
                textBox.querySelector('p').textContent = data.role;
                textBox.querySelector('div').textContent = data.content;
            })
            // Fade in new text
            .add(animateText(textElements, 'in'), "-=0.1");
    }

    function startAutoSlide() {
        stopAutoSlide(); // Clear any existing interval first
        
        slideInterval = setInterval(() => {
            if (!isAnimating && !testimonials.matches(':hover')) {
                changeSlide('next');
            }
        }, SLIDE_INTERVAL_TIME);
    }

    function stopAutoSlide() {
        if (slideInterval) {
            clearInterval(slideInterval);
            slideInterval = null;
        }
    }

    // Smooth hover effects for buttons
    [prevBtn, nextBtn].forEach(btn => {
        btn.addEventListener('mouseenter', () => {
            gsap.to(btn, {
                scale: 1.1,
                duration: 0.3,
                ease: "power2.out"
            });
        });

        btn.addEventListener('mouseleave', () => {
            gsap.to(btn, {
                scale: 1,
                duration: 0.3,
                ease: "power2.out"
            });
        });
    });

    // Event listeners
    prevBtn.onclick = () => {
        if (!isAnimating) {
            changeSlide('prev');
        }
    };

    nextBtn.onclick = () => {
        if (!isAnimating) {
            changeSlide('next');
        }
    };

    // Pause on hover
    testimonials.addEventListener('mouseenter', stopAutoSlide);
    testimonials.addEventListener('mouseleave', () => {
        if (!isAnimating) {
            startAutoSlide();
        }
    });

    // Start auto-sliding
    startAutoSlide();

    // Cleanup on page visibility change
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            stopAutoSlide();
        } else if (!testimonials.matches(':hover')) {
            startAutoSlide();
        }
    });
}); 