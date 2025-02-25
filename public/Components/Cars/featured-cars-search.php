<?php
// Debug function
function logDebug($message, $data = null) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] {$message}";
    if ($data !== null) {
        $logMessage .= ": " . print_r($data, true);
    }
    error_log($logMessage);
}

// Start timing
$searchStartTime = microtime(true);
logDebug("Search request started");

// Set higher execution time limit
set_time_limit(60);
ini_set('max_execution_time', 60);


// Verify this is an AJAX request
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    logDebug("Invalid request - not AJAX");
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'Invalid request method']));
}

// Ensure we're sending JSON response
header('Content-Type: application/json');

// Add request validation and sanitization
function validateAndSanitizeInput($input, $type) {
    $startTime = microtime(true);
    $result = null;
    
    switch($type) {
        case 'string':
            $result = !empty($input) ? htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8') : '';
            break;
        case 'int':
            $result = filter_var($input, FILTER_VALIDATE_INT) !== false ? (int)$input : null;
            break;
        case 'float':
            $result = filter_var($input, FILTER_VALIDATE_FLOAT) !== false ? (float)$input : null;
            break;
        default:
            $result = null;
    }
    
    $endTime = microtime(true);
    logDebug("Input validation for type {$type}", [
        'input' => $input,
        'result' => $result,
        'time_taken' => ($endTime - $startTime) * 1000 . 'ms'
    ]);
    
    return $result;
}

try {
    logDebug("Starting to read car data");
    $dataStartTime = microtime(true);
    
    // Read car data first
    $jsonFile = __DIR__ . '/../../../data/car_listings.json';
    
    if (!file_exists($jsonFile)) {
        logDebug("Car listings file not found", $jsonFile);
        throw new Exception('Car listings data file not found');
    }

    $jsonContent = file_get_contents($jsonFile);
    if ($jsonContent === false) {
        logDebug("Failed to read car listings file");
        throw new Exception('Failed to read car listings data');
    }

    logDebug("JSON file read completed", [
        'file_size' => strlen($jsonContent),
        'time_taken' => (microtime(true) - $dataStartTime) * 1000 . 'ms'
    ]);

    // Validate JSON data
    $parseStartTime = microtime(true);
    $data = json_decode($jsonContent, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        logDebug("JSON decode error", json_last_error_msg());
        throw new Exception('Invalid JSON data: ' . json_last_error_msg());
    }

    logDebug("JSON parsing completed", [
        'parse_time' => (microtime(true) - $parseStartTime) * 1000 . 'ms'
    ]);

    $cars = $data['items'] ?? [];
    if (!is_array($cars)) {
        logDebug("Invalid data structure - cars is not an array");
        throw new Exception('Invalid car data structure');
    }

    logDebug("Total cars before filtering", count($cars));

    // Validate and sanitize input parameters
    $filterStartTime = microtime(true);
    $filters = [
        'brand' => validateAndSanitizeInput($_GET['brand'] ?? '', 'string'),
        'min_price' => validateAndSanitizeInput($_GET['min_price'] ?? '', 'int'),
        'max_price' => validateAndSanitizeInput($_GET['max_price'] ?? '', 'int'),
        'min_year' => validateAndSanitizeInput($_GET['min_year'] ?? '', 'int'),
        'max_year' => validateAndSanitizeInput($_GET['max_year'] ?? '', 'int'),
        'transmission' => validateAndSanitizeInput($_GET['transmission'] ?? '', 'string'),
        'fuel_type' => validateAndSanitizeInput($_GET['fuel_type'] ?? '', 'string'),
        'condition' => validateAndSanitizeInput($_GET['condition'] ?? '', 'string')
    ];

    logDebug("Validated filters", $filters);

    // Filter cars with improved validation
    $filteredCars = array_filter($cars, function($car) use ($filters) {
        if (!is_array($car)) return false;
        
        foreach ($filters as $key => $value) {
            if (empty($value)) continue;

            switch($key) {
                case 'brand':
                    if (!isset($car['brand']) || strtolower($car['brand']) !== strtolower($value)) return false;
                    break;
                case 'min_price':
                    if (!isset($car['price']) || $car['price'] < $value) return false;
                    break;
                case 'max_price':
                    if (!isset($car['price']) || $car['price'] > $value) return false;
                    break;
                case 'min_year':
                    if (!isset($car['year']) || $car['year'] < $value) return false;
                    break;
                case 'max_year':
                    if (!isset($car['year']) || $car['year'] > $value) return false;
                    break;
                case 'transmission':
                    if (!isset($car['transmission']) || strtolower($car['transmission']) !== strtolower($value)) return false;
                    break;
                case 'fuel_type':
                    if (!isset($car['fuel_type']) || strtolower($car['fuel_type']) !== strtolower($value)) return false;
                    break;
                case 'condition':
                    if (!isset($car['condition']) || strtolower($car['condition']) !== strtolower($value)) return false;
                    break;
            }
        }
        return true;
    });

    logDebug("Filtering completed", [
        'filtered_count' => count($filteredCars),
        'filter_time' => (microtime(true) - $filterStartTime) * 1000 . 'ms'
    ]);

    // Calculate market values and trends
    foreach ($filteredCars as &$car) {
        // Find similar cars for market comparison
        $similarCars = array_filter($cars, function($c) use ($car) {
            return $c['brand'] === $car['brand'] && 
                   abs($c['year'] - $car['year']) <= 2 &&
                   $c['id'] !== $car['id'];
        });
        
        // Calculate average market price
        $prices = array_column($similarCars, 'price');
        $car['market_avg'] = !empty($prices) ? array_sum($prices) / count($prices) : $car['price'];
        $car['price_difference'] = round(($car['price'] - $car['market_avg']) / $car['market_avg'] * 100, 1);
        
        // Calculate condition score
        $score = 100;
        $currentYear = date('Y');
        $score -= ($currentYear - $car['year']) * 2; // Age impact
        $score -= floor($car['mileage'] / 10000) * 3; // Mileage impact
        
        switch(strtolower($car['condition'])) {
            case 'new': break;
            case 'like new': $score -= 5; break;
            case 'excellent': $score -= 10; break;
            case 'good': $score -= 20; break;
            case 'fair': $score -= 30; break;
            default: $score -= 40;
        }
        
        $car['condition_score'] = max(0, min(100, $score));
    }

    // Start output buffer for HTML
    $renderStartTime = microtime(true);
    ob_start();
    
    if (empty($filteredCars)) {
        echo '<div class="text-center p-8">
                <p>No cars found matching your criteria.</p>
              </div>';
    } else {
        foreach ($filteredCars as $car) {
            // Get first image or default
            $imagePath = '/car-project/public/assets/images/default-car.jpg';
            if (!empty($car['images'][0])) {
                $imagePath = '/car-project/public/uploads/car_images/' . $car['images'][0];
            }

            $priceClass = $car['price_difference'] < 0 ? 'text-success' : ($car['price_difference'] > 0 ? 'text-error' : '');

            echo '<div class="card bg-base-100 shadow-xl hover:shadow-2xl transition-all duration-300">
                    <figure class="px-4 pt-4 relative">
                        <img src="' . htmlspecialchars($imagePath) . '" 
                             alt="' . htmlspecialchars($car['title']) . '"
                             class="rounded-xl h-48 w-full object-cover" />
                        <div class="absolute top-6 right-6 flex flex-col gap-2">
                            <span class="badge badge-primary">' . htmlspecialchars($car['condition']) . '</span>
                            ' . ($car['warranty'] ? '<span class="badge badge-secondary">Warranty</span>' : '') . '
                        </div>
                    </figure>
                    <div class="card-body">
                        <div class="flex justify-between items-start">
                            <h2 class="card-title">' . htmlspecialchars($car['title']) . '</h2>
                            <div class="text-right">
                                <p class="text-lg font-bold">$' . number_format($car['price']) . '</p>
                                <p class="text-sm ' . $priceClass . '">
                                    ' . ($car['price_difference'] > 0 ? '+' : '') . $car['price_difference'] . '% vs market
                                </p>
                            </div>
                        </div>

                        <div class="mt-2">
                            <div class="flex justify-between items-center mb-1">
                                <span class="text-sm text-base-content/70">Condition Score</span>
                                <span class="text-sm font-semibold">' . $car['condition_score'] . '/100</span>
                            </div>
                            <div class="w-full bg-base-200 rounded-full h-1.5">
                                <div class="bg-primary h-1.5 rounded-full" style="width: ' . $car['condition_score'] . '%"></div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2 my-3">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-base-content/70" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                </svg>
                                <span class="text-sm">' . number_format($car['mileage']) . ' km</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-base-content/70" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                </svg>
                                <span class="text-sm">' . htmlspecialchars($car['location']) . '</span>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2 mb-4">
                            <span class="badge badge-outline">' . htmlspecialchars($car['transmission']) . '</span>
                            <span class="badge badge-outline">' . htmlspecialchars($car['fuel_type']) . '</span>
                            <span class="badge badge-outline">' . htmlspecialchars($car['year']) . '</span>
                            ' . ($car['negotiable'] ? '<span class="badge badge-accent">Negotiable</span>' : '') . '
                        </div>

                        <div class="card-actions justify-end">
                            <a href="/car-project/public/Components/Cars/view.php?id=' . $car['id'] . '" 
                               class="btn btn-primary">View Details</a>
                        </div>
                    </div>
                  </div>';
        }
    }

    $html = ob_get_clean();
    
    logDebug("HTML rendering completed", [
        'html_length' => strlen($html),
        'render_time' => (microtime(true) - $renderStartTime) * 1000 . 'ms'
    ]);

    // Return JSON response
    $response = [
        'success' => true,
        'html' => $html,
        'count' => count($filteredCars)
    ];

    logDebug("Search completed successfully", [
        'total_time' => (microtime(true) - $searchStartTime) * 1000 . 'ms'
    ]);

    echo json_encode($response, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

} catch (Exception $e) {
    logDebug("Search error occurred", [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    
    error_log("Featured cars search error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while searching for cars',
        'debug' => $e->getMessage()
    ]);
}

logDebug("Request completed", [
    'total_execution_time' => (microtime(true) - $searchStartTime) * 1000 . 'ms'
]); 