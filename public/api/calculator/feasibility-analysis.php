<?php
require_once __DIR__ . '/../../../src/bootstrap.php';
header('Content-Type: application/json');

error_log("Feasibility Analysis API called");

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid input data');
    }

    error_log("Received input: " . print_r($input, true));

    // Validate required fields
    $required = ['market_size', 'competition_level', 'initial_investment', 
                'expected_revenue', 'risk_level', 'risk_mitigation'];
    foreach ($required as $field) {
        if (!isset($input[$field])) {
            throw new Exception("Missing required field: {$field}");
        }
    }

    // Calculate market analysis scores
    $marketAnalysis = calculateMarketAnalysis($input['market_size'], $input['competition_level']);
    
    // Calculate risk assessment
    $riskAssessment = calculateRiskAssessment($input['risk_level'], $input['risk_mitigation'], $input['initial_investment']);
    
    // Calculate feasibility score
    $feasibilityScore = calculateFeasibilityScore($marketAnalysis, $riskAssessment, $input);

    $response = [
        'success' => true,
        'market_analysis' => $marketAnalysis,
        'risk_assessment' => $riskAssessment,
        'feasibility_score' => $feasibilityScore,
        'recommendation' => generateRecommendation($feasibilityScore)
    ];

    error_log("Sending response: " . json_encode($response));

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

function calculateMarketAnalysis($marketSize, $competitionLevel) {
    $growthPotential = [
        'small' => 0.3,
        'medium' => 0.6,
        'large' => 0.9
    ][$marketSize] ?? 0.5;

    $competitionScore = [
        'low' => 0.8,
        'medium' => 0.5,
        'high' => 0.3
    ][$competitionLevel] ?? 0.5;

    return [
        'growth_potential' => $growthPotential,
        'competition_level' => $competitionLevel,
        'demand_rating' => ($growthPotential + $competitionScore) / 2
    ];
}

function calculateRiskAssessment($riskLevel, $riskMitigation, $investment) {
    $baseRiskScore = [
        'low' => 0.2,
        'medium' => 0.5,
        'high' => 0.8
    ][$riskLevel] ?? 0.5;

    $mitigationEffect = min(($riskMitigation / $investment), 0.5);
    $finalRiskScore = max(0, $baseRiskScore - $mitigationEffect);

    return [
        'risk_level' => $riskLevel,
        'overall_risk_score' => $finalRiskScore,
        'risk_factors' => [
            'market_risk' => $baseRiskScore,
            'financial_risk' => 1 - ($riskMitigation / $investment),
            'operational_risk' => $finalRiskScore * 0.8
        ]
    ];
}

function calculateFeasibilityScore($marketAnalysis, $riskAssessment, $input) {
    $marketScore = $marketAnalysis['demand_rating'];
    $riskScore = 1 - $riskAssessment['overall_risk_score'];
    $roiScore = min(($input['expected_revenue'] * 12) / $input['initial_investment'], 1);
    
    return ($marketScore * 0.4) + ($riskScore * 0.3) + ($roiScore * 0.3);
}

function generateRecommendation($score) {
    if ($score > 0.7) {
        return "Highly feasible project with strong potential for success.";
    } elseif ($score > 0.4) {
        return "Moderately feasible project with some risks to consider.";
    } else {
        return "High-risk project that requires significant adjustments.";
    }
}
