document.addEventListener('DOMContentLoaded', function() {
    const quickSearchForm = document.getElementById('quickSearch');
    if (quickSearchForm) {
        quickSearchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const params = new URLSearchParams(formData);
            window.location.href = `${window.location.pathname}?${params.toString()}`;
        });
    }
});
