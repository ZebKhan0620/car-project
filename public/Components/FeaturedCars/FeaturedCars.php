<?php

namespace Components\FeaturedCars;

require_once __DIR__ . '/../../../src/Classes/Cars/CarListing.php';
require_once __DIR__ . '/../../../src/Classes/User/Favorites.php';
require_once __DIR__ . '/../../../src/Classes/Cars/ImageHandler.php';
require_once __DIR__ . '/../../../src/Classes/Cars/SearchFilter.php';

use Classes\Cars\CarListing;
use Classes\Cars\ImageHandler;
use Classes\Cars\SearchFilter;
use Classes\Language\TranslationManager;
use User\Favorites;

class FeaturedCars
{
    private $carListing;
    private $favorites;
    private $imageHandler;
    private $userId;
    private $searchFilter;
    private $translationManager;

    public function __construct()
    {
        try {
            $this->carListing = new CarListing();
            $this->favorites = new Favorites();
            $this->imageHandler = new ImageHandler();
            $this->searchFilter = new SearchFilter();
            $this->translationManager = TranslationManager::getInstance();
            
            // Get user ID from Session class with improved security
            $session = \Classes\Auth\Session::getInstance();
            if (!$session->isActive()) {
                $session->start();
            }
            $session->regenerate();
            $this->userId = $session->get('user_id');
        } catch (\Exception $e) {
            error_log("Error initializing FeaturedCars: " . $e->getMessage());
        }
    }

    public function render()
    {
        try {
            // Get search parameters with enhanced filtering
            $params = [
                'brand' => $_GET['brand'] ?? '',
                'condition' => $_GET['condition'] ?? '',
                'min_price' => $_GET['min_price'] ?? '',
                'max_price' => $_GET['max_price'] ?? '',
                'min_year' => $_GET['min_year'] ?? '',
                'max_year' => $_GET['max_year'] ?? '',
                'transmission' => $_GET['transmission'] ?? '',
                'fuel_type' => $_GET['fuel_type'] ?? '',
                'sort' => $_GET['sort'] ?? 'latest'
            ];

            // Get filtered listings
            $allListings = $this->searchFilter->filter($params);

            // Sort listings
            $allListings = $this->sortListings($allListings, $params['sort']);

            // Filter featured cars
            $featuredCars = array_filter($allListings, function ($car) {
                return (!isset($car['featured']) || $car['featured'] === true);
            });

            // Limit to 8 featured cars
            $featuredCars = array_slice($featuredCars, 0, 8);

            // Get user's favorites if logged in
            $userFavorites = $this->userId ? $this->favorites->getFavorites($this->userId) : [];
            $favoritedIds = array_column($userFavorites, 'car_id');

            // Calculate market values and price trends
            $this->calculateMarketValues($featuredCars);

            return $this->renderContent($featuredCars, $favoritedIds, count($allListings));
        } catch (\Exception $e) {
            error_log("Error in FeaturedCars::render: " . $e->getMessage());
            return $this->renderError();
        }
    }

    private function calculateMarketValues(&$cars) {
        if (empty($cars)) return;
        
        try {
            $marketData = $this->getMarketData();
            foreach ($cars as &$car) {
                if (!isset($car['id'])) continue;
                
                $similarCars = $this->findSimilarCars($car, $marketData);
                $marketStats = $this->calculateMarketStatistics($car, $similarCars);
                
                // Safely assign values with defaults
                $car['market_value'] = $marketStats['average'] ?? $car['price'] ?? 0;
                $car['price_trend'] = $marketStats['trend'] ?? 'stable';
                $car['price_difference'] = $marketStats['difference'] ?? 0;
                $car['comparison'] = $marketStats['comparison'] ?? [];
            }
        } catch (\Exception $e) {
            error_log("Error calculating market values: " . $e->getMessage());
            // Set default values on error
            foreach ($cars as &$car) {
                $car['market_value'] = $car['price'] ?? 0;
                $car['price_trend'] = 'stable';
                $car['price_difference'] = 0;
                $car['comparison'] = [];
            }
        }
    }

    private function getMarketData() {
        $cacheFile = __DIR__ . '/../../cache/market_data.cache';
        $cacheExpiry = 3600; // 1 hour

        if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $cacheExpiry)) {
            return json_decode(file_get_contents($cacheFile), true);
        }

        $allListings = $this->carListing->getAll();
        $marketData = [];

        foreach ($allListings as $listing) {
            $key = $listing['brand'] . '_' . $listing['model'];
            if (!isset($marketData[$key])) {
                $marketData[$key] = [];
            }
            $marketData[$key][] = [
                'price' => $listing['price'],
                'year' => $listing['year'],
                'mileage' => $listing['mileage'],
                'condition' => $listing['condition']
            ];
        }

        file_put_contents($cacheFile, json_encode($marketData));
        return $marketData;
    }

    private function findSimilarCars($car, $marketData) {
        $key = $car['brand'] . '_' . $car['model'];
        if (!isset($marketData[$key])) {
            return [];
        }

        return array_filter($marketData[$key], function($similar) use ($car) {
            // More sophisticated similarity criteria
            $yearDiff = abs($similar['year'] - $car['year']);
            $mileageDiff = abs($similar['mileage'] - $car['mileage']) / $car['mileage'];
            $sameCondition = strtolower($similar['condition']) === strtolower($car['condition']);

            // Weight different factors
            return $yearDiff <= 3 && // Within 3 years
                   $mileageDiff <= 0.3 && // Within 30% mileage difference
                   $sameCondition; // Same condition
        });
    }

    private function calculateMarketStatistics($car, $similarCars) {
        if (empty($similarCars)) {
            return [
                'average' => $car['price'] ?? 0,
                'median' => $car['price'] ?? 0,
                'difference' => 0,
                'trend' => 'stable',
                'comparison' => []
            ];
        }

        try {
            // Get prices and sort them
            $prices = array_column($similarCars, 'price');
            sort($prices);

            // Remove outliers (prices outside 1.5 IQR)
            $q1 = $prices[floor(count($prices) * 0.25)];
            $q3 = $prices[floor(count($prices) * 0.75)];
            $iqr = $q3 - $q1;
            $validPrices = array_filter($prices, function($price) use ($q1, $q3, $iqr) {
                return $price >= ($q1 - 1.5 * $iqr) && $price <= ($q3 + 1.5 * $iqr);
            });

            if (empty($validPrices)) {
                return [
                    'average' => $car['price'] ?? 0,
                    'median' => $car['price'] ?? 0,
                    'difference' => 0,
                    'trend' => 'stable',
                    'comparison' => []
                ];
            }

            // Calculate statistics
            $average = array_sum($validPrices) / count($validPrices);
            $median = $validPrices[floor(count($validPrices) / 2)];
            
            // Calculate price difference as percentage
            $carPrice = $car['price'] ?? 0;
            $difference = $average > 0 ? round(($carPrice - $average) / $average * 100, 1) : 0;

            // Determine market trend
            $trend = 'stable';
            if ($difference < -10) $trend = 'below_market';
            if ($difference > 10) $trend = 'above_market';

            return [
                'average' => $average,
                'median' => $median,
                'difference' => $difference,
                'trend' => $trend,
                'comparison' => [
                    'total' => count($similarCars),
                    'median' => $median,
                    'lowest' => min($validPrices),
                    'highest' => max($validPrices)
                ]
            ];
        } catch (\Exception $e) {
            error_log("Error in calculateMarketStatistics: " . $e->getMessage());
            return [
                'average' => $car['price'] ?? 0,
                'median' => $car['price'] ?? 0,
                'difference' => 0,
                'trend' => 'stable',
                'comparison' => []
            ];
        }
    }

    private function calculateConditionScore($car) {
        try {
        $score = 100;
            $currentYear = (int)date('Y');
            
            // Base deductions
            $yearsPenalty = min(40, ($currentYear - $car['year']) * 2); // Max 40 points for age
            $mileagePenalty = min(30, floor($car['mileage'] / 10000) * 2); // Max 30 points for mileage
            
            // Condition-based deduction
            $conditionPenalties = [
                'new' => 0,
                'like new' => 5,
                'excellent' => 10,
                'good' => 20,
                'fair' => 30,
                'poor' => 40
            ];
            
            $conditionPenalty = $conditionPenalties[strtolower($car['condition'])] ?? 40;
            
            // Calculate final score
            $score -= ($yearsPenalty + $mileagePenalty + $conditionPenalty);
            
            // Additional factors
            if (isset($car['warranty']) && $car['warranty']) {
                $score += 10; // Bonus for warranty
            }
            
            // Ensure score is between 0 and 100
        return max(0, min(100, $score));
            
        } catch (\Exception $e) {
            error_log("Error calculating condition score: " . $e->getMessage());
            return 50; // Default score on error
        }
    }

    private function sortListings($listings, $sortBy)
    {
        if (empty($listings)) return [];
        
        try {
            $sortedListings = $listings;
            switch ($sortBy) {
                case 'price_low':
                    usort($sortedListings, function($a, $b) {
                        return ($a['price'] ?? 0) <=> ($b['price'] ?? 0);
                    });
                    break;
                case 'price_high':
                    usort($sortedListings, function($a, $b) {
                        return ($b['price'] ?? 0) <=> ($a['price'] ?? 0);
                    });
                    break;
                case 'year_new':
                    usort($sortedListings, function($a, $b) {
                        return ($b['year'] ?? 0) <=> ($a['year'] ?? 0);
                    });
                    break;
                case 'popularity':
                    usort($sortedListings, function($a, $b) {
                        return ($b['views'] ?? 0) <=> ($a['views'] ?? 0);
                    });
                    break;
                case 'latest':
                default:
                    usort($sortedListings, function($a, $b) {
                        return strtotime($b['created_at'] ?? 'now') <=> strtotime($a['created_at'] ?? 'now');
                    });
                    break;
            }
            return $sortedListings;
        } catch (\Exception $e) {
            error_log("Error sorting listings: " . $e->getMessage());
            return $listings; // Return unsorted on error
        }
    }

    private function renderContent($featuredCars, $favoritedIds, $totalCount)
    {
        return '
        <section class="py-12 bg-base-100">
            <div class="container mx-auto px-4">
                <!-- Header Section with Enhanced UI -->
                <div class="flex flex-col md:flex-row justify-between items-center gap-4 mb-8">
                    <div class="text-center md:text-left">
                        <h2 class="text-3xl font-bold font-heading mb-2">' . __('cars.featured_cars.title') . '</h2>
                        <p class="text-base-content/70 text-sm">
                            ' . __('cars.featured_cars.subtitle') . '
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2 items-center">
                        ' . $this->renderViewToggle() . '
                        ' . ($this->userId ? '
                        <a href="/car-project/public/Components/Cars/add-listing.php" 
                           class="btn btn-primary btn-sm normal-case">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            ' . __('cars.add_listing') . '
                        </a>
                        ' : '') . '
                        ' . $this->renderSortDropdown() . '
                    </div>
                </div>
    
                <!-- Enhanced Quick Search with Advanced Filters -->
                ' . $this->renderAdvancedSearch() . '
    
                <!-- Featured Cars Grid with View Toggle -->
                <div id="carsGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4 gap-4 transition-all duration-300">
                    ' . $this->renderFeaturedCards($featuredCars, $favoritedIds) . '
                </div>
    
                <!-- Compare Selection Bar -->
                <div id="compareBar" class="fixed bottom-0 left-0 right-0 bg-base-100/95 backdrop-blur-sm shadow-lg transform translate-y-full transition-transform duration-300 border-t border-base-200 z-50">
                    <div class="container mx-auto px-4 py-3">
                        <div class="flex items-center justify-between gap-4">
                            <!-- Left side: Title and Counter -->
                            <div class="flex items-center gap-2">
                                <h4 class="font-semibold">' . __('cars.featured_cars.compare.title') . '</h4>
                                <span class="badge badge-primary" id="compareCounter">0</span>
                            </div>

                            <!-- Middle: Car slots -->
                            <div class="flex-1 grid grid-cols-3 gap-3 max-w-3xl mx-auto">
                                <div class="car-slot rounded-lg bg-base-200/50 p-2 min-h-[80px] flex items-center justify-center" data-slot="1">
                                    <div class="text-center text-base-content/50">
                                        <span class="text-2xl">+</span>
                                        <p class="text-xs">' . __('cars.featured_cars.compare.add_car') . ' 1</p>
                                    </div>
                                </div>
                                <div class="car-slot rounded-lg bg-base-200/50 p-2 min-h-[80px] flex items-center justify-center" data-slot="2">
                                    <div class="text-center text-base-content/50">
                                        <span class="text-2xl">+</span>
                                        <p class="text-xs">' . __('cars.featured_cars.compare.add_car') . ' 2</p>
                                    </div>
                                </div>
                                <div class="car-slot rounded-lg bg-base-200/50 p-2 min-h-[80px] flex items-center justify-center" data-slot="3">
                                    <div class="text-center text-base-content/50">
                                        <span class="text-2xl">+</span>
                                        <p class="text-xs">' . __('cars.featured_cars.compare.add_car') . ' 3</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Right side: Compare button -->
                            <button onclick="window.featuredCars.showComparison()" 
                                    class="btn btn-primary btn-sm normal-case min-w-[120px]" 
                                    id="compareButton" 
                                    disabled>
                                ' . __('cars.featured_cars.compare.compare_now') . '
                            </button>
                        </div>
                    </div>
                </div>
    
                <!-- View All Section with Stats -->
                <div class="flex flex-col md:flex-row justify-between items-center gap-4 mt-8 pt-4 border-t">
                    <div class="text-sm text-base-content/70">
                        ' . str_replace(
                            ['{count}', '{total}'],
                            [count($featuredCars), $totalCount],
                            __('cars.featured_cars.stats.showing_results')
                        ) . '
                    </div>
                    <div class="flex gap-2">
                        <a href="/car-project/public/Components/Cars/search.php" 
                           class="btn btn-primary btn-sm normal-case">
                            ' . __('cars.featured_cars.stats.browse_all') . '
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Quick View Modal -->
        <dialog id="quickViewModal" class="modal">
            <div class="modal-box w-11/12 max-w-5xl">
                <div id="quickViewContent"></div>
            </div>
            <form method="dialog" class="modal-backdrop">
                <button>close</button>
            </form>
        </dialog>

        <!-- Comparison Modal -->
        <dialog id="comparisonModal" class="modal">
            <div class="modal-box w-11/12 max-w-7xl">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-bold">' . __('cars.featured_cars.compare.title') . '</h3>
                    <form method="dialog">
                        <button class="btn btn-sm btn-circle btn-ghost">✕</button>
                    </form>
                </div>
                <div id="comparisonContent" class="min-h-[400px]">
                    <!-- Content will be loaded dynamically -->
                </div>
            </div>
            <form method="dialog" class="modal-backdrop">
                <button>close</button>
            </form>
        </dialog>
        
        <!-- Initialize JavaScript -->
        <script>
            // Wait for both DOM and featured-cars.js to be loaded
            function initializePage() {
                if (typeof featuredCars !== "undefined" && typeof featuredCars.initializeFeaturedCars === "function") {
                    featuredCars.initializeFeaturedCars();
                } else {
                    // If the function is not available yet, wait and try again
                    setTimeout(initializePage, 100);
                }
            }

            // Start initialization when DOM is ready
            if (document.readyState === "loading") {
                document.addEventListener("DOMContentLoaded", initializePage);
            } else {
                initializePage();
            }
        </script>';
    }

    private function renderViewToggle() {
        return '
        <div class="btn-group">
            <button onclick="window.featuredCars.setViewMode(\'grid\')" class="btn btn-sm btn-outline" data-view="grid" title="' . __('cars.featured_cars.view_toggle.grid') . '">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                </svg>
            </button>
            <button onclick="window.featuredCars.setViewMode(\'list\')" class="btn btn-sm btn-outline" data-view="list" title="' . __('cars.featured_cars.view_toggle.list') . '">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
        </div>';
    }

    private function renderAdvancedSearch() {
        return '
        <div class="mb-8">
            <form id="advancedSearch" class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4 p-6 bg-base-200 rounded-box">
                <!-- Brand Selection -->
                <div class="form-control">
                    <label class="label"><span class="label-text">' . __('cars.featured_cars.filters.brand') . '</span></label>
                    <select name="brand" class="select select-bordered w-full">
                        <option value="">' . __('cars.featured_cars.filters.all_brands') . '</option>
                        ' . $this->renderBrandOptions($_GET['brand'] ?? '') . '
                    </select>
                </div>

                <!-- Price Range -->
                <div class="form-control">
                    <label class="label"><span class="label-text">' . __('cars.featured_cars.filters.price_range') . '</span></label>
                    <div class="flex gap-2">
                        <input type="number" name="min_price" placeholder="' . __('cars.featured_cars.filters.min_price') . '" 
                               class="input input-bordered w-full" 
                               value="' . ($_GET['min_price'] ?? '') . '">
                        <input type="number" name="max_price" placeholder="' . __('cars.featured_cars.filters.max_price') . '" 
                               class="input input-bordered w-full"
                               value="' . ($_GET['max_price'] ?? '') . '">
                    </div>
                </div>

                <!-- Year Range -->
                <div class="form-control">
                    <label class="label"><span class="label-text">' . __('cars.featured_cars.filters.year_range') . '</span></label>
                    <div class="flex gap-2">
                        <input type="number" name="min_year" placeholder="' . __('cars.featured_cars.filters.year_from') . '" 
                               class="input input-bordered w-full"
                               value="' . ($_GET['min_year'] ?? '') . '">
                        <input type="number" name="max_year" placeholder="' . __('cars.featured_cars.filters.year_to') . '" 
                               class="input input-bordered w-full"
                               value="' . ($_GET['max_year'] ?? '') . '">
                    </div>
                </div>

                <!-- Condition -->
                <div class="form-control">
                    <label class="label"><span class="label-text">' . __('cars.featured_cars.filters.condition') . '</span></label>
                    <select name="condition" class="select select-bordered w-full">
                        <option value="">' . __('cars.featured_cars.filters.any_condition') . '</option>
                        ' . $this->renderConditionOptions($_GET['condition'] ?? '') . '
                    </select>
                </div>

                <!-- Action Buttons -->
                <div class="md:col-span-3 lg:col-span-4 flex justify-end gap-2">
                    <button type="button" class="btn btn-ghost btn-sm" id="resetFilters">' . __('cars.featured_cars.filters.reset') . '</button>
                    <button type="button" class="btn btn-primary btn-sm" id="applyFilters">' . __('cars.featured_cars.filters.apply') . '</button>
                </div>
            </form>
        </div>';
    }

    private function renderCard($car, $isFavorited)
    {
        try {
            $imagePath = $this->imageHandler->getImageUrl($car['images'][0] ?? '', 'medium');
            
            $escapedData = array_map(function($value) {
                return is_string($value) ? htmlspecialchars($value, ENT_QUOTES, 'UTF-8') : $value;
            }, $car);

            $favoriteClass = $isFavorited ? 'text-red-500 fill-current' : '';
            $priceClass = $escapedData['price_difference'] < 0 ? 'text-success' : ($escapedData['price_difference'] > 0 ? 'text-error' : '');
            $timestamp = strtotime($escapedData['created_at'] ?? 'now');

            return '
            <div class="car-card card bg-base-100 shadow-xl hover:shadow-2xl transition-all duration-300 group flex flex-col"
                 data-car-id="' . $escapedData['id'] . '"
                 data-price="' . $escapedData['price'] . '"
                 data-year="' . $escapedData['year'] . '"
                 data-views="' . ($escapedData['views'] ?? 0) . '"
                 data-timestamp="' . $timestamp . '"
                 data-title="' . $escapedData['title'] . '"
                 data-image="' . $imagePath . '">
                
                <!-- Enhanced Image Container -->
                <figure class="relative w-full pt-[60%] overflow-hidden">
                    <img src="' . $imagePath . '" 
                         alt="' . $escapedData['title'] . '"
                         class="absolute inset-0 w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-500 lazy" 
                         loading="lazy"
                         data-src="' . $imagePath . '"
                         onerror="this.onerror=null; this.src=\'' . $this->imageHandler->getImageUrl('', 'medium') . '\';" />
                    
                    <!-- Price Badge -->
                    <div class="absolute top-4 right-4 bg-base-100/90 backdrop-blur-sm rounded-lg p-2 shadow-lg">
                        <div class="text-lg font-bold text-accent">$' . number_format($escapedData['price']) . '</div>
                        <div class="text-xs ' . $priceClass . ' text-right">
                            ' . ($escapedData['price_difference'] > 0 ? '+' : '') . $escapedData['price_difference'] . '% vs market
                        </div>
                    </div>

                    <!-- Action Overlay -->
                    <div class="absolute inset-0 bg-base-100/40 backdrop-blur-[2px] opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center gap-3">
                        <button class="quick-view-btn btn btn-primary btn-sm">
                            ' . __('cars.featured_cars.compare.quick_view') . '
                        </button>
                        <label class="flex items-center gap-2 bg-base-100/90 rounded-lg px-3 py-2">
                            <input type="checkbox" class="compare-checkbox checkbox checkbox-sm checkbox-primary" value="' . $escapedData['id'] . '">
                            <span class="text-sm">' . __('cars.featured_cars.compare.title') . '</span>
                        </label>
                    </div>

                    <!-- Status Badges -->
                    <div class="absolute top-4 left-4 flex flex-col gap-2">
                        <span class="badge badge-lg ' . ($escapedData['condition'] === 'New' ? 'badge-primary' : 'badge-ghost bg-base-100/90') . ' backdrop-blur-sm">
                            ' . $escapedData['condition'] . '
                        </span>
                        ' . ($escapedData['warranty'] ? '
                        <span class="badge badge-lg badge-secondary backdrop-blur-sm">Warranty</span>
                        ' : '') . '
                        ' . ($escapedData['price_difference'] < -5 ? '
                        <span class="badge badge-lg badge-success backdrop-blur-sm">' . __('cars.featured_cars.market_value.good_deal') . '</span>
                        ' : '') . '
                    </div>
                </figure>

                <div class="card-body p-4">
                    <!-- Title -->
                    <h3 class="card-title text-lg font-heading line-clamp-2 min-h-[3.5rem]">
                        ' . $escapedData['title'] . '
                    </h3>

                    <!-- Key Specs Grid -->
                    <div class="grid grid-cols-2 gap-3 my-3">
                        <div class="flex items-center gap-2 bg-base-200/50 rounded-lg p-2">
                            <svg class="h-5 w-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span class="font-medium">' . $escapedData['year'] . '</span>
                        </div>
                        <div class="flex items-center gap-2 bg-base-200/50 rounded-lg p-2">
                            <svg class="h-5 w-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            <span class="font-medium">' . $escapedData['transmission'] . '</span>
                        </div>
                        <div class="flex items-center gap-2 bg-base-200/50 rounded-lg p-2">
                            <svg class="h-5 w-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="font-medium">' . number_format($escapedData['mileage']) . ' km</span>
                        </div>
                        <div class="flex items-center gap-2 bg-base-200/50 rounded-lg p-2">
                            <svg class="h-5 w-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            </svg>
                            <span class="font-medium truncate">' . $escapedData['location'] . '</span>
                        </div>
                    </div>

                    <!-- Market Value -->
                    <div class="bg-base-200/50 rounded-lg p-3 mb-3">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-sm font-medium">' . __('cars.featured_cars.market_value.title') . '</span>
                            <span class="font-semibold">$' . number_format($escapedData['market_value']) . '</span>
                        </div>
                        <div class="w-full bg-base-300 rounded-full h-2">
                            <div class="bg-primary h-2 rounded-full transition-all duration-300" 
                                 style="width: ' . min(100, max(0, (($escapedData['price'] / $escapedData['market_value']) * 100))) . '%">
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="card-actions justify-between items-center mt-auto pt-2 border-t border-base-200">
                        <button class="btn btn-ghost btn-sm gap-2 ' . $favoriteClass . '" 
                                onclick="featuredCars.toggleFavorite(' . $escapedData['id'] . ')">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                      d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                            </svg>
                            ' . __('cars.featured_cars.compare.save') . '
                        </button>
                        <a href="/car-project/public/Components/Cars/view.php?id=' . $escapedData['id'] . '" 
                           class="btn btn-primary btn-sm gap-2">
                            ' . __('hero.car_card.view_details') . '
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>';
        } catch (\Exception $e) {
            error_log("Error rendering car card: " . $e->getMessage());
            return '<div class="alert alert-error">' . __('messages.error') . '</div>';
        }
    }

    private function renderErrorCard() {
        return '
        <div class="card bg-base-100 shadow-xl">
            <div class="card-body">
                <h3 class="card-title text-error">Error Loading Car</h3>
                <p>Unable to display this car listing. Please try again later.</p>
            </div>
        </div>';
    }

    private function renderCompareBar() {
        return '
        <div class="fixed bottom-0 left-0 right-0 bg-base-100 border-t shadow-lg transform translate-y-full transition-transform duration-300 z-50" id="compareBar">
            <div class="container mx-auto px-4 py-4">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div class="flex flex-col md:flex-row items-start md:items-center gap-4 w-full md:w-auto">
                        <div class="flex justify-between items-center w-full md:w-auto">
                            <h4 class="font-semibold">Compare Vehicles (<span id="compareCounter">0</span>)</h4>
                            <button onclick="window.featuredCars.closeCompareBar()" 
                                    class="btn btn-ghost btn-sm btn-square md:hidden">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        <div id="compareContent" class="grid grid-cols-1 sm:grid-cols-2 md:flex gap-4 w-full md:w-auto">
                            <div class="car-slot bg-base-200/50 rounded p-2 w-full md:w-48 h-20 flex items-center justify-center cursor-pointer">
                                <div class="text-center text-base-content/50">
                                    <span class="text-2xl">+</span>
                                    <p class="text-xs">Add Car 1</p>
                                </div>
                            </div>
                            <div class="car-slot bg-base-200/50 rounded p-2 w-full md:w-48 h-20 flex items-center justify-center cursor-pointer">
                                <div class="text-center text-base-content/50">
                                    <span class="text-2xl">+</span>
                                    <p class="text-xs">Add Car 2</p>
                                </div>
                            </div>
                            <div class="car-slot bg-base-200/50 rounded p-2 w-full md:w-48 h-20 flex items-center justify-center cursor-pointer">
                                <div class="text-center text-base-content/50">
                                    <span class="text-2xl">+</span>
                                    <p class="text-xs">Add Car 3</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 w-full md:w-auto justify-end">
                        <button onclick="window.featuredCars.clearCompare()" 
                                class="btn btn-ghost btn-sm">
                            Clear All
                        </button>
                        <button onclick="window.featuredCars.showComparison()" 
                                id="compareButton"
                                class="btn btn-primary btn-sm" 
                                disabled>
                            Compare Now
                        </button>
                        <button onclick="window.featuredCars.closeCompareBar()" 
                                class="btn btn-ghost btn-sm btn-square hidden md:flex">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>';
    }

    private function renderEmptyState()
    {
        return '
        <div class="col-span-full flex flex-col items-center justify-center p-8 text-center">
            <svg class="w-16 h-16 text-base-content/30 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
            </svg>
            <h3 class="text-lg font-semibold mb-2">' . __('hero.car_card.no_cars.title') . '</h3>
            <p class="text-base-content/70 mb-4">' . __('hero.car_card.no_cars.description') . '</p>
            <a href="/car-project/public/Components/Cars/add-listing.php" class="btn btn-primary btn-sm">
                ' . __('cars.add_listing') . '
            </a>
        </div>';
    }

    private function renderBrandOptions($selected = '')
    {
        $options = '';
        foreach (CarListing::BRANDS as $brand => $models) {
            $brand = ucfirst($brand);
            $isSelected = strtolower($brand) === strtolower($selected) ? ' selected' : '';
            $options .= sprintf(
                '<option value="%s"%s>%s</option>',
                htmlspecialchars($brand),
                $isSelected,
                htmlspecialchars($brand)
            );
        }
        return $options;
    }

    private function renderConditionOptions($selected = '')
    {
        $conditionTranslations = [
            'new' => __('cars.featured_cars.conditions.new'),
            'like new' => __('cars.featured_cars.conditions.like_new'),
            'excellent' => __('cars.featured_cars.conditions.excellent'),
            'good' => __('cars.featured_cars.conditions.good'),
            'fair' => __('cars.featured_cars.conditions.fair'),
            'poor' => __('cars.featured_cars.conditions.poor')
        ];

        $options = '';
        foreach (CarListing::CONDITIONS as $condition) {
            $isSelected = strtolower($condition) === strtolower($selected) ? ' selected' : '';
            $translatedCondition = $conditionTranslations[strtolower($condition)] ?? $condition;
            $options .= sprintf(
                '<option value="%s"%s>%s</option>',
                htmlspecialchars($condition),
                $isSelected,
                htmlspecialchars($translatedCondition)
            );
        }
        return $options;
    }

    private function renderError()
    {
        return '
        <div class="alert alert-error shadow-lg max-w-2xl mx-auto my-8">
            <div>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <span>Error loading featured cars. Please try again later.</span>
            </div>
        </div>';
    }

    private function renderFeaturedCards($cars, $favoritedIds)
    {
        $cards = '';
        foreach ($cars as $car) {
            $cards .= $this->renderCard($car, in_array($car['id'], $favoritedIds));
        }
        return $cards ?: $this->renderEmptyState();
    }

    private function renderSortDropdown()
    {
        $sortOptions = [
            'latest' => __('cars.featured_cars.sort.latest'),
            'price_low' => __('cars.featured_cars.sort.price_low'),
            'price_high' => __('cars.featured_cars.sort.price_high'),
            'year_new' => __('cars.featured_cars.sort.year_new'),
            'popularity' => __('cars.featured_cars.sort.popularity')
        ];

        $currentSort = $_GET['sort'] ?? 'latest';
        $options = '';
        
        foreach ($sortOptions as $value => $label) {
            $selected = $value === $currentSort ? ' selected' : '';
            $options .= "<option value=\"{$value}\"{$selected}>{$label}</option>";
        }

        return '
        <div class="form-control">
            <select name="sort" class="select select-bordered select-sm">
                ' . $options . '
            </select>
        </div>';
    }

    private function renderFeaturedCar($car) {
        // Add default values for potentially missing keys
        $car = array_merge([
            'model' => 'N/A',
            'condition' => 'Used',
            'warranty' => 'No Warranty',
            'mileage' => 0,
            'price' => 0,
            'title' => 'Untitled',
            'brand' => 'Unknown',
            'year' => date('Y'),
            'transmission' => 'N/A',
            'fuel_type' => 'N/A',
            'location' => 'N/A',
            'images' => ['default.jpg'],
            'id' => '0'
        ], $car);

        return '
        <div class="card bg-base-100 shadow-xl hover:shadow-2xl transition-shadow duration-300">
            <figure class="relative aspect-[16/9] overflow-hidden">
                <img src="/car-project/public/uploads/cars/' . htmlspecialchars($car['images'][0]) . '" 
                     alt="' . htmlspecialchars($car['title']) . '"
                     class="w-full h-full object-cover"
                     onerror="this.src=\'/car-project/public/assets/images/car-placeholder.jpg\'" />
                <div class="absolute top-4 right-4 flex gap-2">
                    ' . $this->renderConditionBadge($car['condition']) . '
                </div>
            </figure>
            <div class="card-body p-6">
                <div class="flex justify-between items-start mb-2">
                    <div>
                        <h3 class="card-title text-lg">
                            ' . htmlspecialchars($car['brand']) . ' ' . htmlspecialchars($car['model']) . '
                        </h3>
                        <p class="text-sm text-base-content/70">' . htmlspecialchars($car['year']) . '</p>
                    </div>
                    <div class="text-right">
                        <p class="text-lg font-bold text-primary">
                            $' . number_format($car['price'], 2) . '
                        </p>
                        ' . ($car['warranty'] ? '<span class="text-xs text-success">✓ ' . htmlspecialchars($car['warranty']) . '</span>' : '') . '
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 my-4">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-base-content/70" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="text-sm">' . number_format($car['mileage']) . ' km</span>
                    </div>
                    <!-- ... rest of your code ... -->
                </div>
            </div>
        </div>';
    }

    private function renderConditionBadge($condition) {
        $badgeClass = match(strtolower($condition)) {
            'new' => 'badge-success',
            'like new' => 'badge-info',
            'excellent' => 'badge-primary',
            'good' => 'badge-warning',
            'fair' => 'badge-error',
            default => 'badge-ghost'
        };

        return '<div class="badge ' . $badgeClass . ' badge-lg">' . htmlspecialchars($condition) . '</div>';
    }
}
