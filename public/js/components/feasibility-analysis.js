document.addEventListener('DOMContentLoaded', function() {
    const MAX_RETRIES = 3;
    const RETRY_DELAY = 1000;

    // Add rate limiting and retry mechanism
    async function fetchWithRetry(url, options, retries = MAX_RETRIES) {
        try {
            const response = await fetch(url, options);
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            return await response.json();
        } catch (error) {
            if (retries > 0) {
                await new Promise(resolve => setTimeout(resolve, RETRY_DELAY));
                return fetchWithRetry(url, options, retries - 1);
            }
            throw error;
        }
    }

    // Add input validation
    function validateFormData(formData) {
        const validations = {
            initial_investment: value => value > 0 || 'Investment must be greater than 0',
            expected_revenue: value => value >= 0 || 'Revenue cannot be negative',
            risk_mitigation: value => value >= 0 || 'Risk mitigation budget cannot be negative'
        };

        const errors = [];
        for (const [field, validator] of Object.entries(validations)) {
            const value = Number(formData.get(field));
            if (isNaN(value)) {
                errors.push(`${field.replace('_', ' ')} must be a number`);
            } else {
                const result = validator(value);
                if (typeof result === 'string') errors.push(result);
            }
        }
        return errors;
    }

    async function updateFeasibilityResults(data) {
        try {
            // Validate input data
            if (!data || typeof data !== 'object') {
                throw new Error('Invalid data format');
            }

            // Verify all required elements exist
            const requiredElements = [
                'growth-rate',
                'competition-level',
                'demand-score',
                'risk-level',
                'risk-progress',
                'risk-factors',
                'recommendation'
            ];

            const missingElements = requiredElements.filter(id => !document.getElementById(id));
            if (missingElements.length > 0) {
                throw new Error(`Missing HTML elements: ${missingElements.join(', ')}`);
            }

            // Validate required objects
            const requiredObjects = ['market_analysis', 'risk_assessment', 'feasibility_score'];
            for (const obj of requiredObjects) {
                if (!data[obj]) {
                    throw new Error(`Missing required data: ${obj}`);
                }
            }

            // Sanitize numeric inputs
            const sanitizeNumber = (num) => {
                const parsed = parseFloat(num);
                return isNaN(parsed) ? 0 : Math.max(0, Math.min(1, parsed));
            };

            // Update UI with error handling
            try {
                // Market Analysis
                document.getElementById('growth-rate').textContent = data.market_analysis.growth_potential;
                document.getElementById('competition-level').textContent = data.market_analysis.competition_level;
                document.getElementById('demand-score').textContent = data.market_analysis.demand_rating;

                // Risk Assessment
                const riskLevel = document.getElementById('risk-level');
                riskLevel.textContent = data.risk_assessment.risk_level;
                riskLevel.className = `badge badge-lg ${getRiskBadgeColor(data.risk_assessment.risk_level)}`;
                
                // Risk Progress Bar
                const riskProgress = document.getElementById('risk-progress');
                riskProgress.value = data.risk_assessment.overall_risk_score * 100;
                riskProgress.className = `progress ${getRiskProgressColor(data.risk_assessment.overall_risk_score)}`;

                // Risk Factors
                const riskFactors = document.getElementById('risk-factors');
                riskFactors.innerHTML = Object.entries(data.risk_assessment.risk_factors)
                    .map(([factor, score]) => `
                        <div class="flex justify-between items-center">
                            <span class="capitalize">${factor.replace('_', ' ')}</span>
                            <span class="badge ${getRiskBadgeColor(score)}">${(score * 100).toFixed(1)}%</span>
                        </div>
                    `).join('');

                // Recommendation
                const recommendation = document.getElementById('recommendation');
                recommendation.className = `alert ${getRecommendationColor(data.feasibility_score)}`;
                recommendation.innerHTML = `
                    <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <span class="font-bold">Feasibility Score: ${(data.feasibility_score * 100).toFixed(1)}%</span>
                        <p>${data.recommendation}</p>
                    </div>
                `;

                document.getElementById('feasibility-results').classList.remove('hidden');
            } catch (uiError) {
                console.error('UI Update Error:', uiError);
                showErrorMessage('Failed to update display');
                return;
            }

        } catch (error) {
            console.error('Feasibility Analysis Error:', error);
            showErrorMessage(error.message);
        }
    }

    function showErrorMessage(message) {
        const resultsDiv = document.getElementById('feasibility-results');
        resultsDiv.innerHTML = `
            <div class="alert alert-error">
                <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>${message}</span>
            </div>
        `;
        resultsDiv.classList.remove('hidden');
    }

    function getRiskBadgeColor(level) {
        if (typeof level === 'string') {
            return {
                'Low': 'badge-success',
                'Medium': 'badge-warning',
                'High': 'badge-error'
            }[level] || 'badge-info';
        }
        // For numeric values
        return level < 0.3 ? 'badge-success' : 
               level < 0.6 ? 'badge-warning' : 
               'badge-error';
    }

    function getRiskProgressColor(score) {
        return score < 0.3 ? 'progress-success' :
               score < 0.6 ? 'progress-warning' :
               'progress-error';
    }

    function getRecommendationColor(score) {
        return score > 0.7 ? 'alert-success' :
               score > 0.4 ? 'alert-warning' :
               'alert-error';
    }

    // Make functions globally available
    window.updateFeasibilityResults = updateFeasibilityResults;

    const feasibilityForm = document.getElementById('feasibility-form');
    const resultsDiv = document.getElementById('feasibility-results');
    const errorContainer = document.getElementById('error-message');

    if (!feasibilityForm) {
        console.error('Feasibility form not found');
        return;
    }

    feasibilityForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        hideError();
        showLoading();

        try {
            const formData = new FormData(this);
            
            // Validate inputs
            const validationErrors = validateFormData(formData);
            if (validationErrors.length > 0) {
                throw new Error(validationErrors.join(', '));
            }

            const formValues = {
                market_size: formData.get('market_size'),
                competition_level: formData.get('competition_level'),
                initial_investment: Number(formData.get('initial_investment')),
                expected_revenue: Number(formData.get('expected_revenue')),
                risk_level: formData.get('risk_level'),
                risk_mitigation: Number(formData.get('risk_mitigation'))
            };

            // Use retry mechanism
            const data = await fetchWithRetry('/car-project/public/api/calculator/feasibility-analysis.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formValues)
            });
            
            if (!data.success) {
                throw new Error(data.error || 'Analysis failed');
            }
            
            await updateFeasibilityResults(data);
            
        } catch (error) {
            showError(error.message);
            console.error('Feasibility analysis error:', error);
        } finally {
            hideLoading();
        }
    });

    // Add debounce for error messages
    let errorTimeout;
    function showError(message) {
        if (errorContainer) {
            clearTimeout(errorTimeout);
            errorContainer.textContent = message;
            errorContainer.classList.remove('hidden');
            errorTimeout = setTimeout(() => {
                errorContainer.classList.add('hidden');
            }, 5000);
        }
    }

    function hideError() {
        if (errorContainer) errorContainer.classList.add('hidden');
    }

    function showLoading() {
        const button = feasibilityForm.querySelector('button[type="submit"]');
        button.disabled = true;
        button.querySelector('.normal-state').classList.add('hidden');
        button.querySelector('.loading').classList.remove('hidden');
    }

    function hideLoading() {
        const button = feasibilityForm.querySelector('button[type="submit"]');
        button.disabled = false;
        button.querySelector('.normal-state').classList.remove('hidden');
        button.querySelector('.loading').classList.add('hidden');
    }
});
