// Image loading and optimization
document.addEventListener('DOMContentLoaded', function() {
    // Initialize lazy loading
    initLazyLoading();
    
    // Initialize image error handling
    initImageErrorHandling();
});

// Lazy loading implementation
function initLazyLoading() {
    const lazyImages = document.querySelectorAll('img.lazy');
    
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    loadImage(img);
                    imageObserver.unobserve(img);
                }
            });
        });

        lazyImages.forEach(function(img) {
            imageObserver.observe(img);
        });
    } else {
        // Fallback for browsers that don't support IntersectionObserver
        lazyImages.forEach(function(img) {
            loadImage(img);
        });
    }
}

// Load image with fade-in effect
function loadImage(img) {
    const src = img.dataset.src;
    if (!src) return;

    // Create a new image to preload
    const preloader = new Image();
    
    preloader.onload = function() {
        img.src = src;
        img.classList.add('fade-in');
        img.classList.remove('lazy');
        delete img.dataset.src;
    };
    
    preloader.src = src;
}

// Handle image loading errors
function initImageErrorHandling() {
    document.addEventListener('error', function(e) {
        const target = e.target;
        if (target.tagName.toLowerCase() === 'img') {
            handleImageError(target);
        }
    }, true);
}

// Handle image errors with retry
function handleImageError(img) {
    const maxRetries = 3;
    const retryCount = parseInt(img.dataset.retryCount || 0);
    
    if (retryCount < maxRetries) {
        img.dataset.retryCount = retryCount + 1;
        setTimeout(() => {
            img.src = img.dataset.src || img.src;
        }, 1000 * (retryCount + 1)); // Exponential backoff
    } else {
        // If all retries fail, show default image
        img.src = '/car-project/public/assets/images/default-car.jpg';
        img.classList.add('error');
    }
}

// Add dynamic image loading for quick view
function loadQuickViewImages(carId) {
    const modal = document.getElementById('quickViewModal');
    const container = modal.querySelector('.image-container');
    if (!container) return;

    const images = container.querySelectorAll('img.lazy');
    images.forEach(img => loadImage(img));
}

// Export functions for use in other files
window.imageLoader = {
    initLazyLoading,
    loadQuickViewImages,
    handleImageError
}; 