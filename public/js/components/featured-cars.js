// Constants
window.TOAST_DURATION = window.TOAST_DURATION || 3000;
window.REQUEST_TIMEOUT = window.REQUEST_TIMEOUT || 60000; // 60 seconds to match server timeout
window.baseUrl = window.baseUrl || '/car-project/public'; // Base URL for all requests
let currentRequest = null;
let isInitialSearch = true; // Add flag for initial search
let isSearchInitialized = false; // Add flag to prevent duplicate initialization

// Featured Cars Module
window.featuredCars = (function() {
    // State management
    const state = {
        viewMode: localStorage.getItem('carViewMode') || 'grid',
        sortBy: localStorage.getItem('carSortBy') || 'latest',
        compareMode: false,
        selectedForCompare: new Set()
    };

    // Enhanced toast notification with translations
    function showToast(messageKey, type = 'info', params = {}) {
        const toast = document.createElement('div');
        toast.className = `toast toast-end`;
        
        // Get translated message with fallback
        let message = messageKey;
        if (window.translations && typeof messageKey === 'string') {
            const keys = messageKey.split('.');
            let translation = window.translations;
            
            // Traverse the translation object
            for (const key of keys) {
                translation = translation?.[key];
                if (!translation) break;
            }
            
            if (translation) {
                message = translation;
            }
        }

        // Replace parameters in message
        Object.keys(params).forEach(key => {
            message = message.replace(`{${key}}`, params[key]);
        });

        toast.innerHTML = `
            <div class="alert alert-${type}">
                <span>${message}</span>
            </div>
        `;
        document.body.appendChild(toast);
        setTimeout(() => {
            toast.classList.add('fade-out');
            setTimeout(() => toast.remove(), 300);
        }, window.TOAST_DURATION || 3000);
    }

    // Loading state management
    function setLoadingState(container, isLoading) {
        if (isLoading) {
            container.classList.add('opacity-50');
            container.innerHTML = `
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4 gap-4">
                    ${Array(8).fill().map(() => `
                        <div class="card bg-base-100 shadow-xl animate-pulse">
                            <div class="h-48 bg-base-300 rounded-t-xl"></div>
                            <div class="card-body">
                                <div class="h-4 bg-base-300 rounded w-3/4"></div>
                                <div class="h-4 bg-base-300 rounded w-1/2 mt-2"></div>
                                <div class="flex gap-2 mt-4">
                                    <div class="h-8 bg-base-300 rounded w-24"></div>
                                    <div class="h-8 bg-base-300 rounded w-24"></div>
                                </div>
                            </div>
                        </div>
                    `).join('')}
                </div>
            `;
        }
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
            
            // Adjust image container width for list view
            const figure = card.querySelector('figure');
            if (figure) {
                figure.classList.toggle('w-1/3', mode === 'list');
                figure.classList.toggle('w-full', mode === 'grid');
            }
            
            // Adjust content width for list view
            const content = card.querySelector('.card-body');
            if (content) {
                content.classList.toggle('w-2/3', mode === 'list');
                content.classList.toggle('w-full', mode === 'grid');
            }
        });

        // Update active button state
        buttons.forEach(btn => {
            btn.classList.toggle('btn-active', btn.dataset.view === mode);
        });
    }

    // Toggle compare mode
    function toggleCompareMode() {
        state.compareMode = !state.compareMode;
        const checkboxes = document.querySelectorAll('.compare-checkbox');
        const compareBar = document.getElementById('compareBar');
        
        checkboxes.forEach(checkbox => {
            checkbox.closest('label').classList.toggle('opacity-0', !state.compareMode);
            checkbox.closest('label').classList.toggle('pointer-events-none', !state.compareMode);
        });
        
        if (!state.compareMode) {
            clearCompare();
        }
    }

    // Toggle favorite with translation
    async function toggleFavorite(carId) {
        if (!window.isAuthenticated) {
            showToast('messages.login_required', 'warning');
            return;
        }

        try {
            const response = await fetch(`${window.baseUrl}/Components/Cars/toggle-favorite.php`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept-Language': window.currentLocale
                },
                body: JSON.stringify({ car_id: carId })
            });

            const data = await response.json();
            
            if (data.success) {
                const btn = document.querySelector(`button[onclick*="toggleFavorite(${carId})"]`);
                if (btn) {
                    btn.classList.toggle('text-red-500');
                    btn.classList.toggle('fill-current', data.isFavorited);
                }
                showToast(data.messageKey || 'messages.success', 'success');
            } else {
                throw new Error(data.messageKey || 'messages.error');
            }
        } catch (error) {
            console.error('Favorite error:', error);
            showToast('messages.error', 'error');
        }
    }

    // Handle compare selection with translation
    function handleCompareSelection(e) {
        const carId = e.target.value;
        const compareBar = document.getElementById('compareBar');
        const compareButton = document.getElementById('compareButton');
        const compareCounter = document.getElementById('compareCounter');
        
        if (e.target.checked) {
            if (state.selectedForCompare.size >= 3) {
                e.target.checked = false;
                showToast('cars.featured_cars.compare.max_cars', 'warning');
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

    // Show comparison modal
    async function showComparison() {
        const modal = document.getElementById('comparisonModal');
        const content = document.getElementById('comparisonContent');
        
        if (!modal || !content) {
            console.error('Comparison modal elements not found');
            return;
        }

        // Show loading state
        content.innerHTML = '<div class="flex justify-center p-8"><span class="loading loading-spinner loading-lg"></span></div>';
        modal.showModal();
        
        try {
            // Get selected car IDs
            const carIds = Array.from(state.selectedForCompare);
            
            const response = await fetch(`${window.baseUrl}/Components/Cars/compare-view.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ carIds })
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const html = await response.text();
            content.innerHTML = html;
            
        } catch (error) {
            console.error('Comparison error:', error);
            content.innerHTML = `
                <div class="alert alert-error">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <span>Error loading comparison data. Please try again.</span>
                </div>`;
        }
    }

    // Handle quick view
    function handleQuickView(e) {
        const carId = e.target.closest('.car-card').dataset.carId;
        const modal = document.getElementById('quickViewModal');
        
        // Show loading state
        const content = modal.querySelector('#quickViewContent');
        content.innerHTML = '<div class="flex justify-center p-8"><span class="loading loading-spinner loading-lg"></span></div>';
        
        // Open modal
        modal.showModal();
        
        // Fetch car details
        fetch(`${window.baseUrl}/Components/Cars/quick-view.php?id=${carId}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(response => response.text())
            .then(html => {
                content.innerHTML = html;
            })
            .catch(error => {
                content.innerHTML = '<div class="alert alert-error">Error loading car details</div>';
                console.error('Quick view error:', error);
            });
    }

    // Clear compare selection
    function clearCompare() {
        // Clear the state
        state.selectedForCompare.clear();
        
        // Uncheck all compare checkboxes
        document.querySelectorAll('.compare-checkbox').forEach(checkbox => {
            checkbox.checked = false;
        });
        
        // Reset the compare bar
        updateCompareBar();
        
        // Hide the compare bar
        const compareBar = document.getElementById('compareBar');
        if (compareBar) {
            compareBar.classList.add('translate-y-full');
        }
        
        // Reset counter and button
        const compareCounter = document.getElementById('compareCounter');
        const compareButton = document.getElementById('compareButton');
        if (compareCounter) compareCounter.textContent = '0';
        if (compareButton) compareButton.disabled = true;
    }

    // Close compare bar
    function closeCompareBar() {
        clearCompare();
        // Also disable compare mode if it's enabled
        state.compareMode = false;
        document.querySelectorAll('.compare-checkbox').forEach(checkbox => {
            checkbox.closest('label').classList.add('opacity-0', 'pointer-events-none');
        });
    }

    // Update compare bar
    function updateCompareBar() {
        const slots = document.querySelectorAll('.car-slot');
        const selectedCards = Array.from(document.querySelectorAll('.car-card'))
            .filter(card => state.selectedForCompare.has(card.dataset.carId));

        // Reset all slots first
        slots.forEach((slot, index) => {
            slot.innerHTML = `
                <div class="text-center text-base-content/50">
                    <span class="text-2xl">+</span>
                    <p class="text-xs">Add Car ${index + 1}</p>
                </div>
            `;
            slot.classList.add('bg-base-200/50');
        });

        // Fill slots with selected cars
        selectedCards.forEach((card, index) => {
            if (index < slots.length) {
                const carTitle = card.dataset.title;
                const carPrice = card.dataset.price;
                const carImage = card.dataset.image;
                const carId = card.dataset.carId;

                slots[index].innerHTML = `
                    <div class="flex items-center gap-2 w-full">
                        <img src="${carImage}" 
                             alt="${carTitle}" 
                             class="w-16 h-12 object-cover rounded">
                        <div class="flex-1 min-w-0">
                            <div class="font-semibold text-sm truncate">${carTitle}</div>
                            <div class="text-xs text-base-content/70">$${Number(carPrice).toLocaleString()}</div>
                        </div>
                        <button onclick="window.featuredCars.removeFromCompare('${carId}')" 
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

    // Initialize search form with improved handling
    function initializeSearch() {
        if (isSearchInitialized) {
            console.log('[Debug] Search already initialized, skipping');
            return;
        }
        console.log('[Debug] initializeSearch started');
        
        const form = document.getElementById('advancedSearch');
        if (!form) {
            console.error('Search form not found in the DOM');
            return;
        }

        const container = document.getElementById('carsGrid');
        if (!container) {
            console.error('Cars grid container not found in the DOM');
            return;
        }

        // Initialize search state from URL parameters
        const urlParams = new URLSearchParams(window.location.search);
        console.log('[Debug] URL Parameters:', urlParams.toString());

        // Set form values from URL parameters
        for (const [key, value] of urlParams) {
            const input = form.elements[key];
            if (input) {
                input.value = value;
                // If it's a brand select, trigger change event to load models
                if (key === 'brand' && input.tagName === 'SELECT') {
                    const event = new Event('change');
                    input.dispatchEvent(event);
                }
            }
        }

        // Handle Apply Filters button click
        const applyFiltersBtn = document.getElementById('applyFilters');
        if (applyFiltersBtn) {
            applyFiltersBtn.addEventListener('click', function(e) {
                e.preventDefault();
                console.log('[Debug] Apply Filters clicked');
                handleSearch(form, container);
            });
        }

        // Handle Reset Filters button click
        const resetFiltersBtn = document.getElementById('resetFilters');
        if (resetFiltersBtn) {
            resetFiltersBtn.addEventListener('click', function(e) {
                e.preventDefault();
                console.log('[Debug] Reset Filters clicked');
                form.reset();
                
                // Reset dependent dropdowns
                const modelSelect = document.querySelector('select[name="model"]');
                if (modelSelect) {
                    modelSelect.innerHTML = `<option value="">${window.translations?.['cars.featured_cars.dropdowns.all_models'] || 'All Models'}</option>`;
                    modelSelect.disabled = true;
                }
                
                // Clear URL parameters and trigger search
                window.history.pushState({}, '', window.location.pathname);
                handleSearch(form, container);
            });
        }

        // Add change event listeners to select fields
        const selectFields = form.querySelectorAll('select');
        selectFields.forEach(select => {
            select.addEventListener('change', function() {
                console.log('[Debug] Select changed:', this.name, this.value);
                // Don't trigger search for model select when it's being populated
                if (this.name === 'model' && this.disabled) return;
                handleSearch(form, container);
            });
        });

        // Add debounced input handling for numeric fields
        const numericInputs = form.querySelectorAll('input[type="number"]');
        numericInputs.forEach(input => {
            let debounceTimer;
            input.addEventListener('input', function() {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    if (this.checkValidity()) {
                        console.log('[Debug] Numeric input changed:', this.name, this.value);
                        handleSearch(form, container);
                    }
                }, 800);
            });
        });

        // Handle form submission
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            console.log('[Debug] Form submitted');
            handleSearch(form, container);
        });

        // Set initialization flag after all event listeners are set up
        isSearchInitialized = true;

        // Perform initial search if there are URL parameters
        if (urlParams.toString()) {
            console.log('[Debug] Performing initial search from URL parameters');
            handleSearch(form, container);
        }
    }

    // Handle search functionality
    async function handleSearch(form, container) {
        console.log('[Debug] handleSearch called');
        try {
            const formData = new FormData(form);
            const params = new URLSearchParams();
            
            // Log form data
            console.log('[Debug] Form data:');
            for (const [key, value] of formData.entries()) {
                console.log(`[Debug] ${key}:`, value);
                if (value.trim()) {
                    params.append(key, value.trim());
                }
            }
            
            // Show loading state only in the grid area
            if (container) {
                console.log('[Debug] Showing loading state');
                container.classList.add('opacity-50');
                container.innerHTML = `
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4 gap-4">
                        ${Array(8).fill().map(() => `
                            <div class="card bg-base-100 shadow-xl animate-pulse">
                                <div class="h-48 bg-base-300 rounded-t-xl"></div>
                                <div class="card-body">
                                    <div class="h-4 bg-base-300 rounded w-3/4"></div>
                                    <div class="h-4 bg-base-300 rounded w-1/2 mt-2"></div>
                                    <div class="flex gap-2 mt-4">
                                        <div class="h-8 bg-base-300 rounded w-24"></div>
                                        <div class="h-8 bg-base-300 rounded w-24"></div>
                                    </div>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                `;
            }
            
            // Update URL without page reload
            const newUrl = `${window.location.pathname}?${params.toString()}`;
            console.log('[Debug] Updating URL to:', newUrl);
            window.history.pushState({ path: newUrl }, '', newUrl);
            
            console.log('[Debug] Sending search request');
            const response = await fetch(
                `${window.baseUrl}/Components/Cars/featured-cars-search.php?${params.toString()}`,
                {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }
            );
            
            if (!response.ok) {
                throw new Error(`Server error: ${response.status}`);
            }
            
            console.log('[Debug] Received search response');
            const data = await response.json();
            console.log('[Debug] Parsed JSON response:', data);
            
            if (data.success) {
                if (container) {
                    console.log('[Debug] Updating container content');
                    // Remove loading state
                    container.classList.remove('opacity-50');
                    
                    if (data.count === 0) {
                        console.log('[Debug] No results found');
                        // Show no results message within the grid
                        container.innerHTML = `
                            <div class="col-span-full text-center p-8">
                                <div class="mb-4">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <h3 class="text-lg font-semibold mb-2">No cars found</h3>
                                <p class="text-base-content/70">No cars match your search criteria. Try adjusting your filters.</p>
                                <button onclick="document.getElementById('resetFilters').click()" class="btn btn-primary mt-4">
                                    Reset Filters
                                </button>
                            </div>`;
                    } else {
                        console.log('[Debug] Updating grid with new content');
                        // Update grid with new content
                        container.innerHTML = data.html;
                        
                        console.log('[Debug] Setting view mode');
                        // Maintain current view mode
                        setViewMode(state.viewMode, false);
                        
                        console.log('[Debug] Reinitializing components');
                        // Reinitialize components for new content
                        initializeEventListeners();
                        initializeHoverEffects();
                    }
                    
                    // Update results count if element exists
                    updateResultsCount(data.count);
                }
            } else {
                throw new Error(data.message || 'Error updating results');
            }
            
        } catch (error) {
            console.error('[Debug] Search error:', error);
            
            if (container) {
                // Remove loading state
                container.classList.remove('opacity-50');
                
                // Show error message within the grid
                container.innerHTML = `
                    <div class="col-span-full">
                        <div class="alert alert-error shadow-lg max-w-2xl mx-auto my-8">
                            <div class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <div>
                                    <h3 class="font-bold">Search Error</h3>
                                    <div class="text-sm">An error occurred while searching. Please try again.</div>
                                </div>
                            </div>
                            <div class="flex justify-end mt-4">
                                <button onclick="handleSearch(document.getElementById('advancedSearch'), document.getElementById('carsGrid'))" class="btn btn-sm">
                                    Try Again
                                </button>
                            </div>
                        </div>
                    </div>`;
            }
            
            // Show toast notification
            showToast('An error occurred while searching', 'error');
        }
    }

    // Update results count in UI
    function updateResultsCount(count) {
        const countElement = document.querySelector('#totalResults');
        if (countElement) {
            countElement.textContent = `Showing ${count} result${count !== 1 ? 's' : ''}`;
        }
    }

    // Initialize brand dropdown
    function initializeBrandDropdown() {
        const brandSelect = document.querySelector('select[name="brand"]');
        const modelSelect = document.querySelector('select[name="model"]');
        
        if (brandSelect && modelSelect) {
            brandSelect.addEventListener('change', function() {
                const selectedBrand = this.value;
                
                // Reset model select
                modelSelect.innerHTML = `<option value="">${window.translations['cars.featured_cars.dropdowns.all_models']}</option>`;
                
                if (selectedBrand && window.carModels && window.carModels[selectedBrand]) {
                    window.carModels[selectedBrand].forEach(model => {
                        const option = document.createElement('option');
                        option.value = model;
                        option.textContent = model;
                        modelSelect.appendChild(option);
                    });
                    modelSelect.disabled = false;
                } else {
                    modelSelect.disabled = true;
                    modelSelect.innerHTML = `<option value="">${window.translations['cars.featured_cars.dropdowns.model_placeholder']}</option>`;
                }
            });
        }
    }

    // Initialize the module
    function initializeFeaturedCars() {
        // Set initial view mode
        setViewMode(state.viewMode, false);
        
        // Set initial sort option
        const sortOption = document.querySelector(`select[name="sort"] option[value="${state.sortBy}"]`);
        if (sortOption) {
            sortOption.selected = true;
        }
        
        // Initialize dropdowns
        initializeBrandDropdown();
        
        // Initialize event listeners
        initializeEventListeners();
        
        // Initialize hover effects
        initializeHoverEffects();
    }

    // Initialize event listeners
    function initializeEventListeners() {
        console.log('[Debug] Starting initializeEventListeners');
        
        // Use event delegation for the container to handle all car-related events
        const container = document.getElementById('carsGrid');
        if (container) {
            // Remove existing event listener if any
            container.removeEventListener('click', handleContainerClick);
            // Add new event listener
            container.addEventListener('click', handleContainerClick);
        }

        // View toggle buttons - using event delegation
        const viewToggleContainer = document.querySelector('.view-toggle-buttons');
        if (viewToggleContainer) {
            // Remove existing event listener if any
            viewToggleContainer.removeEventListener('click', handleViewToggle);
            // Add new event listener
            viewToggleContainer.addEventListener('click', handleViewToggle);
        }
        
        console.log('[Debug] Finished initializeEventListeners');
    }

    // Handler for container click events
    function handleContainerClick(e) {
        // Handle quick view button clicks
        if (e.target.closest('.quick-view-btn')) {
            console.log('[Debug] Quick view clicked');
            handleQuickView(e);
        }

        // Handle compare checkbox changes
        const checkbox = e.target.closest('.compare-checkbox');
        if (checkbox) {
            console.log('[Debug] Compare checkbox changed');
            handleCompareSelection({ target: checkbox });
        }
    }

    // Handler for view toggle clicks
    function handleViewToggle(e) {
        const button = e.target.closest('[data-view]');
        if (button) {
            console.log('[Debug] View toggle clicked:', button.dataset.view);
            setViewMode(button.dataset.view);
        }
    }

    // Initialize quick view functionality
    function initializeQuickView() {
        const modal = document.getElementById('quickViewModal');
        if (!modal) return;

        // Close modal when clicking outside
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.close();
            }
        });
    }

    // Initialize hover effects
    function initializeHoverEffects() {
        document.querySelectorAll('.car-card').forEach(card => {
            const hoverElements = card.querySelectorAll('.group-hover\\:opacity-100');
            
            card.addEventListener('mouseenter', () => {
                hoverElements.forEach(el => el.classList.remove('opacity-0'));
            });
            
            card.addEventListener('mouseleave', () => {
                hoverElements.forEach(el => el.classList.add('opacity-0'));
            });
        });
    }

    // Remove car from compare
    function removeFromCompare(carId) {
        const checkbox = document.querySelector(`.compare-checkbox[value="${carId}"]`);
        if (checkbox) {
            checkbox.checked = false;
            state.selectedForCompare.delete(carId);
            
            // Update compare bar visibility
            const compareBar = document.getElementById('compareBar');
            if (state.selectedForCompare.size === 0) {
                compareBar.classList.add('translate-y-full');
            }
            
            // Update counter and button state
            const compareCounter = document.getElementById('compareCounter');
            const compareButton = document.getElementById('compareButton');
            compareCounter.textContent = `${state.selectedForCompare.size}`;
            compareButton.disabled = state.selectedForCompare.size < 2;
            
            // Update compare bar
            updateCompareBar();
        }
    }

    // Public API
    return {
        initializeFeaturedCars,
        setViewMode,
        toggleCompareMode,
        toggleFavorite,
        handleCompareSelection,
        handleQuickView,
        clearCompare,
        closeCompareBar,
        removeFromCompare,
        showComparison,
        initializeSearch,
        handleSearch
    };
})();

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.featuredCars.initializeFeaturedCars();
    window.featuredCars.initializeSearch(); // Add search initialization
});