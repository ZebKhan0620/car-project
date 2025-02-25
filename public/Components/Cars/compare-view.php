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
    exit(__('messages.direct_access_denied'));
}

// Get car IDs from POST data
$data = json_decode(file_get_contents('php://input'), true);
$carIds = $data['carIds'] ?? [];

if (empty($carIds)) {
    http_response_code(400);
    exit(__('cars.compare.no_cars_selected'));
}

try {
    $carListing = new CarListing();
    $imageHandler = new ImageHandler();
    
    // Helper function to safely get car property with default value
    function getCarProperty($car, $property, $default = '') {
        if (strpos($property, '.') !== false) {
            // Handle nested properties (e.g., 'specifications.horsepower')
            $parts = explode('.', $property);
            $value = $car;
            foreach ($parts as $part) {
                if (!isset($value[$part])) {
                    return $default;
                }
                $value = $value[$part];
            }
            return $value;
        }
        return isset($car[$property]) ? $car[$property] : $default;
    }

    // Helper function to format number with default
    function formatNumber($number, $default = '0') {
        return $number ? number_format($number) : $default;
    }

    // Helper function to calculate market average
    function calculateMarketAverage($cars) {
        $prices = array_column($cars, 'price');
        return !empty($prices) ? array_sum($prices) / count($prices) : 0;
    }

    // Helper function to calculate condition score
    function calculateConditionScore($car) {
        $conditionScores = [
            'New' => 100,
            'Like New' => 90,
            'Excellent' => 80,
            'Good' => 70,
            'Fair' => 60
        ];
        return $conditionScores[$car['condition']] ?? 60;
    }

    // Get car details with validation
    $cars = array_map(function($id) use ($carListing) {
        $car = $carListing->getById($id);
        if (!$car) return null;
        
        // Calculate market average for all cars
        $marketAvg = calculateMarketAverage($carListing->getAll());
        
        // Calculate price difference percentage
        $priceDiff = $marketAvg > 0 ? round((($car['price'] - $marketAvg) / $marketAvg) * 100) : 0;
        
        // Set default values for all properties
        return array_merge([
            'price_difference' => $priceDiff,
            'market_avg' => $marketAvg,
            'condition_score' => calculateConditionScore($car),
            'exterior_color' => $car['color'] ?? __('common.not_available'),
            'engine' => getCarProperty($car, 'specifications.engine_size', __('common.not_available')),
            'horsepower' => getCarProperty($car, 'specifications.horsepower', __('common.not_available')),
            'fuel_economy' => __('common.not_available'),
            'drive_type' => __('common.not_available'),
            'seating_capacity' => getCarProperty($car, 'specifications.seats', __('common.not_available')),
            'cargo_space' => __('common.not_available'),
            'length' => __('common.not_available'),
            'width' => __('common.not_available'),
            'height' => __('common.not_available'),
            'wheelbase' => __('common.not_available'),
            'safety_features' => array_filter($car['features'] ?? [], function($feature) {
                return in_array($feature, [
                    'ABS', 'Airbags', 'Traction Control', 'Stability Control', 
                    'Parking Sensors', 'Backup Camera', 'Lane Departure Warning'
                ]);
            }),
            'features' => array_filter($car['features'] ?? [], function($feature) {
                return !in_array($feature, [
                    'ABS', 'Airbags', 'Traction Control', 'Stability Control', 
                    'Parking Sensors', 'Backup Camera', 'Lane Departure Warning'
                ]);
            })
        ], $car);
    }, $carIds);
    
    // Filter out any null values
    $cars = array_filter($cars);
    
    if (empty($cars)) {
        throw new Exception(__('cars.compare.no_valid_cars'));
    }
    
    // Calculate key differences
    $differences = [];
    
    // Price difference
    $prices = array_column($cars, 'price');
    $minPrice = min($prices);
    $maxPrice = max($prices);
    if ($maxPrice - $minPrice > 0) {
        $differences[] = __('cars.compare.differences.price', [
            'difference' => number_format($maxPrice - $minPrice)
        ]);
    }
    
    // Mileage difference
    $mileages = array_column($cars, 'mileage');
    $minMileage = min($mileages);
    $maxMileage = max($mileages);
    if ($maxMileage - $minMileage > 1000) {
        $differences[] = __('cars.compare.differences.mileage', [
            'difference' => number_format($maxMileage - $minMileage)
        ]);
    }
    
    // Year difference
    $years = array_column($cars, 'year');
    $minYear = min($years);
    $maxYear = max($years);
    if ($maxYear - $minYear > 0) {
        $differences[] = __('cars.compare.differences.year_range', [
            'from' => $minYear,
            'to' => $maxYear
        ]);
    }

    // Output comparison view
    echo '
    <div class="space-y-8">
        <!-- Key Differences -->
        <div class="bg-base-200/50 rounded-box p-4">
            <h4 class="font-semibold mb-3">' . __('cars.compare.key_differences') . '</h4>
            <ul class="space-y-2">
                ' . (!empty($differences) ? implode('', array_map(function($diff) {
                    return '<li class="flex items-center gap-2">
                        <span class="text-primary">●</span>
                        ' . htmlspecialchars($diff) . '
                    </li>';
                }, $differences)) : '<li class="text-base-content/70">' . __('cars.compare.no_differences') . '</li>') . '
            </ul>
        </div>

        <!-- Overview -->
        <div>
            <h4 class="font-semibold mb-4">' . __('cars.compare.overview') . '</h4>
            <div class="overflow-x-auto">
                <table class="table table-zebra w-full">
                    <thead>
                        <tr>
                            <th>' . __('cars.compare.vehicle') . '</th>
                            ' . implode('', array_map(function($car) use ($imageHandler) {
                                return '<th class="text-center">
                                    <div class="flex flex-col items-center gap-2">
                                        <img src="' . $imageHandler->getImageUrl($car['images'][0] ?? '', 'thumbnail') . '" 
                                             alt="' . htmlspecialchars($car['title']) . '"
                                             class="w-20 h-16 object-cover rounded">
                                        <div class="font-semibold">' . htmlspecialchars($car['title']) . '</div>
                                        <div class="text-sm text-base-content/70">' . htmlspecialchars($car['brand']) . ' ' . htmlspecialchars($car['model']) . '</div>
                                    </div>
                                </th>';
                            }, $cars)) . '
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>' . __('cars.price') . '</td>
                            ' . implode('', array_map(function($car) {
                                return '<td class="text-center">
                                    <div class="font-semibold">$' . formatNumber(getCarProperty($car, 'price')) . '</div>
                                    <div class="text-xs text-' . (getCarProperty($car, 'price_difference', 0) < 0 ? 'success' : (getCarProperty($car, 'price_difference', 0) > 0 ? 'error' : 'base-content/70')) . '">
                                        ' . (getCarProperty($car, 'price_difference', 0) > 0 ? '+' : '') . getCarProperty($car, 'price_difference', 0) . '% ' . __('cars.featured_cars.market_value.vs_market') . '
                                    </div>
                                </td>';
                            }, $cars)) . '
                        </tr>
                        <tr>
                            <td>' . __('cars.compare.market_value') . '</td>
                            ' . implode('', array_map(function($car) {
                                return '<td class="text-center">
                                    <div class="font-semibold">$' . formatNumber(getCarProperty($car, 'market_avg', 0)) . '</div>
                                    <div class="text-xs text-base-content/70">' . __('cars.compare.market_average') . '</div>
                                </td>';
                            }, $cars)) . '
                        </tr>
                        <tr>
                            <td>' . __('cars.compare.condition_score') . '</td>
                            ' . implode('', array_map(function($car) {
                                $score = getCarProperty($car, 'condition_score', 0);
                                $colorClass = $score >= 80 ? 'success' : ($score >= 60 ? 'warning' : 'error');
                                return '<td class="text-center">
                                    <div class="radial-progress text-' . $colorClass . '" style="--value:' . $score . '; --size:2.5rem;">
                                        <span class="text-xs">' . $score . '</span>
                                    </div>
                                </td>';
                            }, $cars)) . '
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Basic Information -->
        <div>
            <h4 class="font-semibold mb-4">' . __('cars.compare.basic_information') . '</h4>
            <div class="overflow-x-auto">
                <table class="table table-zebra w-full">
                    <tbody>
                        <tr>
                            <td>' . __('cars.year') . '</td>
                            ' . implode('', array_map(function($car) {
                                return '<td class="text-center">' . $car['year'] . '</td>';
                            }, $cars)) . '
                        </tr>
                        <tr>
                            <td>' . __('cars.mileage') . '</td>
                            ' . implode('', array_map(function($car) {
                                return '<td class="text-center">' . number_format($car['mileage']) . ' km</td>';
                            }, $cars)) . '
                        </tr>
                        <tr>
                            <td>' . __('cars.transmission') . '</td>
                            ' . implode('', array_map(function($car) {
                                return '<td class="text-center">' . htmlspecialchars($car['transmission']) . '</td>';
                            }, $cars)) . '
                        </tr>
                        <tr>
                            <td>' . __('cars.condition') . '</td>
                            ' . implode('', array_map(function($car) {
                                return '<td class="text-center">
                                    <span class="badge ' . ($car['condition'] === 'New' ? 'badge-primary' : 'badge-ghost') . '">
                                        ' . __('cars.featured_cars.conditions.' . strtolower($car['condition'])) . '
                                    </span>
                                </td>';
                            }, $cars)) . '
                        </tr>
                        <tr>
                            <td>' . __('cars.location') . '</td>
                            ' . implode('', array_map(function($car) {
                                return '<td class="text-center">' . htmlspecialchars($car['location']) . '</td>';
                            }, $cars)) . '
                        </tr>
                        <tr>
                            <td>' . __('cars.compare.body_type') . '</td>
                            ' . implode('', array_map(function($car) {
                                return '<td class="text-center">' . htmlspecialchars($car['body_type']) . '</td>';
                            }, $cars)) . '
                        </tr>
                        <tr>
                            <td>' . __('cars.compare.seating_capacity') . '</td>
                            ' . implode('', array_map(function($car) {
                                return '<td class="text-center">' . htmlspecialchars(getCarProperty($car, 'specifications.seats')) . ' ' . __('cars.compare.seats') . '</td>';
                            }, $cars)) . '
                        </tr>
                        <tr>
                            <td>' . __('cars.compare.color') . '</td>
                            ' . implode('', array_map(function($car) {
                                return '<td class="text-center">
                                    <div class="flex flex-col items-center gap-1">
                                        <span>' . htmlspecialchars($car['color']) . '</span>
                                        <div class="w-6 h-6 rounded border" style="background-color: ' . htmlspecialchars($car['color']) . ';"></div>
                                    </div>
                                </td>';
                            }, $cars)) . '
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Engine & Performance -->
        <div>
            <h4 class="font-semibold mb-4">' . __('cars.compare.engine_performance') . '</h4>
            <div class="overflow-x-auto">
                <table class="table table-zebra w-full">
                    <tbody>
                        <tr>
                            <td>' . __('cars.compare.engine') . '</td>
                            ' . implode('', array_map(function($car) {
                                return '<td class="text-center">' . htmlspecialchars(getCarProperty($car, 'specifications.engine_size')) . '</td>';
                            }, $cars)) . '
                        </tr>
                        <tr>
                            <td>' . __('cars.compare.horsepower') . '</td>
                            ' . implode('', array_map(function($car) {
                                return '<td class="text-center">' . htmlspecialchars(getCarProperty($car, 'specifications.horsepower')) . ' hp</td>';
                            }, $cars)) . '
                        </tr>
                        <tr>
                            <td>' . __('cars.compare.fuel_type') . '</td>
                            ' . implode('', array_map(function($car) {
                                return '<td class="text-center">' . htmlspecialchars($car['fuel_type']) . '</td>';
                            }, $cars)) . '
                        </tr>
                        <tr>
                            <td>' . __('cars.compare.fuel_economy') . '</td>
                            ' . implode('', array_map(function($car) {
                                return '<td class="text-center">' . htmlspecialchars(getCarProperty($car, 'fuel_economy')) . ' km/L</td>';
                            }, $cars)) . '
                        </tr>
                        <tr>
                            <td>' . __('cars.compare.drive_type') . '</td>
                            ' . implode('', array_map(function($car) {
                                return '<td class="text-center">' . htmlspecialchars(getCarProperty($car, 'drive_type')) . '</td>';
                            }, $cars)) . '
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Dimensions & Capacity -->
        <div>
            <h4 class="font-semibold mb-4">' . __('cars.compare.dimensions_capacity') . '</h4>
            <div class="overflow-x-auto">
                <table class="table table-zebra w-full">
                    <tbody>
                        <tr>
                            <td>' . __('cars.compare.cargo_space') . '</td>
                            ' . implode('', array_map(function($car) {
                                return '<td class="text-center">' . htmlspecialchars(getCarProperty($car, 'cargo_space')) . ' L</td>';
                            }, $cars)) . '
                        </tr>
                        <tr>
                            <td>' . __('cars.compare.length') . '</td>
                            ' . implode('', array_map(function($car) {
                                return '<td class="text-center">' . htmlspecialchars(getCarProperty($car, 'length')) . ' mm</td>';
                            }, $cars)) . '
                        </tr>
                        <tr>
                            <td>' . __('cars.compare.width') . '</td>
                            ' . implode('', array_map(function($car) {
                                return '<td class="text-center">' . htmlspecialchars(getCarProperty($car, 'width')) . ' mm</td>';
                            }, $cars)) . '
                        </tr>
                        <tr>
                            <td>' . __('cars.compare.height') . '</td>
                            ' . implode('', array_map(function($car) {
                                return '<td class="text-center">' . htmlspecialchars(getCarProperty($car, 'height')) . ' mm</td>';
                            }, $cars)) . '
                        </tr>
                        <tr>
                            <td>' . __('cars.compare.wheelbase') . '</td>
                            ' . implode('', array_map(function($car) {
                                return '<td class="text-center">' . htmlspecialchars(getCarProperty($car, 'wheelbase')) . ' mm</td>';
                            }, $cars)) . '
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Safety Features -->
        <div>
            <h4 class="font-semibold mb-4">' . __('cars.compare.safety_features') . '</h4>
            <div class="overflow-x-auto">
                <table class="table table-zebra w-full">
                    <tbody>
                        ' . implode('', array_map(function($feature) use ($cars) {
                            $translationKey = in_array($feature, [
                                'ABS', 'Airbags', 'Traction Control', 'Stability Control', 
                                'Parking Sensors', 'Backup Camera', 'Lane Departure Warning'
                            ]) ? 'cars.compare.safety.' : 'cars.compare.features.';
                            
                            $featureKey = strtolower(str_replace(' ', '_', $feature));
                            return '<tr>
                                <td>' . htmlspecialchars(__($translationKey . $featureKey)) . '</td>
                                ' . implode('', array_map(function($car) use ($feature) {
                                    $hasFeature = in_array($feature, $car['safety_features']);
                                    return '<td class="text-center">
                                        <span class="' . ($hasFeature ? 'text-success' : 'text-error') . '">
                                            ' . ($hasFeature ? '✓' : '✗') . '
                                        </span>
                                    </td>';
                                }, $cars)) . '
                            </tr>';
                        }, array_unique(array_merge(...array_map(function($car) {
                            return $car['safety_features'];
                        }, $cars))))) . '
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Features -->
        <div>
            <h4 class="font-semibold mb-4">' . __('cars.compare.features_equipment') . '</h4>
            <div class="overflow-x-auto">
                <table class="table table-zebra w-full">
                    <tbody>
                        ' . implode('', array_map(function($feature) use ($cars) {
                            $translationKey = in_array($feature, [
                                'ABS', 'Airbags', 'Traction Control', 'Stability Control', 
                                'Parking Sensors', 'Backup Camera', 'Lane Departure Warning'
                            ]) ? 'cars.compare.safety.' : 'cars.compare.features.';
                            
                            $featureKey = strtolower(str_replace(' ', '_', $feature));
                            return '<tr>
                                <td>' . htmlspecialchars(__($translationKey . $featureKey)) . '</td>
                                ' . implode('', array_map(function($car) use ($feature) {
                                    $hasFeature = in_array($feature, $car['features']);
                                    return '<td class="text-center">
                                        <span class="' . ($hasFeature ? 'text-success' : 'text-error') . '">
                                            ' . ($hasFeature ? '✓' : '✗') . '
                                        </span>
                                    </td>';
                                }, $cars)) . '
                            </tr>';
                        }, array_unique(array_merge(...array_map(function($car) {
                            return $car['features'];
                        }, $cars))))) . '
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Additional Information -->
        <div>
            <h4 class="font-semibold mb-4">' . __('cars.compare.additional_information') . '</h4>
            <div class="overflow-x-auto">
                <table class="table table-zebra w-full">
                    <tbody>
                        <tr>
                            <td>' . __('cars.compare.vin') . '</td>
                            ' . implode('', array_map(function($car) {
                                return '<td class="text-center font-mono text-xs">' . htmlspecialchars(getCarProperty($car, 'vin')) . '</td>';
                            }, $cars)) . '
                        </tr>
                        <tr>
                            <td>' . __('cars.compare.registration') . '</td>
                            ' . implode('', array_map(function($car) {
                                return '<td class="text-center">' . htmlspecialchars(getCarProperty($car, 'registration')) . '</td>';
                            }, $cars)) . '
                        </tr>
                        <tr>
                            <td>' . __('cars.compare.last_service') . '</td>
                            ' . implode('', array_map(function($car) {
                                return '<td class="text-center">' . htmlspecialchars(getCarProperty($car, 'last_service')) . '</td>';
                            }, $cars)) . '
                        </tr>
                        <tr>
                            <td>' . __('cars.warranty') . '</td>
                            ' . implode('', array_map(function($car) {
                                return '<td class="text-center">
                                    ' . (getCarProperty($car, 'warranty') ? '
                                    <span class="badge badge-success">' . __('common.yes') . '</span>
                                    <div class="text-xs mt-1">' . htmlspecialchars(getCarProperty($car, 'warranty_details')) . '</div>
                                    ' : '<span class="badge badge-ghost">' . __('common.no') . '</span>') . '
                                </td>';
                            }, $cars)) . '
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>';

} catch (Exception $e) {
    error_log("Error in comparison view: " . $e->getMessage());
    http_response_code(500);
    echo '<div class="alert alert-error">' . __('messages.error') . '</div>';
} 