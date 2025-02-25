document.addEventListener('DOMContentLoaded', function() {
    // Global error handler for all calculator components
    window.handleCalculatorError = function(error, component) {
        console.error(`[${component}] Error:`, error);
        const errorMessage = document.getElementById('error-message');
        if (errorMessage) {
            errorMessage.textContent = error.message || 'An error occurred';
            errorMessage.classList.remove('hidden');
            setTimeout(() => errorMessage.classList.add('hidden'), 5000);
        }
    };

    // Global loading state handler
    window.handleLoadingState = function(isLoading, buttonId) {
        const button = document.getElementById(buttonId);
        if (!button) return;

        const normalState = button.querySelector('.normal-state');
        const loadingSpinner = button.querySelector('.loading-spinner');
        
        if (normalState && loadingSpinner) {
            button.disabled = isLoading;
            normalState.classList.toggle('hidden', isLoading);
            loadingSpinner.classList.toggle('hidden', !isLoading);
        }
    };

    // Print handler
    document.getElementById('print-btn')?.addEventListener('click', function() {
        window.print();
    });

    // Initialize tooltips if available
    if (typeof tippy !== 'undefined') {
        tippy('[data-tippy-content]');
    }

    // Debug mode toggle
    const debugMode = localStorage.getItem('debugMode') === 'true';
    if (debugMode) {
        console.log('Debug mode enabled');
        window.debugCalculator = {
            logFormData: true,
            logApiCalls: true,
            logErrors: true
        };
    }

    // Initialize error handler
    if (typeof ErrorHandler !== 'undefined') {
        ErrorHandler.init();
    }

    // Debug initialization state
    console.log('DOM fully loaded');
    console.log('Calculator form:', document.getElementById('import-form'));
    console.log('Error container:', document.getElementById('error-message'));

    const app = {
        init() {
            try {
                this.initializeComponents();
                this.setupEventListeners();
                this.initializeLanguage();
            } catch (error) {
                console.error('Initialization error:', error);
            }
        },

        initializeComponents() {
            // Header initialization
            if (document.querySelector('header')) {
                this.initializeDropdowns();
                this.initializeSearch();
            }

            // Featured Cars initialization
            if (document.getElementById('featured-cars')) {
                this.initializeCars();
            }

            // Calculator initialization
            if (document.getElementById('calculator-form')) {
                this.initializeCalculator();
            }

            // Testimonials initialization
            if (document.getElementById('testimonial-carousel')) {
                this.initializeTestimonials();
            }
        },

        initializeCars() {
            // Car filtering functionality
            const sortSelect = document.getElementById('sort-by');
            const filterSelect = document.getElementById('filter-type');
            const carItems = document.querySelectorAll('.car-item');

            if (sortSelect) {
                sortSelect.addEventListener('change', (e) => {
                    this.sortCars(e.target.value, carItems);
                });
            }

            if (filterSelect) {
                filterSelect.addEventListener('change', (e) => {
                    this.filterCars(e.target.value, carItems);
                });
            }
        },

        sortCars(criteria, cars) {
            // Add sorting logic here
            console.log('Sorting cars by:', criteria);
        },

        filterCars(type, cars) {
            // Add filtering logic here
            console.log('Filtering cars by type:', type);
        },

        initializeDropdowns() {
            const dropdowns = document.querySelectorAll('.dropdown');
            dropdowns.forEach(dropdown => {
                dropdown.addEventListener('click', (e) => {
                    e.currentTarget.classList.toggle('dropdown-open');
                });
            });
        },

        initializeSearch() {
            const searchForm = document.querySelector('[data-search-form]');
            if (searchForm) {
                searchForm.addEventListener('submit', (e) => {
                    e.preventDefault();
                    console.log('Search submitted');
                });
            }
        },

        initializeCalculator() {
            const form = document.getElementById('calculator-form');
            if (form) {
                form.addEventListener('submit', (e) => {
                    e.preventDefault();
                    console.log('Calculating...');
                });
            }
        },

        initializeTestimonials() {
            const carousel = document.getElementById('testimonial-carousel');
            if (carousel) {
                console.log('Testimonials initialized');
            }
        },

        setupEventListeners() {
            // Language switcher
            const langButtons = document.querySelectorAll('[data-lang]');
            langButtons.forEach(button => {
                button.addEventListener('click', (e) => {
                    this.switchLanguage(e.target.dataset.lang);
                });
            });
        },

        initializeLanguage() {
            const currentLang = localStorage.getItem('language') || 'en';
            document.documentElement.setAttribute('lang', currentLang);
        },

        switchLanguage(lang) {
            localStorage.setItem('language', lang);
            window.location.reload();
        }
    };

    // Initialize application
    app.init();
});