<?php
require_once __DIR__ . './../../../src/bootstrap.php';

use App\Services\ExchangeRateService;
use App\Services\CacheManager;
use Classes\Language\TranslationManager;

// Initialize translation manager
$translator = TranslationManager::getInstance();
$currentLocale = $translator->getLocale();

$cacheManager = new CacheManager();
$exchangeService = new ExchangeRateService($cacheManager);

// Get available currencies (you might want to store these in a config file)
$availableCurrencies = ['USD', 'AED', 'PHP'];

// Get available languages
$availableLocales = $translator->getAvailableLocales();
?>

<!DOCTYPE html>
<html lang="<?php echo $currentLocale; ?>" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo $translator->trans('cars.calculators.meta.description'); ?>">
    <meta name="keywords" content="<?php echo $translator->trans('cars.calculators.meta.keywords'); ?>">
    <title><?php echo $translator->trans('cars.calculators.meta.title'); ?> - <?php echo $translator->trans('common.meta.title'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@3.9.4/dist/full.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- <link rel="icon" type="image/x-icon" href="/car-project/public/assets/images/favicon.ico"> -->
</head>
<body class="min-h-screen bg-base-200">
    <div class="navbar bg-base-100 shadow-xl">
        <div class="flex-1">
            <a href="/car-project/public/index.php" class="btn btn-ghost normal-case text-xl">
                <?php echo $translator->trans('common.welcome'); ?>
            </a>
        </div>
        <div class="flex-none gap-2">
            <!-- Language Selector -->
            <div class="dropdown dropdown-end">
                <label tabindex="0" class="btn btn-ghost">
                    <?php echo strtoupper($currentLocale); ?>
                </label>
                <ul tabindex="0" class="dropdown-content z-[1] menu p-2 shadow bg-base-100 rounded-box">
                    <?php foreach ($availableLocales as $locale): ?>
                        <li>
                            <a href="?locale=<?php echo $locale; ?>" 
                               class="<?php echo $locale === $currentLocale ? 'active' : ''; ?>">
                                <?php echo strtoupper($locale); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <a href="#" class="btn btn-ghost" id="theme-toggle">🌓</a>
        </div>
    </div>

    <main class="container mx-auto px-4 py-8">
        <div class="tabs tabs-boxed justify-center mb-8">
            <a class="tab tab-active" data-tab="import">Import Calculator</a>
            <a class="tab" data-tab="partnership">Partnership Calculator</a>
            <a class="tab" data-tab="feasibility">Feasibility Analysis</a>
        </div>

        <!-- Error Alert -->
        <div id="error-message" class="alert alert-error hidden mb-4"></div>

        <!-- Loading Indicator -->
        <div id="loading-indicator" class="hidden flex justify-center my-4">
            <span class="loading loading-spinner loading-lg"></span>
        </div>

        <!-- Import Calculator -->
        <div id="import-calculator" class="calculator-section">
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title">Import Cost Calculator</h2>
                    <form id="calculatorForm" class="space-y-4" novalidate>
                        <!-- Add novalidate to handle validation in JS -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Vehicle Value & Currency -->
                            <div class="form-control">
                                <label class="label">Vehicle Value & Currency</label>
                                <div class="input-group">
                                    <select class="select select-bordered" name="currency" id="currency">
                                        <option value="USD">USD ($)</option>
                                        <option value="AED">AED (د.إ)</option>
                                        <option value="PHP">PHP (₱)</option>
                                    </select>
                                    <input type="number" name="value" class="input input-bordered w-full" required>
                                </div>
                            </div>

                            <!-- Target Currency -->
                            <div class="form-control">
                                <label class="label">Convert To</label>
                                <select class="select select-bordered w-full" name="targetCurrency" id="targetCurrency" required>
                                    <option value="USD">US Dollar ($)</option>
                                    <option value="AED">UAE Dirham (د.إ)</option>
                                    <option value="PHP">Philippine Peso (₱)</option>
                                </select>
                            </div>

                            <!-- Destination -->
                            <div class="form-control">
                                <label class="label">Destination Country</label>
                                <select class="select select-bordered w-full" name="destination" required>
                                    <option value="">Select Country</option>
                                    <option value="UAE">United Arab Emirates</option>
                                    <option value="PH">Philippines</option>
                                </select>
                            </div>

                            <!-- Vehicle Age -->
                            <div class="form-control">
                                <label class="label">Vehicle Age (Years)</label>
                                <input type="number" name="age" class="input input-bordered" min="0" required>
                            </div>

                            <!-- Vehicle Type -->
                            <div class="form-control">
                                <label class="label">Vehicle Type</label>
                                <select class="select select-bordered" name="type" required>
                                    <option value="">Select Type</option>
                                    <option value="sedan">Sedan</option>
                                    <option value="suv">SUV</option>
                                    <option value="truck">Truck</option>
                                </select>
                            </div>

                            <!-- Dimensions -->
                            <div class="form-control col-span-2">
                                <label class="label">Dimensions (meters)</label>
                                <div class="grid grid-cols-3 gap-2">
                                    <input type="number" name="length" placeholder="Length" class="input input-bordered" step="0.01" min="0" required>
                                    <input type="number" name="width" placeholder="Width" class="input input-bordered" step="0.01" min="0" required>
                                    <input type="number" name="height" placeholder="Height" class="input input-bordered" step="0.01" min="0" required>
                                </div>
                            </div>

                            <!-- Weight -->
                            <div class="form-control">
                                <label class="label">Weight (kg)</label>
                                <input type="number" name="weight" class="input input-bordered" min="0" required>
                            </div>
                        </div>

                        <div class="card-actions justify-end mt-4">
                            <button type="submit" class="btn btn-primary" id="calculate-btn">
                                <span class="normal-state">Calculate</span>
                                <span class="loading-spinner hidden">
                                    <span class="loading loading-spinner loading-sm"></span>
                                </span>
                            </button>
                            <!-- Remove onclick from print button and handle in JS -->
                            <button type="button" class="btn btn-outline" id="print-btn">Print</button>
                        </div>
                    </form>

                    <!-- Results Section -->
                    <div id="calculator-results" class="mt-6 hidden">
                        <div class="stats shadow">
                            <div class="stat">
                                <div class="stat-title">Original Amount</div>
                                <div id="basePrice" class="stat-value">-</div>
                                <div class="stat-desc"><span id="sourceCurrency">-</span></div>
                            </div>
                            <div class="stat">
                                <div class="stat-title">Converted Amount</div>
                                <div id="convertedAmount" class="stat-value">-</div>
                                <div id="targetCurrencyDisplay" class="stat-desc">-</div>
                            </div>
                            <div class="stat">
                                <div class="stat-title">Total Cost</div>
                                <div id="finalCost" class="stat-value">-</div>
                            </div>
                        </div>

                        <div class="overflow-x-auto mt-4">
                            <table class="table table-zebra w-full">
                                <thead>
                                    <tr>
                                        <th>Cost Component</th>
                                        <th class="text-right">Amount</th>
                                    </tr>
                                </thead>
                                <tbody id="costBreakdown">
                                    <!-- Breakdown items will be inserted here -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Partnership Calculator -->
        <div id="partnership-calculator" class="calculator-section hidden">
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title">Partnership Cost Calculator</h2>
                    <form id="partnership-form" class="space-y-4" novalidate>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Investment Amount -->
                            <div class="form-control">
                                <label class="label">Investment Amount</label>
                                <div class="input-group">
                                    <select class="select select-bordered" name="currency">
                                        <option value="USD">USD ($)</option>
                                        <option value="AED">AED (د.إ)</option>
                                        <option value="PHP">PHP (₱)</option>
                                    </select>
                                    <input type="number" name="investment" class="input input-bordered w-full" min="0" required>
                                </div>
                            </div>

                            <!-- Partnership Type -->
                            <div class="form-control">
                                <label class="label">Partnership Type</label>
                                <select class="select select-bordered" name="type" required>
                                    <option value="">Select Type</option>
                                    <option value="full">Full Partnership</option>
                                    <option value="silent">Silent Partner</option>
                                    <option value="limited">Limited Partnership</option>
                                </select>
                            </div>

                            <!-- Duration -->
                            <div class="form-control">
                                <label class="label">Duration (Months)</label>
                                <input type="number" name="duration" class="input input-bordered" min="1" required>
                            </div>

                            <!-- Profit Share -->
                            <div class="form-control">
                                <label class="label">Profit Share (%)</label>
                                <input type="number" name="profit_share" class="input input-bordered" min="1" max="100" required>
                            </div>
                        </div>

                        <!-- Fixed button structure to match JS expectations -->
                        <div class="card-actions justify-end mt-4">
                            <button type="submit" class="btn btn-primary" id="partnership-calculate-btn">
                                <span class="normal-state">Calculate Partnership</span>
                                <span class="loading-spinner hidden">
                                    <span class="loading loading-spinner loading-sm"></span>
                                </span>
                            </button>
                        </div>
                    </form>

                    <!-- Results Section -->
                    <div id="partnership-results" class="mt-8 hidden">
                        <!-- Results will be inserted here by JavaScript -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Feasibility Analysis -->
        <div id="feasibility-calculator" class="calculator-section hidden">
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title">Feasibility Analysis</h2>
                    <form id="feasibility-form" class="space-y-4">
                        <!-- Market Analysis -->
                        <div class="form-control">
                            <label class="label">Market Analysis</label>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <select name="market_size" class="select select-bordered" required>
                                    <option value="">Select Market Size</option>
                                    <option value="small">Small (Local)</option>
                                    <option value="medium">Medium (Regional)</option>
                                    <option value="large">Large (National)</option>
                                </select>
                                <select name="competition_level" class="select select-bordered" required>
                                    <option value="">Competition Level</option>
                                    <option value="low">Low Competition</option>
                                    <option value="medium">Medium Competition</option>
                                    <option value="high">High Competition</option>
                                </select>
                            </div>
                        </div>

                        <!-- Financial Inputs -->
                        <div class="form-control">
                            <label class="label">Financial Details</label>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <input type="number" name="initial_investment" 
                                       class="input input-bordered" 
                                       placeholder="Initial Investment" required>
                                <input type="number" name="expected_revenue" 
                                       class="input input-bordered" 
                                       placeholder="Expected Monthly Revenue" required>
                            </div>
                        </div>

                        <!-- Risk Factors -->
                        <div class="form-control">
                            <label class="label">Risk Assessment</label>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <select name="risk_level" class="select select-bordered" required>
                                    <option value="">Risk Level</option>
                                    <option value="low">Low Risk</option>
                                    <option value="medium">Medium Risk</option>
                                    <option value="high">High Risk</option>
                                </select>
                                <input type="number" name="risk_mitigation" 
                                       class="input input-bordered" 
                                       placeholder="Risk Mitigation Budget" required>
                            </div>
                        </div>

                        <div class="card-actions justify-end">
                            <button type="submit" class="btn btn-primary">
                                <span class="normal-state">Analyze Feasibility</span>
                                <span class="loading loading-spinner loading-sm hidden"></span>
                            </button>
                        </div>
                    </form>

                    <!-- Results Section -->
                    <div id="feasibility-results" class="mt-8 hidden">
                        <!-- Market Analysis Results -->
                        <div class="stats shadow mb-4">
                            <div class="stat">
                                <div class="stat-title">Growth Rate</div>
                                <div class="stat-value" id="growth-rate">0%</div>
                            </div>
                            <div class="stat">
                                <div class="stat-title">Competition Level</div>
                                <div class="stat-value" id="competition-level">-</div>
                            </div>
                            <div class="stat">
                                <div class="stat-title">Demand Score</div>
                                <div class="stat-value" id="demand-score">0</div>
                            </div>
                        </div>

                        <!-- Risk Assessment -->
                        <div class="card bg-base-100 shadow-xl mb-4">
                            <div class="card-body">
                                <h3 class="card-title">
                                    Risk Assessment
                                    <span id="risk-level" class="badge badge-lg">-</span>
                                </h3>
                                <progress id="risk-progress" class="progress w-full" value="0" max="100"></progress>
                                <div id="risk-factors" class="mt-4 space-y-2">
                                    <!-- Risk factors will be inserted here -->
                                </div>
                            </div>
                        </div>

                        <!-- Recommendation -->
                        <div id="recommendation" class="alert mt-4">
                            <!-- Recommendation will be inserted here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Add debug logging -->
    <script>
        window.onerror = function(msg, url, lineNo, columnNo, error) {
            console.error('Error: ' + msg + '\nURL: ' + url + '\nLine: ' + lineNo);
            document.getElementById('error-message').textContent = msg;
            document.getElementById('error-alert').classList.remove('hidden');
            return false;
        };
    </script>

    <!-- Load dependencies in correct order -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="/car-project/public/js/calculator.js"></script>
    <script src="/car-project/public/js/utils/error-handler.js"></script>
    <script src="/car-project/public/js/utils/api-helper.js"></script>
    <script src="/car-project/public/js/components/partnership-calculator.js"></script>
    <script src="/car-project/public/js/components/feasibility-analysis.js"></script>
    <script src="/car-project/public/js/main.js"></script>

    <script>
        // Theme toggler
        document.getElementById('theme-toggle').addEventListener('click', function() {
            const html = document.querySelector('html');
            html.dataset.theme = html.dataset.theme === 'light' ? 'dark' : 'light';
        });

        // Tab switching
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', function(e) {
                e.preventDefault(); // Add this line
                
                // Update active tab
                document.querySelectorAll('.tab').forEach(t => t.classList.remove('tab-active'));
                this.classList.add('tab-active');

                const targetId = `${this.dataset.tab}-calculator`;
                console.log('Switching to:', targetId); // Add debug

                // Show corresponding calculator
                document.querySelectorAll('.calculator-section').forEach(section => {
                    if (section.id === targetId) {
                        section.classList.remove('hidden');
                    } else {
                        section.classList.add('hidden');

                    }
                });
            });
        });
        
        // Add form submission debug
        const feasibilityForm = document.getElementById('feasibility-form');
        if (feasibilityForm) {
            feasibilityForm.addEventListener('submit', function(e) {
                console.log('Feasibility form submitted');
            });
        }
    </script>
</body>
</html>

