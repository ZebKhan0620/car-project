<?php
// Add composer autoloader
require_once __DIR__ . '/../../../vendor/autoload.php';

require_once __DIR__ . '/../../../src/Classes/Cars/CarListing.php';
require_once __DIR__ . '/../../../src/Classes/Cars/ImageHandler.php';
require_once __DIR__ . '/../../../src/helpers.php';

use Classes\Cars\CarListing;
use Classes\Cars\ImageHandler;
use Classes\Language\TranslationManager;
use Classes\Auth\Session;

// Initialize Session and TranslationManager
Session::getInstance()->start();
$translationManager = TranslationManager::getInstance();

// Verify AJAX request
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    exit('Direct access not allowed');
}

// Get car ID from request
$carId = $_GET['id'] ?? null;
if (!$carId) {
    http_response_code(400);
    exit('Car ID is required');
}

try {
    $carListing = new CarListing();
    $imageHandler = new ImageHandler();
    
    $car = $carListing->getById($carId);
    if (!$car) {
        http_response_code(404);
        exit(__('messages.car_not_found', [], 'messages'));
    }

    // Set default values for market data if not present
    $car['price_difference'] = $car['price_difference'] ?? 0;
    $car['market_avg'] = $car['market_avg'] ?? $car['price']; // Default to car's price if no market data
    $car['views'] = $car['views'] ?? 0;
    $car['features'] = $car['features'] ?? [];
    $car['warranty'] = $car['warranty'] ?? false;

    // Calculate price difference class
    $priceClass = $car['price_difference'] < 0 ? 'text-success' : ($car['price_difference'] > 0 ? 'text-error' : '');

    // Get main image and gallery with large versions for switching
    $mainImage = !empty($car['images']) ? $imageHandler->getImageUrl($car['images'][0], 'large') : '';
    $gallery = !empty($car['images']) ? array_slice($car['images'], 1, 4) : [];
    
    // Prepare all images data including large versions
    $allImages = array_map(function($img) use ($imageHandler) {
        return [
            'thumb' => $imageHandler->getImageUrl($img, 'thumbnail'),
            'large' => $imageHandler->getImageUrl($img, 'large')
        ];
    }, array_merge([$car['images'][0] ?? ''], $gallery));

    // Calculate market comparison percentage safely
    $marketComparisonPercentage = $car['market_avg'] > 0 
        ? min(100, max(0, (($car['price'] / $car['market_avg']) * 100))) 
        : 100;

    // Debug: Log image data
    echo '<script>console.log("Initial Images Data:", ' . json_encode($allImages) . ');</script>';

    echo '
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Left Column: Images -->
        <div class="space-y-4">
            <!-- Main Image -->
            <div class="relative rounded-xl overflow-hidden bg-base-200">
                <img id="quickViewMainImage" 
                     src="' . htmlspecialchars($mainImage) . '" 
                     alt="' . htmlspecialchars($car['title']) . '"
                     class="w-full aspect-[4/3] object-cover opacity-100" />
                
                <!-- Price Badge -->
                <div class="absolute top-4 right-4 bg-base-100/90 backdrop-blur-sm rounded-lg p-3 shadow-lg">
                    <div class="text-2xl font-bold text-accent">$' . number_format($car['price']) . '</div>
                    ' . ($car['price_difference'] !== 0 ? '
                    <div class="text-sm ' . $priceClass . '">
                        ' . ($car['price_difference'] > 0 ? '+' : '') . $car['price_difference'] . '% ' . __('cars.featured_cars.market_value.vs_market') . '
                    </div>
                    ' : '') . '
                </div>

                <!-- Status Badges -->
                <div class="absolute top-4 left-4 flex flex-col gap-2">
                    <span class="badge badge-lg ' . ($car['condition'] === 'New' ? 'badge-primary' : 'badge-ghost bg-base-100/90') . ' backdrop-blur-sm">
                        ' . __('cars.featured_cars.conditions.' . strtolower($car['condition'])) . '
                    </span>
                    ' . ($car['warranty'] ? '
                    <span class="badge badge-lg badge-secondary backdrop-blur-sm">' . __('cars.warranty') . '</span>
                    ' : '') . '
                </div>
            </div>

            <!-- Image Gallery -->
            <div id="imageGallery" class="grid grid-cols-4 gap-2">
                ' . implode('', array_map(function($img, $index) use ($car) {
                    return '
                    <div class="relative aspect-square rounded-lg overflow-hidden bg-base-200 cursor-pointer gallery-thumb' . ($index === 0 ? ' ring-2 ring-primary ring-offset-2' : '') . '"
                         data-large-img="' . htmlspecialchars($img['large']) . '"
                         data-index="' . $index . '">
                        <img src="' . htmlspecialchars($img['thumb']) . '" 
                             alt="' . htmlspecialchars($car['title']) . ' ' . __('cars.gallery_image') . '"
                             class="w-full h-full object-cover hover:opacity-90 transition-all duration-300" />
                    </div>';
                }, $allImages, array_keys($allImages))) . '
            </div>
        </div>

        <!-- Right Column: Details -->
        <div class="space-y-6">
            <!-- Title and Basic Info -->
            <div>
                <h3 class="text-2xl font-bold mb-2">' . htmlspecialchars($car['title']) . '</h3>
                <div class="flex flex-wrap gap-4 text-sm text-base-content/70">
                    <span class="flex items-center gap-1">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        ' . __('cars.listed_time', ['time' => timeAgo(strtotime($car['created_at']))]) . '
                    </span>
                    <span class="flex items-center gap-1">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        ' . __('cars.view_count', ['count' => number_format($car['views'])]) . '
                    </span>
                </div>
            </div>

            <!-- Key Specs Grid -->
            <div class="grid grid-cols-2 gap-3">
                <div class="flex items-center gap-3 bg-base-200/50 rounded-lg p-3">
                    <svg class="h-6 w-6 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <div>
                        <div class="text-sm text-base-content/70">' . __('cars.year') . '</div>
                        <div class="font-semibold">' . $car['year'] . '</div>
                    </div>
                </div>
                <div class="flex items-center gap-3 bg-base-200/50 rounded-lg p-3">
                    <svg class="h-6 w-6 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    <div>
                        <div class="text-sm text-base-content/70">' . __('cars.transmission') . '</div>
                        <div class="font-semibold">' . htmlspecialchars($car['transmission']) . '</div>
                    </div>
                </div>
                <div class="flex items-center gap-3 bg-base-200/50 rounded-lg p-3">
                    <svg class="h-6 w-6 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                        <div class="text-sm text-base-content/70">' . __('cars.mileage') . '</div>
                        <div class="font-semibold">' . number_format($car['mileage']) . ' km</div>
                    </div>
                </div>
                <div class="flex items-center gap-3 bg-base-200/50 rounded-lg p-3">
                    <svg class="h-6 w-6 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    </svg>
                    <div>
                        <div class="text-sm text-base-content/70">' . __('cars.location') . '</div>
                        <div class="font-semibold">' . htmlspecialchars($car['location']) . '</div>
                    </div>
                </div>
            </div>

            <!-- Market Value -->
            <div class="bg-base-200/50 rounded-lg p-4">
                <div class="flex justify-between items-center mb-3">
                    <span class="font-medium">' . __('cars.featured_cars.market_value.title') . '</span>
                    <span class="font-semibold">$' . number_format($car['market_avg']) . '</span>
                </div>
                <div class="w-full bg-base-300 rounded-full h-2.5 mb-2">
                    <div class="bg-primary h-2.5 rounded-full transition-all duration-300" 
                         style="width: ' . $marketComparisonPercentage . '%">
                    </div>
                </div>
                ' . ($car['price_difference'] !== 0 ? '
                <div class="text-sm text-base-content/70">
                    ' . __('cars.featured_cars.market_value.price_difference', [
                        'status' => $car['price_difference'] > 0 ? __('cars.featured_cars.market_value.above_market') : __('cars.featured_cars.market_value.below_market'),
                        'percentage' => abs($car['price_difference'])
                    ]) . '
                </div>
                ' : '
                <div class="text-sm text-base-content/70">
                    ' . __('cars.featured_cars.market_value.at_market_price') . '
                </div>
                ') . '
            </div>

            <!-- Features -->
            <div>
                <h4 class="font-semibold mb-3">' . __('cars.key_features') . '</h4>
                <div class="flex flex-wrap gap-2">
                    ' . implode('', array_map(function($feature) {
                        return '<span class="badge badge-outline">' . htmlspecialchars($feature) . '</span>';
                    }, $car['features'])) . '
                </div>
            </div>

            <!-- Description -->
            <div>
                <h4 class="font-semibold mb-2">' . __('cars.description') . '</h4>
                <p class="text-base-content/70 text-sm line-clamp-4">' . 
                    htmlspecialchars($car['description'] ?? __('cars.no_description')) . '
                </p>
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-3 pt-4 border-t border-base-200">
                <button onclick="window.featuredCars.handleCompareSelection({target:{checked:true,value:\'' . $car['id'] . '\'}})" 
                        class="btn btn-outline flex-1">
                    ' . __('cars.featured_cars.compare.add_car') . '
                </button>
                <a href="/car-project/public/Components/Cars/view.php?id=' . $car['id'] . '" 
                   class="btn btn-primary flex-1">
                    ' . __('cars.view_full_details') . '
                </a>
            </div>
        </div>
    </div>';

} catch (Exception $e) {
    error_log("Error in quick view for car ID {$carId}: " . $e->getMessage());
    http_response_code(500);
    echo '<div class="alert alert-error">' . __('messages.error') . '</div>';
}

function timeAgo($timestamp) {
    $difference = time() - $timestamp;
    
    if ($difference < 60) {
        return "just now";
    } elseif ($difference < 3600) {
        return floor($difference / 60) . " minutes ago";
    } elseif ($difference < 86400) {
        return floor($difference / 3600) . " hours ago";
    } elseif ($difference < 604800) {
        return floor($difference / 86400) . " days ago";
    } else {
        return date('M j, Y', $timestamp);
    }
} 