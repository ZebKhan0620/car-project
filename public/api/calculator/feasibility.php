<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../../src/bootstrap.php';

use Classes\Calculator\Business\FeasibilityAnalyzer;
use Classes\Report\CostReportGenerator;
use Classes\Validation\RequestValidator;

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $validator = new RequestValidator([
        'market' => 'required|in:UAE,PH',
        'financials' => 'required|array',
        'financials.roi_percentage' => 'required|numeric',
        'financials.breakeven_months' => 'required|numeric'
    ]);

    if (!$validator->validate($input)) {
        throw new Exception('Invalid input: ' . json_encode($validator->getErrors()));
    }

    $analyzer = new FeasibilityAnalyzer();
    $analysis = $analyzer->analyzeFeasibility($input['market'], $input['financials']);

    // Generate detailed PDF report
    $reportGenerator = new CostReportGenerator();
    $reportFile = $reportGenerator->generateReport($analysis, [
        'type' => 'feasibility',
        'market' => $input['market'],
        'reference' => uniqid('FEASIBILITY-')
    ]);

    echo json_encode([
        'success' => true,
        'data' => $analysis,
        'report_url' => '/reports/' . $reportFile
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
