<?php
require_once __DIR__ . '/../../../src/bootstrap.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid input data');
    }

    // Validate required fields
    $required = [
        'investment' => ['type' => 'numeric', 'message' => 'Investment amount is required'],
        'currency' => ['type' => 'string', 'message' => 'Currency is required'],
        'type' => ['type' => 'string', 'message' => 'Partnership type is required'],
        'duration' => ['type' => 'numeric', 'message' => 'Duration is required'],
        'profit_share' => ['type' => 'numeric', 'message' => 'Profit share percentage is required']
    ];

    foreach ($required as $field => $rules) {
        if (!isset($input[$field])) {
            throw new Exception($rules['message']);
        }
    }

    // Calculate partnership costs
    $total_investment = floatval($input['investment']);
    $profit_share = floatval($input['profit_share']) / 100;
    $duration = intval($input['duration']);
    
    // Example calculations (adjust based on your business logic)
    $expected_return = $total_investment * (1 + ($profit_share * ($duration / 12)));
    $monthly_share = ($expected_return - $total_investment) / $duration;

    echo json_encode([
        'success' => true,
        'data' => [
            'total_investment' => $total_investment,
            'expected_return' => $expected_return,
            'monthly_share' => $monthly_share,
            'duration' => $duration,
            'profit_share' => $input['profit_share'],
            'type' => $input['type'],
            'currency' => $input['currency']
        ]
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
