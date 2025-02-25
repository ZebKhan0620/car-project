document.addEventListener('DOMContentLoaded', function() {
    // Only run this code on pages with the filter form
    const filterForm = document.querySelector('#filterForm');
    if (!filterForm) return; // Exit if not on search page

    const resultsContainer = document.querySelector('#searchResults');
    const totalResults = document.querySelector('#totalResults');
    let debounceTimer;

    // Function to update results
    async function updateResults(formData) {
        try {
            const queryString = new URLSearchParams(formData).toString();
            const response = await fetch(`/car-project/public/Components/Cars/ajax-search.php?${queryString}`);
            const data = await response.json();

            if (data.success) {
                resultsContainer.innerHTML = data.html;
                totalResults.textContent = `Showing ${data.total} results`;
                
                // Update URL without page reload
                const newUrl = `${window.location.pathname}?${queryString}`;
                window.history.pushState({ path: newUrl }, '', newUrl);
            }
        } catch (error) {
            console.error('Error updating results:', error);
        }
    }

    // Handle form changes
    filterForm.querySelectorAll('input, select').forEach(element => {
        element.addEventListener('change', function() {
            const formData = new FormData(filterForm);
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                updateResults(formData);
            }, 500);
        });
    });

    // Handle price range inputs
    const priceInputs = filterForm.querySelectorAll('input[type="number"]');
    priceInputs.forEach(input => {
        input.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                const formData = new FormData(filterForm);
                updateResults(formData);
            }, 800);
        });
    });

    // Handle sort change
    const sortSelect = document.querySelector('#sortSelect');
    if (sortSelect) {
        sortSelect.addEventListener('change', function() {
            const formData = new FormData(filterForm);
            formData.append('sort', this.value);
            updateResults(formData);
        });
    }
}); 