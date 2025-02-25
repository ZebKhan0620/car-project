document.addEventListener('DOMContentLoaded', function() {
    // Import Calculator Integration
    const importCalculator = {
        init: function() {
            this.bindCalculatorEvents();
        },

        bindCalculatorEvents: function() {
            const currencySelect = document.getElementById('currency');
            const targetCurrencySelect = document.getElementById('targetCurrency');
            const calculatorForm = document.getElementById('calculatorForm');

            if (!currencySelect || !targetCurrencySelect || !calculatorForm) {
                console.error('Required import calculator elements not found');
                return;
            }

            // Handle currency changes
            currencySelect.addEventListener('change', () => {
                if (calculatorForm.checkValidity()) {
                    window.calculatorFunctions?.calculateCosts(new FormData(calculatorForm));
                }
            });

            targetCurrencySelect.addEventListener('change', () => {
                if (calculatorForm.checkValidity()) {
                    window.calculatorFunctions?.calculateCosts(new FormData(calculatorForm));
                }
            });
        }
    };

    // Initialize calculator integration
    if (document.getElementById('import-calculator')) {
        importCalculator.init();
    }

    const form = document.getElementById('import-form');
    if (!form) return;

    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        try {
            const formElement = e.target;
            if (!(formElement instanceof HTMLFormElement)) {
                throw new Error('Invalid form element');
            }

            const formData = new FormData(formElement);
            // Rest of your form handling code...
        } catch (error) {
            console.error('Form submission error:', error);
            showError(error.message);
        }
    });
});
