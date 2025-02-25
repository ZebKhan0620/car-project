<?php

namespace Components\Hero;

require_once __DIR__ . '/../../../src/Classes/Cars/CarListing.php';
require_once __DIR__ . '/../../../src/Classes/Search/SearchTracker.php';

use Classes\Cars\CarListing;
use Classes\Search\SearchTracker;
use Classes\Language\TranslationManager;

class Hero {
    private $carListing;
    private $searchTracker;
    private $translationManager;

    public function __construct() {
        $this->carListing = new CarListing();
        $this->searchTracker = new SearchTracker();
        $this->translationManager = TranslationManager::getInstance();
    }

    public function render() {
        $featuredCar = $this->getFeaturedCar();
        
        return '
        <div class="hero min-h-[600px] bg-base-200" style="background-image: url(\'/car-project/public/assets/images/hero-bg.jpg\');">
            <div class="hero-overlay bg-opacity-60"></div>
            <div class="hero-content flex-col lg:flex-row-reverse gap-8 max-w-7xl">
                <!-- Right Side - Featured Car Preview -->
                <div class="w-full lg:w-1/2" id="hero-car-preview">
                    ' . $this->renderCarCard($featuredCar) . '
                </div>

                <!-- Left Side - Search Content -->
                <div class="w-full lg:w-1/2 text-left">
                    <div class="max-w-xl">
                        <h1 class="mb-5 text-5xl font-bold">' . __('hero.title') . '</h1>
                        <p class="mb-8 text-lg opacity-90">' . __('hero.description') . '</p>
                        
                        <!-- Quick Search Form -->
                        <form id="heroSearchForm" class="join join-vertical w-full gap-2">
                            <div class="join-item w-full">
                                <input type="text" 
                                       name="search" 
                                       placeholder="' . __('hero.search.placeholder') . '" 
                                       class="input input-bordered w-full" />
                            </div>
                            <div class="flex gap-2">
                                <select name="location" class="select select-bordered flex-1">
                                    <option value="">' . __('hero.search.all_locations') . '</option>
                                    ' . $this->renderLocations() . '
                                </select>
                                <button type="submit" class="btn btn-primary">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                    </svg>
                                    ' . __('hero.search.button') . '
                                </button>
                            </div>
                        </form>
                        
                        <!-- Popular Searches -->
                        ' . $this->renderPopularSearches() . '

                        <!-- Quick Stats -->
                        <div class="stats stats-vertical lg:stats-horizontal shadow mt-8 bg-base-200 bg-opacity-50">
                            <div class="stat">
                                <div class="stat-title">' . __('hero.stats.listings.title') . '</div>
                                <div class="stat-value">' . __('hero.stats.listings.value') . '</div>
                            </div>
                            <div class="stat">
                                <div class="stat-title">' . __('hero.stats.dealers.title') . '</div>
                                <div class="stat-value">' . __('hero.stats.dealers.value') . '</div>
                            </div>
                            <div class="stat">
                                <div class="stat-title">' . __('hero.stats.customers.title') . '</div>
                                <div class="stat-value">' . __('hero.stats.customers.value') . '</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>';
    }

    private function getFeaturedCar() {
        try {
            $listings = $this->carListing->getAll();
            
            // First try to get featured and active cars
            $featuredCars = array_filter($listings, function($car) {
                return isset($car['featured']) && $car['featured'] === true 
                    && isset($car['status']) && $car['status'] === 'active';
            });
            
            // If no featured cars, try to get any active car
            if (empty($featuredCars)) {
                $activeCars = array_filter($listings, function($car) {
                    return (!isset($car['status']) || $car['status'] === 'active');
                });
                
                if (!empty($activeCars)) {
                    // Get the most recent active car
                    usort($activeCars, function($a, $b) {
                        $dateA = strtotime($a['created_at'] ?? '0');
                        $dateB = strtotime($b['created_at'] ?? '0');
                        return $dateB - $dateA;
                    });
                    return array_values($activeCars)[0];
                }
            }
            
            // If we found featured cars, return the first one
            if (!empty($featuredCars)) {
                return array_values($featuredCars)[0];
            }
            
            // If no active cars at all, just get the most recent car regardless of status
            if (!empty($listings)) {
                usort($listings, function($a, $b) {
                    $dateA = strtotime($a['created_at'] ?? '0');
                    $dateB = strtotime($b['created_at'] ?? '0');
                    return $dateB - $dateA;
                });
                return array_values($listings)[0];
            }
            
            return null;
            
        } catch (\Exception $e) {
            error_log("Error getting featured car: " . $e->getMessage());
            return null;
        }
    }

    public function renderCarCard($car) {
        if (!$car) {
            return $this->renderEmptyCard();
        }

        $imagePath = '/car-project/public/assets/images/default-car.jpg';
        if (isset($car['images']) && !empty($car['images'])) {
            $fullPath = __DIR__ . '/../../uploads/car_images/' . $car['images'][0];
            if (file_exists($fullPath)) {
                $imagePath = '/car-project/public/uploads/car_images/' . $car['images'][0];
            }
        }

        // Add status badge if car is not active
        $statusBadge = '';
        if (isset($car['status']) && $car['status'] !== 'active') {
            $statusClass = match($car['status']) {
                'pending' => 'badge-warning',
                'sold' => 'badge-error',
                'inactive' => 'badge-ghost',
                default => 'badge-ghost'
            };
            $statusBadge = '<span class="badge ' . $statusClass . ' ml-2">' . ucfirst($car['status']) . '</span>';
        }

        return '
        <div class="relative">
            <div class="card glass">
                <figure class="px-4 pt-4">
                    <img src="' . htmlspecialchars($imagePath) . '" 
                         class="rounded-xl h-64 w-full object-cover" 
                         alt="' . htmlspecialchars($car['title']) . '" />
                    <div class="absolute top-6 right-6 flex gap-2">
                        ' . (isset($car['featured']) && $car['featured'] ? '<span class="badge badge-primary">' . __('hero.car_card.featured') . '</span>' : '') . '
                        ' . $statusBadge . '
                    </div>
                </figure>
                <div class="card-body p-4">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="card-title text-lg">' . htmlspecialchars($car['title']) . '</h3>
                            <p class="text-sm opacity-80">' . htmlspecialchars($car['condition']) . '</p>
                        </div>
                        <div class="text-right">
                            <div class="text-2xl font-bold text-primary">$' . number_format($car['price']) . '</div>
                            <div class="text-sm opacity-80">' . __('hero.car_card.available') . '</div>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4 mt-4">
                        ' . $this->renderSpecItem('Power', $car['horsepower'] ?? '400 HP') . '
                        ' . $this->renderSpecItem('Transmission', $car['transmission']) . '
                        ' . $this->renderSpecItem('Fuel', $car['fuel_type']) . '
                        ' . $this->renderSpecItem('Location', $car['location']) . '
                    </div>
                    <div class="card-actions justify-end mt-4">
                        <a href="/car-project/public/Components/Cars/view.php?id=' . $car['id'] . '" 
                           class="btn btn-primary btn-sm">' . __('hero.car_card.view_details') . '</a>
                    </div>
                </div>
            </div>
            <div class="absolute -top-4 -right-4 w-24 h-24 bg-primary/20 rounded-full blur-xl"></div>
            <div class="absolute -bottom-4 -left-4 w-32 h-32 bg-secondary/20 rounded-full blur-xl"></div>
        </div>';
    }

    public function renderEmptyCard() {
        return '
        <div class="relative">
            <div class="card glass">
                <div class="card-body p-8 text-center">
                    <svg class="mx-auto h-16 w-16 text-base-content/30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <h3 class="mt-4 text-lg font-semibold">' . __('hero.car_card.no_cars.title') . '</h3>
                    <p class="mt-2 text-sm opacity-70">' . __('hero.car_card.no_cars.description') . '</p>
                </div>
            </div>
            <div class="absolute -top-4 -right-4 w-24 h-24 bg-primary/20 rounded-full blur-xl"></div>
            <div class="absolute -bottom-4 -left-4 w-32 h-32 bg-secondary/20 rounded-full blur-xl"></div>
        </div>';
    }

    public function renderSpecItem($label, $value) {
        return '
        <div class="flex items-center gap-2">
            <svg class="w-4 h-4 opacity-70" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
            <span class="text-sm">' . htmlspecialchars($value) . '</span>
        </div>';
    }

    private function renderLocations() {
        $locations = [
            'Dubai',
            'Japan',
            'Philippines'
        ];
        
        $options = '';
        foreach ($locations as $location) {
            $selected = isset($_GET['location']) && $_GET['location'] === $location ? ' selected' : '';
            $options .= "<option value=\"{$location}\"{$selected}>{$location}</option>";
        }
        return $options;
    }

    public function renderPopularSearches() {
        $popularSearches = $this->searchTracker->getPopularSearches();
        
        $links = '';
        foreach ($popularSearches as $term => $count) {
            $links .= sprintf(
                '<a href="javascript:void(0)" onclick="quickSearch(\'%s\')" 
                    class="link link-hover text-sm">%s</a>',
                htmlspecialchars($term),
                htmlspecialchars(ucfirst($term))
            );
        }

        return '
        <div class="mt-6 flex flex-wrap gap-4 popular-searches">
            <span class="text-sm opacity-90">' . __('hero.popular.label') . '</span>
            ' . ($links ?: '
            <span class="text-sm opacity-70">' . __('hero.popular.no_searches') . '</span>
            ') . '
        </div>';
    }
}