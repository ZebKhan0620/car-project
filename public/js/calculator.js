document.addEventListener('DOMContentLoaded', function() {
    // Get form elements
    const form = document.getElementById('calculatorForm');
    const calculateBtn = document.getElementById('calculate-btn');
    const currencySelect = document.getElementById('currency');
    const targetCurrencySelect = document.getElementById('targetCurrency');
    const resultsDiv = document.getElementById('calculator-results');
    const basePrice = document.getElementById('basePrice');
    const convertedAmount = document.getElementById('convertedAmount');
    const finalCost = document.getElementById('finalCost');
    const sourceCurrency = document.getElementById('sourceCurrency');
    const costBreakdown = document.getElementById('costBreakdown');
    const errorMessage = document.getElementById('error-message');

    // Debug element existence
    console.log('Form elements found:', {
        form: !!form,
        calculateBtn: !!calculateBtn,
        currencySelect: !!currencySelect,
        targetCurrencySelect: !!targetCurrencySelect
    });

    // Early return if required elements aren't found
    if (!form || !calculateBtn || !currencySelect) {
        console.error('Required calculator elements not found');
        return;
    }

    // Get loading state elements
    const normalState = calculateBtn?.querySelector('.normal-state');
    const loadingSpinner = calculateBtn?.querySelector('.loading-spinner');

    function showLoading() {
        if (calculateBtn && normalState && loadingSpinner) {
            calculateBtn.disabled = true;
            normalState.classList.add('hidden');
            loadingSpinner.classList.remove('hidden');
        }
    }

    function hideLoading() {
        if (calculateBtn && normalState && loadingSpinner) {
            calculateBtn.disabled = false;
            normalState.classList.remove('hidden');
            loadingSpinner.classList.add('hidden');
        }
    }

    function showError(message) {
        if (errorMessage) {
            errorMessage.textContent = message;
            errorMessage.classList.remove('hidden');
            setTimeout(() => errorMessage.classList.add('hidden'), 5000);
        }
    }

    function formatCurrency(amount, currency) {
        const formatOptions = {
            'USD': { symbol: '$', locale: 'en-US' },
            'AED': { symbol: 'د.إ', locale: 'ar-AE' },
            'PHP': { symbol: '₱', locale: 'en-PH' }
        };

        const options = formatOptions[currency] || formatOptions['USD'];
        
        return new Intl.NumberFormat(options.locale, {
            style: 'currency',
            currency: currency,
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(amount);
    }

    function updateResults(data) {
        if (!data || !data.breakdown) return;

        // Breakdown items insertion
        let breakdownHtml = '';
        for (const [key, value] of Object.entries(data.breakdown)) {
            breakdownHtml += `
                <tr>
                    <td class="capitalize">${formatBreakdownLabel(key)}</td>
                    <td class="text-right">${formatCurrency(value, data.currency)}</td>
                </tr>
            `;
        }
        costBreakdown.innerHTML = breakdownHtml;

        // Risk factors insertion
        const riskFactors = document.getElementById('risk-factors');
        if (riskFactors && data.risk_factors) {
            let riskHtml = '';
            for (const [factor, risk] of Object.entries(data.risk_factors)) {
                riskHtml += `
                    <div class="flex justify-between items-center">
                        <span>${formatBreakdownLabel(factor)}</span>
                        <span class="badge ${getRiskBadgeColor(risk)}">${risk}%</span>
                    </div>
                `;
            }
            riskFactors.innerHTML = riskHtml;
        }

        // Recommendation insertion
        const recommendation = document.getElementById('recommendation');
        if (recommendation && data.recommendation) {
            recommendation.innerHTML = `
                <div class="alert-content">
                    <h4 class="font-bold">Recommendation</h4>
                    <p>${data.recommendation}</p>
                </div>
            `;
            recommendation.classList.remove('hidden');
        }

        // Show results
        resultsDiv.classList.remove('hidden');
    }

    function formatBreakdownLabel(key) {
        return key.split('_')
                 .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                 .join(' ');
    }

    function getRiskBadgeColor(risk) {
        return risk < 30 ? 'badge-success' :
               risk < 70 ? 'badge-warning' :
               'badge-error';
    }

    async function calculateCosts(formData) {
        showLoading();
        try {
            // Validate and prepare request data
            const value = parseFloat(formData.get('value'));
            const type = formData.get('type');
            
            if (!value || isNaN(value)) {
                throw new Error('Invalid vehicle value');
            }

            const requestData = {
                value: value,
                currency: formData.get('currency'),
                targetCurrency: formData.get('targetCurrency'),
                destination: formData.get('destination'),
                length: parseFloat(formData.get('length')),
                width: parseFloat(formData.get('width')),
                height: parseFloat(formData.get('height')),
                weight: parseFloat(formData.get('weight')),
                age: parseInt(formData.get('age')),
                type: type  // Changed from vehicle_type to type to match API expectation
            };

            // Debug log the request
            console.log('Sending request:', requestData);

            const response = await fetch('/car-project/public/api/calculator/import-cost.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(requestData)
            });

            // Debug log the response
            console.log('Response status:', response.status);
            const responseText = await response.text();
            console.log('Response text:', responseText);

            if (!response.ok) {
                throw new Error(`Server error: ${responseText}`);
            }

            const data = JSON.parse(responseText);
            if (!data.success) {
                throw new Error(data.error || 'Calculation failed');
            }

            updateUI(data.data, requestData);

        } catch (error) {
            console.error('Calculation error:', error);
            showError(error.message);
        } finally {
            hideLoading();
        }
    }

    function validateRequestData(data) {
        return data.value > 0 &&
               data.currency &&
               data.destination &&
               data.length > 0 &&
               data.width > 0 &&
               data.height > 0 &&
               data.weight > 0 &&
               data.age >= 0 &&
               data.vehicle_type;
    }

    function updateUI(data, requestData) {
        // Update currency displays
        basePrice.textContent = formatCurrency(requestData.value, requestData.currency);
        convertedAmount.textContent = formatCurrency(data.breakdown.base_price, data.currency);
        sourceCurrency.textContent = requestData.currency;
        
        if (document.getElementById('targetCurrencyDisplay')) {
            document.getElementById('targetCurrencyDisplay').textContent = data.currency;
        }

        // Update final cost
        if (finalCost) {
            finalCost.textContent = formatCurrency(data.total, data.currency);
        }

        // Update breakdown
        updateResults(data);
        
        // Show results
        resultsDiv.classList.remove('hidden');
    }

    // Form submission handler
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        await calculateCosts(formData);
    });

    // Currency change handlers
    currencySelect.addEventListener('change', function() {
        if (form.checkValidity()) {
            const formData = new FormData(form);
            calculateCosts(formData);
        }
    });

    if (targetCurrencySelect) {
        targetCurrencySelect.addEventListener('change', function() {
            if (form.checkValidity()) {
                const formData = new FormData(form);
                calculateCosts(formData);
            }
        });
    }

    // Add input validation handlers
    const numericInputs = form.querySelectorAll('input[type="number"]');
    numericInputs.forEach(input => {
        input.addEventListener('input', function() {
            if (this.value < 0) this.value = 0;
        });
    });

    // Initialize tooltips for currency information
    if (typeof tippy !== 'undefined') {
        tippy('[data-tippy-content]');
    }

    // Export functions for testing
    window.calculatorFunctions = {
        formatCurrency,
        updateResults,
        calculateCosts
    };
});
