document.addEventListener('DOMContentLoaded', function() {
    const brandSelect = document.querySelector('select[name="brand"]');
    const modelInput = document.querySelector('input[name="model"]');
    const modelDatalist = document.getElementById('model-list');
    
    brandSelect.addEventListener('change', function() {
        const brand = this.value;
        if (brand && carBrands[brand]) {
            modelDatalist.innerHTML = '';
            carBrands[brand].forEach(model => {
                const option = document.createElement('option');
                option.value = model;
                modelDatalist.appendChild(option);
            });
            modelInput.removeAttribute('disabled');
        } else {
            modelInput.value = '';
            modelInput.setAttribute('disabled', 'disabled');
        }
    });
}); 