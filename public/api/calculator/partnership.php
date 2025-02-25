<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../../src/bootstrap.php';

use Classes\Calculator\Business\PartnershipCalculator;
use Classes\Report\CostReportGenerator;
use Classes\Validation\RequestValidator;

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate request
    $validator = new RequestValidator([
        'market' => 'required|in:UAE,PH',
        'inventory_size' => 'required|numeric|min:5|max:50',
        'monthly_volume' => 'required|numeric|min:1',
        'avg_value' => 'required|numeric|min:5000'
    ]);

    if (!$validator->validate($input)) {
        throw new Exception('Invalid input: ' . json_encode($validator->getErrors()));
    }

    $calculator = new PartnershipCalculator();
    
    // Calculate investment requirements
    $investment = $calculator->calculateInitialInvestment(
        $input['market'], 
        $input['inventory_size']
    );

    // Calculate monthly projections
    $monthly = $calculator->calculateMonthlyProjection(
        $input['market'],
        [
            'volume' => $input['monthly_volume'],
            'avg_value' => $input['avg_value']
        ]
    );

    // Calculate ROI
    $roi = $calculator->calculateROI($investment, $monthly);

    $data = array_merge($investment, $monthly, $roi);

    // Generate PDF report
    $reportGenerator = new CostReportGenerator();
    $reportFile = $reportGenerator->generateReport($data, [
        'type' => 'partnership',
        'market' => $input['market'],
        'reference' => uniqid('PARTNER-')
    ]);

    echo json_encode([
        'success' => true,
        'data' => $data,
        'report_url' => '/reports/' . $reportFile
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
