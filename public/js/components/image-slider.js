document.addEventListener('DOMContentLoaded', function() {
    // Image slider functionality
    const slider = document.querySelector('.carousel');
    if (!slider) return;

    // Get all slides and thumbnails
    const slides = slider.querySelectorAll('.carousel-item');
    const thumbnails = document.querySelectorAll('.thumbnail-item');
    const totalSlides = slides.length;

    // Initialize current slide index
    let currentSlideIndex = 0;

    // Function to update thumbnails
    function updateThumbnails(activeIndex) {
        thumbnails.forEach((thumb, index) => {
            if (index === activeIndex) {
                thumb.classList.add('ring-2', 'ring-primary');
            } else {
                thumb.classList.remove('ring-2', 'ring-primary');
            }
        });
    }

    // Function to show slide
    window.showSlide = function(index) {
        currentSlideIndex = index;
        // Transform slides
        slides.forEach((slide, i) => {
            const offset = (i - index) * 100;
            slide.style.transform = `translateX(${offset}%)`;
            slide.style.opacity = i === index ? '1' : '0';
            slide.style.zIndex = i === index ? '1' : '0';
        });

        // Update thumbnails
        updateThumbnails(index);
    }

    // Navigation function
    function navigateSlider(direction) {
        // Add transition class to all slides
        slides.forEach(slide => {
            slide.classList.add('transition-all');
        });

        if (direction === 'next') {
            currentSlideIndex = (currentSlideIndex + 1) % totalSlides;
        } else {
            currentSlideIndex = (currentSlideIndex - 1 + totalSlides) % totalSlides;
        }
        showSlide(currentSlideIndex);

        // Remove transition class after animation
        setTimeout(() => {
            slides.forEach(slide => {
                slide.classList.remove('transition-all');
            });
        }, 300);
    }

    // Make navigateSlider available globally
    window.navigateSlider = navigateSlider;

    // Show initial slide
    showSlide(currentSlideIndex);

    // Keyboard navigation
    document.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowLeft') {
            navigateSlider('prev');
        } else if (e.key === 'ArrowRight') {
            navigateSlider('next');
        }
    });

    // Touch support
    let touchStartX = 0;
    let touchEndX = 0;

    slider.addEventListener('touchstart', (e) => {
        touchStartX = e.changedTouches[0].screenX;
    });

    slider.addEventListener('touchend', (e) => {
        touchEndX = e.changedTouches[0].screenX;
        handleSwipe();
    });

    function handleSwipe() {
        const swipeThreshold = 50;
        const diff = touchStartX - touchEndX;

        if (Math.abs(diff) > swipeThreshold) {
            if (diff > 0) {
                navigateSlider('next');
            } else {
                navigateSlider('prev');
            }
        }
    }

    // Thumbnail navigation
    thumbnails.forEach((thumb, index) => {
        thumb.addEventListener('click', () => {
            showSlide(index);
        });
    });

    // Add CSS for initial positioning
    slides.forEach((slide, i) => {
        if (i !== 0) {
            slide.style.transform = 'translateX(100%)';
            slide.style.opacity = '0';
            slide.style.zIndex = '0';
        }
    });
}); 