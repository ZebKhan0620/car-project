<?php
require_once __DIR__ . '/../../../src/bootstrap.php';

use App\Calculator\CostBreakdown;
use App\Services\ExchangeRateService;
use App\Services\CacheManager;

// Add CORS and content type headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Debug logging
    error_log('[Import Cost Calculator] Starting calculation...');
    
    $rawInput = file_get_contents('php://input');
    error_log('[Import Cost Calculator] Raw input: ' . $rawInput);
    
    $input = json_decode($rawInput, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('JSON decode error: ' . json_last_error_msg());
    }
    
    error_log('[Import Cost Calculator] Decoded input: ' . print_r($input, true));
    
    // Required fields validation with detailed errors
    $required = [
        'value' => ['type' => 'numeric', 'message' => 'Vehicle value must be a number'],
        'currency' => ['type' => 'string', 'message' => 'Currency code is required'],
        'targetCurrency' => ['type' => 'string', 'message' => 'Target currency code is required'],
        'destination' => ['type' => 'string', 'message' => 'Destination country is required'],
        'length' => ['type' => 'numeric', 'message' => 'Length must be a number'],
        'width' => ['type' => 'numeric', 'message' => 'Width must be a number'],
        'height' => ['type' => 'numeric', 'message' => 'Height must be a number'],
        'weight' => ['type' => 'numeric', 'message' => 'Weight must be a number'],
        'age' => ['type' => 'numeric', 'message' => 'Vehicle age must be a number'],
        'type' => ['type' => 'string', 'message' => 'Vehicle type is required']
    ];

    // Validate all required fields
    foreach ($required as $field => $rules) {
        if (!isset($input[$field])) {
            throw new Exception($rules['message']);
        }
        
        $valid = match($rules['type']) {
            'numeric' => is_numeric($input[$field]),
            'string' => is_string($input[$field]) && !empty(trim($input[$field])),
            default => false
        };
        
        if (!$valid) {
            throw new Exception($rules['message']);
        }
    }

    // Initialize services
    $cacheManager = new CacheManager();
    $exchangeService = new ExchangeRateService($cacheManager);
    $costBreakdown = new CostBreakdown($exchangeService);
    
    // Prepare params for calculation
    $params = [
        'destination' => $input['destination'],
        'dimensions' => [
            'length' => (float)$input['length'],
            'width' => (float)$input['width'],
            'height' => (float)$input['height']
        ],
        'weight' => (float)$input['weight'],
        'age' => (int)$input['age'],
        'type' => $input['type']
    ];

    // Calculate costs
    $result = $costBreakdown->calculateTotalImportCost(
        (float)$input['value'],
        $input['currency'],
        $input['targetCurrency'] ?? 'USD',
        $params
    );
    
    error_log('[Import Cost Calculator] Calculation successful');
    
    echo json_encode([
        'success' => true,
        'data' => $result
    ]);

} catch (Exception $e) {
    error_log('[Import Cost Calculator] Error: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
