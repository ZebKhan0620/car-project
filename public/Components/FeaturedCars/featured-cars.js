// Featured Cars Module
window.featuredCars = (function() {
    // Private state with defaults
    const state = {
        viewMode: localStorage.getItem('carViewMode') || 'grid',
        sortBy: localStorage.getItem('carSortBy') || 'latest',
        selectedForCompare: new Set(),
        maxCompareItems: 3,
        isLoading: false
    };

    // Initialize the module with error handling
    function initializeFeaturedCars() {
        try {
            // Set initial view mode safely
            setViewMode(state.viewMode, false);
            
            // Set initial sort option with null check
            const sortOption = document.querySelector(`select[name="sort"] option[value="${state.sortBy}"]`);
            if (sortOption) sortOption.selected = true;
            
            initializeEventListeners();
            initializeHoverEffects();
        } catch (error) {
            console.error('Failed to initialize featured cars:', error);
            handleInitError();
        }
    }

    // Safer event listener initialization
    function initializeEventListeners() {
        // View toggle with error boundary
        document.querySelectorAll('[data-view]').forEach(button => {
            button.addEventListener('click', (e) => {
                try {
                    setViewMode(e.target.dataset.view);
                } catch (error) {
                    console.error('View mode error:', error);
                }
            });
        });

        // Sort dropdown with validation
        const sortSelect = document.querySelector('select[name="sort"]');
        if (sortSelect) {
            sortSelect.addEventListener('change', (e) => {
                if (state.isLoading) return;
                try {
                    state.sortBy = e.target.value;
                    localStorage.setItem('carSortBy', state.sortBy);
                    applySorting();
                } catch (error) {
                    console.error('Sorting error:', error);
                }
            });
        }

        // Compare functionality with limits
        document.querySelectorAll('.compare-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', (e) => {
                try {
                    handleCompareSelection(e);
                } catch (error) {
                    console.error('Compare selection error:', error);
                    e.target.checked = false;
                }
            });
        });
    }

    // Set view mode (grid/list)
    function setViewMode(mode, savePreference = true) {
        const container = document.getElementById('carsGrid');
        const buttons = document.querySelectorAll('[data-view]');
        
        if (!container) return;

        // Update state
        state.viewMode = mode;
        if (savePreference) {
            localStorage.setItem('carViewMode', mode);
        }

        // Update container classes
        container.classList.remove('grid-cols-1', 'md:grid-cols-2', 'lg:grid-cols-3', '2xl:grid-cols-4');
        
        if (mode === 'grid') {
            container.classList.add('grid-cols-1', 'md:grid-cols-2', 'lg:grid-cols-3', '2xl:grid-cols-4');
        } else {
            container.classList.add('grid-cols-1');
        }

        // Update cards layout
        document.querySelectorAll('.car-card').forEach(card => {
            card.classList.toggle('flex-row', mode === 'list');
            card.classList.toggle('flex-col', mode === 'grid');
        });

        // Update active button state
        buttons.forEach(btn => {
            btn.classList.toggle('btn-active', btn.dataset.view === mode);
        });
    }

    // Initialize hover effects
    function initializeHoverEffects() {
        document.querySelectorAll('.car-card').forEach(card => {
            card.addEventListener('mouseenter', () => {
                card.querySelector('.card-hover-content')?.classList.remove('opacity-0');
            });
            
            card.addEventListener('mouseleave', () => {
                card.querySelector('.card-hover-content')?.classList.add('opacity-0');
            });
        });
    }

    // Handle compare selection with translation
    function handleCompareSelection(e) {
        const carId = e.target.value;
        const compareBar = document.getElementById('compareBar');
        const compareButton = document.getElementById('compareButton');
        const compareCounter = document.getElementById('compareCounter');
        
        if (e.target.checked) {
            if (state.selectedForCompare.size >= state.maxCompareItems) {
                e.target.checked = false;
                window.showToast('cars.featured_cars.compare.max_cars', 'warning');
                return;
            }
            state.selectedForCompare.add(carId);
        } else {
            state.selectedForCompare.delete(carId);
        }

        // Update compare bar
        updateCompareBar();
        
        // Update counter
        compareCounter.textContent = `${state.selectedForCompare.size}/3`;
        
        // Enable/disable compare button
        compareButton.disabled = state.selectedForCompare.size < 2;
        
        // Show/hide compare bar
        if (state.selectedForCompare.size > 0) {
            compareBar.classList.remove('translate-y-full');
        } else {
            compareBar.classList.add('translate-y-full');
        }
    }

    // Handle quick view with translation
    function handleQuickView(e) {
        const button = e.target.closest('.quick-view-btn');
        if (!button) return;

        const card = button.closest('.car-card');
        if (!card) return;

        const carId = card.dataset.carId;
        if (!carId) {
            console.error('Car ID not found');
            return;
        }

        const modal = document.getElementById('quickViewModal');
        if (!modal) {
            console.error('Quick view modal not found');
            return;
        }

        // Show loading state
        const content = modal.querySelector('#quickViewContent');
        content.innerHTML = `<div class="flex justify-center p-8"><span class="loading loading-spinner loading-lg"></span></div>`;
        
        modal.showModal();
        
        // Fetch car details with proper headers
        fetch(`${window.baseUrl}/Components/Cars/quick-view.php?id=${carId}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept-Language': window.currentLocale
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text();
        })
        .then(html => {
            content.innerHTML = html;
            initializeGallery();
        })
        .catch(error => {
            content.innerHTML = `
                <div class="alert alert-error">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <span>${window.translations['messages.error']}</span>
                </div>`;
            console.error('Quick view error:', error);
        });
    }

    // Gallery initialization function
    function initializeGallery() {
        const mainImage = document.getElementById("quickViewMainImage");
        const galleryContainer = document.getElementById("imageGallery");
        
        if (!mainImage || !galleryContainer) {
            console.error("Required elements not found!");
            return;
        }

        const thumbs = galleryContainer.getElementsByClassName("gallery-thumb");
        
        // Set initial state
        mainImage.style.transition = "opacity 0.3s ease-in-out";
        
        function switchMainImage(thumbElement) {
            // Get the gallery-thumb div whether we clicked the img or the div
            const thumb = thumbElement.closest(".gallery-thumb");
            
            if (!thumb || !thumb.dataset.largeImg) {
                console.error("Invalid thumbnail data:", thumb);
                return;
            }

            // Create new image to preload
            const newImage = new Image();
            
            newImage.onload = function() {
                mainImage.style.opacity = "0";
                
                setTimeout(() => {
                    mainImage.src = thumb.dataset.largeImg;
                    mainImage.style.opacity = "1";
                    
                    // Update active thumbnail state
                    Array.from(thumbs).forEach(t => {
                        t.classList.remove("ring-2", "ring-primary", "ring-offset-2");
                    });
                    thumb.classList.add("ring-2", "ring-primary", "ring-offset-2");
                }, 300);
            };

            newImage.onerror = function() {
                console.error("Failed to load image:", thumb.dataset.largeImg);
            };
            
            newImage.src = thumb.dataset.largeImg;
        }

        // Add click handlers to thumbnails
        Array.from(thumbs).forEach(thumb => {
            thumb.addEventListener("click", function(e) {
                e.preventDefault();
                switchMainImage(this);
            });
        });
    }

    // Apply sorting
    function applySorting() {
        if (state.isLoading) return;
        state.isLoading = true;

        const container = document.getElementById('carsGrid');
        if (!container) return;

        // Clear existing content first
        container.innerHTML = '<div class="loading">Loading...</div>';

        // Get all filter values
        const filters = {
            brand: document.querySelector('select[name="brand"]')?.value,
            condition: document.querySelector('select[name="condition"]')?.value,
            min_price: document.querySelector('input[name="min_price"]')?.value,
            max_price: document.querySelector('input[name="max_price"]')?.value,
            sort: state.sortBy
        };

        // Use URLSearchParams to build query string
        const params = new URLSearchParams(filters);

        fetch(`${window.baseUrl}/Components/Cars/ajax-search.php?${params}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) throw new Error(data.message);
            
            // Clear existing content and update with new results
            container.innerHTML = data.html;
            
            // Reinitialize event listeners on new content
            initializeHoverEffects();
            initializeCompareCheckboxes();
        })
        .catch(error => {
            console.error('Filter error:', error);
            container.innerHTML = `
                <div class="alert alert-error">
                    ${window.translations?.['messages.error'] || 'An error occurred while filtering'}
                </div>`;
        })
        .finally(() => {
            state.isLoading = false;
        });
    }

    // Get sort value from card
    function getSortValue(card) {
        switch (state.sortBy) {
            case 'price_low':
            case 'price_high':
                return parseFloat(card.dataset.price || 0);
            case 'year_new':
                return parseInt(card.dataset.year || 0);
            case 'popularity':
                return parseInt(card.dataset.views || 0);
            default:
                return parseInt(card.dataset.timestamp || 0);
        }
    }

    // Update compare bar
    function updateCompareBar() {
        const slots = document.querySelectorAll('.car-slot');
        const selectedCards = Array.from(document.querySelectorAll('.car-card'))
            .filter(card => state.selectedForCompare.has(card.dataset.carId));
        
        // Reset all slots
        slots.forEach(slot => {
            slot.innerHTML = `
                <div class="text-center text-base-content/50">
                    <span class="text-2xl">+</span>
                    <p class="text-xs">Add Car</p>
                </div>
            `;
            slot.classList.add('bg-base-200/50');
        });
        
        // Fill slots with selected cars
        selectedCards.forEach((card, index) => {
            if (index < slots.length) {
                slots[index].innerHTML = `
                    <div class="flex items-center gap-2 w-full">
                        <img src="${card.dataset.image}" 
                             alt="${card.dataset.title}" 
                             class="w-16 h-12 object-cover rounded">
                        <div class="flex-1 min-w-0">
                            <div class="font-semibold text-sm truncate">${card.dataset.title}</div>
                            <div class="text-xs text-base-content/70">$${card.dataset.price}</div>
                        </div>
                        <button onclick="window.featuredCars.removeFromCompare('${card.dataset.carId}')" 
                                class="btn btn-ghost btn-xs btn-square">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                `;
                slots[index].classList.remove('bg-base-200/50');
            }
        });
    }

    // Remove car from comparison
    function removeFromCompare(carId) {
        const checkbox = document.querySelector(`.compare-checkbox[value="${carId}"]`);
        if (checkbox) {
            checkbox.checked = false;
            handleCompareSelection({ target: checkbox });
        }
    }

    // Show comparison with better error handling
    function showComparison() {
        if (state.isLoading) return;
        state.isLoading = true;

        const modal = document.getElementById('comparisonModal');
        const content = document.getElementById('comparisonContent');
        if (!modal || !content) return;
        
        content.innerHTML = `<div class="flex justify-center p-8"><span class="loading loading-spinner loading-lg"></span></div>`;
        
        const carIds = Array.from(state.selectedForCompare);
        
        fetch(`${window.baseUrl}/Components/Cars/compare-view.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept-Language': window.currentLocale || 'en'
            },
            body: JSON.stringify({ carIds })
        })
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.text();
        })
        .then(html => {
            content.innerHTML = html;
            modal.showModal();
        })
        .catch(error => {
            content.innerHTML = `
                <div class="alert alert-error">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <span>${window.translations?.['messages.error'] || 'An error occurred'}</span>
                </div>`;
            console.error('Comparison error:', error);
        })
        .finally(() => {
            state.isLoading = false;
        });
    }

    // Clear compare selection
    function clearCompare() {
        state.selectedForCompare.clear();
        document.querySelectorAll('.compare-checkbox').forEach(checkbox => {
            checkbox.checked = false;
        });
        updateCompareBar();
        document.getElementById('compareBar').classList.add('translate-y-full');
        document.getElementById('compareButton').disabled = true;
        document.getElementById('compareCounter').textContent = '0/3';
    }

    // Public API with error boundaries
    return {
        initializeFeaturedCars,
        setViewMode: (...args) => {
            try {
                setViewMode(...args);
            } catch (error) {
                console.error('View mode error:', error);
            }
        },
        handleCompareSelection: (...args) => {
            try {
                handleCompareSelection(...args);
            } catch (error) {
                console.error('Compare selection error:', error);
            }
        },
        handleQuickView: (...args) => {
            try {
                handleQuickView(...args);
            } catch (error) {
                console.error('Quick view error:', error);
            }
        },
        clearCompare,
        removeFromCompare,
        showComparison
    };
})();

// Initialize with error boundary
document.addEventListener('DOMContentLoaded', function() {
    try {
        window.featuredCars.initializeFeaturedCars();
    } catch (error) {
        console.error('Failed to initialize featured cars module:', error);
    }
}); 