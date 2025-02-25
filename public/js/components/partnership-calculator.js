document.addEventListener('DOMContentLoaded', function() {
    // Get all required elements
    const form = document.getElementById('partnership-form');
    const submitButton = form?.querySelector('button[type="submit"]');
    const resultsDiv = document.getElementById('partnership-results');
    const errorMessage = document.getElementById('error-message');

    // Early return if required elements aren't found
    if (!form || !submitButton) {
        console.error('Required partnership calculator elements not found');
        return;
    }

    // Get loading state elements
    const normalState = submitButton.querySelector('.normal-state');
    const loadingSpinner = submitButton.querySelector('.loading-spinner');

    function showLoading() {
        submitButton.disabled = true;
        normalState?.classList.add('hidden');
        loadingSpinner?.classList.remove('hidden');
    }

    function hideLoading() {
        submitButton.disabled = false;
        normalState?.classList.remove('hidden');
        loadingSpinner?.classList.add('hidden');
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

    async function calculatePartnership(formData) {
        showLoading();
        try {
            const requestData = {
                investment: parseFloat(formData.get('investment')),
                currency: formData.get('currency'),
                type: formData.get('type'),
                duration: parseInt(formData.get('duration')),
                profit_share: parseFloat(formData.get('profit_share'))
            };

            // Validate data
            if (!requestData.investment || !requestData.currency || !requestData.type || 
                !requestData.duration || !requestData.profit_share) {
                throw new Error('Please fill in all required fields');
            }

            const response = await fetch('/car-project/public/api/calculator/partnership-cost.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(requestData)
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.error || `HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            if (!data.success) {
                throw new Error(data.error || 'Calculation failed');
            }

            displayResults(data.data);

        } catch (error) {
            console.error('Partnership calculation error:', error);
            showError(error.message);
        } finally {
            hideLoading();
        }
    }

    function displayResults(data) {
        if (!resultsDiv) return;

        resultsDiv.innerHTML = `
            <div class="stats shadow">
                <div class="stat">
                    <div class="stat-title">Total Investment</div>
                    <div class="stat-value">${formatCurrency(data.total_investment, data.currency)}</div>
                </div>
                <div class="stat">
                    <div class="stat-title">Expected Return</div>
                    <div class="stat-value">${formatCurrency(data.expected_return, data.currency)}</div>
                </div>
                <div class="stat">
                    <div class="stat-title">Monthly Share</div>
                    <div class="stat-value">${formatCurrency(data.monthly_share, data.currency)}</div>
                </div>
            </div>

            <div class="mt-4 card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h3 class="card-title">Partnership Details</h3>
                    <div class="overflow-x-auto">
                        <table class="table table-zebra w-full">
                            <tbody>
                                <tr>
                                    <td class="font-semibold">Duration</td>
                                    <td class="text-right">${data.duration} months</td>
                                </tr>
                                <tr>
                                    <td class="font-semibold">Profit Share</td>
                                    <td class="text-right">${data.profit_share}%</td>
                                </tr>
                                <tr>
                                    <td class="font-semibold">Partnership Type</td>
                                    <td class="text-right capitalize">${data.type}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;

        resultsDiv.classList.remove('hidden');
    }

    // Form submission handler
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        if (this.checkValidity()) {
            await calculatePartnership(new FormData(this));
        } else {
            showError('Please fill in all required fields correctly');
        }
    });
});
