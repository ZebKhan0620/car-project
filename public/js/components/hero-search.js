// Add this function for popular searches
function quickSearch(term) {
    const searchInput = document.querySelector('#heroSearchForm input[name="search"]');
    if (searchInput) {
        searchInput.value = term;
        const form = document.getElementById('heroSearchForm');
        if (form) {
            form.dispatchEvent(new Event('submit'));
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const heroSearchForm = document.getElementById('heroSearchForm');
    const carPreview = document.getElementById('hero-car-preview');
    const popularSearchesContainer = document.querySelector('.hero-content .popular-searches');

    if (heroSearchForm && carPreview) {
        heroSearchForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            try {
                // Add loading state
                carPreview.classList.add('opacity-50');
                const loadingHtml = `
                    <div class="flex items-center justify-center h-full">
                        <div class="animate-spin rounded-full h-32 w-32 border-b-2 border-primary"></div>
                    </div>
                `;
                carPreview.innerHTML = loadingHtml;
                
                // Get form data
                const formData = new FormData(this);
                const params = new URLSearchParams(formData);
                
                // Perform the fetch with timeout
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 10000); // 10 second timeout
                
                const response = await fetch(
                    `${baseUrl}/Components/Cars/hero-search.php?${params.toString()}`,
                    { signal: controller.signal }
                );
                
                clearTimeout(timeoutId);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    throw new Error('Invalid response format. Expected JSON.');
                }
                
                const data = await response.json();
                
                if (data.success) {
                    carPreview.innerHTML = data.html;
                    // Only update popular searches if container exists and we have new data
                    if (popularSearchesContainer && data.popularSearchesHtml) {
                        popularSearchesContainer.innerHTML = data.popularSearchesHtml;
                    }
                } else {
                    throw new Error(data.message || 'Search failed');
                }
            } catch (error) {
                console.error('Search error:', error);
                let errorMessage = 'An error occurred while searching';
                
                if (error.name === 'AbortError') {
                    errorMessage = 'Search request timed out. Please try again.';
                } else if (error.message) {
                    errorMessage = error.message;
                }
                
                carPreview.innerHTML = `
                    <div class="alert alert-error shadow-lg">
                        <div>
                            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current flex-shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>${errorMessage}</span>
                        </div>
                    </div>`;
            } finally {
                carPreview.classList.remove('opacity-50');
            }
        });
    }

    // Quick Search from Hero
    const quickSearchHero = document.getElementById('quickSearchHero');
    if (quickSearchHero) {
        quickSearchHero.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const params = new URLSearchParams();
            
            // Add non-empty values to params
            for (const [key, value] of formData.entries()) {
                if (value.trim()) {
                    params.append(key, value.trim());
                }
            }

            // Redirect to search page with parameters
            window.location.href = `${baseUrl}/Components/Cars/search.php?${params.toString()}`;
        });
    }

    // Popular searches functionality
    if (popularSearchesContainer) {
        // Handle popular search clicks
        popularSearchesContainer.addEventListener('click', function(e) {
            const searchBtn = e.target.closest('.popular-search-btn');
            if (searchBtn) {
                e.preventDefault();
                const searchTerm = searchBtn.dataset.search;
                if (searchTerm) {
                    window.location.href = `${baseUrl}/Components/Cars/search.php?q=${encodeURIComponent(searchTerm)}`;
                }
            }
        });
    }

    // Initialize brand dropdown if exists
    const brandSelect = document.getElementById('heroSearchBrand');
    if (brandSelect) {
        brandSelect.addEventListener('change', function() {
            const selectedBrand = this.value;
            const modelSelect = document.getElementById('heroSearchModel');
            
            if (modelSelect) {
                // Clear current options
                modelSelect.innerHTML = '<option value="">All Models</option>';
                
                if (selectedBrand && typeof carModels !== 'undefined' && carModels[selectedBrand]) {
                    carModels[selectedBrand].forEach(model => {
                        const option = document.createElement('option');
                        option.value = model;
                        option.textContent = model;
                        modelSelect.appendChild(option);
                    });
                    modelSelect.disabled = false;
                } else {
                    modelSelect.disabled = true;
                }
            }
        });
    }
}); 